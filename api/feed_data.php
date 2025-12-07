<?php
session_start();
header('Content-Type: application/json');

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

$filter = $_GET['filter'] ?? 'all';

if ($filter !== 'all') {
    $query = "SELECT c.*, s.name FROM complaints c 
              LEFT JOIN students s ON c.student_id = s.id 
              WHERE c.status = ? 
              ORDER BY c.created_at DESC LIMIT 50";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$filter]);
} else {
    $query = "SELECT c.*, s.name FROM complaints c 
              LEFT JOIN students s ON c.student_id = s.id 
              ORDER BY c.created_at DESC LIMIT 50";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
}

$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = array_map(function($c) {
    return [
        'id' => (int)$c['id'],
        'title' => $c['title'],
        'description' => $c['description'],
        'category' => $c['category'],
        'priority' => $c['priority'],
        'status' => $c['status'],
        'name' => $c['anonymous'] ? 'Anonymous' : ($c['name'] ?? 'Unknown'),
        'anonymous' => (bool)$c['anonymous'],
        'image' => $c['image'],
        'created_at' => $c['created_at'],
        'likes' => 0,
        'comments' => 0,
        'liked' => false
    ];
}, $complaints);

echo json_encode($result);
?>