<?php
session_start();
require_once '../db_config.php';

if (!isset($_SESSION['student']) || !in_array($_SESSION['student']['role'] ?? '', ['admin', 'superadmin'])) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

$pdo = getDBConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'change_password':
            changePassword($pdo);
            break;
        case 'delete_logs':
            deleteLogs($pdo);
            break;
        case 'get_stats':
            getStats($pdo);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Admin system error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Operation failed: ' . $e->getMessage()]);
}

function changePassword($pdo) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    if (empty($current_password) || empty($new_password)) {
        die(json_encode(['success' => false, 'message' => 'Current and new password are required']));
    }

    if (strlen($new_password) < 6) {
        die(json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']));
    }

    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM students WHERE id = ?");
    $stmt->execute([$_SESSION['student']['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($current_password, $user['password'])) {
        die(json_encode(['success' => false, 'message' => 'Current password is incorrect']));
    }

    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
    $stmt->execute([$hashed_password, $_SESSION['student']['id']]);

    // Log the action
    logAction($pdo, 'change_password', 'Password changed successfully');

    echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
}

function deleteLogs($pdo) {
    $log_id = $_POST['log_id'] ?? null;
    $delete_all = $_POST['delete_all'] ?? false;

    if ($log_id) {
        // Delete single log
        $stmt = $pdo->prepare("DELETE FROM system_logs WHERE id = ?");
        $stmt->execute([$log_id]);
        $message = "Log entry deleted";
    } elseif ($delete_all) {
        // Delete all logs
        $stmt = $pdo->query("DELETE FROM system_logs");
        $message = "All logs deleted";
    } else {
        die(json_encode(['success' => false, 'message' => 'No valid operation specified']));
    }

    // Log the action
    logAction($pdo, 'delete_logs', $message);

    echo json_encode(['success' => true, 'message' => $message]);
}

function getStats($pdo) {
    // Get complaint stats
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'progress' THEN 1 ELSE 0 END) as progress,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
        FROM complaints
    ");
    $complaint_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get user stats
    $user_count = $pdo->query("SELECT COUNT(*) FROM students WHERE verified = 1")->fetchColumn();
    $admin_count = $pdo->query("SELECT COUNT(*) FROM students WHERE role IN ('admin', 'superadmin')")->fetchColumn();

    echo json_encode([
        'success' => true,
        'stats' => [
            'complaints' => $complaint_stats,
            'users' => [
                'total' => $user_count,
                'admins' => $admin_count
            ]
        ]
    ]);
}

function logAction($pdo, $action, $details) {
    $log_stmt = $pdo->prepare("
        INSERT INTO system_logs (user_id, action, details, ip_address) 
        VALUES (?, ?, ?, ?)
    ");
    $log_stmt->execute([
        $_SESSION['student']['id'],
        $action,
        $details,
        $_SERVER['REMOTE_ADDR']
    ]);
}
?>