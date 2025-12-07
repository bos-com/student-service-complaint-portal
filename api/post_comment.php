<?php
header("Content-Type: application/json");
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['student'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Please login first"]);
    exit;
}

$student_id = $_SESSION['student']['id'];
$data = json_decode(file_get_contents('php://input'), true);
$complaint_id = $data['complaint_id'] ?? null;
$content = trim($data['content'] ?? '');

if (!$complaint_id || empty($content)) {
    echo json_encode(["success" => false, "message" => "Complaint ID and content are required"]);
    exit;
}

try {
    $sql = "INSERT INTO comments (complaint_id, student_id, content) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$complaint_id, $student_id, $content]);
    
    echo json_encode([
        "success" => true,
        "message" => "Comment posted successfully"
    ]);
    
} catch (Exception $e) {
    error_log("Post comment error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Failed to post comment"]);
}
?>