<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user'])) { 
    echo json_encode(['success'=>false,'error'=>'Unauthenticated']); 
    exit; 
}

$userId = $_SESSION['user']['id'];
$id     = (int)($_POST['id'] ?? 0);

if ($id === 0) {
    echo json_encode(['success'=>false,'error'=>'Invalid complaint ID']);
    exit;
}

// Check if user owns the complaint
$stmt = $pdo->prepare("SELECT user_id FROM complaints WHERE id=?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) { 
    echo json_encode(['success'=>false,'error'=>'Complaint not found']); 
    exit; 
}

if ($row['user_id'] != $userId) { 
    echo json_encode(['success'=>false,'error'=>'Not owner']); 
    exit; 
}

$title = trim($_POST['title'] ?? '');
$desc  = trim($_POST['description'] ?? '');
$cat   = $_POST['category'] ?? '';
$loc   = trim($_POST['location'] ?? '');
$prio  = $_POST['priority'] ?? 'medium';
$anon  = !empty($_POST['anonymous']) ? 1 : 0;

// Validate required fields
if (empty($title)) {
    echo json_encode(['success'=>false,'error'=>'Title is required']);
    exit;
}

if (empty($desc)) {
    echo json_encode(['success'=>false,'error'=>'Description is required']);
    exit;
}

$sql = "UPDATE complaints SET title=?, description=?, category=?, location=?, priority=?, anonymous=?, updated_at=NOW()";
$params = [$title, $desc, $cat, $loc, $prio, $anon];

$imagePath = null;
if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','pdf'];
    
    if (!in_array($ext, $allowed)) { 
        echo json_encode(['success'=>false,'error'=>'Invalid file type']); 
        exit; 
    }
    
    $newName = uniqid('img_').'.'.$ext;
    $dest = '../uploads/'.$newName;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
        $imagePath = $newName;
        
        // Delete old image
        $oldStmt = $pdo->prepare("SELECT image FROM complaints WHERE id=?");
        $oldStmt->execute([$id]);
        $oldImg = $oldStmt->fetchColumn();
        
        if ($oldImg && file_exists('../uploads/'.$oldImg)) {
            @unlink('../uploads/'.$oldImg);
        }
    }
}

if ($imagePath !== null) { 
    $sql .= ", image=?"; 
    $params[] = $imagePath; 
}

$sql .= " WHERE id=?"; 
$params[] = $id;

try {
    $updateStmt = $pdo->prepare($sql);
    $updateStmt->execute($params);
    
    // Fetch updated complaint with author info
    $stmt = $pdo->prepare("SELECT c.*, u.name AS author_name FROM complaints c LEFT JOIN users u ON c.user_id = u.id WHERE c.id = ?");
    $stmt->execute([$id]);
    $complaint = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success'=>true,'complaint'=>$complaint]);
    
} catch (PDOException $e) {
    error_log("Update complaint error: " . $e->getMessage());
    echo json_encode(['success'=>false,'error'=>'Database error: ' . $e->getMessage()]);
}
?>