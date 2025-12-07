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

// Determine user details based on session type
if ($isAdmin) {
    $user_id   = $_SESSION['admin']['id'];
    $user_name = $_SESSION['admin']['name'];
} else {
    $user_id   = $_SESSION['student']['id'];
    $user_name = $_SESSION['student']['name'];
}

$complaint_id = $_POST['id'] ?? 0;

if (empty($complaint_id)) {
    echo json_encode(['success' => false, 'message' => 'Complaint ID is required']);
    exit;
}

$pdo = getDBConnection();

try {
    // First, get the complaint to check for image and log details
    $stmt = $pdo->prepare("SELECT * FROM complaints WHERE id = ?");
    $stmt->execute([$complaint_id]);
    $complaint = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$complaint) {
        echo json_encode(['success' => false, 'message' => 'Complaint not found']);
        exit;
    }

    // Delete associated image if exists
    if (!empty($complaint['image']) && file_exists('../uploads/' . $complaint['image'])) {
        @unlink('../uploads/' . $complaint['image']);
    }

    // Manually delete dependent records (comments, likes, ratings) to satisfy FK constraints
    $pdo->beginTransaction();
    try {
        // Delete related comments
        $stmt = $pdo->prepare("DELETE FROM comments WHERE complaint_id = ?");
        $stmt->execute([$complaint_id]);

        // Delete related likes
        $stmt = $pdo->prepare("DELETE FROM likes WHERE complaint_id = ?");
        $stmt->execute([$complaint_id]);

        // Delete related ratings
        $stmt = $pdo->prepare("DELETE FROM complaint_ratings WHERE complaint_id = ?");
        $stmt->execute([$complaint_id]);

        // Delete the complaint itself
        $stmt = $pdo->prepare("DELETE FROM complaints WHERE id = ?");
        $stmt->execute([$complaint_id]);

        $pdo->commit();
    } catch (Throwable $tx) {
        $pdo->rollBack();
        throw $tx;
    }

    // Log the action
    try {
        $log_stmt = $pdo->prepare("
            INSERT INTO system_logs (user_id, action, details, ip_address)
            VALUES (?, ?, ?, ?)
        ");
        $log_stmt->execute([
            $user_id,
            'delete_complaint',
            "Deleted complaint #$complaint_id: " . substr($complaint['title'], 0, 50) . " (by $user_name)",
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    } catch (Throwable $e) {
        // ignore if logs table missing
    }

    echo json_encode(['success' => true, 'message' => 'Complaint deleted successfully']);
} catch (Throwable $e) {
    error_log("delete_complaint.php error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to delete complaint']);
}
