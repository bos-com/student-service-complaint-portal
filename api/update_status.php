<?php
// filepath: api/update_status.php
session_start();
header('Content-Type: application/json');

// Check for admin session
if (!isset($_SESSION['student']) || $_SESSION['student']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Database Connection (Ensure these match your settings)
$host = 'localhost';
$db = 'campusvoice';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$complaintId = $data['id'] ?? null;
$status = $data['status'] ?? null;
$adminId = $_SESSION['student']['id'] ?? 0;

if (!$complaintId || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing ID or status.']);
    exit;
}

$valid_statuses = ['pending', 'progress', 'resolved'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

try {
    // 1. Update the complaint status
    $stmt = $pdo->prepare("UPDATE complaints SET status = :status WHERE id = :id");
    $stmt->execute([':status' => $status, ':id' => $complaintId]);

    // 2. Log the action (Optional but highly recommended)
    $log_action = "Status Updated";
    $log_details = "Complaint ID {$complaintId} set to " . ucfirst($status);
    
    $log_stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (:admin_id, :action, :details)");
    $log_stmt->execute([
        ':admin_id' => $adminId, 
        ':action' => $log_action, 
        ':details' => $log_details
    ]);

    echo json_encode(['success' => true, 'message' => 'Status successfully updated and logged.']);

} catch (PDOException $e) {
    // Return a more detailed error for debugging
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

?>