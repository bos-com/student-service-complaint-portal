<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=campusvoice;charset=utf8mb4",
        'root', '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

$user_id = $_SESSION['user']['id'];
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// Validate input
$title = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$category = trim($data['category'] ?? '');

if (!$title || !$description || !$category) {
    echo json_encode(['success' => false, 'message' => 'All fields required']);
    exit;
}

// Insert complaint
try {
    $stmt = $pdo->prepare("
        INSERT INTO complaints 
        (student_id, title, description, category, status, created_at)
        VALUES (?, ?, ?, ?, 'open', NOW())
    ");
    $stmt->execute([$user_id, $title, $description, $category]);
    $complaint_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Complaint posted successfully!',
        'complaint_id' => $complaint_id
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to post complaint']);
}
?>