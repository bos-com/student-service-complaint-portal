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

if (!$complaint_id) {
    echo json_encode(["success" => false, "message" => "Complaint ID is required"]);
    exit;
}

try {
    // Check if already liked
    $check_stmt = $pdo->prepare("SELECT id FROM complaint_likes WHERE complaint_id = ? AND student_id = ?");
    $check_stmt->execute([$complaint_id, $student_id]);
    $existing = $check_stmt->fetch();
    
    if ($existing) {
        // Unlike
        $delete_stmt = $pdo->prepare("DELETE FROM complaint_likes WHERE complaint_id = ? AND student_id = ?");
        $delete_stmt->execute([$complaint_id, $student_id]);
        echo json_encode(["success" => true, "action" => "unliked"]);
    } else {
        // Like
        $insert_stmt = $pdo->prepare("INSERT INTO complaint_likes (complaint_id, student_id) VALUES (?, ?)");
        $insert_stmt->execute([$complaint_id, $student_id]);
        echo json_encode(["success" => true, "action" => "liked"]);
    }
    
} catch (Exception $e) {
    error_log("Like toggle error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Failed to process like"]);
}
?>