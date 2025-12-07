<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$studentId = $_SESSION['student']['id'];
$questions = json_decode($_POST['questions'], true);

if (empty($questions) || count($questions) < 5) {
    echo json_encode(['success' => false, 'message' => 'All 5 questions required']);
    exit;
}

try {
    // Delete existing questions
    $stmt = $pdo->prepare("DELETE FROM security_questions WHERE student_id = ?");
    $stmt->execute([$studentId]);
    
    // Insert new ones
    $stmt = $pdo->prepare("INSERT INTO security_questions (student_id, question, answer_hash, question_order) 
        VALUES (?, ?, ?, ?)");
    
    foreach ($questions as $index => $qa) {
        $hashedAnswer = password_hash($qa['answer'], PASSWORD_DEFAULT);
        $stmt->execute([$studentId, $qa['question'], $hashedAnswer, $index + 1]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Security questions saved']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>