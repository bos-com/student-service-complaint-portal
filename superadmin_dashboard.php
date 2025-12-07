<?php
// filepath: superadmin_dashboard.php
session_start();

// Check if user is logged in and is superadmin
if (!isset($_SESSION['student']) || ($_SESSION['student']['role'] ?? '') !== 'superadmin') {
    header('Location: index.php#login');
    exit;
}

$superadmin = $_SESSION['student'];
$host = 'localhost';
$db = 'campusvoice';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed');
}

// Function to create missing tables
function ensureSystemTables($pdo) {
    try {
        // Create system_settings table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Insert default settings if table is empty
        $count = $pdo->query("SELECT COUNT(*) FROM system_settings")->fetchColumn();
        if ($count == 0) {
            $defaultSettings = [
                ['platform_name', 'CampusVoice'],
                ['maintenance_mode', '0'],
                ['max_login_attempts', '5'],
                ['session_timeout', '30'],
                ['smtp_host', ''],
                ['smtp_port', '587'],
                ['smtp_username', ''],
                ['smtp_password', ''],
                ['password_policy', 'medium'],
                ['data_retention', '365'],
                ['analytics_enabled', '1'],
                ['auto_reports', 'weekly'],
                ['default_theme', 'light'],
                ['primary_color', '#1E3A8A']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($defaultSettings as $setting) {
                $stmt->execute($setting);
            }
        }
        
        // Create system_logs table
        $pdo->exec("CREATE TABLE IF NOT EXISTS system_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create notifications table
        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipient_type ENUM('all', 'students', 'admins', 'specific') DEFAULT 'all',
            recipient_email VARCHAR(100),
            title VARCHAR(255),
            message TEXT,
            sent_by INT,
            sent_by_name VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create backups table
        $pdo->exec("CREATE TABLE IF NOT EXISTS system_backups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255),
            filepath VARCHAR(500),
            size BIGINT,
            created_by INT,
            created_by_name VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Create department_contacts table
        $pdo->exec("CREATE TABLE IF NOT EXISTS department_contacts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            department_name VARCHAR(255) NOT NULL,
            contact_person VARCHAR(255),
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(50),
            office_location VARCHAR(255),
            category VARCHAR(100),
            description TEXT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        // Insert default department contacts if table is empty
        $deptCount = $pdo->query("SELECT COUNT(*) FROM department_contacts")->fetchColumn();
        if ($deptCount == 0) {
            $defaultDepartments = [
                ['Academic Affairs', 'Dr. John Smith', 'academic@bugema.ac.ug', '+256-XXX-XXXX', 'Main Campus, Building A', 'academic', 'Handles academic programs and curriculum'],
                ['Student Affairs', 'Ms. Sarah Johnson', 'studentaffairs@bugema.ac.ug', '+256-XXX-XXXX', 'Student Center, Room 101', 'student', 'Student welfare and activities'],
                ['IT Department', 'Mr. David Brown', 'it-support@bugema.ac.ug', '+256-XXX-XXXX', 'Tech Building, Room 205', 'technical', 'Technical support and IT services'],
                ['Finance Office', 'Mrs. Grace Williams', 'finance@bugema.ac.ug', '+256-XXX-XXXX', 'Administration Block, Room 10', 'financial', 'Fee payments and financial matters'],
                ['Library Services', 'Mr. Robert Davis', 'library@bugema.ac.ug', '+256-XXX-XXXX', 'Library Building', 'academic', 'Library resources and services'],
                ['Health Services', 'Dr. Mary Wilson', 'health@bugema.ac.ug', '+256-XXX-XXXX', 'Health Center', 'health', 'Medical services and health concerns'],
                ['Security Office', 'Mr. James Miller', 'security@bugema.ac.ug', '+256-XXX-XXXX', 'Main Gate Security Post', 'security', 'Campus security and safety'],
                ['Hostel Management', 'Mrs. Elizabeth Taylor', 'hostels@bugema.ac.ug', '+256-XXX-XXXX', 'Hostel Block A', 'accommodation', 'Accommodation and hostel issues']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO department_contacts (department_name, contact_person, email, phone, office_location, category, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($defaultDepartments as $dept) {
                $stmt->execute($dept);
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Table creation error: " . $e->getMessage());
        return false;
    }
}

// Ensure all system tables exist
ensureSystemTables($pdo);

// Get comprehensive statistics
$total_complaints = $pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
$pending_complaints = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status='pending'")->fetchColumn();
$progress_complaints = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status='progress'")->fetchColumn();
$resolved_complaints = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status='resolved'")->fetchColumn();

// Get complaints for management
$complaints_stmt = $pdo->query("
    SELECT c.*, s.name as student_name, s.email as student_email 
    FROM complaints c 
    LEFT JOIN students s ON c.student_id = s.id 
    ORDER BY c.created_at DESC
");
$all_complaints = $complaints_stmt->fetchAll(PDO::FETCH_ASSOC);

$total_users = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM students WHERE role='admin'")->fetchColumn();
$total_superadmins = $pdo->query("SELECT COUNT(*) FROM students WHERE role='superadmin'")->fetchColumn();
$total_students = $pdo->query("SELECT COUNT(*) FROM students WHERE role='student'")->fetchColumn();

$resolved_pct = $total_complaints > 0 ? round(($resolved_complaints / $total_complaints) * 100) : 0;

// Get system logs
try {
    $system_logs = $pdo->query("
        SELECT sl.*, s.name as user_name 
        FROM system_logs sl 
        LEFT JOIN students s ON sl.user_id = s.id 
        ORDER BY sl.created_at DESC LIMIT 50
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $system_logs = [];
}
/* ---------- RATINGS STATISTICS ---------- */
$ratings_stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_ratings,
        AVG(rating) as avg_rating,
        SUM(CASE WHEN would_recommend = 1 THEN 1 ELSE 0 END) as would_recommend_count,
        COUNT(DISTINCT student_id) as unique_raters
    FROM complaint_ratings
");
$ratings_stats = $ratings_stmt->fetch(PDO::FETCH_ASSOC);

$total_ratings = $ratings_stats['total_ratings'] ?? 0;
$avg_rating = round($ratings_stats['avg_rating'] ?? 0, 1);
$would_recommend_pct = $total_ratings > 0 ? round(($ratings_stats['would_recommend_count'] / $total_ratings) * 100) : 0;
$unique_raters = $ratings_stats['unique_raters'] ?? 0;

// Get recent ratings with student info
$recent_ratings_stmt = $pdo->query("
    SELECT cr.*, s.name as student_name, s.email as student_email, c.title as complaint_title
    FROM complaint_ratings cr
    LEFT JOIN students s ON cr.student_id = s.id
    LEFT JOIN complaints c ON cr.complaint_id = c.id
    ORDER BY cr.created_at DESC
    LIMIT 20
");
$recent_ratings = $recent_ratings_stmt->fetchAll(PDO::FETCH_ASSOC);
// Get all users for management
$users_stmt = $pdo->query("SELECT id, name, email, student_id, role, status, created_at FROM students ORDER BY created_at DESC");
$all_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all admins
$admins_stmt = $pdo->query("SELECT id, name, email, student_id, role, created_at FROM students WHERE role IN ('admin', 'superadmin') ORDER BY created_at DESC");
$all_admins = $admins_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get system settings
$settings = [];
try {
    $settings_stmt = $pdo->query("SELECT * FROM system_settings");
    $system_settings = $settings_stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($system_settings as $setting) {
        $settings[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (Exception $e) {
    $settings = [
        'platform_name' => 'CampusVoice',
        'maintenance_mode' => '0',
        'max_login_attempts' => '5',
        'session_timeout' => '30'
    ];
}

// Get department contacts
try {
    $dept_stmt = $pdo->query("SELECT * FROM department_contacts ORDER BY department_name");
    $department_contacts = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $department_contacts = [];
}

// Get database info
$db_size = $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb 
                        FROM information_schema.tables 
                        WHERE table_schema = '$db'")->fetchColumn();

$table_counts = $pdo->query("SELECT COUNT(*) as table_count 
                            FROM information_schema.tables 
                            WHERE table_schema = '$db'")->fetchColumn();

$superadminName = $_SESSION['student']['name'] ?? 'Super Admin';
$superadminEmail = $_SESSION['student']['email'] ?? 'superadmin@bugema.ac.ug';

// Log superadmin access
try {
    $log_stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $log_stmt->execute([
        $_SESSION['student']['id'],
        'superadmin_login',
        'Super Admin accessed dashboard',
        $_SERVER['REMOTE_ADDR']
    ]);
} catch (Exception $e) {
    // Log error silently
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CampusVoice - Super Admin Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      /* Dark blue themed palette for super admin */
      --primary: #1E3A8A;       /* deep blue */
      --primary-light: #3B82F6; /* brighter blue accent */
      --secondary: #10b981;
      --warning: #f59e0b;
      --danger: #dc2626;
      --info: #2563EB;          /* blue-600 */
      --super: #4F46E5;         /* indigo accent for super admin */
      --bg: #f8fafc;
      --card: #ffffff;
      --text: #0f172a;
      --text-light: #64748b;
      --border: #e2e8f0;
      --radius: 12px;
    }

    body.dark-mode {
      --bg: #0f172a;
      --card: #1e293b;
      --text: #e2e8f0;
      --text-light: #cbd5e1;
      --border: #334155;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    html, body {
      width: 100%;
      height: 100%;
      overflow-x: hidden;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text);
      display: flex;
      flex-direction: column;
      font-size: 14px;
      transition: background-color 0.3s, color 0.3s;
    }

    /* Header */
    header {
      background: linear-gradient(135deg, var(--super) 0%, var(--primary) 100%);
      color: white;
      padding: 12px 14px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 4px 12px rgba(139, 92, 246, 0.15);
      flex-shrink: 0;
    }

    .logo-section {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .logo-icon {
      width: 36px;
      height: 36px;
      background: white url('assets/bugemalogo.jpg') center/cover;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      font-weight: bold;
    }

    .logo-section h1 {
      font-size: 15px;
      font-weight: 700;
      margin: 0;
    }

    .nav-actions {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .nav-actions i {
      font-size: 18px;
      cursor: pointer;
      transition: all 0.2s;
      padding: 6px;
      border-radius: 6px;
    }

    .nav-actions i:hover {
      background: rgba(255, 255, 255, 0.2);
      transform: scale(1.05);
    }

    /* Main Layout */
    main {
      flex: 1;
      overflow-y: auto;
      overflow-x: hidden;
      padding: 12px 8px 100px; /* more bottom padding so tabs like Departments/Settings are fully visible */
      display: flex;
      flex-direction: column;
      width: 100%;
      gap: 0;
    }

    @media (min-width: 1024px) {
      main {
        flex-direction: row;
        gap: 20px;
        padding: 16px;
        max-width: 1600px;
        margin: 0 auto;
        align-items: flex-start;
      }
    }

    .main-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 12px;
      width: 100%;
    }

    @media (min-width: 1024px) {
      .main-content {
        padding: 0;
        min-width: 0;
      }
    }

    .page-title {
      font-size: 16px;
      font-weight: 700;
      color: var(--super);
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
      gap: 10px;
      margin-bottom: 14px;
    }

    .stat-card {
      background: var(--card);
      padding: 12px;
      border-radius: var(--radius);
      text-align: center;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
      transition: all 0.3s;
      border-left: 3px solid var(--primary);
    }

    .stat-card.super { border-left-color: var(--super); }
    .stat-card.pending { border-left-color: var(--warning); }
    .stat-card.progress { border-left-color: var(--info); }
    .stat-card.resolved { border-left-color: var(--secondary); }
    .stat-card.danger { border-left-color: var(--danger); }

    .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .stat-card i {
      font-size: 20px;
      margin-bottom: 6px;
      display: block;
    }

    .stat-card h3 {
      font-size: 18px;
      margin: 3px 0;
      font-weight: 700;
    }

    .stat-card p {
      font-size: 11px;
      color: var(--text-light);
    }

    .table-wrapper {
      overflow-x: auto;
      border-radius: var(--radius);
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
      margin-bottom: 14px;
      background: var(--card);
      -webkit-overflow-scrolling: touch;
    }

    .data-table {
      width: 100%;
      min-width: 600px;
      background: var(--card);
      border-collapse: collapse;
    }

    th, td {
      padding: 10px;
      text-align: left;
      font-size: 12px;
      white-space: nowrap;
    }

    th {
      background: var(--bg);
      font-weight: 600;
      color: var(--primary);
      border-bottom: 2px solid var(--border);
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    td {
      border-bottom: 1px solid var(--border);
      color: var(--text);
    }

    tr:hover {
      background: var(--bg);
    }

    .badge {
      padding: 3px 8px;
      border-radius: 16px;
      font-size: 10px;
      font-weight: 600;
      display: inline-block;
    }

    .badge.superadmin { background: #ede9fe; color: #6d28d9; }
    .badge.admin { background: #dbeafe; color: #1d4ed8; }
    .badge.student { background: #dcfce7; color: #166534; }
    .badge.pending { background: #fef3c7; color: #92400e; }
    .badge.progress { background: #e9d5ff; color: #6b21a8; }
    .badge.resolved { background: #dcfce7; color: #166534; }
    .badge.active { background: #dcfce7; color: #166534; }
    .badge.inactive { background: #fef3c7; color: #92400e; }
    .badge.suspended { background: #fee2e2; color: #991b1b; }
    .badge.low { background: #dbeafe; color: #1e40af; }
    .badge.medium { background: #fef3c7; color: #92400e; }
    .badge.high { background: #fee2e2; color: #991b1b; }

    .btn {
      padding: 5px 10px;
      border: none;
      border-radius: 6px;
      font-size: 11px;
      cursor: pointer;
      font-weight: 600;
      margin: 2px;
      transition: all 0.2s;
    }

    .btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .btn-primary { background: var(--primary); color: white; }
    .btn-success { background: var(--secondary); color: white; }
    .btn-warning { background: var(--warning); color: white; }
    .btn-danger { background: var(--danger); color: white; }
    .btn-super { background: var(--super); color: white; }
    .btn-info { background: var(--info); color: white; }

    .action-group {
      display: flex;
      gap: 3px;
      flex-wrap: wrap;
    }

    /* Sidebar */
    .sidebar {
      display: none;
    }

    @media (min-width: 1024px) {
      .sidebar {
        display: block;
        width: 320px;
        flex-shrink: 0;
        position: sticky;
        top: 80px;
        max-height: calc(100vh - 100px);
        overflow-y: auto;
      }
    }

    .sidebar-card {
      background: var(--card);
      border-radius: var(--radius);
      padding: 12px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
      margin-bottom: 12px;
      border: 1px solid var(--border);
    }

    .sidebar-title {
      font-size: 12px;
      font-weight: 700;
      margin: 0 0 10px;
      color: var(--super);
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .news-item {
      padding: 8px;
      border-radius: 8px;
      margin-bottom: 6px;
      background: var(--bg);
      border-left: 2px solid var(--super);
      cursor: pointer;
      transition: all 0.2s;
    }

    .news-item:hover {
      background: var(--card);
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
      transform: translateX(2px);
    }

    .news-item h5 {
      font-size: 11px;
      font-weight: 700;
      margin: 0 0 3px;
    }

    .news-item p {
      font-size: 10px;
      color: var(--text-light);
      margin: 0;
    }

    /* Modal */
    .modal {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(3px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      padding: 12px;
    }

    .modal.active { display: flex; }

    .modal-content {
      background: var(--card);
      padding: 16px;
      border-radius: var(--radius);
      width: 100%;
      max-width: 500px;
      max-height: 85vh;
      overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(40px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
      position: sticky;
      top: 0;
      background: var(--card);
      padding-bottom: 8px;
      border-bottom: 1px solid var(--border);
    }

    .modal-header h2 {
      margin: 0;
      font-size: 15px;
      color: var(--super);
    }

    .close-btn {
      background: none;
      border: none;
      font-size: 20px;
      cursor: pointer;
      color: var(--text-light);
      transition: all 0.2s;
    }

    .close-btn:hover { color: var(--text); }

    input, textarea, select {
      width: 100%;
      padding: 9px;
      margin: 6px 0;
      border-radius: 8px;
      border: 1px solid var(--border);
      font-size: 13px;
      background: var(--bg);
      color: var(--text);
      font-family: inherit;
      transition: all 0.2s;
    }

    input:focus, textarea:focus, select:focus {
      outline: none;
      border-color: var(--super);
      background: var(--card);
      box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.1);
    }

    label {
      display: block;
      margin-top: 8px;
      font-weight: 600;
      font-size: 12px;
      color: var(--super);
    }

    textarea {
      min-height: 80px;
      resize: vertical;
    }

    .form-group {
      margin-bottom: 10px;
    }

    .bottom-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: var(--card);
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: space-around;
      padding: 8px 0;
      box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.06);
      z-index: 99;
      flex-shrink: 0;
    }

    /* Small-screen responsiveness tweaks */
    @media (max-width: 640px) {
      .data-table {
        min-width: 100%;
      }

      th, td {
        white-space: normal;
        font-size: 11px;
        padding: 8px 6px;
      }

      .stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      main {
        padding: 10px 6px 80px;
      }
    }

    .nav-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 3px;
      font-size: 10px;
      color: var(--text-light);
      cursor: pointer;
      padding: 6px 10px;
      border-radius: 8px;
      transition: all 0.2s;
      flex: 1;
      text-decoration: none;
    }

    .nav-item i {
      font-size: 18px;
    }

    .nav-item.active {
      color: var(--super);
      background: rgba(139, 92, 246, 0.08);
    }

    .tab-content {
      display: none;
      animation: fadeIn 0.3s ease;
      width: 100%;
    }

    .tab-content.active { 
      display: block; 
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .profile-section {
      text-align: center;
      padding: 12px 0 10px;
      border-bottom: 1px solid var(--border);
      margin-bottom: 12px;
    }

    .profile-avatar {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--super), var(--primary));
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 800;
      font-size: 24px;
      margin: 0 auto 8px;
      box-shadow: 0 2px 8px rgba(139, 92, 246, 0.2);
    }

    .profile-section h2 {
      font-size: 14px;
      font-weight: 700;
      margin: 0 0 2px;
    }

    .profile-section p {
      font-size: 11px;
      color: var(--text-light);
      margin: 0;
    }

    .stats-paragraph {
      background: var(--card);
      padding: 12px;
      border-radius: var(--radius);
      margin-bottom: 12px;
      border-left: 4px solid var(--super);
      font-size: 12px;
      line-height: 1.4;
    }

    .stats-paragraph strong {
      color: var(--super);
    }

    /* System Health */
    .system-health {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 10px;
      margin-bottom: 14px;
    }

    .health-card {
      background: var(--card);
      padding: 12px;
      border-radius: var(--radius);
      text-align: center;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    .health-card.good { border-left: 3px solid var(--secondary); }
    .health-card.warning { border-left: 3px solid var(--warning); }
    .health-card.danger { border-left: 3px solid var(--danger); }

    /* Settings Grid */
    .settings-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 12px;
      margin-bottom: 14px;
    }

    .setting-card {
      background: var(--card);
      padding: 12px;
      border-radius: var(--radius);
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
      border-left: 3px solid var(--super);
    }

    .setting-card h4 {
      font-size: 12px;
      margin-bottom: 8px;
      color: var(--super);
    }

    .setting-card p {
      font-size: 11px;
      color: var(--text-light);
      margin-bottom: 8px;
    }

    /* Department Contacts Grid */
    .dept-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 12px;
      margin-bottom: 14px;
    }

    .dept-card {
      background: var(--card);
      border-radius: var(--radius);
      padding: 15px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
      border-left: 4px solid var(--super);
      transition: all 0.3s ease;
    }

    .dept-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    .dept-header {
      display: flex;
      justify-content: between;
      align-items: flex-start;
      margin-bottom: 10px;
    }

    .dept-name {
      font-size: 14px;
      font-weight: 700;
      color: var(--super);
      margin: 0;
      flex: 1;
    }

    .dept-status {
      font-size: 10px;
      padding: 3px 8px;
      border-radius: 12px;
      font-weight: 600;
    }

    .dept-status.active {
      background: var(--secondary);
      color: white;
    }

    .dept-status.inactive {
      background: var(--warning);
      color: white;
    }

    .dept-contact-person {
      font-size: 12px;
      color: var(--text);
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .dept-email {
      font-size: 11px;
      color: var(--primary);
      margin-bottom: 5px;
      display: flex;
      align-items: center;
      gap: 5px;
      word-break: break-all;
    }

    .dept-phone {
      font-size: 11px;
      color: var(--text-light);
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .dept-location {
      font-size: 11px;
      color: var(--text-light);
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .dept-description {
      font-size: 11px;
      color: var(--text-light);
      line-height: 1.4;
      margin-bottom: 10px;
    }

    .dept-category {
      display: inline-block;
      padding: 2px 8px;
      background: var(--bg);
      color: var(--text);
      border-radius: 10px;
      font-size: 10px;
      margin-bottom: 10px;
    }

    .dept-actions {
      display: flex;
      gap: 5px;
      justify-content: flex-end;
    }

    .toast-message {
      position: fixed;
      bottom: 80px;
      left: 50%;
      transform: translateX(-50%);
      background: var(--success);
      color: white;
      padding: 12px 20px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 13px;
      z-index: 9999;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
      animation: slideUp 0.3s ease;
    }
  </style>
</head>

<body>

  <!-- Header -->
  <header>
    <div class="logo-section">
      <div class="logo-icon"></div>
      <h1>CampusVoice Super Admin</h1>
    </div>
    <div class="nav-actions">
      <i class="bi bi-shield-check" title="Super Admin"></i>
      <i class="bi bi-bell" id="notifyBtn" title="Notifications"></i>
      <i class="bi bi-moon" id="themeToggle" title="Toggle Theme"></i>
      <i class="bi bi-person" id="profileBtn" title="Profile"></i>
      <i class="bi bi-box-arrow-right" id="logoutBtn" title="Logout"></i>
    </div>
  </header>

  <!-- Main Content -->
  <main>
    <div class="main-content">

      <!-- DASHBOARD TAB -->
      <div class="tab-content active" id="tab-dashboard">
        <h2 class="page-title">🏠 Super Admin Dashboard</h2>

        <!-- Stats Paragraph -->
        <div class="stats-paragraph">
          <strong>🚀 System Overview:</strong> Welcome to the Super Admin Dashboard. You have full control over the entire CampusVoice platform. 
          Currently monitoring <strong><?php echo $total_users; ?> total users</strong> across <strong><?php echo $table_counts; ?> database tables</strong>. 
          System is running optimally with <strong><?php echo $db_size; ?>MB</strong> database usage.
        </div>

        <!-- System Health -->
        <div class="system-health">
          <div class="health-card good">
            <i class="bi bi-database" style="color: var(--secondary);"></i>
            <h3><?php echo $db_size; ?> MB</h3>
            <p>Database Size</p>
          </div>
          <div class="health-card good">
            <i class="bi bi-table" style="color: var(--secondary);"></i>
            <h3><?php echo $table_counts; ?></h3>
            <p>Total Tables</p>
          </div>
          <div class="health-card good">
            <i class="bi bi-check-circle" style="color: var(--secondary);"></i>
            <h3>Online</h3>
            <p>System Status</p>
          </div>
          <div class="health-card good">
            <i class="bi bi-lightning" style="color: var(--secondary);"></i>
            <h3>100%</h3>
            <p>Uptime</p>
          </div>
        </div>

        <div class="stats">
          <div class="stat-card super">
            <i class="bi bi-shield-check" style="color: var(--super);"></i>
            <h3><?php echo $total_superadmins; ?></h3>
            <p>Super Admins</p>
          </div>
          <div class="stat-card">
            <i class="bi bi-person-gear" style="color: var(--primary);"></i>
            <h3><?php echo $total_admins; ?></h3>
            <p>Admins</p>
          </div>
          <div class="stat-card">
            <i class="bi bi-people" style="color: var(--info);"></i>
            <h3><?php echo $total_students; ?></h3>
            <p>Students</p>
          </div>
          <div class="stat-card">
            <i class="bi bi-chat-square-text" style="color: var(--warning);"></i>
            <h3><?php echo $total_complaints; ?></h3>
            <p>Total Complaints</p>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="table-wrapper">
          <div style="padding: 15px;">
            <h3 style="margin-bottom: 15px; color: var(--super);">⚡ Quick Actions</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
              <button class="btn btn-super" onclick="openModal('createAdminModal')">
                <i class="bi bi-person-plus"></i> Create User
              </button>
              <button class="btn btn-primary" onclick="openModal('systemSettingsModal')">
                <i class="bi bi-gear"></i> System Settings
              </button>
              <button class="btn btn-warning" onclick="openModal('backupModal')">
                <i class="bi bi-download"></i> Backup Data
              </button>
              <button class="btn btn-info" onclick="openModal('reportsModal')">
                <i class="bi bi-file-earmark-bar-graph"></i> Generate Reports
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- COMPLAINTS TAB -->
      <div class="tab-content" id="tab-complaints">
        <h2 class="page-title">📋 Complaints Management</h2>

        <!-- Stats Paragraph -->
        <div class="stats-paragraph">
          <strong>📊 Quick Overview:</strong> You have <strong><?php echo $total_complaints; ?> total complaints</strong> with 
          <strong><?php echo $resolved_complaints; ?> resolved</strong> (<?php echo $resolved_pct; ?>% success rate). 
          Currently <strong><?php echo $pending_complaints; ?> pending</strong> and <strong><?php echo $progress_complaints; ?> in progress</strong>.
        </div>

        <div class="stats">
          <div class="stat-card">
            <i class="bi bi-list-check" style="color: var(--primary);"></i>
            <h3><?php echo $total_complaints; ?></h3>
            <p>Total</p>
          </div>
          <div class="stat-card pending">
            <i class="bi bi-hourglass-split" style="color: var(--warning);"></i>
            <h3><?php echo $pending_complaints; ?></h3>
            <p>Pending</p>
          </div>
          <div class="stat-card progress">
            <i class="bi bi-arrow-repeat" style="color: var(--info);"></i>
            <h3><?php echo $progress_complaints; ?></h3>
            <p>In Progress</p>
          </div>
          <div class="stat-card resolved">
            <i class="bi bi-check-circle-fill" style="color: var(--secondary);"></i>
            <h3><?php echo $resolved_complaints; ?></h3>
            <p>Resolved</p>
          </div>
        </div>

        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Title</th>
                <th>Student</th>
                <th>Category</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($all_complaints as $c): ?>
              <tr>
                <td><?php echo htmlspecialchars(substr($c['title'], 0, 20)); ?></td>
                <td><?php echo htmlspecialchars(substr($c['student_name'] ?? 'Unknown', 0, 15)); ?></td>
                <td><?php echo htmlspecialchars($c['category'] ?? 'General'); ?></td>
                <td>
                  <span class="badge <?php echo $c['priority'] ?? 'medium'; ?>">
                    <?php echo ucfirst($c['priority'] ?? 'medium'); ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?php echo $c['status']; ?>">
                    <?php echo ucfirst($c['status']); ?>
                  </span>
                </td>
                <td><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                <td class="action-group">
                  <button class="btn btn-primary" onclick="editComplaint(<?php echo $c['id']; ?>)" title="Edit">✏️</button>
                  <button class="btn btn-warning" onclick="setProgress(<?php echo $c['id']; ?>)" title="In Progress">⏳</button>
                  <button class="btn btn-success" onclick="resolveComplaint(<?php echo $c['id']; ?>)" title="Resolve">✓</button>
                  <button class="btn btn-danger" onclick="deleteComplaint(<?php echo $c['id']; ?>)" title="Delete">🗑️</button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- USER MANAGEMENT TAB -->
      <div class="tab-content" id="tab-users">
        <h2 class="page-title">👥 User Management</h2>
        
        <div class="stats">
          <div class="stat-card super">
            <i class="bi bi-people" style="color: var(--super);"></i>
            <h3><?php echo $total_users; ?></h3>
            <p>Total Users</p>
          </div>
          <div class="stat-card">
            <i class="bi bi-person-check" style="color: var(--secondary);"></i>
            <h3><?php echo array_reduce($all_users, function($carry, $user) { 
              return $carry + ($user['status'] === 'active' ? 1 : 0); 
            }, 0); ?></h3>
            <p>Active Users</p>
          </div>
          <div class="stat-card warning">
            <i class="bi bi-person-x" style="color: var(--warning);"></i>
            <h3><?php echo array_reduce($all_users, function($carry, $user) { 
              return $carry + ($user['status'] === 'suspended' ? 1 : 0); 
            }, 0); ?></h3>
            <p>Suspended</p>
          </div>
        </div>

        <input type="text" placeholder="🔍 Search users..." id="searchUsers" style="margin-bottom: 10px;">

        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Student ID</th>
                <th>Role</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="usersTable">
              <?php foreach ($all_users as $u): ?>
              <tr class="user-row">
                <td><?php echo htmlspecialchars(substr($u['name'], 0, 15)); ?></td>
                <td><?php echo htmlspecialchars(substr($u['email'], 0, 18)); ?></td>
                <td><?php echo htmlspecialchars($u['student_id'] ?? 'N/A'); ?></td>
                <td>
                  <span class="badge <?php echo $u['role']; ?>">
                    <?php echo ucfirst($u['role']); ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?php echo $u['status'] ?? 'active'; ?>">
                    <?php echo ucfirst($u['status'] ?? 'active'); ?>
                  </span>
                </td>
                <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                <td class="action-group">
                  <button class="btn btn-primary" onclick="editUser(<?php echo $u['id']; ?>)">✏️</button>
                  <button class="btn btn-warning" onclick="suspendUser(<?php echo $u['id']; ?>)">⏸️</button>
                  <button class="btn btn-danger" onclick="deleteUser(<?php echo $u['id']; ?>)">🗑️</button>
                  <?php if ($u['role'] !== 'superadmin'): ?>
                  <button class="btn btn-super" onclick="promoteToAdmin(<?php echo $u['id']; ?>)">⬆️</button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ADMIN MANAGEMENT TAB -->
      <div class="tab-content" id="tab-admins">
        <h2 class="page-title">🛡️ Admin Management</h2>

        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Student ID</th>
                <th>Role</th>
                <th>Joined</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($all_admins as $admin): ?>
              <tr>
                <td><?php echo htmlspecialchars(substr($admin['name'], 0, 15)); ?></td>
                <td><?php echo htmlspecialchars(substr($admin['email'], 0, 18)); ?></td>
                <td><?php echo htmlspecialchars($admin['student_id'] ?? 'N/A'); ?></td>
                <td>
                  <span class="badge <?php echo $admin['role']; ?>">
                    <?php echo ucfirst($admin['role']); ?>
                  </span>
                </td>
                <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                <td class="action-group">
                  <?php if ($admin['role'] !== 'superadmin'): ?>
                  <button class="btn btn-warning" onclick="demoteAdmin(<?php echo $admin['id']; ?>)">⬇️</button>
                  <button class="btn btn-danger" onclick="deleteUser(<?php echo $admin['id']; ?>)">🗑️</button>
                  <?php else: ?>
                  <span class="badge super">Protected</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- DEPARTMENT CONTACTS TAB -->
      <div class="tab-content" id="tab-departments">
        <h2 class="page-title">🏢 Department Contacts</h2>

        <div class="stats-paragraph">
          <strong>📞 Contact Management:</strong> Manage department contact information for complaint routing. 
          Currently <strong><?php echo count($department_contacts); ?> departments</strong> configured. 
          Admins can use these contacts to reach relevant departments when complaints are submitted.
        </div>

        <div style="margin-bottom: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
          <button class="btn btn-super" onclick="openModal('addDeptModal')">
            <i class="bi bi-plus-circle"></i> Add Department
          </button>
          <button class="btn btn-primary" onclick="exportDepartmentContacts()">
            <i class="bi bi-download"></i> Export Contacts
          </button>
          <input type="text" placeholder="🔍 Search departments..." id="searchDepts" style="flex: 1; min-width: 200px;">
        </div>

        <div class="dept-grid" id="departmentsGrid">
          <?php foreach ($department_contacts as $dept): ?>
          <div class="dept-card" data-dept-name="<?php echo htmlspecialchars(strtolower($dept['department_name'])); ?>">
            <div class="dept-header">
              <h3 class="dept-name"><?php echo htmlspecialchars($dept['department_name']); ?></h3>
              <span class="dept-status <?php echo $dept['status']; ?>">
                <?php echo ucfirst($dept['status']); ?>
              </span>
            </div>
            
            <?php if (!empty($dept['contact_person'])): ?>
            <div class="dept-contact-person">
              <i class="bi bi-person"></i>
              <?php echo htmlspecialchars($dept['contact_person']); ?>
            </div>
            <?php endif; ?>
            
            <div class="dept-email">
              <i class="bi bi-envelope"></i>
              <a href="mailto:<?php echo htmlspecialchars($dept['email']); ?>" style="color: inherit;">
                <?php echo htmlspecialchars($dept['email']); ?>
              </a>
            </div>
            
            <?php if (!empty($dept['phone'])): ?>
            <div class="dept-phone">
              <i class="bi bi-telephone"></i>
              <?php echo htmlspecialchars($dept['phone']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($dept['office_location'])): ?>
            <div class="dept-location">
              <i class="bi bi-geo-alt"></i>
              <?php echo htmlspecialchars($dept['office_location']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($dept['category'])): ?>
            <div class="dept-category">
              <?php echo htmlspecialchars(ucfirst($dept['category'])); ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($dept['description'])): ?>
            <div class="dept-description">
              <?php echo htmlspecialchars($dept['description']); ?>
            </div>
            <?php endif; ?>
            
            <div class="dept-actions">
              <button class="btn btn-primary" onclick="editDepartment(<?php echo $dept['id']; ?>)" title="Edit">
                <i class="bi bi-pencil"></i>
              </button>
              <button class="btn btn-warning" onclick="toggleDepartmentStatus(<?php echo $dept['id']; ?>, '<?php echo $dept['status']; ?>')" title="Toggle Status">
                <i class="bi bi-power"></i>
              </button>
              <button class="btn btn-danger" onclick="deleteDepartment(<?php echo $dept['id']; ?>)" title="Delete">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <?php if (empty($department_contacts)): ?>
        <div class="table-wrapper" style="text-align: center; padding: 40px;">
          <i class="bi bi-building" style="font-size: 48px; color: var(--text-light); margin-bottom: 15px;"></i>
          <h3 style="color: var(--text-light); margin-bottom: 10px;">No Departments Configured</h3>
          <p style="color: var(--text-light); margin-bottom: 20px;">Add department contacts to enable complaint routing</p>
          <button class="btn btn-super" onclick="openModal('addDeptModal')">
            <i class="bi bi-plus-circle"></i> Add First Department
          </button>
        </div>
        <?php endif; ?>
      </div>

      <!-- SYSTEM LOGS TAB -->
      <div class="tab-content" id="tab-logs">
        <h2 class="page-title">📊 System Logs</h2>

        <div style="margin-bottom: 10px;">
          <button class="btn btn-danger" onclick="deleteLogs()">
            <i class="bi bi-trash"></i> Delete All Logs
          </button>
        </div>

        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Time</th>
                <th>User</th>
                <th>Action</th>
                <th>Details</th>
                <th>IP Address</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($system_logs as $log): ?>
              <tr>
                <td><?php echo date('M d h:i', strtotime($log['created_at'])); ?></td>
                <td><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></td>
                <td><span class="badge progress"><?php echo htmlspecialchars($log['action']); ?></span></td>
                <td><?php echo htmlspecialchars(substr($log['details'] ?? '', 0, 30)); ?></td>
                <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                <td>
                  <button class="btn btn-danger btn-sm" onclick="deleteLogs(<?php echo $log['id']; ?>)">🗑️</button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- SYSTEM SETTINGS TAB -->
      <div class="tab-content" id="tab-settings">
        <h2 class="page-title">⚙️ System Settings</h2>

        <div class="settings-grid">
          <div class="setting-card">
            <h4>🛡️ Security Settings</h4>
            <p>Configure password policies, session timeout, and security measures</p>
            <button class="btn btn-primary" onclick="openModal('securitySettingsModal')">Configure</button>
          </div>
          <div class="setting-card">
            <h4>📧 Email Settings</h4>
            <p>Setup SMTP, email templates, and notification preferences</p>
            <button class="btn btn-primary" onclick="openModal('emailSettingsModal')">Configure</button>
          </div>
          <div class="setting-card">
            <h4>🎨 Appearance</h4>
            <p>Customize platform colors, logos, and interface settings</p>
            <button class="btn btn-primary" onclick="openModal('appearanceSettingsModal')">Configure</button>
          </div>
          <div class="setting-card">
            <h4>📊 Analytics</h4>
            <p>Configure reporting, data retention, and analytics settings</p>
            <button class="btn btn-primary" onclick="openModal('analyticsSettingsModal')">Configure</button>
          </div>
        </div>

        <!-- Current Settings Display -->
        <div class="table-wrapper">
          <div style="padding: 15px;">
            <h3 style="margin-bottom: 15px; color: var(--super);">📋 Current System Settings</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
              <?php foreach ($settings as $key => $value): ?>
              <div style="background: var(--bg); padding: 10px; border-radius: 6px;">
                <strong style="font-size: 11px; color: var(--text-light);"><?php echo htmlspecialchars($key); ?></strong>
                <p style="font-size: 12px; margin: 5px 0 0; word-break: break-all;"><?php echo htmlspecialchars($value); ?></p>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Sidebar -->
    <div class="sidebar">
      <div class="sidebar-card">
        <div class="sidebar-title">
          <i class="bi bi-graph-up"></i> System Performance
        </div>
        <div class="news-item">
          <h5>Database Performance</h5>
          <p>Optimal - <?php echo $db_size; ?>MB used</p>
        </div>
        <div class="news-item">
          <h5>User Activity</h5>
          <p><?php echo $total_users; ?> active users</p>
        </div>
        <div class="news-item">
          <h5>Complaint Resolution</h5>
          <p><?php echo $resolved_pct; ?>% success rate</p>
        </div>
      </div>

      <div class="sidebar-card">
        <div class="sidebar-title">
          <i class="bi bi-shield-check"></i> Security Overview
        </div>
        <div class="news-item">
          <h5>Last Backup</h5>
          <p>Today, 02:00 AM</p>
        </div>
        <div class="news-item">
          <h5>Security Scan</h5>
          <p>No threats detected</p>
        </div>
        <div class="news-item">
          <h5>System Updates</h5>
          <p>All systems up to date</p>
        </div>
      </div>

      <div class="sidebar-card">
        <div class="sidebar-title">
          <i class="bi bi-activity"></i> Recent Activity
        </div>
        <?php foreach (array_slice($system_logs, 0, 5) as $log): ?>
        <div class="news-item">
          <h5><?php echo htmlspecialchars($log['action']); ?></h5>
          <p><?php echo date('h:i A', strtotime($log['created_at'])); ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </main>
<!-- RATINGS TAB -->
<div class="tab-content" id="tab-ratings">
    <h2 class="page-title">⭐ Complaint Ratings</h2>

    <!-- Ratings Overview -->
    <div class="stats-paragraph">
        <strong>📊 Ratings Overview:</strong> 
        You have received <strong><?php echo $total_ratings; ?> ratings</strong> with an average of 
        <strong><?php echo $avg_rating; ?>/5 stars</strong>. 
        <strong><?php echo $would_recommend_pct; ?>%</strong> of users would recommend CampusVoice.
        <strong><?php echo $unique_raters; ?> unique users</strong> have provided feedback.
    </div>

    <!-- Rating Stats -->
    <div class="stats">
        <div class="stat-card">
            <i class="bi bi-star-fill" style="color: #fbbf24;"></i>
            <h3><?php echo $avg_rating; ?>/5</h3>
            <p>Average Rating</p>
        </div>
        <div class="stat-card">
            <i class="bi bi-chat-square-text-fill" style="color: var(--secondary);"></i>
            <h3><?php echo $total_ratings; ?></h3>
            <p>Total Ratings</p>
        </div>
        <div class="stat-card">
            <i class="bi bi-hand-thumbs-up-fill" style="color: var(--info);"></i>
            <h3><?php echo $would_recommend_pct; ?>%</h3>
            <p>Would Recommend</p>
        </div>
        <div class="stat-card">
            <i class="bi bi-people-fill" style="color: var(--super);"></i>
            <h3><?php echo $unique_raters; ?></h3>
            <p>Unique Raters</p>
        </div>
    </div>

    <!-- Send Thank You Button -->
    <div style="margin-bottom: 15px; display: flex; gap: 10px;">
        <button class="btn btn-success" onclick="sendThankYouToAll()">
            <i class="bi bi-envelope-heart"></i> Send Thank You to All Raters
        </button>
        <button class="btn btn-info" onclick="exportRatings()">
            <i class="bi bi-download"></i> Export Ratings
        </button>
    </div>

    <!-- Ratings Table -->
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Complaint</th>
                    <th>Rating</th>
                    <th>Response Time</th>
                    <th>Resolution Quality</th>
                    <th>Would Recommend</th>
                    <th>Feedback</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_ratings as $rating): ?>
                <tr>
                    <td>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($rating['student_name']); ?></div>
                        <div style="font-size: 11px; color: var(--text-light);"><?php echo htmlspecialchars($rating['student_email']); ?></div>
                    </td>
                    <td><?php echo htmlspecialchars(substr($rating['complaint_title'] ?? 'N/A', 0, 25)); ?></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 2px;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star<?php echo $i <= $rating['rating'] ? '-fill' : ''; ?>" 
                                   style="color: <?php echo $i <= $rating['rating'] ? '#fbbf24' : '#cbd5e1'; ?>"></i>
                            <?php endfor; ?>
                            <span style="margin-left: 5px; font-weight: 600;"><?php echo $rating['rating']; ?>/5</span>
                        </div>
                    </td>
                    <td>
                        <span class="badge <?php echo $rating['response_time']; ?>">
                            <?php echo ucfirst($rating['response_time']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?php echo $rating['resolution_quality']; ?>">
                            <?php echo ucfirst($rating['resolution_quality']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($rating['would_recommend']): ?>
                            <span class="badge active" style="background: #dcfce7; color: #166534;">
                                <i class="bi bi-check-circle"></i> Yes
                            </span>
                        <?php else: ?>
                            <span class="badge inactive" style="background: #fee2e2; color: #991b1b;">
                                <i class="bi bi-x-circle"></i> No
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($rating['feedback'])): ?>
                            <span title="<?php echo htmlspecialchars($rating['feedback']); ?>">
                                <?php echo htmlspecialchars(substr($rating['feedback'], 0, 30)); ?>
                                <?php if (strlen($rating['feedback']) > 30): ?>...<?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span style="color: var(--text-light);">No feedback</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($rating['created_at'])); ?></td>
                    <td class="action-group">
                        <button class="btn btn-success" onclick="sendThankYou(<?php echo $rating['id']; ?>, '<?php echo addslashes($rating['student_email']); ?>')">
                            <i class="bi bi-envelope-heart"></i> Thank
                        </button>
                        <button class="btn btn-primary" onclick="viewRatingDetails(<?php echo $rating['id']; ?>)">
                            <i class="bi bi-eye"></i> View
                        </button>
                        <button class="btn btn-danger" onclick="deleteRating(<?php echo $rating['id']; ?>)">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($recent_ratings)): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 30px; color: var(--text-light);">
                        <i class="bi bi-star" style="font-size: 48px; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                        No ratings received yet. Encourage users to rate their resolved complaints.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Rating Distribution Chart -->
    <div class="sidebar-card" style="margin-top: 15px;">
        <div class="sidebar-title">
            <i class="bi bi-bar-chart-fill"></i> Rating Distribution
        </div>
        <div id="ratingDistribution" style="padding: 10px;">
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>5 Stars</span>
                    <span id="star5Count">0</span>
                </div>
                <div style="background: #e2e8f0; border-radius: 5px; height: 8px;">
                    <div id="star5Bar" style="background: #fbbf24; height: 100%; border-radius: 5px; width: 0%;"></div>
                </div>
            </div>
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>4 Stars</span>
                    <span id="star4Count">0</span>
                </div>
                <div style="background: #e2e8f0; border-radius: 5px; height: 8px;">
                    <div id="star4Bar" style="background: #a3a3a3; height: 100%; border-radius: 5px; width: 0%;"></div>
                </div>
            </div>
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>3 Stars</span>
                    <span id="star3Count">0</span>
                </div>
                <div style="background: #e2e8f0; border-radius: 5px; height: 8px;">
                    <div id="star3Bar" style="background: #a3a3a3; height: 100%; border-radius: 5px; width: 0%;"></div>
                </div>
            </div>
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>2 Stars</span>
                    <span id="star2Count">0</span>
                </div>
                <div style="background: #e2e8f0; border-radius: 5px; height: 8px;">
                    <div id="star2Bar" style="background: #a3a3a3; height: 100%; border-radius: 5px; width: 0%;"></div>
                </div>
            </div>
            <div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>1 Star</span>
                    <span id="star1Count">0</span>
                </div>
                <div style="background: #e2e8f0; border-radius: 5px; height: 8px;">
                    <div id="star1Bar" style="background: #a3a3a3; height: 100%; border-radius: 5px; width: 0%;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
  <!-- BOTTOM NAV -->
  <div class="bottom-nav">
      <a class="nav-item active" data-tab="tab-dashboard">
          <i class="bi bi-house"></i>
          <span>Dashboard</span>
      </a>
      <a class="nav-item" data-tab="tab-complaints">
          <i class="bi bi-chat-square-text"></i>
          <span>Complaints</span>
      </a>
      <a class="nav-item" data-tab="tab-users">
          <i class="bi bi-people"></i>
          <span>Users</span>
      </a>
      <a class="nav-item" data-tab="tab-admins">
          <i class="bi bi-shield-check"></i>
          <span>Admins</span>
      </a>
      <a class="nav-item" data-tab="tab-departments">
          <i class="bi bi-building"></i>
          <span>Departments</span>
      </a>
      <a class="nav-item" data-tab="tab-logs">
          <i class="bi bi-journal-text"></i>
          <span>Logs</span>
      </a>
      <a class="nav-item" data-tab="tab-settings">
          <i class="bi bi-gear"></i>
          <span>Settings</span>
      </a>
      <a class="nav-item" data-tab="tab-ratings">
    <i class="bi bi-star-fill"></i>
    <span>Ratings</span>
</a>
  </div>

  <!-- MODALS -->
  <!-- Create User Modal -->
  <div class="modal" id="createAdminModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>👤 Create New User</h2>
        <button class="close-btn" onclick="closeModal('createAdminModal')">✕</button>
      </div>
      <form id="createAdminForm">
        <div class="form-group">
          <label>Full Name *</label>
          <input type="text" id="admin_name" required>
        </div>
        <div class="form-group">
          <label>Email *</label>
          <input type="email" id="admin_email" required>
        </div>
        <div class="form-group">
          <label>User Type *</label>
          <select id="user_type" onchange="toggleIdField()">
            <option value="student">Student</option>
            <option value="staff">Staff Member</option>
            <option value="admin">Administrator</option>
            <option value="superadmin">Super Administrator</option>
          </select>
        </div>
        <div class="form-group" id="studentIdGroup">
          <label>Student ID</label>
          <input type="text" id="admin_student_id" placeholder="Enter student ID">
        </div>
        <div class="form-group" id="staffIdGroup" style="display: none;">
          <label>Staff ID</label>
          <input type="text" id="admin_staff_id" placeholder="Enter staff ID">
        </div>
        <div class="form-group">
          <label>Initial Password *</label>
          <input type="password" id="admin_password" required minlength="6" placeholder="At least 6 characters">
        </div>
        <button type="submit" class="btn btn-super" style="width: 100%;">Create User</button>
      </form>
    </div>
  </div>

  <!-- System Settings Modal -->
  <div class="modal" id="systemSettingsModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>⚙️ System Settings</h2>
        <button class="close-btn" onclick="closeModal('systemSettingsModal')">✕</button>
      </div>
      <form id="systemSettingsForm">
        <div class="form-group">
          <label>Platform Name</label>
          <input type="text" id="platform_name" value="<?php echo $settings['platform_name'] ?? 'CampusVoice'; ?>">
        </div>
        <div class="form-group">
          <label>Maintenance Mode</label>
          <select id="maintenance_mode">
            <option value="0" <?php echo ($settings['maintenance_mode'] ?? '0') === '0' ? 'selected' : ''; ?>>Disabled</option>
            <option value="1" <?php echo ($settings['maintenance_mode'] ?? '0') === '1' ? 'selected' : ''; ?>>Enabled</option>
          </select>
        </div>
        <div class="form-group">
          <label>Max Login Attempts</label>
          <input type="number" id="max_login_attempts" value="<?php echo $settings['max_login_attempts'] ?? '5'; ?>">
        </div>
        <div class="form-group">
          <label>Session Timeout (minutes)</label>
          <input type="number" id="session_timeout" value="<?php echo $settings['session_timeout'] ?? '30'; ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Save Settings</button>
      </form>
    </div>
  </div>

  <!-- Security Settings Modal -->
  <div class="modal" id="securitySettingsModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>🛡️ Security Settings</h2>
        <button class="close-btn" onclick="closeModal('securitySettingsModal')">✕</button>
      </div>
      <form id="securitySettingsForm">
        <div class="form-group">
          <label>Max Login Attempts</label>
          <input type="number" id="security_max_attempts" value="<?php echo $settings['max_login_attempts'] ?? '5'; ?>" min="3" max="10">
        </div>
        <div class="form-group">
          <label>Session Timeout (minutes)</label>
          <input type="number" id="security_timeout" value="<?php echo $settings['session_timeout'] ?? '30'; ?>" min="15" max="240">
        </div>
        <div class="form-group">
          <label>Password Policy</label>
          <select id="password_policy">
            <option value="weak" <?php echo ($settings['password_policy'] ?? 'medium') === 'weak' ? 'selected' : ''; ?>>Weak (6+ characters)</option>
            <option value="medium" <?php echo ($settings['password_policy'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>Medium (8+ characters with mix)</option>
            <option value="strong" <?php echo ($settings['password_policy'] ?? 'medium') === 'strong' ? 'selected' : ''; ?>>Strong (10+ characters with special chars)</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Save Security Settings</button>
      </form>
    </div>
  </div>

  <!-- Email Settings Modal -->
  <div class="modal" id="emailSettingsModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>📧 Email Settings</h2>
        <button class="close-btn" onclick="closeModal('emailSettingsModal')">✕</button>
      </div>
      <form id="emailSettingsForm">
        <div class="form-group">
          <label>SMTP Host</label>
          <input type="text" id="smtp_host" value="<?php echo $settings['smtp_host'] ?? ''; ?>" placeholder="smtp.gmail.com">
        </div>
        <div class="form-group">
          <label>SMTP Port</label>
          <input type="number" id="smtp_port" value="<?php echo $settings['smtp_port'] ?? '587'; ?>" placeholder="587">
        </div>
        <div class="form-group">
          <label>SMTP Username</label>
          <input type="email" id="smtp_username" value="<?php echo $settings['smtp_username'] ?? ''; ?>" placeholder="your-email@gmail.com">
        </div>
        <div class="form-group">
          <label>SMTP Password</label>
          <input type="password" id="smtp_password" placeholder="Enter SMTP password">
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Save Email Settings</button>
      </form>
    </div>
  </div>

  <!-- Appearance Settings Modal -->
  <div class="modal" id="appearanceSettingsModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>🎨 Appearance Settings</h2>
        <button class="close-btn" onclick="closeModal('appearanceSettingsModal')">✕</button>
      </div>
      <form id="appearanceSettingsForm">
        <div class="form-group">
          <label>Platform Name</label>
          <input type="text" id="appearance_platform_name" value="<?php echo $settings['platform_name'] ?? 'CampusVoice'; ?>">
        </div>
        <div class="form-group">
          <label>Default Theme</label>
          <select id="default_theme">
            <option value="light" <?php echo ($settings['default_theme'] ?? 'light') === 'light' ? 'selected' : ''; ?>>Light Mode</option>
            <option value="dark" <?php echo ($settings['default_theme'] ?? 'light') === 'dark' ? 'selected' : ''; ?>>Dark Mode</option>
            <option value="auto" <?php echo ($settings['default_theme'] ?? 'light') === 'auto' ? 'selected' : ''; ?>>Auto (System Preference)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Primary Color</label>
          <input type="color" id="primary_color" value="<?php echo $settings['primary_color'] ?? '#1E3A8A'; ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Save Appearance</button>
      </form>
    </div>
  </div>

  <!-- Analytics Settings Modal -->
  <div class="modal" id="analyticsSettingsModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>📊 Analytics Settings</h2>
        <button class="close-btn" onclick="closeModal('analyticsSettingsModal')">✕</button>
      </div>
      <form id="analyticsSettingsForm">
        <div class="form-group">
          <label>Data Retention (days)</label>
          <input type="number" id="data_retention" value="<?php echo $settings['data_retention'] ?? '365'; ?>" min="30" max="1095">
          <small style="font-size: 11px; color: var(--text-light);">How long to keep logs and analytics data</small>
        </div>
        <div class="form-group">
          <label>Enable Analytics</label>
          <select id="analytics_enabled">
            <option value="1" <?php echo ($settings['analytics_enabled'] ?? '1') === '1' ? 'selected' : ''; ?>>Enabled</option>
            <option value="0" <?php echo ($settings['analytics_enabled'] ?? '1') === '0' ? 'selected' : ''; ?>>Disabled</option>
          </select>
        </div>
        <div class="form-group">
          <label>Auto-generate Reports</label>
          <select id="auto_reports">
            <option value="daily" <?php echo ($settings['auto_reports'] ?? 'weekly') === 'daily' ? 'selected' : ''; ?>>Daily</option>
            <option value="weekly" <?php echo ($settings['auto_reports'] ?? 'weekly') === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
            <option value="monthly" <?php echo ($settings['auto_reports'] ?? 'weekly') === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
            <option value="never" <?php echo ($settings['auto_reports'] ?? 'weekly') === 'never' ? 'selected' : ''; ?>>Never</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Save Analytics Settings</button>
      </form>
    </div>
  </div>

  <!-- Add/Edit Department Modal -->
  <div class="modal" id="addDeptModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>🏢 Add Department Contact</h2>
        <button class="close-btn" onclick="closeModal('addDeptModal')">✕</button>
      </div>
      <form id="deptForm">
        <input type="hidden" id="dept_id">
        <div class="form-group">
          <label>Department Name *</label>
          <input type="text" id="dept_name" required>
        </div>
        <div class="form-group">
          <label>Contact Person</label>
          <input type="text" id="dept_contact_person">
        </div>
        <div class="form-group">
          <label>Email Address *</label>
          <input type="email" id="dept_email" required>
        </div>
        <div class="form-group">
          <label>Phone Number</label>
          <input type="text" id="dept_phone">
        </div>
        <div class="form-group">
          <label>Office Location</label>
          <input type="text" id="dept_location">
        </div>
        <div class="form-group">
          <label>Category</label>
          <select id="dept_category">
            <option value="academic">Academic</option>
            <option value="administrative">Administrative</option>
            <option value="technical">Technical</option>
            <option value="financial">Financial</option>
            <option value="health">Health</option>
            <option value="security">Security</option>
            <option value="student">Student Services</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea id="dept_description" placeholder="Brief description of the department's role..."></textarea>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select id="dept_status">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <button type="submit" class="btn btn-super" style="width: 100%;">Save Department</button>
      </form>
    </div>
  </div>

  <!-- PROFILE MODAL -->
  <div class="modal" id="profileModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>👤 Super Admin Profile</h2>
        <button class="close-btn" onclick="closeModal('profileModal')">✕</button>
      </div>
      <div class="profile-section">
        <div class="profile-avatar"><?php echo strtoupper($superadminName[0]); ?></div>
        <h2><?php echo htmlspecialchars($superadminName); ?></h2>
        <p><?php echo htmlspecialchars($superadminEmail); ?></p>
        <p style="font-weight: 600; margin-top: 4px; color: var(--super);">Super Administrator</p>
      </div>
      <form onsubmit="changePassword(event)">
        <label>Current Password</label>
        <input type="password" id="currentPass" placeholder="Enter current password" required>
        <label>New Password</label>
        <input type="password" id="newPass" placeholder="Enter new password" minlength="6" required>
        <label>Confirm Password</label>
        <input type="password" id="confirmPass" placeholder="Confirm password" minlength="6" required>
        <button type="submit" class="btn btn-super" style="width: 100%; margin-top: 10px;">Update Password</button>
      </form>
    </div>
  </div>

  <!-- Notification Modal -->
  <div class="modal" id="notifyModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>🔔 Send Notification</h2>
        <button class="close-btn" onclick="closeModal('notifyModal')">✕</button>
      </div>
      <form id="notifyForm">
        <div class="form-group">
          <label>Recipient Type</label>
          <select id="notify_recipient" onchange="toggleSpecificEmail()">
            <option value="all">All Users (Students & Admins)</option>
            <option value="students">All Students</option>
            <option value="admins">All Admins</option>
            <option value="specific">Specific Email</option>
          </select>
        </div>
        <div class="form-group" id="specificStudentGroup" style="display: none;">
          <label>Recipient Email</label>
          <input type="email" id="notify_email" placeholder="user@bugema.ac.ug">
        </div>
        <div class="form-group">
          <label>Notification Title</label>
          <input type="text" id="notify_title" placeholder="Enter notification title" required>
        </div>
        <div class="form-group">
          <label>Message</label>
          <textarea id="notify_message" placeholder="Write your notification message..." required></textarea>
        </div>
        <button type="submit" class="btn btn-super" style="width: 100%;">Send Notification</button>
      </form>
    </div>
  </div>

  <!-- Edit Complaint Modal -->
  <div class="modal" id="editComplaintModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>✏️ Edit Complaint</h2>
        <button class="close-btn" onclick="closeModal('editComplaintModal')">✕</button>
      </div>
      <form id="editComplaintForm">
        <input type="hidden" id="edit_complaint_id">
        <div class="form-group">
          <label>Title</label>
          <input type="text" id="edit_title" required>
        </div>
        <div class="form-group">
          <label>Category</label>
          <input type="text" id="edit_category">
        </div>
        <div class="form-group">
          <label>Priority</label>
          <select id="edit_priority">
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
          </select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select id="edit_status">
            <option value="pending">Pending</option>
            <option value="progress">In Progress</option>
            <option value="resolved">Resolved</option>
          </select>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea id="edit_description"></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Save Changes</button>
      </form>
    </div>
  </div>

  <!-- Backup Modal -->
  <div class="modal" id="backupModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>💾 Backup Data</h2>
        <button class="close-btn" onclick="closeModal('backupModal')">✕</button>
      </div>
      <div class="form-group">
        <label>Backup Type</label>
        <select id="backup_type">
          <option value="database">Database Only</option>
          <option value="full">Full System Backup</option>
        </select>
      </div>
      <div class="form-group">
        <label>Include Data</label>
        <div>
          <input type="checkbox" id="include_users" checked> Users Data<br>
          <input type="checkbox" id="include_complaints" checked> Complaints Data<br>
          <input type="checkbox" id="include_logs" checked> System Logs<br>
          <input type="checkbox" id="include_settings" checked> System Settings
        </div>
      </div>
      <button class="btn btn-warning" style="width: 100%;" onclick="createBackup()">
        <i class="bi bi-download"></i> Create Backup
      </button>
      <div id="backupProgress" style="display: none; margin-top: 10px; text-align: center;">
        <p>Creating backup... Please wait</p>
      </div>
    </div>
  </div>

  <!-- Reports Modal -->
  <div class="modal" id="reportsModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>📊 Generate Reports</h2>
        <button class="close-btn" onclick="closeModal('reportsModal')">✕</button>
      </div>
      <div class="form-group">
        <label>Report Type</label>
        <select id="report_type">
          <option value="complaints">Complaints Report</option>
          <option value="users">Users Report</option>
          <option value="system">System Usage Report</option>
          <option value="logs">Activity Logs Report</option>
        </select>
      </div>
      <div class="form-group">
        <label>Date Range</label>
        <div style="display: flex; gap: 10px;">
          <input type="date" id="report_start_date" style="flex: 1;">
          <input type="date" id="report_end_date" style="flex: 1;">
        </div>
      </div>
      <div class="form-group">
        <label>Format</label>
        <select id="report_format">
          <option value="pdf">PDF</option>
          <option value="excel">Excel</option>
          <option value="csv">CSV</option>
        </select>
      </div>
      <button class="btn btn-primary" style="width: 100%;" onclick="generateReport()">
        <i class="bi bi-file-earmark-arrow-down"></i> Generate Report
      </button>
    </div>
  </div>

<script>
// Enhanced error handling and initialization
window.addEventListener('error', function(e) {
    console.error('Global error:', e.error);
    showToast('❌ Script error occurred. Please refresh the page.', 'error');
});

// Ensure DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Super Admin Dashboard loaded');
    
    // Initialize theme
    initializeTheme();
    
    // Initialize all event listeners safely
    initializeEventListeners();
    
    // Initialize date fields
    initializeDateFields();
    
    // Initialize rating distribution when ratings tab is clicked
    document.querySelector('[data-tab="tab-ratings"]')?.addEventListener('click', function() {
        setTimeout(loadRatingDistribution, 300);
    });
});

function initializeTheme() {
    const themeToggle = document.getElementById('themeToggle');
    const isDarkMode = localStorage.getItem('superadminDarkMode') === 'true';

    if (isDarkMode) {
        document.body.classList.add('dark-mode');
        themeToggle.classList.add('bi-sun');
        themeToggle.classList.remove('bi-moon');
    }
}

function initializeEventListeners() {
    try {
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', handleThemeToggle);
        }

        // Header buttons
        const profileBtn = document.getElementById('profileBtn');
        const notifyBtn = document.getElementById('notifyBtn');
        const logoutBtn = document.getElementById('logoutBtn');

        if (profileBtn) profileBtn.addEventListener('click', () => openModal('profileModal'));
        if (notifyBtn) notifyBtn.addEventListener('click', () => openModal('notifyModal'));
        if (logoutBtn) logoutBtn.addEventListener('click', handleLogout);

        // Bottom navigation
        document.querySelectorAll('.nav-item').forEach(nav => {
            nav.addEventListener('click', function(e) {
                e.preventDefault();
                const tabId = this.getAttribute('data-tab');
                if (tabId) {
                    switchTab(tabId, this);
                }
            });
        });

        // Modal close buttons
        document.querySelectorAll('.close-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const modal = this.closest('.modal');
                if (modal) closeModal(modal.id);
            });
        });

        // Close modals when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) closeModal(this.id);
            });
        });

        // Form submissions
        const notifyForm = document.getElementById('notifyForm');
        if (notifyForm) notifyForm.addEventListener('submit', handleNotifySubmit);

        const editComplaintForm = document.getElementById('editComplaintForm');
        if (editComplaintForm) editComplaintForm.addEventListener('submit', handleEditComplaintSubmit);

        const createAdminForm = document.getElementById('createAdminForm');
        if (createAdminForm) createAdminForm.addEventListener('submit', handleCreateAdminSubmit);

        const deptForm = document.getElementById('deptForm');
        if (deptForm) deptForm.addEventListener('submit', handleDeptSubmit);

        const systemSettingsForm = document.getElementById('systemSettingsForm');
        if (systemSettingsForm) systemSettingsForm.addEventListener('submit', handleSystemSettingsSubmit);

        const securitySettingsForm = document.getElementById('securitySettingsForm');
        if (securitySettingsForm) securitySettingsForm.addEventListener('submit', handleSecuritySettingsSubmit);

        const emailSettingsForm = document.getElementById('emailSettingsForm');
        if (emailSettingsForm) emailSettingsForm.addEventListener('submit', handleEmailSettingsSubmit);

        const appearanceSettingsForm = document.getElementById('appearanceSettingsForm');
        if (appearanceSettingsForm) appearanceSettingsForm.addEventListener('submit', handleAppearanceSettingsSubmit);

        const analyticsSettingsForm = document.getElementById('analyticsSettingsForm');
        if (analyticsSettingsForm) analyticsSettingsForm.addEventListener('submit', handleAnalyticsSettingsSubmit);

        // Search functionality
        const searchUsers = document.getElementById('searchUsers');
        if (searchUsers) searchUsers.addEventListener('input', handleUserSearch);

        const searchDepts = document.getElementById('searchDepts');
        if (searchDepts) searchDepts.addEventListener('input', handleDeptSearch);

        // Notification recipient toggle
        const notifyRecipient = document.getElementById('notify_recipient');
        if (notifyRecipient) notifyRecipient.addEventListener('change', toggleSpecificEmail);

        // User type toggle
        const userType = document.getElementById('user_type');
        if (userType) userType.addEventListener('change', toggleIdField);

        console.log('All event listeners initialized successfully');
    } catch (error) {
        console.error('Error initializing event listeners:', error);
        showToast('❌ Interface loading error. Please refresh.', 'error');
    }
}

function initializeDateFields() {
    const today = new Date().toISOString().split('T')[0];
    const startDate = document.getElementById('report_start_date');
    const endDate = document.getElementById('report_end_date');
    
    if (startDate) startDate.value = today;
    if (endDate) endDate.value = today;
}

function handleThemeToggle() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('superadminDarkMode', isDark);
    
    this.classList.toggle('bi-sun');
    this.classList.toggle('bi-moon');
}

function handleLogout() {
    if (confirm('🚪 Logout from Super Admin?')) {
        window.location.href = 'logout.php';
    }
}

// Tab Switching
function switchTab(tabId, clickedElement) {
    try {
        console.log('Switching to tab:', tabId);
        
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Remove active class from all nav items
        document.querySelectorAll('.nav-item').forEach(nav => {
            nav.classList.remove('active');
        });
        
        // Show selected tab
        const selectedTab = document.getElementById(tabId);
        if (selectedTab) {
            selectedTab.classList.add('active');
            console.log('Tab activated:', tabId);
        } else {
            console.error('Tab not found:', tabId);
            showToast('❌ Tab not found', 'error');
            return;
        }
        
        // Activate clicked nav item
        if (clickedElement) {
            clickedElement.classList.add('active');
        }
        
    } catch (error) {
        console.error('Error switching tab:', error);
        showToast('❌ Error switching tab', 'error');
    }
}

// Modal Management
function openModal(modalId) {
    try {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            console.log('Opened modal:', modalId);
        } else {
            console.error('Modal not found:', modalId);
            showToast('❌ Modal not found', 'error');
        }
    } catch (error) {
        console.error('Error opening modal:', error);
        showToast('❌ Error opening dialog', 'error');
    }
}

function closeModal(modalId) {
    try {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            console.log('Closed modal:', modalId);
        }
    } catch (error) {
        console.error('Error closing modal:', error);
    }
}

// Toggle specific email field
function toggleSpecificEmail() {
    const recipient = document.getElementById('notify_recipient');
    const specificGroup = document.getElementById('specificStudentGroup');
    if (recipient && specificGroup) {
        specificGroup.style.display = recipient.value === 'specific' ? 'block' : 'none';
    }
}

// Toggle ID field based on user type
function toggleIdField() {
    const userType = document.getElementById('user_type').value;
    const studentIdGroup = document.getElementById('studentIdGroup');
    const staffIdGroup = document.getElementById('staffIdGroup');
    
    if (userType === 'student') {
        studentIdGroup.style.display = 'block';
        staffIdGroup.style.display = 'none';
    } else if (userType === 'staff') {
        studentIdGroup.style.display = 'none';
        staffIdGroup.style.display = 'block';
    } else {
        studentIdGroup.style.display = 'none';
        staffIdGroup.style.display = 'none';
    }
}

// Notification Handler
async function handleNotifySubmit(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('recipient', document.getElementById('notify_recipient').value);
    formData.append('email', document.getElementById('notify_email').value);
    formData.append('title', document.getElementById('notify_title').value);
    formData.append('message', document.getElementById('notify_message').value);
    
    try {
        showToast('📤 Sending notification...', 'info');
        const response = await fetch('api/send_notification.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Notification sent successfully!', 'success');
            closeModal('notifyModal');
            e.target.reset();
            document.getElementById('specificStudentGroup').style.display = 'none';
        } else {
            showToast('❌ ' + (data.message || 'Failed to send notification'), 'error');
        }
    } catch (error) {
        showToast('❌ Error: ' + error.message, 'error');
    }
}

// Complaint Management Functions
async function editComplaint(id) {
    try {
        const response = await fetch(`api/get_complaint.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('edit_complaint_id').value = data.complaint.id;
            document.getElementById('edit_title').value = data.complaint.title;
            document.getElementById('edit_category').value = data.complaint.category || '';
            document.getElementById('edit_priority').value = data.complaint.priority || 'medium';
            document.getElementById('edit_status').value = data.complaint.status;
            document.getElementById('edit_description').value = data.complaint.description || '';
            openModal('editComplaintModal');
        } else {
            showToast('❌ Failed to load complaint data', 'error');
        }
    } catch (error) {
        showToast('❌ Failed to load complaint data', 'error');
    }
}

async function handleEditComplaintSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('id', document.getElementById('edit_complaint_id').value);
    formData.append('title', document.getElementById('edit_title').value);
    formData.append('category', document.getElementById('edit_category').value);
    formData.append('priority', document.getElementById('edit_priority').value);
    formData.append('status', document.getElementById('edit_status').value);
    formData.append('description', document.getElementById('edit_description').value);

    try {
        const response = await fetch('api/update_complaint.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Complaint updated successfully', 'success');
            closeModal('editComplaintModal');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        showToast('❌ Error: ' + error.message, 'error');
    }
}

// RATING FUNCTIONS - UPDATED WITH CORRECT PATHS
// Send Thank You to a specific rater
async function sendThankYou(ratingId, studentEmail) {
    const message = prompt('Enter thank you message:', 
        `Dear Student,\n\nThank you for taking the time to rate your experience with CampusVoice. ` +
        `Your feedback is invaluable to us and helps us improve our services.\n\nBest regards,\nCampusVoice Team`);
    
    if (!message) return;
    
    const formData = new FormData();
    formData.append('rating_id', ratingId);
    formData.append('student_email', studentEmail);
    formData.append('message', message);

    try {
        showToast('📤 Sending thank you message...', 'info');
        const response = await fetch('api/send_thankyou.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.status === 401) {
            showToast('❌ Unauthorized. Please log in again.', 'error');
            return;
        }
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ ' + data.message, 'success');
            if (data.note) {
                console.log('Note:', data.note);
            }
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('❌ Error: ' + error.message, 'error');
    }
}

// Send Thank You to all raters
async function sendThankYouToAll() {
    if (!confirm('Send thank you message to ALL students who have rated?\n\nThis will send individual emails to each student.')) return;
    
    const message = prompt('Enter thank you message for all raters:',
        `Dear Student,\n\nThank you for taking the time to provide feedback on CampusVoice. ` +
        `We appreciate your input and are committed to continuously improving our platform based on user feedback.\n\nSincerely,\nCampusVoice Administration`);
    
    if (!message) return;

    try {
        showToast('📤 Sending messages to all raters...', 'info');
        const response = await fetch('api/send_thankyou_all.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ message: message })
        });
        
        if (response.status === 401) {
            showToast('❌ Unauthorized. Please log in again.', 'error');
            return;
        }
        
        const data = await response.json();
        
        if (data.success) {
            showToast(`✅ ${data.message}`, 'success');
            if (data.note) {
                console.log('Note:', data.note);
            }
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('❌ Error: ' + error.message, 'error');
    }
}

// Export ratings to CSV
async function exportRatings() {
    try {
        showToast('📤 Exporting ratings data...', 'info');
        const response = await fetch('api/export_ratings.php');
        
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `complaint_ratings_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            showToast('✅ Ratings exported successfully!', 'success');
        } else {
            showToast('❌ Failed to export ratings', 'error');
        }
    } catch (error) {
        showToast('❌ Error exporting ratings: ' + error.message, 'error');
    }
}

// View rating details
async function viewRatingDetails(ratingId) {
    try {
        const response = await fetch(`api/get_rating.php?id=${ratingId}`);
        if (response.status === 401) {
            showToast('❌ Unauthorized. Please log in again.', 'error');
            return;
        }
        
        const data = await response.json();
        
        if (data.success && data.rating) {
            const rating = data.rating;
            const modalContent = `
                <div style="background: var(--card); padding: 20px; border-radius: 12px;">
                    <h3 style="color: var(--super); margin-bottom: 15px;">⭐ Rating Details</h3>
                    
                    <div style="margin-bottom: 15px;">
                        <strong style="color: var(--text-light);">Student:</strong><br>
                        ${rating.student_name || 'N/A'} (${rating.student_email || 'N/A'})
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <strong style="color: var(--text-light);">Complaint:</strong><br>
                        ${rating.complaint_title || 'N/A'}
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <strong style="color: var(--text-light);">Rating:</strong><br>
                        ${'★'.repeat(rating.rating)}${'☆'.repeat(5 - rating.rating)} (${rating.rating}/5)
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <strong style="color: var(--text-light);">Response Time:</strong><br>
                        <span class="badge ${rating.response_time}">${rating.response_time}</span>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <strong style="color: var(--text-light);">Resolution Quality:</strong><br>
                        <span class="badge ${rating.resolution_quality}">${rating.resolution_quality}</span>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <strong style="color: var(--text-light);">Would Recommend:</strong><br>
                        ${rating.would_recommend ? '✅ Yes' : '❌ No'}
                    </div>
                    
                    ${rating.feedback ? `
                    <div style="margin-bottom: 15px;">
                        <strong style="color: var(--text-light);">Feedback:</strong><br>
                        <div style="background: var(--bg); padding: 10px; border-radius: 8px; margin-top: 5px;">
                            ${rating.feedback}
                        </div>
                    </div>
                    ` : ''}
                    
                    <div style="margin-bottom: 15px;">
                        <strong style="color: var(--text-light);">Date:</strong><br>
                        ${new Date(rating.created_at).toLocaleDateString()}
                    </div>
                    
                    ${rating.student_email ? `
                    <button onclick="sendThankYou(${rating.id}, '${rating.student_email}')" 
                            class="btn btn-success" style="width: 100%;">
                        <i class="bi bi-envelope-heart"></i> Send Thank You
                    </button>
                    ` : ''}
                </div>
            `;
            
            // Create modal
            const modal = document.createElement('div');
            modal.className = 'modal active';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>📊 Rating Details</h2>
                        <button class="close-btn" onclick="this.closest('.modal').remove()">✕</button>
                    </div>
                    <div class="modal-body">
                        ${modalContent}
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        } else {
            showToast('❌ ' + (data.message || 'Failed to load rating details'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('❌ Error loading rating details', 'error');
    }
}

// Delete rating
async function deleteRating(ratingId) {
    if (!confirm('⚠️ Delete this rating? This cannot be undone.')) return;
    
    try {
        const response = await fetch('api/delete_rating.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ rating_id: ratingId })
        });
        
        if (response.status === 401) {
            showToast('❌ Unauthorized. Please log in again.', 'error');
            return;
        }
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Rating deleted', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('❌ Error: ' + error.message, 'error');
    }
}

// Load rating distribution chart
async function loadRatingDistribution() {
    try {
        const response = await fetch('api/get_rating_distribution.php');
        if (response.status === 401) {
            console.error('Unauthorized access to rating distribution');
            return;
        }
        
        const data = await response.json();
        
        if (data.success && data.distribution) {
            const total = data.total;
            
            // Update counts and bars for each star rating
            [1, 2, 3, 4, 5].forEach(star => {
                const count = data.distribution[star] || 0;
                const percentage = total > 0 ? (count / total * 100) : 0;
                
                const countElement = document.getElementById(`star${star}Count`);
                const barElement = document.getElementById(`star${star}Bar`);
                
                if (countElement) countElement.textContent = `${count} (${percentage.toFixed(1)}%)`;
                if (barElement) barElement.style.width = `${percentage}%`;
                
                // Color the bars based on star rating
                if (barElement) {
                    if (star === 5) barElement.style.background = '#fbbf24'; // gold for 5 stars
                    else if (star === 4) barElement.style.background = '#a3a3a3'; // gray for 4 stars
                    else barElement.style.background = '#d4d4d4'; // light gray for others
                }
            });
        }
    } catch (error) {
        console.error('Error loading rating distribution:', error);
    }
}

// Set Complaint Progress
async function setProgress(id) {
    if (!confirm('Set this complaint to "In Progress"?')) return;
    
    const formData = new FormData();
    formData.append('id', id);
    formData.append('status', 'progress');

    try {
        const response = await fetch('api/update_complaint.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Complaint set to In Progress', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        showToast('❌ Error: ' + error.message, 'error');
    }
}

// Resolve Complaint
async function resolveComplaint(id) {
    if (!confirm('Mark this complaint as Resolved?')) return;
    
    const formData = new FormData();
    formData.append('id', id);
    formData.append('status', 'resolved');

    try {
        const response = await fetch('api/update_complaint.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Complaint resolved successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        showToast('❌ Error: ' + error.message, 'error');
    }
}

// Delete Complaint
async function deleteComplaint(id) {
    if (!confirm('⚠️ Delete this complaint permanently? This cannot be undone.')) return;
    
    const formData = new FormData();
    formData.append('id', id);

    try {
        const response = await fetch('api/delete_complaint.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Complaint deleted successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        showToast('❌ Error: ' + error.message, 'error');
    }
}

// User Management Functions
async function promoteToAdmin(userId) {
    if (!confirm('Promote this user to Admin role?')) return;
    
    const formData = new FormData();
    formData.append('user_id', userId);

    try {
        const response = await fetch('api/promote_to_admin.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ User promoted to Admin', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        showToast('❌ Error: ' + error.message, 'error');
    }
}

async function demoteAdmin(userId) {
    if (!confirm('Demote this admin to Student role?')) return;
    
    const formData = new FormData();
    formData.append('user_id', userId);

    try {
        const response = await fetch('api/demote_admin.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Admin demoted to Student', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        showToast('❌ Error: ' + error.message, 'error');
    }
}

async function suspendUser(userId) {
    if (!confirm('Toggle suspension for this user?')) return;
    
    const formData = new FormData();
    formData.append('user_id', userId);

    try {
        const response = await fetch('api/suspend_user.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ User status updated', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        showToast('❌ Error: ' + error.message, 'error');
    }
}

async function deleteUser(userId) {
    if (!confirm('⚠️ Permanently delete this user? This cannot be undone.')) return;
    
    const formData = new FormData();
    formData.append('id', userId);

    try {
        const response = await fetch('api/delete_student.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ User deleted', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        showToast('❌ Error: ' + error.message, 'error');
    }
}

// Enhanced Edit User Function
async function editUser(userId) {
    try {
        showToast('📝 Loading user data...', 'info');
        const response = await fetch(`api/get_student.php?id=${userId}`);
        const data = await response.json();
        
        if (data.success && data.student) {
            // Create a custom edit modal
            const newName = prompt('Edit User Name:', data.student.name);
            if (newName === null) return; // User cancelled
            
            const newEmail = prompt('Edit User Email:', data.student.email);
            if (newEmail === null) return;
            
            const newStudentId = prompt('Edit Student/Staff ID:', data.student.student_id || '');
            if (newStudentId === null) return;
            
            // Confirm changes
            if (!confirm(`Update user details?\n\nName: ${newName}\nEmail: ${newEmail}\nID: ${newStudentId}`)) {
                return;
            }
            
            // Send update request
            const formData = new FormData();
            formData.append('id', userId);
            formData.append('name', newName);
            formData.append('email', newEmail);
            formData.append('student_id', newStudentId);
            
            const updateResponse = await fetch('api/update_student.php', {
                method: 'POST',
                body: formData
            });
            const updateData = await updateResponse.json();
            
            if (updateData.success) {
                showToast('✅ User updated successfully!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('❌ Failed to update user: ' + (updateData.message || 'Unknown error'), 'error');
            }
        } else {
            showToast('❌ Failed to load user data', 'error');
        }
    } catch (error) {
        console.error('Edit user error:', error);
        showToast('❌ Error loading user data: ' + error.message, 'error');
    }
}

// Create User Handler
async function handleCreateAdminSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('name', document.getElementById('admin_name').value);
    formData.append('email', document.getElementById('admin_email').value);
    
    const userType = document.getElementById('user_type').value;
    // API expects "role" – map directly from selected user type
    formData.append('role', userType);

    // Both student and staff IDs go into student_id column
    if (userType === 'student') {
        formData.append('student_id', document.getElementById('admin_student_id').value);
    } else if (userType === 'staff') {
        formData.append('student_id', document.getElementById('admin_staff_id').value);
    } else {
        formData.append('student_id', '');
    }
    
    formData.append('password', document.getElementById('admin_password').value);
    
    try {
        const response = await fetch('api/create_admin.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ User created successfully', 'success');
            closeModal('createAdminModal');
            e.target.reset();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        showToast('❌ Error: ' + error.message, 'error');
    }
}

// Settings Form Handlers
async function handleSystemSettingsSubmit(e) {
    e.preventDefault();
    await saveSettings('system');
}

async function handleSecuritySettingsSubmit(e) {
    e.preventDefault();
    await saveSettings('security');
}

async function handleEmailSettingsSubmit(e) {
    e.preventDefault();
    await saveSettings('email');
}

async function handleAppearanceSettingsSubmit(e) {
    e.preventDefault();
    await saveSettings('appearance');
}

async function handleAnalyticsSettingsSubmit(e) {
    e.preventDefault();
    await saveSettings('analytics');
}

async function saveSettings(type) {
    const formData = new FormData();
    
    switch(type) {
        case 'system':
            formData.append('platform_name', document.getElementById('platform_name').value);
            formData.append('maintenance_mode', document.getElementById('maintenance_mode').value);
            formData.append('max_login_attempts', document.getElementById('max_login_attempts').value);
            formData.append('session_timeout', document.getElementById('session_timeout').value);
            break;
        case 'security':
            formData.append('max_login_attempts', document.getElementById('security_max_attempts').value);
            formData.append('session_timeout', document.getElementById('security_timeout').value);
            formData.append('password_policy', document.getElementById('password_policy').value);
            break;
        case 'email':
            formData.append('smtp_host', document.getElementById('smtp_host').value);
            formData.append('smtp_port', document.getElementById('smtp_port').value);
            formData.append('smtp_username', document.getElementById('smtp_username').value);
            formData.append('smtp_password', document.getElementById('smtp_password').value);
            break;
        case 'appearance':
            formData.append('platform_name', document.getElementById('appearance_platform_name').value);
            formData.append('default_theme', document.getElementById('default_theme').value);
            formData.append('primary_color', document.getElementById('primary_color').value);
            break;
        case 'analytics':
            formData.append('data_retention', document.getElementById('data_retention').value);
            formData.append('analytics_enabled', document.getElementById('analytics_enabled').value);
            formData.append('auto_reports', document.getElementById('auto_reports').value);
            break;
    }

    try {
        showToast('💾 Saving settings...', 'info');
        const response = await fetch('api/save_settings.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Settings saved successfully!', 'success');
            closeModal(type + 'SettingsModal');
            if (type === 'email') {
                document.getElementById('smtp_password').value = ''; // Clear password field
            }
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        showToast('❌ Error: ' + error.message, 'error');
    }
}

// Department Management Functions
async function editDepartment(deptId) {
    try {
        const response = await fetch(`api/get_department.php?id=${deptId}`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('dept_id').value = data.department.id;
            document.getElementById('dept_name').value = data.department.department_name;
            document.getElementById('dept_contact_person').value = data.department.contact_person || '';
            document.getElementById('dept_email').value = data.department.email;
            document.getElementById('dept_phone').value = data.department.phone || '';
            document.getElementById('dept_location').value = data.department.office_location || '';
            document.getElementById('dept_category').value = data.department.category || 'academic';
            document.getElementById('dept_description').value = data.department.description || '';
            document.getElementById('dept_status').value = data.department.status || 'active';
            
            document.querySelector('#addDeptModal .modal-header h2').textContent = '✏️ Edit Department';
            openModal('addDeptModal');
        } else {
            showToast('❌ Failed to load department data', 'error');
        }
    } catch (error) {
        showToast('❌ Error loading department data', 'error');
    }
}

// Department Form Handler
async function handleDeptSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('id', document.getElementById('dept_id').value);
    formData.append('department_name', document.getElementById('dept_name').value);
    formData.append('contact_person', document.getElementById('dept_contact_person').value);
    formData.append('email', document.getElementById('dept_email').value);
    formData.append('phone', document.getElementById('dept_phone').value);
    formData.append('office_location', document.getElementById('dept_location').value);
    formData.append('category', document.getElementById('dept_category').value);
    formData.append('description', document.getElementById('dept_description').value);
    formData.append('status', document.getElementById('dept_status').value);

    try {
        const response = await fetch('api/save_department.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Department saved successfully', 'success');
            closeModal('addDeptModal');
            e.target.reset();
            document.querySelector('#addDeptModal .modal-header h2').textContent = '🏢 Add Department Contact';
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        showToast('❌ Error: ' + error.message, 'error');
    }
}

async function toggleDepartmentStatus(deptId, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    
    if (!confirm(`Set department status to "${newStatus}"?`)) return;
    
    const formData = new FormData();
    formData.append('id', deptId);
    formData.append('status', newStatus);

    try {
        const response = await fetch('api/update_department_status.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Department status updated', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        showToast('❌ Error: ' + error.message, 'error');
    }
}

async function deleteDepartment(deptId) {
    if (!confirm('⚠️ Delete this department contact? This cannot be undone.')) return;
    
    const formData = new FormData();
    formData.append('id', deptId);

    try {
        const response = await fetch('api/delete_department.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Department deleted', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        showToast('❌ Error: ' + error.message, 'error');
    }
}

// Export Department Contacts
async function exportDepartmentContacts() {
    try {
        showToast('📤 Exporting department contacts...', 'info');
        const response = await fetch('api/export_departments.php');
        
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `department_contacts_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            showToast('✅ Department contacts exported successfully!', 'success');
        } else {
            showToast('❌ Failed to export contacts', 'error');
        }
    } catch (error) {
        showToast('❌ Error exporting contacts: ' + error.message, 'error');
    }
}

// Search Handlers
function handleUserSearch(e) {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('.user-row').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
}

function handleDeptSearch(e) {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('.dept-card').forEach(card => {
        const deptName = card.getAttribute('data-dept-name') || '';
        card.style.display = deptName.includes(term) ? '' : 'none';
    });
}

// Backup Functionality
async function createBackup() {
    const backupProgress = document.getElementById('backupProgress');
    if (backupProgress) backupProgress.style.display = 'block';
    
    const formData = new FormData();
    formData.append('type', document.getElementById('backup_type').value);
    formData.append('include_users', document.getElementById('include_users').checked ? '1' : '0');
    formData.append('include_complaints', document.getElementById('include_complaints').checked ? '1' : '0');
    formData.append('include_logs', document.getElementById('include_logs').checked ? '1' : '0');
    formData.append('include_settings', document.getElementById('include_settings').checked ? '1' : '0');

    try {
        showToast('💾 Creating backup...', 'info');
        const response = await fetch('api/create_backup.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Backup created successfully!', 'success');
            // Create download link
            if (data.download_url) {
                const downloadLink = document.createElement('a');
                downloadLink.href = data.download_url;
                downloadLink.download = data.filename || 'backup.zip';
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
            }
            closeModal('backupModal');
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        showToast('❌ Error creating backup: ' + error.message, 'error');
    } finally {
        if (backupProgress) backupProgress.style.display = 'none';
    }
}

// Generate Reports
async function generateReport() {
    const formData = new FormData();
    formData.append('type', document.getElementById('report_type').value);
    formData.append('start_date', document.getElementById('report_start_date').value);
    formData.append('end_date', document.getElementById('report_end_date').value);
    formData.append('format', document.getElementById('report_format').value);

    try {
        showToast('📊 Generating report...', 'info');
        const response = await fetch('api/generate_report.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `report_${new Date().toISOString().split('T')[0]}.${document.getElementById('report_format').value}`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            showToast('✅ Report generated successfully!', 'success');
            closeModal('reportsModal');
        } else {
            showToast('❌ Failed to generate report', 'error');
        }
    } catch (error) {
        showToast('❌ Error generating report: ' + error.message, 'error');
    }
}

// Delete Logs
async function deleteLogs(logId = null) {
    const message = logId 
        ? 'Delete this log entry?' 
        : '⚠️ Delete ALL system logs? This cannot be undone.';
    
    if (!confirm(message)) return;
    
    const formData = new FormData();
    if (logId) {
        formData.append('log_id', logId);
    } else {
        formData.append('delete_all', 'true');
    }

    try {
        showToast('🗑️ Deleting logs...', 'info');
        const response = await fetch('api/delete_logs.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ ' + data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        showToast('❌ Error: ' + error.message, 'error');
    }
}

// Change Password
async function changePassword(e) {
    e.preventDefault();
    
    const currentPass = document.getElementById('currentPass').value;
    const newPass = document.getElementById('newPass').value;
    const confirmPass = document.getElementById('confirmPass').value;
    
    if (newPass !== confirmPass) {
        showToast('❌ New passwords do not match', 'error');
        return;
    }
    
    if (newPass.length < 6) {
        showToast('❌ Password must be at least 6 characters', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('current_password', currentPass);
    formData.append('new_password', newPass);
    formData.append('confirm_password', confirmPass);
    
    try {
        showToast('🔐 Updating password...', 'info');
        const response = await fetch('api/change_password.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Password updated successfully!', 'success');
            closeModal('profileModal');
            e.target.reset();
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    } catch (error) {
        showToast('❌ Error: ' + error.message, 'error');
    }
}

// Toast Notification System
function showToast(message, type = 'info') {
    // Remove existing toasts
    document.querySelectorAll('.toast-message').forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = 'toast-message';
    
    // Set background color based on type
    let backgroundColor;
    switch(type) {
        case 'success': backgroundColor = 'var(--secondary)'; break;
        case 'error': backgroundColor = 'var(--danger)'; break;
        case 'warning': backgroundColor = 'var(--warning)'; break;
        default: backgroundColor = 'var(--super)';
    }
    
    toast.style.cssText = `
        position: fixed;
        bottom: 80px;
        left: 50%;
        transform: translateX(-50%);
        background: ${backgroundColor};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        z-index: 9999;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        animation: slideUp 0.3s ease;
        max-width: 90%;
        text-align: center;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

// Add animations to head if not already present
if (!document.querySelector('#toast-animations')) {
    const style = document.createElement('style');
    style.id = 'toast-animations';
    style.textContent = `
        @keyframes slideUp {
            from { transform: translate(-50%, 100px); opacity: 0; }
            to { transform: translate(-50%, 0); opacity: 1; }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; transform: translate(-50%, 20px); }
        }
    `;
    document.head.appendChild(style);
}
// Add this function to your JavaScript
async function loadNotifications() {
    try {
        const response = await fetch('api/get_notifications.php');
        const data = await response.json();
        
        if (data.success) {
            // Update notification badge count
            const unreadCount = data.notifications.filter(n => !n.is_read).length;
            const notifyBtn = document.getElementById('notifyBtn');
            if (notifyBtn && unreadCount > 0) {
                notifyBtn.innerHTML = `<i class="bi bi-bell-fill"></i> <span style="background: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; position: absolute; top: -5px; right: -5px;">${unreadCount}</span>`;
            }
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}

// Call this on dashboard load
document.addEventListener('DOMContentLoaded', function() {
    // ... existing code ...
    loadNotifications();
});
// Quick Actions - Make sure these are accessible
window.openModal = openModal;
window.switchTab = switchTab;

// Make rating functions globally accessible
window.viewRatingDetails = viewRatingDetails;
window.deleteRating = deleteRating;
window.sendThankYou = sendThankYou;
window.sendThankYouToAll = sendThankYouToAll;
window.exportRatings = exportRatings;
window.loadRatingDistribution = loadRatingDistribution;

console.log('Super Admin Dashboard JavaScript loaded successfully!');
</script>
</body>
</html>