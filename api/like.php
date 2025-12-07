<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$studentId = $_SESSION['student']['id'];
$complaintId = $_POST['complaint_id'] ?? null;
$content = $_POST['content'] ?? '';

if (!$complaintId || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO comments (complaint_id, student_id, content, created_at) 
        VALUES (?, ?, ?, NOW())");
    $stmt->execute([$complaintId, $studentId, $content]);
    
    $commentId = $pdo->lastInsertId();
    
    // Get comment with author info
    $stmt = $pdo->prepare("SELECT c.*, s.name as author_name FROM comments c 
        LEFT JOIN students s ON c.student_id = s.id 
        WHERE c.id = ?");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Create notification for complaint owner
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, created_at)
        SELECT student_id, 'New Comment', ?, 'comment', NOW() 
        FROM complaints WHERE id = ?");
    $stmt->execute(["New comment on your complaint", $complaintId]);
    
    echo json_encode(['success' => true, 'comment' => $comment]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>