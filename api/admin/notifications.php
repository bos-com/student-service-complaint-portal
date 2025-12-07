<?php
session_start();
require_once '../db_config.php';

if (!isset($_SESSION['student']) || !in_array($_SESSION['student']['role'] ?? '', ['admin', 'superadmin'])) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

$pdo = getDBConnection();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

$recipient = $_POST['recipient'] ?? '';
$email     = trim($_POST['email'] ?? '');
$title     = $_POST['title'] ?? 'Notification from Admin';
$message   = trim($_POST['message'] ?? '');

if ($message === '') {
    die(json_encode(['success' => false, 'message' => 'Message is required']));
}

try {
    // Determine targets like in student-facing notifications
    $targets = [];

    if ($recipient === 'specific') {
        if ($email === '') {
            die(json_encode(['success' => false, 'message' => 'Student email is required']));
        }
        $stmt = $pdo->prepare("SELECT id, email FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $targets[] = $row;
        }
    } else { // "all" for this endpoint = all students
        $stmt = $pdo->query("SELECT id, email FROM students WHERE role = 'student'");
        $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($targets)) {
        die(json_encode(['success' => false, 'message' => 'No matching students found']));
    }

    // Use existing notifications table structure
    $insert = $pdo->prepare("
        INSERT INTO notifications (user_id, title, student_email, message, is_read, read_status)
        VALUES (?, ?, ?, ?, 0, 0)
    ");

    foreach ($targets as $t) {
        $insert->execute([
            $t['id'],
            $title,
            $t['email'],
            $message
        ]);
    }

    // Log the action
    $log_stmt = $pdo->prepare("
        INSERT INTO system_logs (user_id, action, details, ip_address) 
        VALUES (?, ?, ?, ?)
    ");
    $log_stmt->execute([
        $_SESSION['student']['id'],
        'send_notification',
        "Admin sent notification to {$recipient}" . ($email ? ": $email" : ""),
        $_SERVER['REMOTE_ADDR']
    ]);

    echo json_encode(['success' => true, 'message' => 'Notification sent successfully']);

} catch (Exception $e) {
    error_log("Admin notifications error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to send notification: ' . $e->getMessage()]);
}
?>