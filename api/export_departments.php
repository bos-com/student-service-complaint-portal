<?php
session_start();

if (!isset($_SESSION['student']) || ($_SESSION['student']['role'] ?? '') !== 'superadmin') {
    header('HTTP/1.1 403 Forbidden');
    die('Unauthorized');
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
    header('HTTP/1.1 500 Internal Server Error');
    die('Database connection failed');
}

try {
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
    $log_stmt = $pdo->prepare("INSERT INTO system_logs (user_id, user_name, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    $log_stmt->execute([
        $_SESSION['student']['id'],
        $_SESSION['student']['name'],
        'export_departments',
        'Exported department contacts to CSV',
        $_SERVER['REMOTE_ADDR']
    ]);
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    die('Failed to export departments');
}
?>