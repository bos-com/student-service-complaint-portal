<?php
// api/register.php
header('Content-Type: application/json');
session_start();

$siteURL = 'http://localhost/campusvoice'; // Update when live

try {
    $pdo = new PDO("mysql:host=localhost;dbname=campusvoice;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

// === INPUT VALIDATION ===
$name       = trim($data['name'] ?? '');
$email      = trim($data['email'] ?? '');
$student_id = trim($data['student_id'] ?? '');
$contact    = trim($data['contact'] ?? '');
$password   = trim($data['password'] ?? '');

if (!$name || !$email || !$student_id || !$contact || !$password) {
    echo json_encode(['success' => false, 'message' => 'All fields required']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

if (!preg_match('/^(\+\d{1,3}|0)?\d{9,14}$/', $contact)) {
    echo json_encode(['success' => false, 'message' => 'Invalid contact number']);
    exit;
}

if (!preg_match('/^[\w\/\-]+$/', $student_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID format']);
    exit;
}

if (!preg_match('/^[a-zA-Z\s\-\']+$/', $name)) {
    echo json_encode(['success' => false, 'message' => 'Name contains invalid characters']);
    exit;
}

// === CHECK DUPLICATES ===
$stmt = $pdo->prepare("SELECT id FROM students WHERE email = ? OR student_id = ?");
$stmt->execute([$email, $student_id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email or Student ID already registered']);
    exit;
}

// === HASH PASSWORD & GENERATE TOKEN ===
$hash = password_hash($password, PASSWORD_BCRYPT);
$token = bin2hex(random_bytes(32));
$expire = date('Y-m-d H:i:s', strtotime('+1 hour'));

try {
    $stmt = $pdo->prepare("
        INSERT INTO students 
        (name, email, student_id, contact, password, verification_token, token_expires, verified)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0)
    ");
    $stmt->execute([$name, $email, $student_id, $contact, $hash, $token, $expire]);
    $newId = $pdo->lastInsertId();
} catch (Exception $e) {
    error_log("Register error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Registration failed']);
    exit;
}

// === SUCCESS: REDIRECT TO verify.php WITH TOKEN & EMAIL ===
echo json_encode([
    'success' => true,
    'message' => 'Account created! Use default code: 1948',
    'redirect' => "verify.php?token=$token&email=" . urlencode($email)
]);
?>