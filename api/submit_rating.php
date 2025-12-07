<?php
// filepath: api/submit_rating.php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['student'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$student = $_SESSION['student'];
$studentId = $student['id'];

// Get form data
$complaint_id = intval($_POST['complaint_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$feedback = trim($_POST['feedback'] ?? '');
$response_time = trim($_POST['response_time'] ?? '');
$resolution_quality = trim($_POST['resolution_quality'] ?? '');
$would_recommend = isset($_POST['would_recommend']) && $_POST['would_recommend'] === 'true' ? 1 : 0;

// Validate
if ($complaint_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid complaint ID']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
    exit;
}

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=campusvoice;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check if student owns this complaint
$check_stmt = $pdo->prepare("SELECT id, status FROM complaints WHERE id = ? AND student_id = ?");
$check_stmt->execute([$complaint_id, $studentId]);
$complaint = $check_stmt->fetch(PDO::FETCH_ASSOC);

if (!$complaint) {
    echo json_encode(['success' => false, 'message' => 'Complaint not found or you do not have permission']);
    exit;
}

// Check if already rated
$existing_stmt = $pdo->prepare("SELECT id FROM complaint_ratings WHERE complaint_id = ? AND student_id = ?");
$existing_stmt->execute([$complaint_id, $studentId]);
if ($existing_stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You have already rated this complaint']);
    exit;
}

// Insert rating
try {
    $stmt = $pdo->prepare("
        INSERT INTO complaint_ratings 
        (complaint_id, student_id, rating, feedback, response_time, resolution_quality, would_recommend, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $complaint_id,
        $studentId,
        $rating,
        $feedback,
        $response_time,
        $resolution_quality,
        $would_recommend
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Thank you for your feedback!'
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to submit rating: ' . $e->getMessage()]);
}
?>