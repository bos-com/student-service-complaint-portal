<?php
// api/submit_complaint.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['student'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$student = $_SESSION['student'];
$studentId = $student['id'];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=campusvoice;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get form data
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = trim($_POST['category'] ?? '');
$location = trim($_POST['location'] ?? '');
$priority = $_POST['priority'] ?? 'medium';
$anonymous = ($_POST['anonymous'] ?? '0') === '1';

// Validate required fields
if (!$title || !$description || !$category) {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
    exit;
}

// Handle file upload
$imagePath = null;
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileExtension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    
    if (in_array($fileExtension, $allowedExtensions)) {
        $fileName = uniqid('complaint_') . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            $imagePath = 'uploads/' . $fileName;
        }
    }
}

try {
    // FIXED: Removed student_name from INSERT - it's derived from student_id via JOIN
    $stmt = $pdo->prepare("
        INSERT INTO complaints 
        (student_id, title, description, category, location, priority, status, image, anonymous)
        VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?)
    ");
    
    $stmt->execute([
        $studentId,
        $title,
        $description,
        $category,
        $location,
        $priority,
        $imagePath,
        $anonymous ? 1 : 0
    ]);
    
    $complaintId = $pdo->lastInsertId();
    
    // Create notification for admins
    $adminStmt = $pdo->query("SELECT id FROM students WHERE type = 'admin'");
    $admins = $adminStmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($admins as $adminId) {
        $notifStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES (?, ?, ?, 'complaint')
        ");
        $notifStmt->execute([
            $adminId,
            'New Complaint',
            "New {$category} complaint: {$title}"
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Complaint submitted successfully',
        'complaint_id' => $complaintId
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to submit complaint: ' . $e->getMessage()
    ]);
}
?>