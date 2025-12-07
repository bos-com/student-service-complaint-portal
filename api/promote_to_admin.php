// filepath: api/promote_to_admin.php
<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['student']) || $_SESSION['student']['role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_POST['user_id'] ?? 0;

try {
    $pdo = new PDO("mysql:host=localhost;dbname=campusvoice;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $stmt = $pdo->prepare("UPDATE students SET role = 'admin' WHERE id = ?");
    $stmt->execute([$userId]);
    
    echo json_encode(['success' => true, 'message' => 'User promoted to Admin']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed: ' . $e->getMessage()]);
}
?>