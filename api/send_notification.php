<?php
session_start();
header('Content-Type: application/json');

require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Allow both admin and superadmin sessions
$isAdmin      = isset($_SESSION['admin']) && in_array(($_SESSION['admin']['role'] ?? ''), ['superadmin', 'admin']);
$isSuperadmin = isset($_SESSION['student']) && ($_SESSION['student']['role'] ?? '') === 'superadmin';

if (!$isAdmin && !$isSuperadmin) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Determine sender details based on session type
if ($isAdmin) {
    $senderName = $_SESSION['admin']['name'];
} else {
    $senderName = $_SESSION['student']['name'];
}

$recipient = $_POST['recipient'] ?? 'all';      // all | students | admins | specific
$email     = trim($_POST['email'] ?? '');
$title     = $_POST['title'] ?? 'Admin Notification';
$message   = trim($_POST['message'] ?? '');

if ($message === '') {
    echo json_encode(['success' => false, 'message' => 'Message is required']);
    exit;
}

$pdo = getDBConnection();

try {
    // Figure out target users based on recipient type
    $targets = [];

    if ($recipient === 'specific') {
        if ($email === '') {
            echo json_encode(['success' => false, 'message' => 'Recipient email is required']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT id, email FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $targets[] = $row;
        }
    } elseif ($recipient === 'students') {
        $stmt = $pdo->query("SELECT id, email FROM students WHERE role = 'student'");
        $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($recipient === 'admins') {
        $stmt = $pdo->query("SELECT id, email FROM students WHERE role IN ('admin','superadmin')");
        $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else { // 'all'
        $stmt = $pdo->query("SELECT id, email FROM students");
        $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($targets)) {
        echo json_encode(['success' => false, 'message' => 'No matching users found for this notification']);
        exit;
    }

    // Insert one notification row per target into the existing notifications table
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

    // Log the notification action (best-effort)
    try {
        $log_stmt = $pdo->prepare("
            INSERT INTO system_logs (user_id, action, details, ip_address)
            VALUES (?, ?, ?, ?)
        ");
        $log_stmt->execute([
            $_SESSION['student']['id'] ?? ($_SESSION['admin']['id'] ?? null),
            'send_notification',
            "Sent notification '{$title}' to {$recipient} ({$senderName})",
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    } catch (Throwable $e) {
        // ignore log errors
    }

    echo json_encode(['success' => true, 'message' => 'Notification sent successfully']);
} catch (Throwable $e) {
    error_log('send_notification.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to send notification']);
}
