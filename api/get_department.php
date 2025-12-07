<?php
session_start();

if (!isset($_SESSION['student']) || ($_SESSION['student']['role'] ?? '') !== 'superadmin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
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
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$deptId = $_GET['id'] ?? null;

if (!$deptId) {
    die(json_encode(['success' => false, 'message' => 'Department ID is required']));
}

try {
    $stmt = $pdo->prepare("SELECT * FROM department_contacts WHERE id = ?");
    $stmt->execute([$deptId]);
    $department = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($department) {
        echo json_encode(['success' => true, 'department' => $department]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Department not found']);
    }
} catch (Exception $e) {
    error_log("Get department error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to fetch department: ' . $e->getMessage()]);
}
?>