<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode([]);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=campusvoice;charset=utf8mb4",
        'root', '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user']['id'];

// Mark all as read if POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->prepare("UPDATE notifications SET read_status = 1 WHERE user_id = ? AND read_status = 0")
        ->execute([$user_id]);
    echo json_encode(['success' => true]);
    exit;
}

// Fetch notifications
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

function timeAgo($datetime) {
    $now = new DateTime();
    $past = new DateTime($datetime);
    $interval = $now->diff($past);

    if ($interval->y) return $interval->y . 'y ago';
    if ($interval->m) return $interval->m . 'mo ago';
    if ($interval->d) return $interval->d . 'd ago';
    if ($interval->h) return $interval->h . 'h ago';
    if ($interval->i) return $interval->i . 'm ago';
    return 'just now';
}

$result = array_map(function($n) {
    return [
        'id' => $n['id'],
        'title' => $n['message'] ?? 'New notification',
        'time' => timeAgo($n['created_at']),
        'read' => (bool)$n['read_status']
    ];
}, $notifications);

echo json_encode($result);
?>