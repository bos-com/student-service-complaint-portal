<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is superadmin
if (!isset($_SESSION['student']) || ($_SESSION['student']['role'] ?? '') !== 'superadmin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Rating ID required']);
    exit;
}

$rating_id = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("
        SELECT cr.*, s.name as student_name, s.email as student_email, 
               c.title as complaint_title, c.description as complaint_description
        FROM complaint_ratings cr
        LEFT JOIN students s ON cr.student_id = s.id
        LEFT JOIN complaints c ON cr.complaint_id = c.id
        WHERE cr.id = ?
    ");
    $stmt->execute([$rating_id]);
    $rating = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($rating) {
        echo json_encode([
            'success' => true,
            'rating' => $rating
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Rating not found'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>