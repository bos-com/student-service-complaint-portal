<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is superadmin
if (!isset($_SESSION['student']) || ($_SESSION['student']['role'] ?? '') !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$host = 'localhost';
$db = 'campusvoice';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaint_id = $_POST['id'] ?? '';
    $status = $_POST['status'] ?? '';
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $priority = $_POST['priority'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($complaint_id)) {
        echo json_encode(['success' => false, 'message' => 'Complaint ID is required']);
        exit;
    }
    
    try {
        // Build update query based on provided fields
        $updateFields = [];
        $params = [];
        
        if (!empty($status)) {
            $updateFields[] = "status = ?";
            $params[] = $status;
        }
        if (!empty($title)) {
            $updateFields[] = "title = ?";
            $params[] = $title;
        }
        if (!empty($category)) {
            $updateFields[] = "category = ?";
            $params[] = $category;
        }
        if (!empty($priority)) {
            $updateFields[] = "priority = ?";
            $params[] = $priority;
        }
        if (!empty($description)) {
            $updateFields[] = "description = ?";
            $params[] = $description;
        }
        
        // Add updated_at manually since your table has ON UPDATE CURRENT_TIMESTAMP
        $updateFields[] = "updated_at = NOW()";
        
        if (empty($updateFields)) {
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            exit;
        }
        
        $params[] = $complaint_id;
        
        $sql = "UPDATE complaints SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            // Log the action - CORRECTED: Removed user_name from parameters
            $log_stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $log_stmt->execute([
                $_SESSION['student']['id'],
                'update_complaint',
                "Updated complaint ID $complaint_id" . (!empty($status) ? " to status: $status" : ""),
                $_SERVER['REMOTE_ADDR']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Complaint updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made or complaint not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>