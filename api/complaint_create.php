<?php
session_start();
require '../db.php';
if (!isset($_SESSION['user'])) { echo json_encode(['success'=>false,'error'=>'Unauthenticated']); exit; }
$userId = $_SESSION['user']['id'];

$title       = trim($_POST['title'] ?? '');
$desc        = trim($_POST['description'] ?? '');
$cat         = $_POST['category'] ?? '';
$loc         = trim($_POST['location'] ?? '');
$prio        = $_POST['priority'] ?? 'medium';
$anon        = !empty($_POST['anonymous']) ? 1 : 0;

if (!$title || !$desc || !$cat) { echo json_encode(['success'=>false,'error'=>'Missing fields']); exit; }

$imagePath = null;
if (!empty($_FILES['image']['name'])) {
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg','jpeg','png','gif','pdf'];
    if (!in_array(strtolower($ext), $allowed)) { echo json_encode(['success'=>false,'error'=>'Invalid file']); exit; }
    $newName = uniqid('img_').'.'.$ext;
    $dest = '../uploads/'.$newName;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) $imagePath = $newName;
}

$stmt = $pdo->prepare("
    INSERT INTO complaints (user_id,title,description,category,location,priority,anonymous,image)
    VALUES (?,?,?,?,?,?,?,?)
");
$stmt->execute([$userId,$title,$desc,$cat,$loc,$prio,$anon,$imagePath]);
$newId = $pdo->lastInsertId();

$stmt = $pdo->prepare("SELECT c.*, u.name AS author_name FROM complaints c LEFT JOIN users u ON c.user_id=u.id WHERE c.id=?");
$stmt->execute([$newId]);
$complaint = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['success'=>true,'complaint'=>$complaint]);