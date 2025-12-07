<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success'=>false,'message'=>'Login required']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=campusvoice;charset=utf8mb4",'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    echo json_encode(['success'=>false,'message'=>'DB error']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $complaint_id = (int)($_GET['complaint_id'] ?? 0);
    if (!$complaint_id) { echo json_encode([]); exit; }
    $stmt = $pdo->prepare("SELECT c.*, s.name FROM comments c LEFT JOIN students s ON c.student_id = s.id WHERE c.complaint_id = ? ORDER BY c.created_at ASC");
    $stmt->execute([$complaint_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
    exit;
}

// POST - add comment
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$complaint_id = (int)($data['complaint_id'] ?? 0);
$content = trim($data['content'] ?? '');

if (!$complaint_id || $content === '') {
    echo json_encode(['success'=>false,'message'=>'Missing fields']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO comments (complaint_id, student_id, content, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$complaint_id, $_SESSION['user']['id'], $content]);
    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT c.*, s.name FROM comments c LEFT JOIN students s ON c.student_id = s.id WHERE c.id = ?");
    $stmt->execute([$id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success'=>true,'comment'=>$comment]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>'DB insert failed']);
}
?>