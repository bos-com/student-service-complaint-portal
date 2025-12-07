<?php
session_start();
header('Content-Type: application/json');

require_once 'db_config.php';

// Only superadmin can change global system settings
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

$pdo = getDBConnection();

/**
 * Helper to upsert a single setting in system_settings
 */
function save_setting(PDO $pdo, string $key, string $value): void {
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value)
        VALUES (:k, :v)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    $stmt->execute([':k' => $key, ':v' => $value]);
}

try {
    // We don't actually need the "type" here; the JS already sends the concrete fields.
    // Just persist any known keys that are present in POST.
    $map = [
        // Core/system settings
        'platform_name',
        'maintenance_mode',
        'max_login_attempts',
        'session_timeout',
        // Security
        'password_policy',
        // Email / SMTP
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        // Appearance
        'default_theme',
        'primary_color',
        // Analytics
        'data_retention',
        'analytics_enabled',
        'auto_reports',
    ];

    $pdo->beginTransaction();

    foreach ($map as $key) {
        if (isset($_POST[$key])) {
            // Coerce everything to string for storage
            $value = (string)$_POST[$key];
            save_setting($pdo, $key, $value);
        }
    }

    // Log the change
    try {
        $log = $pdo->prepare("
            INSERT INTO system_logs (user_id, action, details, ip_address)
            VALUES (?, ?, ?, ?)
        ");
        $log->execute([
            $_SESSION['student']['id'],
            'update_settings',
            'System settings updated from superadmin dashboard',
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    } catch (Throwable $e) {
        // If logs table is missing we just ignore
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('save_settings.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save settings']);
}

