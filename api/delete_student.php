<?php
session_start();
header('Content-Type: application/json');

// Check for both admin and superadmin sessions
$isAdmin = isset($_SESSION['admin']) && in_array(($_SESSION['admin']['role'] ?? ''), ['superadmin', 'admin']);
$isSuperadmin = isset($_SESSION['student']) && ($_SESSION['student']['role'] ?? '') === 'superadmin';

if (!$isAdmin && !$isSuperadmin) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Determine user details based on session type
if ($isAdmin) {
    $user_id = $_SESSION['admin']['id'];
    $user_name = $_SESSION['admin']['name'];
    $user_role = $_SESSION['admin']['role'];
} else {
    $user_id = $_SESSION['student']['id'];
    $user_name = $_SESSION['student']['name'];
    $user_role = 'superadmin';
}

$id = $_POST['id'] ?? 0;

if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=campusvoice;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // First, get student details for logging
    $stmt = $pdo->prepare("SELECT name, email FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    
    // Delete the student
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$id]);
    
    // Log the action
    try {
        $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, user_name, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
        $logStmt->execute([
            $user_id,
            $user_name,
            'delete_student',
            "Deleted student: {$student['name']} ({$student['email']})",
            $_SERVER['REMOTE_ADDR']
        ]);
    } catch (Exception $e) {
        // If system_logs table doesn't exist, try admin_logs
        try {
            $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, admin_name, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
            $logStmt->execute([
                $user_id,
                $user_name,
                'delete_student',
                "Deleted student: {$student['name']} ({$student['email']})",
                $_SERVER['REMOTE_ADDR']
            ]);
        } catch (Exception $e2) {
            // Ignore if both log tables don't exist
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()]);
}
?>