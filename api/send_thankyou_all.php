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
$message = trim($data['message'] ?? '');

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message is required']);
    exit;
}

try {
    // Get all students who have submitted ratings
    $stmt = $pdo->query("
        SELECT DISTINCT s.email, s.name 
        FROM complaint_ratings cr
        JOIN students s ON cr.student_id = s.id
        WHERE s.email IS NOT NULL AND s.email != ''
    ");
    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $count = 0;
    $success_count = 0;
    
    foreach ($recipients as $recipient) {
        $subject = "Thank You for Your Feedback - CampusVoice";
        $full_message = "Dear " . ($recipient['name'] ?: 'Student') . ",\n\n" . 
                       $message . "\n\n" .
                       "Best regards,\n" .
                       "CampusVoice Administration";
        
        try {
            // Insert into notifications table with your actual structure
            $log_stmt = $pdo->prepare("
                INSERT INTO notifications 
                (user_id, title, student_email, message, is_read, read_status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $log_stmt->execute([
                $_SESSION['student']['id'],  // user_id (sender)
                $subject,                     // title
                $recipient['email'],          // student_email (recipient)
                $full_message,                // message
                0,                            // is_read
                0                             // read_status
            ]);
            
            $success_count++;
        } catch (PDOException $e) {
            error_log("Failed to save notification for {$recipient['email']}: " . $e->getMessage());
        }
        
        $count++;
    }
    
    // Log the bulk action
    $log_stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $log_stmt->execute([
        $_SESSION['student']['id'],
        'send_thankyou_all',
        "Sent thank you emails to $success_count/$count recipients",
        $_SERVER['REMOTE_ADDR']
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => "Thank you messages saved for $success_count recipients",
        'count' => $success_count,
        'total' => $count,
        'note' => 'Emails logged in notifications system.'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>