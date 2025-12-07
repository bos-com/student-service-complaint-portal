<?php
// filepath: api/delete_entry.php
session_start();
header('Content-Type: application/json');

// Check for admin session
if (!isset($_SESSION['student']) || $_SESSION['student']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Database Connection
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

$entryType = $data['type'] ?? null; // 'complaint' or 'user'
$entryId = $data['id'] ?? null;
$adminId = $_SESSION['student']['id'] ?? 0;

if (!$entryId || !$entryType) {
    echo json_encode(['success' => false, 'message' => 'Missing entry ID or type.']);
    exit;
}

// Determine table and primary key based on entry type
switch ($entryType) {
    case 'complaint':
        $table = 'complaints';
        break;
    case 'user':
        $table = 'students';
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid deletion type.']);
        exit;
}

try {
    // 1. Delete the entry
    $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id = :id");
    $stmt->execute([':id' => $entryId]);

    if ($stmt->rowCount() > 0) {
        // 2. Log the action
        $log_action = "{$entryType} Deleted";
        $log_details = ucfirst($entryType) . " ID {$entryId} deleted.";
        
        $log_stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (:admin_id, :action, :details)");
        $log_stmt->execute([
            ':admin_id' => $adminId, 
            ':action' => $log_action, 
            ':details' => $log_details
        ]);
        
        echo json_encode(['success' => true, 'message' => ucfirst($entryType) . ' successfully deleted.']);
    } else {
        echo json_encode(['success' => false, 'message' => ucfirst($entryType) . ' not found.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

?>