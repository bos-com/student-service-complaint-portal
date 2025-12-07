<?php
// api/get_complaints.php - Improved version
session_start();
header('Content-Type: application/json; charset=utf-8');

// Allow a local debug mode: call with ?debug=1 from localhost only
$debug = (isset($_GET['debug']) && $_GET['debug'] === '1');
if ($debug) {
    $allowed = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
    if (!$allowed) {
        echo json_encode(['success' => false, 'message' => 'Debug only allowed from localhost']);
        exit;
    }
}

// Require DB config (provides $pdo)
$configFile = __DIR__ . '/../config/database.php';
if (!file_exists($configFile)) {
    echo json_encode(['success' => false, 'message' => 'Database config not found']);
    exit;
}
require_once $configFile;

// If not debug, require authenticated session
if (!$debug && !isset($_SESSION['student'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Determine student id (0 if not available in debug)
$studentId = isset($_SESSION['student']['id']) ? (int)$_SESSION['student']['id'] : 0;

// Accept filter param (validate)
$filter = $_GET['filter'] ?? 'all';
$allowedFilters = ['all', 'pending', 'progress', 'resolved'];
if (!in_array($filter, $allowedFilters)) {
    $filter = 'all';
}

try {
    // For debug: return some diagnostics
    if ($debug) {
        $cols = $pdo->query("SHOW COLUMNS FROM complaints")->fetchAll(PDO::FETCH_ASSOC);
        $count = $pdo->query("SELECT COUNT(*) AS total FROM complaints")->fetch(PDO::FETCH_ASSOC);
        echo json_encode([
            'success' => true,
            'debug' => true,
            'complaints_count' => (int)($count['total'] ?? 0),
            'complaints_columns' => $cols
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Build query
    $sql = "
        SELECT
            c.id,
            c.student_id,
            c.title,
            c.description,
            c.category,
            c.location,
            c.priority,
            c.status,
            c.image,
            c.anonymous,
            c.created_at,
            s.name AS student_name,
            (SELECT COUNT(*) FROM complaint_likes WHERE complaint_id = c.id) AS likes,
            (SELECT COUNT(*) FROM complaint_likes WHERE complaint_id = c.id AND student_id = ?) AS user_liked,
            (SELECT COUNT(*) FROM comments WHERE complaint_id = c.id) AS comment_count
        FROM complaints c
        LEFT JOIN students s ON c.student_id = s.id
    ";

    $params = [$studentId];

    if ($filter !== 'all') {
        $sql .= " WHERE c.status = ?";
        $params[] = $filter;
    }

    $sql .= " ORDER BY c.created_at DESC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Helper for time formatting
    $timeAgo = function($datetime) {
        $timestamp = strtotime($datetime);
        if ($timestamp === false) return '';
        $diff = time() - $timestamp;
        if ($diff < 60) return 'now';
        if ($diff < 3600) return floor($diff / 60) . 'm';
        if ($diff < 86400) return floor($diff / 3600) . 'h';
        return floor($diff / 86400) . 'd';
    };

    // Normalize results for frontend
    foreach ($complaints as &$c) {
        $c['created_at_formatted'] = $timeAgo($c['created_at'] ?? '');
        // Ensure numeric types / booleans consistent
        $c['likes'] = isset($c['likes']) ? (int)$c['likes'] : 0;
        $c['comment_count'] = isset($c['comment_count']) ? (int)$c['comment_count'] : 0;
        $c['user_liked'] = isset($c['user_liked']) && (int)$c['user_liked'] > 0 ? true : false;
        // anonymous may be stored as 0/1 or '0'/'1'
        $c['anonymous'] = isset($c['anonymous']) && ($c['anonymous'] == 1 || $c['anonymous'] === '1') ? 1 : 0;
        // student_name fallback
        if (empty($c['student_name'])) $c['student_name'] = 'Student';
    }
    unset($c);

    echo json_encode([
        'success' => true,
        'data' => [
            'complaints' => $complaints,
            'filter' => $filter
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // Log server-side if desired: error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load complaints: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}