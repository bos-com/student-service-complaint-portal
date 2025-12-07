<?php
// api/get_notifications.php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['student'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['student']['id'];

try {
    // Get notifications for this user
    $stmt = $pdo->prepare("
        SELECT n.*, s.name as sender_name 
        FROM notifications n
        LEFT JOIN students s ON n.user_id = s.id
        WHERE n.student_email = :email OR n.user_id = :user_id
        ORDER BY n.created_at DESC
        LIMIT 50
    ");
    
    $stmt->execute([
        ':email' => $_SESSION['student']['email'],
        ':user_id' => $user_id
    ]);
    
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'count' => count($notifications)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>