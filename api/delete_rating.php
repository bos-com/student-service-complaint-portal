<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is superadmin
if (!isset($_SESSION['student']) || ($_SESSION['student']['role'] ?? '') !== 'superadmin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$rating_id = intval($data['rating_id'] ?? 0);

if ($rating_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM complaint_ratings WHERE id = ?");
    $result = $stmt->execute([$rating_id]);
    
    if ($result && $stmt->rowCount() > 0) {
        // Log the action
        $log_stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $log_stmt->execute([
            $_SESSION['student']['id'],
            'delete_rating',
            "Deleted rating ID: $rating_id",
            $_SERVER['REMOTE_ADDR']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Rating deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Rating not found or already deleted']);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>