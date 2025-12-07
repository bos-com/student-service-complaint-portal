<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$query = "SELECT c.*, s.name as author_name FROM complaints c 
          LEFT JOIN students s ON c.student_id = s.id WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (c.title LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $query .= " AND c.category = ?";
    $params[] = $category;
}

if (!empty($status)) {
    $query .= " AND c.status = ?";
    $params[] = $status;
}

if (!empty($priority)) {
    $query .= " AND c.priority = ?";
    $params[] = $priority;
}

if (!empty($dateFrom)) {
    $query .= " AND DATE(c.created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $query .= " AND DATE(c.created_at) <= ?";
    $params[] = $dateTo;
}

$query .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$countQuery = preg_replace('/SELECT.*FROM/', 'SELECT COUNT(*) as total FROM', $query);
$countQuery = preg_replace('/LIMIT \? OFFSET \?/', '', $countQuery);
array_splice($params, -2); // Remove limit and offset params

$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$total = $stmt->fetchColumn();

echo json_encode([
    'success' => true,
    'complaints' => $complaints,
    'total' => $total,
    'pages' => ceil($total / $limit)
]);
?>