<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

if (!isset($_SESSION['student']) || ($_SESSION['student']['role'] ?? '') !== 'superadmin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
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
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$deptId = $_POST['id'] ?? null;

if (!$deptId) {
    die(json_encode(['success' => false, 'message' => 'Department ID is required']));
}

try {
    // Check if department exists
    $checkStmt = $pdo->prepare("SELECT * FROM department_contacts WHERE id = ?");
    $checkStmt->execute([$deptId]);
    $department = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$department) {
        die(json_encode(['success' => false, 'message' => 'Department not found']));
    }
    
    // Delete the department
    $stmt = $pdo->prepare("DELETE FROM department_contacts WHERE id = ?");
    $stmt->execute([$deptId]);
    
    // Log the action
    $log_stmt = $pdo->prepare("INSERT INTO system_logs (user_id, user_name, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    $log_stmt->execute([
        $_SESSION['student']['id'],
        $_SESSION['student']['name'],
        'delete_department',
        'Deleted department: ' . $department['department_name'],
        $_SERVER['REMOTE_ADDR']
    ]);

    echo json_encode(['success' => true, 'message' => 'Department deleted successfully']);
} catch (Exception $e) {
    error_log("Delete department error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to delete department: ' . $e->getMessage()]);
}
?>