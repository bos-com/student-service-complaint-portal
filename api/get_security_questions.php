<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$studentId = $_SESSION['student']['id'];

$stmt = $pdo->prepare("SELECT question FROM security_questions WHERE student_id = ? ORDER BY question_order");
$stmt->execute([$studentId]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'questions' => $questions]);
?>