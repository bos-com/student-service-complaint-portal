<?php
// api/get_comments.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['student'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=campusvoice;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$complaint_id = $_GET['complaint_id'] ?? 0;

if (!$complaint_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid complaint ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            s.name as user_name
        FROM comments c
        LEFT JOIN students s ON c.student_id = s.id
        WHERE c.complaint_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$complaint_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'comments' => $comments
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to load comments']);
}
?>