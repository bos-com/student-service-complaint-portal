<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

if (!isset($_SESSION['student']) || ($_SESSION['student']['role'] ?? '') !== 'superadmin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Database connection
$host = 'localhost';
$db = 'campusvoice';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$deptId = $_POST['id'] ?? null;
$departmentName = $_POST['department_name'] ?? '';
$contactPerson = $_POST['contact_person'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$officeLocation = $_POST['office_location'] ?? '';
$category = $_POST['category'] ?? 'academic';
$description = $_POST['description'] ?? '';
$status = $_POST['status'] ?? 'active';

if (empty($departmentName) || empty($email)) {
    die(json_encode(['success' => false, 'message' => 'Department name and email are required']));
}

try {
    if ($deptId) {
        // Update existing department
        $stmt = $pdo->prepare("UPDATE department_contacts SET department_name = ?, contact_person = ?, email = ?, phone = ?, office_location = ?, category = ?, description = ?, status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$departmentName, $contactPerson, $email, $phone, $officeLocation, $category, $description, $status, $deptId]);
        $action = 'update_department';
        $message = 'Department updated successfully';
    } else {
        // Insert new department
        $stmt = $pdo->prepare("INSERT INTO department_contacts (department_name, contact_person, email, phone, office_location, category, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$departmentName, $contactPerson, $email, $phone, $officeLocation, $category, $description, $status]);
        $action = 'create_department';
        $message = 'Department created successfully';
    }
    
    // Log the action
    $log_stmt = $pdo->prepare("INSERT INTO system_logs (user_id, user_name, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    $log_stmt->execute([
        $_SESSION['student']['id'],
        $_SESSION['student']['name'],
        $action,
        'Department: ' . $departmentName,
        $_SERVER['REMOTE_ADDR']
    ]);

    echo json_encode(['success' => true, 'message' => $message]);
} catch (Exception $e) {
    error_log("Save department error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to save department: ' . $e->getMessage()]);
}
?>