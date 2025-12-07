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

$logId = $_POST['log_id'] ?? null;
$deleteAll = isset($_POST['delete_all']) && $_POST['delete_all'] === 'true';

try {
    if ($logId) {
        // Delete single log
        $stmt = $pdo->prepare("DELETE FROM system_logs WHERE id = ?");
        $stmt->execute([$logId]);
        $message = "Log entry deleted successfully";
    } elseif ($deleteAll) {
        // Delete all logs
        $stmt = $pdo->query("DELETE FROM system_logs");
        $message = "All logs deleted successfully";
    } else {
        echo json_encode(['success' => false, 'message' => 'No valid operation specified']);
        exit;
    }
    
    // Log the action (if system_logs table still exists and we're not deleting all logs)
    if (!$deleteAll) {
        $log_stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $log_stmt->execute([
            $_SESSION['student']['id'],
            'delete_logs',
            $message,
            $_SERVER['REMOTE_ADDR']
        ]);
    }

    echo json_encode(['success' => true, 'message' => $message]);
} catch (Exception $e) {
    error_log("Delete logs error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to delete logs: ' . $e->getMessage()]);
}
?>