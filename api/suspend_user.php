<?php
session_start();
header('Content-Type: application/json');

// Check for both admin and superadmin sessions
$isAdmin = isset($_SESSION['admin']) && in_array(($_SESSION['admin']['role'] ?? ''), ['superadmin', 'admin']);
$isSuperadmin = isset($_SESSION['student']) && ($_SESSION['student']['role'] ?? '') === 'superadmin';

if (!$isAdmin && !$isSuperadmin) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_POST['user_id'] ?? 0;

if (empty($user_id)) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

// Database connection
$host = 'localhost';
$db = 'campusvoice';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Toggle between active and suspended
    $stmt = $pdo->prepare("SELECT status FROM students WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $new_status = $user['status'] === 'active' ? 'suspended' : 'active';
    
    $update_stmt = $pdo->prepare("UPDATE students SET status = ? WHERE id = ?");
    $update_stmt->execute([$new_status, $user_id]);
    
    echo json_encode(['success' => true, 'message' => "User $new_status successfully"]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to update user status: ' . $e->getMessage()]);
}
?>