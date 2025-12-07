<?php
session_start();
require_once '../db_config.php';

if (!isset($_SESSION['student']) || !in_array($_SESSION['student']['role'] ?? '', ['admin', 'superadmin'])) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

$pdo = getDBConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'get_department':
            getDepartment($pdo);
            break;
        case 'save_department':
            saveDepartment($pdo);
            break;
        case 'update_status':
            updateDepartmentStatus($pdo);
            break;
        case 'delete_department':
            deleteDepartment($pdo);
            break;
        case 'export':
            exportDepartments($pdo);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Admin departments error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Operation failed: ' . $e->getMessage()]);
}

function getDepartment($pdo) {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        die(json_encode(['success' => false, 'message' => 'Department ID required']));
    }

    $stmt = $pdo->prepare("SELECT * FROM department_contacts WHERE id = ?");
    $stmt->execute([$id]);
    $department = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($department) {
        echo json_encode(['success' => true, 'department' => $department]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Department not found']);
    }
}

function saveDepartment($pdo) {
    $id = $_POST['id'] ?? null;
    $name = $_POST['department_name'] ?? '';
    $contact_person = $_POST['contact_person'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $location = $_POST['office_location'] ?? '';
    $category = $_POST['category'] ?? 'academic';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? 'active';

    if (empty($name) || empty($email)) {
        die(json_encode(['success' => false, 'message' => 'Department name and email are required']));
    }

    if ($id) {
        // Update existing
        $stmt = $pdo->prepare("
            UPDATE department_contacts 
            SET department_name = ?, contact_person = ?, email = ?, phone = ?, 
                office_location = ?, category = ?, description = ?, status = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$name, $contact_person, $email, $phone, $location, $category, $description, $status, $id]);
        $message = 'Department updated successfully';
        $logAction = 'update_department';
    } else {
        // Insert new
        $stmt = $pdo->prepare("
            INSERT INTO department_contacts 
            (department_name, contact_person, email, phone, office_location, category, description, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $contact_person, $email, $phone, $location, $category, $description, $status]);
        $message = 'Department created successfully';
        $logAction = 'create_department';
    }

    // Log the action
    logAction($pdo, $logAction, "Department: $name");

    echo json_encode(['success' => true, 'message' => $message]);
}

function updateDepartmentStatus($pdo) {
    $id = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? '';

    if (!$id || !in_array($status, ['active', 'inactive'])) {
        die(json_encode(['success' => false, 'message' => 'Invalid status or department ID']));
    }

    $stmt = $pdo->prepare("UPDATE department_contacts SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $id]);

    // Log the action
    logAction($pdo, 'update_department_status', "Changed department #$id status to $status");

    echo json_encode(['success' => true, 'message' => "Department status updated to $status"]);
}

function deleteDepartment($pdo) {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        die(json_encode(['success' => false, 'message' => 'Department ID required']));
    }

    // Get department info for logging
    $stmt = $pdo->prepare("SELECT department_name FROM department_contacts WHERE id = ?");
    $stmt->execute([$id]);
    $dept = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("DELETE FROM department_contacts WHERE id = ?");
    $stmt->execute([$id]);

    // Log the action
    logAction($pdo, 'delete_department', "Deleted department: " . ($dept['department_name'] ?? "#$id"));

    echo json_encode(['success' => true, 'message' => 'Department deleted successfully']);
}

function exportDepartments($pdo) {
    $stmt = $pdo->query("SELECT * FROM department_contacts ORDER BY department_name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="department_contacts_' . date('Y-m-d') . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, ['ID', 'Department Name', 'Contact Person', 'Email', 'Phone', 'Office Location', 'Category', 'Status', 'Description']);
    
    // Add data rows
    foreach ($departments as $dept) {
        fputcsv($output, [
            $dept['id'],
            $dept['department_name'],
            $dept['contact_person'],
            $dept['email'],
            $dept['phone'],
            $dept['office_location'],
            $dept['category'],
            $dept['status'],
            $dept['description']
        ]);
    }
    
    fclose($output);
    
    // Log the export action
    logAction($pdo, 'export_departments', 'Exported department contacts to CSV');
    exit;
}

function logAction($pdo, $action, $details) {
    $log_stmt = $pdo->prepare("
        INSERT INTO system_logs (user_id, action, details, ip_address) 
        VALUES (?, ?, ?, ?)
    ");
    $log_stmt->execute([
        $_SESSION['student']['id'],
        $action,
        $details,
        $_SERVER['REMOTE_ADDR']
    ]);
}
?>