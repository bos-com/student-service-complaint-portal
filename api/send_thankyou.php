<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is superadmin
if (!isset($_SESSION['student']) || ($_SESSION['student']['role'] ?? '') !== 'superadmin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$rating_id = intval($_POST['rating_id'] ?? 0);
$student_email = filter_var($_POST['student_email'] ?? '', FILTER_SANITIZE_EMAIL);
$message = trim($_POST['message'] ?? '');

if ($rating_id <= 0 || empty($student_email) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

try {
    // Get rating details
    $stmt = $pdo->prepare("
        SELECT cr.*, s.name as student_name 
        FROM complaint_ratings cr
        LEFT JOIN students s ON cr.student_id = s.id
        WHERE cr.id = ?
    ");
    $stmt->execute([$rating_id]);
    $rating = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$rating) {
        echo json_encode(['success' => false, 'message' => 'Rating not found']);
        exit;
    }
    
    $subject = "Thank You for Your Feedback - CampusVoice";
    $full_message = "Dear " . ($rating['student_name'] ?: 'Student') . ",\n\n" . 
                   $message . "\n\n" .
                   "Your rating: " . $rating['rating'] . "/5 stars\n" .
                   "Feedback: " . ($rating['feedback'] ?: 'No additional feedback') . "\n\n" .
                   "Best regards,\n" .
                   "CampusVoice Administration";
    
    // Insert into notifications table with your actual structure
    $log_stmt = $pdo->prepare("
        INSERT INTO notifications 
        (user_id, title, student_email, message, is_read, read_status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $log_stmt->execute([
        $_SESSION['student']['id'],  // user_id (sender)
        $subject,                     // title
        $student_email,               // student_email (recipient)
        $full_message,                // message
        0,                            // is_read
        0                             // read_status
    ]);
    
    // Log the action in system_logs
    $log_stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $log_stmt->execute([
        $_SESSION['student']['id'],
        'send_thankyou',
        "Sent thank you email for rating ID: $rating_id to: $student_email",
        $_SERVER['REMOTE_ADDR']
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Thank you message saved for ' . $student_email,
        'note' => 'Email logged in notifications system.'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>