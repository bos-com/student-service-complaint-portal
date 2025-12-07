<?php
session_start();

// === GET TOKEN & EMAIL FROM URL (from register.php) ===
$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

if (!$token || !$email) {
    die("Invalid verification link.");
}

// === DATABASE CONNECTION ===
try {
    $pdo = new PDO("mysql:host=localhost;dbname=campusvoice;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    die("Database connection failed.");
}

// === DEFAULT CODE: 1948 ===
$default_code = '1948';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');

    if ($code === $default_code) {
        // === STEP 1: MARK AS VERIFIED ===
        $stmt = $pdo->prepare("
            UPDATE students 
            SET verified = 1, verification_token = NULL, token_expires = NULL 
            WHERE email = ? AND verification_token = ?
        ");
        $stmt->execute([$email, $token]);

        if ($stmt->rowCount() > 0) {
            // === STEP 2: AUTO LOGIN ===
            $stmt = $pdo->prepare("SELECT id, name, student_id FROM students WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $email,
                    'student_id' => $user['student_id'],
                    'role' => 'student'
                ];

                // === STEP 3: REDIRECT TO FEED ===
                header("Location: feed.php");
                exit;
            }
        } else {
            $error = "Account not found or already verified.";
        }
    } else {
        $error = "Wrong code. Use: <strong>1948</strong>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Verify Account | CampusVoice</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #1E3A8A;
      --primary-light: #3B82F6;
      --bg: #f9fafb;
      --card: #ffffff;
      --text: #1f2937;
      --text-light: #6b7280;
      --border: #e5e7eb;
      --shadow: 0 6px 18px rgba(0,0,0,0.1);
      --success: #dcfce7;
      --error: #fee2e2;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
      color: white;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .card {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 24px;
      padding: 40px;
      width: 100%;
      max-width: 420px;
      text-align: center;
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
      animation: slideUp 0.6s ease;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .logo {
      width: 80px;
      height: 80px;
      background: white;
      border-radius: 16px;
      padding: 8px;
      margin: 0 auto 20px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    .logo img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      border-radius: 12px;
    }

    h1 {
      font-size: 26px;
      font-weight: 800;
      margin-bottom: 8px;
    }

    p {
      font-size: 15px;
      opacity: 0.95;
      margin-bottom: 24px;
    }

    .code-input {
      width: 100%;
      padding: 16px;
      font-size: 20px;
      font-weight: 700;
      text-align: center;
      letter-spacing: 8px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 14px;
      background: rgba(255, 255, 255, 0.1);
      color: white;
      margin-bottom: 16px;
    }

    .code-input:focus {
      outline: none;
      border-color: white;
      background: rgba(255, 255, 255, 0.2);
    }

    .submit-btn {
      width: 100%;
      padding: 16px;
      background: white;
      color: var(--primary);
      border: none;
      border-radius: 14px;
      font-weight: 700;
      font-size: 16px;
      cursor: pointer;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    .submit-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.3);
    }

    .error {
      background: var(--error);
      color: #991b1b;
      padding: 12px;
      border-radius: 10px;
      font-size: 14px;
      margin-bottom: 16px;
      border-left: 4px solid #ef4444;
    }

    .hint {
      margin-top: 20px;
      font-size: 13px;
      opacity: 0.9;
    }

    .hint strong {
      font-size: 18px;
      font-weight: 800;
    }

    .back-link {
      margin-top: 24px;
      font-size: 14px;
    }

    .back-link a {
      color: rgba(255, 255, 255, 0.8);
      text-decoration: none;
      font-weight: 600;
    }

    .back-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="logo">
      <img src="assets/bugemalogo.jpg" alt="Logo" onerror="this.style.display='none'">
    </div>
    <h1>Verify Account</h1>
    <p>Enter the default code to activate your account.</p>

    <?php if ($error): ?>
      <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <input 
        type="text" 
        name="code" 
        class="code-input" 
        placeholder="1948" 
        maxlength="4" 
        required 
        autofocus 
        autocomplete="off"
      >
      <button type="submit" class="submit-btn">Verify & Login</button>
    </form>

    <div class="hint">
      <strong> Code: 1948</strong><br>
  
    </div>

    <div class="back-link">
      <a href="index.php">Back to Home</a>
    </div>
  </div>
</body>
</html>