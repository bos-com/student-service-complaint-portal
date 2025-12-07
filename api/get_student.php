<?php
session_start();
header('Content-Type: application/json');

require_once 'db_config.php';

// Allow both admin and superadmin sessions
$isAdmin      = isset($_SESSION['admin']) && in_array(($_SESSION['admin']['role'] ?? ''), ['superadmin', 'admin']);
$isSuperadmin = isset($_SESSION['student']) && ($_SESSION['student']['role'] ?? '') === 'superadmin';

if (!$isAdmin && !$isSuperadmin) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$student_id = $_GET['id'] ?? 0;

if (empty($student_id)) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit;
}

$pdo = getDBConnection();

try {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        echo json_encode(['success' => true, 'student' => $student]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
    }
} catch (Throwable $e) {
    error_log('get_student.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to fetch student']);
}
