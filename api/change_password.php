<?php
session_start();
header('Content-Type: application/json');

require_once 'db_config.php';

// Used by superadmin_dashboard profile modal
if (!isset($_SESSION['student']) || ($_SESSION['student']['role'] ?? '') !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$current_password = $_POST['current_password'] ?? '';
$new_password     = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? ($_POST['new_password'] ?? '');

if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
    exit;
}

if (empty($current_password) || empty($new_password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

$user_id = $_SESSION['student']['id'];
$pdo     = getDBConnection();

try {
    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM students WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($current_password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit;
    }

    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
    $stmt->execute([$hashed_password, $user_id]);

    // Log the action into system_logs if available
    try {
        $log_stmt = $pdo->prepare("
            INSERT INTO system_logs (user_id, action, details, ip_address)
            VALUES (?, ?, ?, ?)
        ");
        $log_stmt->execute([
            $user_id,
            'change_password',
            'Superadmin changed their password',
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    } catch (Throwable $e) {
        // ignore logging errors
    }

    echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
} catch (Throwable $e) {
    error_log('change_password.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
