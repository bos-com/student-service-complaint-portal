<?php
session_start();
header('Content-Type: application/json');

// Check if superadmin
if (!isset($_SESSION['student']) || ($_SESSION['student']['role'] ?? '') !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$student_id = $_POST['student_id'] ?? '';
$role = $_POST['role'] ?? 'admin';
$password = $_POST['password'] ?? '';

if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Database connection
$host = 'localhost';
$db = 'campusvoice';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Check if email already exists
    $check_stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
    $check_stmt->execute([$email]);
    if ($check_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Create admin user - including contact field with empty string
    $stmt = $pdo->prepare("INSERT INTO students (name, email, student_id, contact, password, role, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$name, $email, $student_id, '', $hashed_password, $role]);
    
    // Log the action (if system_logs table exists)
    try {
        $log_stmt = $pdo->prepare("INSERT INTO system_logs (user_id, user_name, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
        $log_stmt->execute([
            $_SESSION['student']['id'],
            $_SESSION['student']['name'],
            'create_admin',
            "Created new $role: $name ($email)",
            $_SERVER['REMOTE_ADDR']
        ]);
    } catch (Exception $e) {
        // Ignore if system_logs table doesn't exist
    }

    echo json_encode(['success' => true, 'message' => 'Admin created successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to create admin: ' . $e->getMessage()]);
}
?>