<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['student']) || ($_SESSION['student']['role'] ?? '') !== 'superadmin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

$pdo = getDBConnection();

$type   = $_POST['type'] ?? 'complaints';
$format = $_POST['format'] ?? 'csv'; // csv, pdf, excel
$start  = $_POST['start_date'] ?? null;
$end    = $_POST['end_date'] ?? null;

// Normalise dates (optional filter)
if ($start && !$end) {
    $end = $start;
}

function build_date_clause(&$params, $start, $end, $column = 'created_at') {
    $clause = '';
    if ($start && $end) {
        $clause = "WHERE DATE($column) BETWEEN :start AND :end";
        $params[':start'] = $start;
        $params[':end']   = $end;
    }
    return $clause;
}

try {
    $params = [];
    $rows   = [];
    $filenameBase = 'report_' . $type . '_' . date('Y-m-d');

    switch ($type) {
        case 'users':
            $clause = build_date_clause($params, $start, $end, 'created_at');
            $sql = "
                SELECT id, name, email, student_id, role, status, created_at
                FROM students
                $clause
                ORDER BY created_at DESC
            ";
            break;

        case 'system':
        case 'logs':
            $clause = build_date_clause($params, $start, $end, 'created_at');
            $sql = "
                SELECT id, user_id, action, details, ip_address, created_at
                FROM system_logs
                $clause
                ORDER BY created_at DESC
            ";
            $type = 'logs'; // normalise
            break;

        case 'complaints':
        default:
            $clause = build_date_clause($params, $start, $end, 'created_at');
            $sql = "
                SELECT id, student_id, title, category, priority, status, location, created_at, updated_at
                FROM complaints
                $clause
                ORDER BY created_at DESC
            ";
            $type = 'complaints';
            break;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build CSV content (works for csv/excel; caller just sets extension)
    $output = fopen('php://temp', 'r+');

    if (!empty($rows)) {
        // Header
        fputcsv($output, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
    } else {
        fputcsv($output, ['No data found for selected filters']);
    }

    rewind($output);
    $csvData = stream_get_contents($output);
    fclose($output);

    // Log the report generation
    try {
        $log = $pdo->prepare("
            INSERT INTO system_logs (user_id, action, details, ip_address)
            VALUES (?, ?, ?, ?)
        ");
        $log->execute([
            $_SESSION['student']['id'],
            'generate_report',
            "Generated $type report ($format)",
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    } catch (Throwable $e) {
        // ignore logging errors
    }

    // Send as a downloadable file; the frontend uses blob() so this is fine.
    $ext = $format === 'excel' ? 'csv' : ($format === 'pdf' ? 'txt' : 'csv');
    $filename = $filenameBase . '.' . $ext;

    header_remove('Content-Type');
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($csvData));

    echo $csvData;
    exit;
} catch (Throwable $e) {
    error_log('generate_report.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to generate report']);
}

