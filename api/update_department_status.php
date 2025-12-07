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
$status = $_POST['status'] ?? 'active';

if (!$deptId) {
    die(json_encode(['success' => false, 'message' => 'Department ID is required']));
}

try {
    // Update department status
    $stmt = $pdo->prepare("UPDATE department_contacts SET status = ? WHERE id = ?");
    $stmt->execute([$status, $deptId]);
    
    // Log the action
    $log_stmt = $pdo->prepare("INSERT INTO system_logs (user_id, user_name, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    $log_stmt->execute([
        $_SESSION['student']['id'],
        $_SESSION['student']['name'],
        'update_department_status',
        'Updated department status to: ' . $status,
        $_SERVER['REMOTE_ADDR']
    ]);

    echo json_encode(['success' => true, 'message' => 'Department status updated successfully']);
} catch (Exception $e) {
    error_log("Update department status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update department status: ' . $e->getMessage()]);
}
?>