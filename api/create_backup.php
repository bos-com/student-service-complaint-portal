<?php
session_start();
header('Content-Type: application/json');

require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['student']) || ($_SESSION['student']['role'] ?? '') !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Create data directory if it doesn't exist
$backupDir = '../data/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$backupType = $_POST['type'] ?? 'database';
$timestamp  = date('Y-m-d_H-i-s');
$filename   = "backup_{$backupType}_{$timestamp}.sql";
$filepath   = $backupDir . $filename;

$pdo = getDBConnection();

try {
    // Get all table data
    $tables = ['students', 'complaints', 'system_logs', 'system_settings', 'notifications'];
    $backupContent = "-- CampusVoice Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n-- By: " . $_SESSION['student']['name'] . "\n\n";

    foreach ($tables as $table) {
        $backupContent .= "-- Table: $table\n";
        $stmt = $pdo->query("SELECT * FROM $table");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $columns = implode(', ', array_keys($row));
            $values  = implode("', '", array_map(function ($value) {
                return str_replace("'", "''", (string)$value);
            }, array_values($row)));
            $backupContent .= "INSERT INTO $table ($columns) VALUES ('$values');\n";
        }
        $backupContent .= "\n";
    }

    // Save backup file
    file_put_contents($filepath, $backupContent);

    // Log backup creation
    try {
        $log_stmt = $pdo->prepare("
            INSERT INTO system_logs (user_id, action, details, ip_address)
            VALUES (?, ?, ?, ?)
        ");
        $log_stmt->execute([
            $_SESSION['student']['id'],
            'create_backup',
            "Created backup: $filename",
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    } catch (Throwable $e) {
        // ignore log errors
    }

    echo json_encode([
        'success'      => true,
        'message'      => 'Backup created successfully',
        'filename'     => $filename,
        'download_url' => 'data/' . $filename,
    ]);
} catch (Throwable $e) {
    error_log('create_backup.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Backup failed']);
}
