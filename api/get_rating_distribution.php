<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is superadmin
if (!isset($_SESSION['student']) || ($_SESSION['student']['role'] ?? '') !== 'superadmin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    // Get rating distribution
    $distribution = [];
    $total = 0;
    
    for ($i = 1; $i <= 5; $i++) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM complaint_ratings WHERE rating = ?");
        $stmt->execute([$i]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $distribution[$i] = intval($result['count']);
        $total += $distribution[$i];
    }
    
    echo json_encode([
        'success' => true,
        'distribution' => $distribution,
        'total' => $total
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>