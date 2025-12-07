<?php
// api/update_profile.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['student'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$studentId = $_SESSION['student']['id'];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=campusvoice;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$name = trim($input['name'] ?? '');
$contact = trim($input['contact'] ?? '');

if (!$name) {
    echo json_encode(['success' => false, 'message' => 'Name is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE students SET name = ?, contact = ? WHERE id = ?");
    $stmt->execute([$name, $contact, $studentId]);
    
    // Update session
    $_SESSION['student']['name'] = $name;
    $_SESSION['student']['contact'] = $contact;
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
}