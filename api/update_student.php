<?php
session_start();
header('Content-Type: application/json');

// Check for both admin and superadmin sessions
$isAdmin = isset($_SESSION['admin']) && in_array(($_SESSION['admin']['role'] ?? ''), ['superadmin', 'admin']);
$isSuperadmin = isset($_SESSION['student']) && ($_SESSION['student']['role'] ?? '') === 'superadmin';

if (!$isAdmin && !$isSuperadmin) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Determine user details based on session type
if ($isAdmin) {
    $user_id = $_SESSION['admin']['id'];
    $user_name = $_SESSION['admin']['name'];
} else {
    $user_id = $_SESSION['student']['id'];
    $user_name = $_SESSION['student']['name'];
}

$student_id = $_POST['id'] ?? 0;
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$student_id_num = $_POST['student_id'] ?? '';

if (empty($student_id) || empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
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
    $stmt = $pdo->prepare("UPDATE students SET name = ?, email = ?, student_id = ? WHERE id = ?");
    $stmt->execute([$name, $email, $student_id_num, $student_id]);
    
    // Log the action
    try {
        $log_stmt = $pdo->prepare("INSERT INTO system_logs (user_id, user_name, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
        $log_stmt->execute([
            $user_id,
            $user_name,
            'update_student',
            "Updated student: $name ($email)",
            $_SERVER['REMOTE_ADDR']
        ]);
    } catch (Exception $e) {
        // Ignore if system_logs table doesn't exist
    }

    echo json_encode(['success' => true, 'message' => 'Student updated successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to update student: ' . $e->getMessage()]);
}
?>