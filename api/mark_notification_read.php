<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$studentId = $_SESSION['student']['id'];
$notificationId = $_POST['notification_id'] ?? null;

try {
    if ($notificationId) {
        // Mark single notification as read
        $stmt = $pdo->prepare("UPDATE notifications SET read_status = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notificationId, $studentId]);
    } else {
        // Mark all as read
        $stmt = $pdo->prepare("UPDATE notifications SET read_status = 1 WHERE user_id = ?");
        $stmt->execute([$studentId]);
    }
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>