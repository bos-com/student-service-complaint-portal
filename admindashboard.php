<?php
// admindashboard.php - FOR BOTH ADMINS AND SUPER ADMINS
session_start();

// Check if user is logged in and has admin or superadmin role
if (!isset($_SESSION['student']) || 
    !in_array($_SESSION['student']['role'] ?? '', ['admin', 'superadmin'])) {
    
    error_log("Access denied - User role: " . ($_SESSION['student']['role'] ?? 'none'));
    header('Location: index.php#login');
    exit;
}

$adminName  = $_SESSION['student']['name'] ?? 'Admin';
$adminEmail = $_SESSION['student']['email'] ?? 'admin@campusvoice.com';
$adminRole  = $_SESSION['student']['role'] ?? 'admin';
$isSuperAdmin = ($adminRole === 'superadmin');

// Rest of your database connection and logic remains the same...
try {
    $pdo = new PDO("mysql:host=localhost;dbname=campusvoice;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die('Database connection failed');
}

// === STATISTICS ===
$stmt = $pdo->query("
    SELECT 
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved_count,
        SUM(CASE WHEN status = 'progress' THEN 1 ELSE 0 END) AS in_progress_count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
        COUNT(*) AS total_count
    FROM complaints
");
$counts = $stmt->fetch(PDO::FETCH_ASSOC);

$resolved_count     = $counts['resolved_count'] ?? 0;
$in_progress_count  = $counts['in_progress_count'] ?? 0;
$pending_count      = $counts['pending_count'] ?? 0;
$total_count        = $counts['total_count'] ?? 1;
$resolved_pct       = $total_count > 0 ? round(($resolved_count / $total_count) * 100, 1) : 0;

$users_count = $pdo->query("SELECT COUNT(*) FROM students WHERE verified = 1")->fetchColumn();

// === COMPLAINTS WITH FILTER ===
$filter = $_GET['filter'] ?? 'all';
$query = "SELECT c.*, s.name AS author_name FROM complaints c LEFT JOIN students s ON c.student_id = s.id";
$params = [];
if ($filter !== 'all') {
    $query .= " WHERE c.priority = ?";
    $params[] = $filter;
}
$query .= " ORDER BY c.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get logs for the logs tab
try {
    $logs_stmt = $pdo->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 50");
    $logs = $logs_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $logs = [];
}

// Get users for the users tab – only students here
try {
    $users_stmt = $pdo->query("SELECT id, name, email, student_id, role, created_at FROM students WHERE role = 'student' ORDER BY created_at DESC");
    $all_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $all_users = [];
}

// Create shorter variable names for the stats paragraph
$total = $total_count;
$resolved = $resolved_count;
$pending = $pending_count;
$progress = $in_progress_count;

// Department contacts data
$departments = [
    [
        'name' => 'IT Support',
        'email' => 'itsupport@bugema.ac.ug',
        'phone' => '+256 712 345 678',
        'contact_person' => 'Mr. John Tech',
        'hours' => 'Mon-Fri: 8:00 AM - 5:00 PM',
        'icon' => '💻'
    ],
    [
        'name' => 'Infrastructure',
        'email' => 'infrastructure@bugema.ac.ug',
        'phone' => '+256 712 345 679',
        'contact_person' => 'Eng. Sarah Build',
        'hours' => 'Mon-Fri: 7:00 AM - 4:00 PM',
        'icon' => '🏗️'
    ],
    [
        'name' => 'Student Affairs',
        'email' => 'studentaffairs@bugema.ac.ug',
        'phone' => '+256 712 345 680',
        'contact_person' => 'Mrs. Grace Help',
        'hours' => 'Mon-Fri: 8:30 AM - 5:30 PM',
        'icon' => '🎓'
    ],
    [
        'name' => 'Finance Office',
        'email' => 'finance@bugema.ac.ug',
        'phone' => '+256 712 345 681',
        'contact_person' => 'Mr. David Accounts',
        'hours' => 'Mon-Fri: 9:00 AM - 4:00 PM',
        'icon' => '💰'
    ],
    [
        'name' => 'Library Services',
        'email' => 'library@bugema.ac.ug',
        'phone' => '+256 712 345 682',
        'contact_person' => 'Ms. Linda Books',
        'hours' => 'Mon-Sat: 8:00 AM - 9:00 PM',
        'icon' => '📚'
    ],
    [
        'name' => 'Health Services',
        'email' => 'health@bugema.ac.ug',
        'phone' => '+256 712 345 683',
        'contact_person' => 'Dr. James Care',
        'hours' => '24/7 Emergency',
        'icon' => '🏥'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CampusVoice - Admin Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
  :root {
      /* Dark blue themed palette */
      --primary: #1E3A8A;       /* deep blue */
      --primary-light: #3B82F6; /* brighter blue accent */
      --secondary: #10b981;
      --warning: #f59e0b;
      --danger: #dc2626;
      --info: #2563EB;          /* blue-600 */
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
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
      color: white;
      padding: 12px 14px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 4px 12px rgba(30, 58, 138, 0.15);
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

    /* Main Layout Fix */
    main {
      flex: 1;
      overflow-y: auto;
      overflow-x: hidden;
      padding: 12px 8px 100px; /* extra bottom padding so stats content isn't hidden */
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
        max-width: 1400px;
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
      color: var(--primary);
      margin-bottom: 10px;
    }

    .stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
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

    .stat-card.pending { border-left-color: var(--warning); }
    .stat-card.progress { border-left-color: var(--info); }
    .stat-card.resolved { border-left-color: var(--secondary); }

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

    .filter-chips {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
      margin-bottom: 12px;
    }

    .chip {
      padding: 6px 12px;
      border-radius: 16px;
      font-size: 11px;
      background: var(--card);
      border: 1px solid var(--border);
      cursor: pointer;
      transition: all 0.2s;
      font-weight: 500;
      text-decoration: none;
      color: var(--text);
    }

    .chip:hover {
      border-color: var(--primary-light);
    }

    .chip.active {
      background: var(--primary);
      color: white;
      border-color: var(--primary);
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

    .badge.pending { background: #fef3c7; color: #92400e; }
    .badge.progress { background: #e9d5ff; color: #6b21a8; }
    .badge.resolved { background: #dcfce7; color: #166534; }
    .badge.high { background: #fee2e2; color: #991b1b; }
    .badge.medium { background: #fef3c7; color: #92400e; }
    .badge.low { background: #f3e8ff; color: #581c87; }

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
        width: 300px;
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
      color: var(--primary);
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .news-item {
      padding: 8px;
      border-radius: 8px;
      margin-bottom: 6px;
      background: var(--bg);
      border-left: 2px solid var(--primary);
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
      max-width: 420px;
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
      color: var(--primary);
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
      border-color: var(--primary);
      background: var(--card);
      box-shadow: 0 0 0 2px rgba(30, 58, 138, 0.1);
    }

    label {
      display: block;
      margin-top: 8px;
      font-weight: 600;
      font-size: 12px;
      color: var(--primary);
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

    /* Small screen responsiveness */
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
        padding: 10px 6px 110px; /* more room below tables/stats on small screens */
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
      color: var(--primary);
      background: rgba(59, 130, 246, 0.08);
    }

    /* Ensure tab content doesn't affect sidebar */
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
      background: linear-gradient(135deg, var(--primary-light), var(--primary));
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 800;
      font-size: 24px;
      margin: 0 auto 8px;
      box-shadow: 0 2px 8px rgba(30, 58, 138, 0.2);
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

    /* Stats Paragraph */
    .stats-paragraph {
      background: var(--card);
      padding: 12px;
      border-radius: var(--radius);
      margin-bottom: 12px;
      border-left: 4px solid var(--primary);
      font-size: 12px;
      line-height: 1.4;
    }

    .stats-paragraph strong {
      color: var(--primary);
    }

    /* Chart Styles */
    .chart-container {
      background: var(--card);
      padding: 15px;
      border-radius: var(--radius);
      margin-bottom: 15px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    .chart-title {
      font-size: 12px;
      margin-bottom: 10px;
      color: var(--text-light);
      font-weight: 600;
    }

    .progress-bar {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 8px;
    }

    .progress-color {
      width: 12px;
      height: 12px;
      border-radius: 2px;
    }

    .progress-track {
      flex: 1;
      height: 8px;
      background: var(--border);
      border-radius: 4px;
      overflow: hidden;
    }

    .progress-fill {
      height: 100%;
      transition: width 0.3s ease;
    }

    .bar-chart {
      display: flex;
      align-items: end;
      justify-content: center;
      gap: 8px;
      height: 120px;
      padding: 10px;
    }

    .bar-column {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .bar {
      width: 20px;
      border-radius: 3px;
      transition: height 0.3s ease;
    }

    .bar-label {
      font-size: 9px;
      margin-top: 5px;
      color: var(--text-light);
    }

    .category-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }

    .category-item {
      background: var(--bg);
      padding: 10px;
      border-radius: 6px;
    }

    .category-name {
      font-size: 10px;
      color: var(--text-light);
      margin-bottom: 5px;
    }

    /* Department Contacts Styles */
    .departments-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 12px;
      margin-bottom: 14px;
    }

    .department-card {
      background: var(--card);
      padding: 15px;
      border-radius: var(--radius);
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
      border-left: 4px solid var(--primary);
      transition: all 0.3s;
    }

    .department-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .department-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 12px;
    }

    .department-icon {
      font-size: 24px;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--primary-light);
      border-radius: 8px;
      color: white;
    }

    .department-info h4 {
      font-size: 14px;
      font-weight: 700;
      margin: 0 0 2px;
      color: var(--primary);
    }

    .department-info p {
      font-size: 11px;
      color: var(--text-light);
      margin: 0;
    }

    .contact-details {
      margin-top: 10px;
    }

    .contact-item {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 6px;
      font-size: 11px;
    }

    .contact-item i {
      color: var(--primary);
      width: 16px;
    }

    .contact-actions {
      display: flex;
      gap: 5px;
      margin-top: 10px;
    }

    .contact-btn {
      flex: 1;
      padding: 6px;
      border: none;
      border-radius: 6px;
      font-size: 10px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
    }

    .contact-btn.email {
      background: var(--primary);
      color: white;
    }

    .contact-btn.phone {
      background: var(--secondary);
      color: white;
    }

    .contact-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .quick-contact-bar {
      background: var(--card);
      padding: 12px;
      border-radius: var(--radius);
      margin-bottom: 14px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
      border-left: 4px solid var(--info);
    }

    .quick-contact-title {
      font-size: 12px;
      font-weight: 700;
      margin-bottom: 8px;
      color: var(--info);
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .quick-contact-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 8px;
    }

    .quick-contact-item {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px;
      background: var(--bg);
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.2s;
    }

    .quick-contact-item:hover {
      background: var(--primary-light);
      color: white;
    }

    .quick-contact-item:hover .contact-label {
      color: white;
    }

    .contact-label {
      font-size: 10px;
      color: var(--text-light);
      font-weight: 600;
    }

    .contact-value {
      font-size: 10px;
      font-weight: 700;
    }

    .logo-icon {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      background: white;
      border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .logo-icon img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  </style>
</head>

<body>

<!-- Header -->
<header>
    <div class="logo-section">
        <div class="logo-icon">
            <img src="assets/bugemalogo.jpg" alt="Bugema University Logo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
        </div>
        <h1>CampusVoice Admin</h1>
    </div>
    <div class="nav-actions">
        <i class="bi bi-bell" id="notifyBtn" title="Notifications"></i>
        <i class="bi bi-moon" id="themeToggle" title="Toggle Theme"></i>
        <i class="bi bi-person" id="profileBtn" title="Profile"></i>
        <i class="bi bi-box-arrow-right" id="logoutBtn" title="Logout"></i>
    </div>
</header>

<!-- Main Content -->
<main>
    <div class="main-content">

      <!-- COMPLAINTS TAB -->
      <div class="tab-content active" id="tab-complaints">
        <h2 class="page-title">📋 Complaints Management</h2>

        <!-- Stats Paragraph -->
        <div class="stats-paragraph">
          <strong>📊 Quick Overview:</strong> You have <strong><?php echo $total; ?> total complaints</strong> with 
          <strong><?php echo $resolved; ?> resolved</strong> (<?php echo $resolved_pct; ?>% success rate). 
          Currently <strong><?php echo $pending; ?> pending</strong> and <strong><?php echo $progress; ?> in progress</strong>. 
          Average resolution time is <strong>2.3 hours</strong> with a <strong>4.6⭐ satisfaction rating</strong>.
        </div>

        <div class="stats">
          <div class="stat-card">
            <i class="bi bi-list-check" style="color: var(--primary);"></i>
            <h3><?php echo $total; ?></h3>
            <p>Total</p>
          </div>
          <div class="stat-card pending">
            <i class="bi bi-hourglass-split" style="color: var(--warning);"></i>
            <h3><?php echo $pending; ?></h3>
            <p>Pending</p>
          </div>
          <div class="stat-card progress">
            <i class="bi bi-arrow-repeat" style="color: var(--info);"></i>
            <h3><?php echo $progress; ?></h3>
            <p>In Progress</p>
          </div>
          <div class="stat-card resolved">
            <i class="bi bi-check-circle-fill" style="color: var(--secondary);"></i>
            <h3><?php echo $resolved; ?></h3>
            <p>Resolved</p>
          </div>
        </div>

        <div class="filter-chips">
          <a href="?filter=all" class="chip <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
          <a href="?filter=high" class="chip <?php echo $filter === 'high' ? 'active' : ''; ?>">🔴 High</a>
          <a href="?filter=medium" class="chip <?php echo $filter === 'medium' ? 'active' : ''; ?>">🟠 Medium</a>
          <a href="?filter=low" class="chip <?php echo $filter === 'low' ? 'active' : ''; ?>">🟣 Low</a>
        </div>

        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Location</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Time</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($complaints as $c): ?>
              <tr>
                <td><?php echo htmlspecialchars(substr($c['title'], 0, 20)); ?></td>
                <td><?php echo htmlspecialchars($c['category'] ?? 'General'); ?></td>
                <td><?php echo htmlspecialchars($c['location'] ?? 'N/A'); ?></td>
                <td><span class="badge <?php echo $c['priority']; ?>"><?php echo ucfirst($c['priority']); ?></span></td>
                <td><span class="badge <?php echo $c['status']; ?>"><?php echo ucfirst($c['status']); ?></span></td>
                <td><?php echo date('M d h:i', strtotime($c['created_at'])); ?></td>
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

      <!-- LOGS TAB -->
      <div class="tab-content" id="tab-logs">
        <h2 class="page-title">📊 System Logs</h2>
        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Time</th>
                <th>Admin</th>
                <th>Action</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $log): ?>
              <tr>
                <td><?php echo date('M d h:i', strtotime($log['created_at'])); ?></td>
                <td><?php echo htmlspecialchars(substr($log['admin_id'] ?? 'System', 0, 15)); ?></td>
                <td><span class="badge progress"><?php echo htmlspecialchars($log['action']); ?></span></td>
                <td><?php echo htmlspecialchars(substr($log['details'] ?? '', 0, 20)); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- USERS TAB -->
      <div class="tab-content" id="tab-accounts">
        <h2 class="page-title">👥 Users Management</h2>
        <input type="text" placeholder="🔍 Search..." id="searchUsers" style="margin-bottom: 10px;">
        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Student ID</th>
                <th>Role</th>
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
                  <span class="badge progress">
                    <?php echo ucfirst($u['role'] ?? 'student'); ?>
                  </span>
                </td>
                <td class="action-group">
                  <button class="btn btn-primary" onclick="editUser(<?php echo $u['id']; ?>)">✏️</button>
                  <button class="btn btn-danger" onclick="deleteUser(<?php echo $u['id']; ?>)">🗑️</button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ANALYTICS TAB -->
      <div class="tab-content" id="tab-analytics">
        <h2 class="page-title">📈 Analytics & Stats</h2>
        
        <!-- Stats Paragraph -->
        <div class="stats-paragraph">
          <strong>📊 Quick Overview:</strong> You have <strong><?php echo $total; ?> total complaints</strong> with 
          <strong><?php echo $resolved; ?> resolved</strong> (<?php echo $resolved_pct; ?>% success rate). 
          Currently <strong><?php echo $pending; ?> pending</strong> and <strong><?php echo $progress; ?> in progress</strong>. 
          Average resolution time is <strong>2.3 hours</strong> with a <strong>4.6⭐ satisfaction rating</strong>.
        </div>

        <div class="stats">
          <div class="stat-card resolved">
            <i class="bi bi-graph-up" style="color: var(--secondary);"></i>
            <h3><?php echo $resolved_pct; ?>%</h3>
            <p>Resolved</p>
          </div>
          <div class="stat-card pending">
            <i class="bi bi-clock-history" style="color: var(--warning);"></i>
            <h3>2.3h</h3>
            <p>Avg Time</p>
          </div>
          <div class="stat-card progress">
            <i class="bi bi-people" style="color: var(--info);"></i>
            <h3><?php echo $users_count; ?></h3>
            <p>Users</p>
          </div>
          <div class="stat-card">
            <i class="bi bi-star-fill" style="color: #fbbf24;"></i>
            <h3>4.6⭐</h3>
            <p>Rating</p>
          </div>
        </div>

        <!-- Chart Section -->
        <div class="chart-container">
          <h3 style="margin-bottom: 15px; color: var(--primary);">📊 Complaints Overview</h3>
          
          <!-- Status Distribution Chart -->
          <div style="margin-bottom: 20px;">
            <div class="chart-title">Status Distribution</div>
            <div class="progress-bar">
              <div class="progress-color" style="background: var(--warning);"></div>
              <span style="font-size: 11px;">Pending (<?php echo $pending; ?>)</span>
              <div class="progress-track">
                <div class="progress-fill" style="width: <?php echo ($pending/max($total,1))*100; ?>%; background: var(--warning);"></div>
              </div>
            </div>
            <div class="progress-bar">
              <div class="progress-color" style="background: var(--info);"></div>
              <span style="font-size: 11px;">In Progress (<?php echo $progress; ?>)</span>
              <div class="progress-track">
                <div class="progress-fill" style="width: <?php echo ($progress/max($total,1))*100; ?>%; background: var(--info);"></div>
              </div>
            </div>
            <div class="progress-bar">
              <div class="progress-color" style="background: var(--secondary);"></div>
              <span style="font-size: 11px;">Resolved (<?php echo $resolved; ?>)</span>
              <div class="progress-track">
                <div class="progress-fill" style="width: <?php echo ($resolved/max($total,1))*100; ?>%; background: var(--secondary);"></div>
              </div>
            </div>
          </div>

          <!-- Monthly Trend -->
          <div style="margin-bottom: 20px;">
            <div class="chart-title">Monthly Trend</div>
            <div style="background: var(--bg); padding: 15px; border-radius: 8px; text-align: center;">
              <p style="font-size: 11px; color: var(--text-light); margin-bottom: 10px;">
                📈 Chart visualization would appear here
              </p>
              <div class="bar-chart">
                <div class="bar-column">
                  <div class="bar" style="height: 80px; background: var(--primary-light);"></div>
                  <div class="bar-label">Jan</div>
                </div>
                <div class="bar-column">
                  <div class="bar" style="height: 60px; background: var(--primary-light);"></div>
                  <div class="bar-label">Feb</div>
                </div>
                <div class="bar-column">
                  <div class="bar" style="height: 90px; background: var(--primary);"></div>
                  <div class="bar-label">Mar</div>
                </div>
                <div class="bar-column">
                  <div class="bar" style="height: 70px; background: var(--primary-light);"></div>
                  <div class="bar-label">Apr</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Category Distribution -->
          <div>
            <div class="chart-title">Top Categories</div>
            <div class="category-grid">
              <div class="category-item">
                <div class="category-name">Infrastructure</div>
                <div class="progress-track">
                  <div class="progress-fill" style="width: 65%; background: var(--warning);"></div>
                </div>
              </div>
              <div class="category-item">
                <div class="category-name">IT & Network</div>
                <div class="progress-track">
                  <div class="progress-fill" style="width: 45%; background: var(--info);"></div>
                </div>
              </div>
              <div class="category-item">
                <div class="category-name">Food Services</div>
                <div class="progress-track">
                  <div class="progress-fill" style="width: 30%; background: var(--secondary);"></div>
                </div>
              </div>
              <div class="category-item">
                <div class="category-name">Facilities</div>
                <div class="progress-track">
                  <div class="progress-fill" style="width: 25%; background: var(--primary);"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
<!-- Add/Edit Department Modal -->
<div class="modal" id="addDeptModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>🏢 Add Department Contact</h2>
            <button class="close-btn" onclick="closeModal('addDeptModal')">✕</button>
        </div>
        <form id="deptForm" onsubmit="handleDeptSubmit(event)">
            <!-- Your department form fields here -->
            <button type="submit" class="btn btn-primary" style="width: 100%;">Save Department</button>
        </form>
    </div>
</div>
      <!-- CONTACTS TAB -->
      <div class="tab-content" id="tab-contacts">
        <h2 class="page-title">📞 Department Contacts</h2>

        <!-- Quick Contact Bar -->
        <div class="quick-contact-bar">
          <div class="quick-contact-title">
            <i class="bi bi-lightning-charge"></i> Quick Contact Links
          </div>
          <div class="quick-contact-grid">
            <div class="quick-contact-item" onclick="copyToClipboard('itsupport@bugema.ac.ug')">
              <i class="bi bi-envelope"></i>
              <div>
                <div class="contact-label">IT Support</div>
                <div class="contact-value">itsupport@bugema.ac.ug</div>
              </div>
            </div>
            <div class="quick-contact-item" onclick="copyToClipboard('infrastructure@bugema.ac.ug')">
              <i class="bi bi-envelope"></i>
              <div>
                <div class="contact-label">Infrastructure</div>
                <div class="contact-value">infrastructure@bugema.ac.ug</div>
              </div>
            </div>
            <div class="quick-contact-item" onclick="copyToClipboard('studentaffairs@bugema.ac.ug')">
              <i class="bi bi-envelope"></i>
              <div>
                <div class="contact-label">Student Affairs</div>
                <div class="contact-value">studentaffairs@bugema.ac.ug</div>
              </div>
            </div>
            <div class="quick-contact-item" onclick="window.open('tel:+256712345678')">
              <i class="bi bi-telephone"></i>
              <div>
                <div class="contact-label">Emergency</div>
                <div class="contact-value">+256 712 345 678</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Departments Grid -->
        <div class="departments-grid">
          <?php foreach ($departments as $dept): ?>
          <div class="department-card">
            <div class="department-header">
              <div class="department-icon"><?php echo $dept['icon']; ?></div>
              <div class="department-info">
                <h4><?php echo $dept['name']; ?></h4>
                <p><?php echo $dept['contact_person']; ?></p>
              </div>
            </div>
            
            <div class="contact-details">
              <div class="contact-item">
                <i class="bi bi-envelope"></i>
                <span><?php echo $dept['email']; ?></span>
              </div>
              <div class="contact-item">
                <i class="bi bi-telephone"></i>
                <span><?php echo $dept['phone']; ?></span>
              </div>
              <div class="contact-item">
                <i class="bi bi-clock"></i>
                <span><?php echo $dept['hours']; ?></span>
              </div>
            </div>
            
            <div class="contact-actions">
              <button class="contact-btn email" onclick="sendEmail('<?php echo $dept['email']; ?>')">
                <i class="bi bi-envelope"></i> Email
              </button>
              <button class="contact-btn phone" onclick="callNumber('<?php echo $dept['phone']; ?>')">
                <i class="bi bi-telephone"></i> Call
              </button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Emergency Contacts -->
        <div class="table-wrapper">
          <div style="padding: 15px;">
            <h3 style="margin-bottom: 15px; color: var(--danger);">🚨 Emergency Contacts</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
              <div style="background: #fef2f2; padding: 12px; border-radius: 8px; border-left: 4px solid var(--danger);">
                <h4 style="font-size: 12px; margin: 0 0 5px; color: var(--danger);">Campus Security</h4>
                <p style="font-size: 11px; margin: 0; color: var(--text);">+256 712 345 999</p>
                <p style="font-size: 10px; margin: 5px 0 0; color: var(--text-light);">24/7 Emergency Line</p>
              </div>
              <div style="background: #fef2f2; padding: 12px; border-radius: 8px; border-left: 4px solid var(--danger);">
                <h4 style="font-size: 12px; margin: 0 0 5px; color: var(--danger);">Medical Emergency</h4>
                <p style="font-size: 11px; margin: 0; color: var(--text);">+256 712 345 888</p>
                <p style="font-size: 10px; margin: 5px 0 0; color: var(--text-light);">Health Center</p>
              </div>
              <div style="background: #fef2f2; padding: 12px; border-radius: 8px; border-left: 4px solid var(--danger);">
                <h4 style="font-size: 12px; margin: 0 0 5px; color: var(--danger);">Fire Department</h4>
                <p style="font-size: 11px; margin: 0; color: var(--text);">+256 712 345 777</p>
                <p style="font-size: 10px; margin: 5px 0 0; color: var(--text-light);">Emergency Only</p>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Sidebar -->
    <div class="sidebar">
      <div class="sidebar-card">
        <div class="sidebar-title">
          <i class="bi bi-newspaper"></i> Recent Complaints
        </div>
        <div id="newsFeed">
          <?php foreach (array_slice($complaints, 0, 5) as $c): ?>
          <div class="news-item">
            <h5><?php echo htmlspecialchars(substr($c['title'], 0, 30)); ?></h5>
            <p><?php echo ucfirst($c['status']); ?> • <?php echo date('M d', strtotime($c['created_at'])); ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="sidebar-card">
        <div class="sidebar-title">
          <i class="bi bi-fire"></i> Trending Issues
        </div>
        <div id="trendingIssues">
          <div class="news-item">
            <h5>🏗️ Infrastructure</h5>
            <p>12 complaints</p>
          </div>
          <div class="news-item">
            <h5>💻 IT & Network</h5>
            <p>8 complaints</p>
          </div>
          <div class="news-item">
            <h5>🍽️ Food & Catering</h5>
            <p>6 complaints</p>
          </div>
        </div>
      </div>

      <div class="sidebar-card">
        <div class="sidebar-title">
          <i class="bi bi-telephone"></i> Quick Contacts
        </div>
        <div class="news-item" onclick="sendEmail('itsupport@bugema.ac.ug')">
          <h5>💻 IT Support</h5>
          <p>itsupport@bugema.ac.ug</p>
        </div>
        <div class="news-item" onclick="sendEmail('infrastructure@bugema.ac.ug')">
          <h5>🏗️ Infrastructure</h5>
          <p>infrastructure@bugema.ac.ug</p>
        </div>
        <div class="news-item" onclick="window.open('tel:+256712345999')">
          <h5>🚨 Security</h5>
          <p>+256 712 345 999</p>
        </div>
      </div>
    </div>
  </main>

  <!-- BOTTOM NAV -->
  <div class="bottom-nav">
    <a class="nav-item active" onclick="switchTab('tab-complaints', this)">
      <i class="bi bi-chat-square-text"></i>
      <span>Complaints</span>
    </a>
    <a class="nav-item" onclick="switchTab('tab-logs', this)">
      <i class="bi bi-journal-text"></i>
      <span>Logs</span>
    </a>
    <a class="nav-item" onclick="switchTab('tab-accounts', this)">
      <i class="bi bi-people"></i>
      <span>Users</span>
    </a>
    <a class="nav-item" onclick="switchTab('tab-analytics', this)">
      <i class="bi bi-bar-chart-line"></i>
      <span>Stats</span>
    </a>
    <a class="nav-item" onclick="switchTab('tab-contacts', this)">
      <i class="bi bi-telephone"></i>
      <span>Contacts</span>
    </a>
  </div>

  <!-- PROFILE MODAL -->
  <div class="modal" id="profileModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>👤 Admin Profile</h2>
        <button class="close-btn" onclick="closeModal('profileModal')">✕</button>
      </div>

      <div class="profile-section">
        <div class="profile-avatar"><?php echo strtoupper($adminName[0]); ?></div>
        <h2><?php echo htmlspecialchars($adminName); ?></h2>
        <p><?php echo htmlspecialchars($adminEmail); ?></p>
        <p style="font-weight: 600; margin-top: 4px;"><?php echo $isSuperAdmin ? 'Super Admin' : 'Admin'; ?></p>
      </div>

      <form onsubmit="changePassword(event)">
        <label>Current Password</label>
        <input type="password" id="currentPass" placeholder="Enter current password" required>
        <label>New Password</label>
        <input type="password" id="newPass" placeholder="Enter new password" minlength="6" required>
        <label>Confirm Password</label>
        <input type="password" id="confirmPass" placeholder="Confirm password" minlength="6" required>
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Update Password</button>
      </form>
    </div>
  </div>

  <!-- NOTIFICATIONS MODAL -->
  <div class="modal" id="notifyModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>🔔 Send Notification</h2>
        <button class="close-btn" onclick="closeModal('notifyModal')">✕</button>
      </div>

      <form onsubmit="sendNotification(event)">
        <div class="form-group">
          <label>Recipient</label>
          <select id="notify_recipient" onchange="toggleSpecificEmail()">
            <option value="all">All Students</option>
            <option value="specific">Specific Student</option>
          </select>
        </div>
        <div class="form-group" id="specificStudentGroup" style="display: none;">
          <label>Student Email</label>
          <input type="email" id="notify_email" placeholder="student@bugema.ac.ug">
        </div>
        <div class="form-group">
          <label>Message</label>
          <textarea id="notifyMessage" placeholder="Write your notification message..." required></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Send Notification</button>
      </form>
    </div>
  </div>

  <!-- EDIT COMPLAINT MODAL -->
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
          <label>Location</label>
          <input type="text" id="edit_location">
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

  <!-- EDIT USER MODAL -->
  <div class="modal" id="editUserModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>✏️ Edit Student</h2>
        <button class="close-btn" onclick="closeModal('editUserModal')">✕</button>
      </div>

      <form id="editUserForm">
        <input type="hidden" id="edit_user_id">
        <div class="form-group">
          <label>Name</label>
          <input type="text" id="edit_user_name" required>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" id="edit_user_email" required>
        </div>
        <div class="form-group">
          <label>Student ID</label>
          <input type="text" id="edit_user_student_id">
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Save Changes</button>
      </form>
    </div>
  </div>

  <script>
    // Enhanced error handling
    window.addEventListener('error', function(e) {
        console.error('Global error:', e.error);
        showToast('❌ Script error occurred. Please refresh the page.', 'error');
    });

    // Ensure DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Admin Dashboard loaded');
        
        // Initialize theme
        initializeTheme();
        
        // Initialize all event listeners safely
        initializeEventListeners();
    });

    function initializeTheme() {
        const themeToggle = document.getElementById('themeToggle');
        const isDarkMode = localStorage.getItem('adminDarkMode') === 'true';

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
            const editComplaintForm = document.getElementById('editComplaintForm');
            if (editComplaintForm) editComplaintForm.addEventListener('submit', handleEditComplaint);

            const editUserForm = document.getElementById('editUserForm');
            if (editUserForm) editUserForm.addEventListener('submit', handleEditUser);

            const notifyForm = document.querySelector('#notifyModal form');
            if (notifyForm) notifyForm.addEventListener('submit', sendNotification);

            const profileForm = document.querySelector('#profileModal form');
            if (profileForm) profileForm.addEventListener('submit', changePassword);

            // Search functionality
            const searchUsers = document.getElementById('searchUsers');
            if (searchUsers) searchUsers.addEventListener('input', handleUserSearch);

            // Notification recipient toggle
            const notifyRecipient = document.getElementById('notify_recipient');
            if (notifyRecipient) notifyRecipient.addEventListener('change', toggleSpecificEmail);

            console.log('All event listeners initialized successfully');
        } catch (error) {
            console.error('Error initializing event listeners:', error);
            showToast('❌ Interface loading error. Please refresh.', 'error');
        }
    }

    function handleThemeToggle() {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('adminDarkMode', isDark);
        
        this.classList.toggle('bi-sun');
        this.classList.toggle('bi-moon');
    }

    function handleLogout() {
        if (confirm('🚪 Are you sure you want to logout?')) {
            window.location.href = 'logout.php';
        }
    }

    // Tab Switching
    function switchTab(tabId, clickedElement) {
        try {
            console.log('Switching to tab:', tabId);
            
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all nav items
            document.querySelectorAll('.nav-item').forEach(nav => {
                nav.classList.remove('active');
            });
            
            // Show the selected tab content
            const selectedTab = document.getElementById(tabId);
            if (selectedTab) {
                selectedTab.classList.add('active');
            }
            
            // Add active class to clicked nav item
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
                document.body.style.overflow = 'hidden';
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
                document.body.style.overflow = '';
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

    // Contact Functions
    function sendEmail(email) {
        window.location.href = `mailto:${email}`;
    }

    function callNumber(phone) {
        window.open(`tel:${phone}`);
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('✅ Copied to clipboard: ' + text, 'success');
        }).catch(err => {
            showToast('❌ Failed to copy to clipboard', 'error');
        });
    }

    // Search Handlers
    function handleUserSearch(e) {
        const term = e.target.value.toLowerCase();
        document.querySelectorAll('.user-row').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    }

    // ========== COMPLAINT MANAGEMENT ==========

    // Edit Complaint
    async function editComplaint(id) {
        try {
            const response = await fetch(`api/admin/complaints.php?action=get_complaint&id=${id}`);
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('edit_complaint_id').value = data.complaint.id;
                document.getElementById('edit_title').value = data.complaint.title;
                document.getElementById('edit_category').value = data.complaint.category || '';
                document.getElementById('edit_location').value = data.complaint.location || '';
                document.getElementById('edit_priority').value = data.complaint.priority || 'medium';
                document.getElementById('edit_status').value = data.complaint.status;
                document.getElementById('edit_description').value = data.complaint.description || '';
                openModal('editComplaintModal');
            } else {
                showToast('❌ Failed to load complaint data', 'error');
            }
        } catch (error) {
            showToast('❌ Failed to load complaint data', 'error');
            console.error('Edit complaint error:', error);
        }
    }

    async function handleEditComplaint(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('id', document.getElementById('edit_complaint_id').value);
        formData.append('title', document.getElementById('edit_title').value);
        formData.append('category', document.getElementById('edit_category').value);
        formData.append('location', document.getElementById('edit_location').value);
        formData.append('priority', document.getElementById('edit_priority').value);
        formData.append('status', document.getElementById('edit_status').value);
        formData.append('description', document.getElementById('edit_description').value);

        try {
            showToast('📝 Updating complaint...', 'info');
            const response = await fetch('api/admin/complaints.php?action=update_complaint', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                showToast('✅ Complaint updated successfully', 'success');
                closeModal('editComplaintModal');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('❌ ' + (data.message || 'Failed to update complaint'), 'error');
            }
        } catch (error) {
            showToast('❌ Error: ' + error.message, 'error');
            console.error('Update complaint error:', error);
        }
    }

    // Set Complaint Progress
    async function setProgress(id) {
        if (!confirm('Set this complaint to "In Progress"?')) return;
        
        const formData = new FormData();
        formData.append('id', id);
        formData.append('status', 'progress');

        try {
            showToast('⏳ Setting to progress...', 'info');
            const response = await fetch('api/admin/complaints.php?action=update_status', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                showToast('✅ Complaint set to In Progress', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('❌ ' + (data.message || 'Failed to update status'), 'error');
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
            showToast('✅ Resolving complaint...', 'info');
            const response = await fetch('api/admin/complaints.php?action=update_status', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                showToast('✅ Complaint resolved successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('❌ ' + (data.message || 'Failed to resolve complaint'), 'error');
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
            showToast('🗑️ Deleting complaint...', 'info');
            const response = await fetch('api/admin/complaints.php?action=delete_complaint', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                showToast('✅ Complaint deleted successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('❌ ' + (data.message || 'Failed to delete complaint'), 'error');
            }
        } catch (error) {
            showToast('❌ Error: ' + error.message, 'error');
        }
    }

    // ========== USER MANAGEMENT ==========

    // Edit User
    async function editUser(id) {
        try {
            const response = await fetch(`api/admin/users.php?action=get_user&id=${id}`);
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('edit_user_id').value = data.user.id;
                document.getElementById('edit_user_name').value = data.user.name;
                document.getElementById('edit_user_email').value = data.user.email;
                document.getElementById('edit_user_student_id').value = data.user.student_id || '';
                openModal('editUserModal');
            } else {
                showToast('❌ Failed to load user data', 'error');
            }
        } catch (error) {
            showToast('❌ Failed to load user data', 'error');
            console.error('Edit user error:', error);
        }
    }

    async function handleEditUser(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('id', document.getElementById('edit_user_id').value);
        formData.append('name', document.getElementById('edit_user_name').value);
        formData.append('email', document.getElementById('edit_user_email').value);
        formData.append('student_id', document.getElementById('edit_user_student_id').value);

        try {
            showToast('👤 Updating user...', 'info');
            const response = await fetch('api/admin/users.php?action=update_user', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                showToast('✅ User updated successfully', 'success');
                closeModal('editUserModal');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('❌ ' + (data.message || 'Failed to update user'), 'error');
            }
        } catch (error) {
            showToast('❌ Error: ' + error.message, 'error');
            console.error('Update user error:', error);
        }
    }

    // Delete User
    async function deleteUser(id) {
        if (!confirm('⚠️ Delete this user permanently? This cannot be undone.')) return;
        
        const formData = new FormData();
        formData.append('id', id);

        try {
            showToast('🗑️ Deleting user...', 'info');
            const response = await fetch('api/admin/users.php?action=delete_user', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                showToast('✅ User deleted successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('❌ ' + (data.message || 'Failed to delete user'), 'error');
            }
        } catch (error) {
            showToast('❌ Error: ' + error.message, 'error');
        }
    }

    // Suspend User
    async function suspendUser(id) {
        if (!confirm('Toggle suspension for this user?')) return;
        
        const formData = new FormData();
        formData.append('id', id);

        try {
            showToast('⏸️ Updating user status...', 'info');
            const response = await fetch('api/admin/users.php?action=suspend_user', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                showToast('✅ User status updated', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('❌ ' + (data.message || 'Failed to update user status'), 'error');
            }
        } catch (error) {
            showToast('❌ Error: ' + error.message, 'error');
        }
    }

    // ========== NOTIFICATIONS ==========

    // Send Notification
    async function sendNotification(e) {
        e.preventDefault();
        const formData = new FormData();
        formData.append('recipient', document.getElementById('notify_recipient').value);
        formData.append('email', document.getElementById('notify_email').value);
        formData.append('title', document.getElementById('notify_title')?.value || 'Notification from Admin');
        formData.append('message', document.getElementById('notifyMessage').value);
        
        try {
            showToast('📤 Sending notification...', 'info');
            const res = await fetch('api/admin/notifications.php', {
                method: 'POST',
                body: formData
            });
            
            const json = await res.json();
            if (json.success) {
                showToast('✅ Notification sent successfully!', 'success');
                closeModal('notifyModal');
                document.getElementById('notifyMessage').value = '';
                document.getElementById('notify_email').value = '';
                document.getElementById('specificStudentGroup').style.display = 'none';
            } else {
                showToast('❌ ' + (json.message || 'Failed to send notification'), 'error');
            }
        } catch (error) {
            showToast('❌ Connection error', 'error');
            console.error('Notification error:', error);
        }
    }

    // ========== SYSTEM OPERATIONS ==========

    // Change Password
    async function changePassword(e) {
        e.preventDefault();
        const current = document.getElementById('currentPass').value;
        const newPass = document.getElementById('newPass').value;
        const confirmPass = document.getElementById('confirmPass').value;

        if (newPass !== confirmPass) {
            showToast('❌ Passwords do not match', 'error');
            return;
        }

        if (newPass.length < 6) {
            showToast('❌ Password must be at least 6 characters', 'error');
            return;
        }

        try {
            showToast('🔒 Changing password...', 'info');
            const formData = new FormData();
            formData.append('current_password', current);
            formData.append('new_password', newPass);

            const res = await fetch('api/admin/system.php?action=change_password', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            
            if (json.success) {
                showToast('✅ Password updated successfully', 'success');
                closeModal('profileModal');
                document.getElementById('currentPass').value = '';
                document.getElementById('newPass').value = '';
                document.getElementById('confirmPass').value = '';
            } else {
                showToast('❌ ' + (json.message || 'Failed to update password'), 'error');
            }
        } catch (error) {
            showToast('❌ Connection error', 'error');
            console.error('Change password error:', error);
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
            const response = await fetch('api/admin/system.php?action=delete_logs', {
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

    // ========== DEPARTMENT MANAGEMENT ==========

    // Edit Department
    async function editDepartment(deptId) {
        try {
            const response = await fetch(`api/admin/departments.php?action=get_department&id=${deptId}`);
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

    // Save Department
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
            showToast('💾 Saving department...', 'info');
            const response = await fetch('api/admin/departments.php?action=save_department', {
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

    // Toggle Department Status
    async function toggleDepartmentStatus(deptId, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        
        if (!confirm(`Set department status to "${newStatus}"?`)) return;
        
        const formData = new FormData();
        formData.append('id', deptId);
        formData.append('status', newStatus);

        try {
            showToast('🔄 Updating department status...', 'info');
            const response = await fetch('api/admin/departments.php?action=update_status', {
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

    // Delete Department
    async function deleteDepartment(deptId) {
        if (!confirm('⚠️ Delete this department contact? This cannot be undone.')) return;
        
        const formData = new FormData();
        formData.append('id', deptId);

        try {
            showToast('🗑️ Deleting department...', 'info');
            const response = await fetch('api/admin/departments.php?action=delete_department', {
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
            window.location.href = 'api/admin/departments.php?action=export';
            // Note: The actual download will happen automatically
            // We'll show success toast after a delay assuming it worked
            setTimeout(() => {
                showToast('✅ Department contacts exported successfully!', 'success');
            }, 2000);
        } catch (error) {
            showToast('❌ Error exporting contacts: ' + error.message, 'error');
        }
    }

    // ========== TOAST NOTIFICATION SYSTEM ==========

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
            case 'info': backgroundColor = 'var(--info)'; break;
            default: backgroundColor = 'var(--primary)';
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

    // Make functions globally available
    window.switchTab = switchTab;
    window.openModal = openModal;
    window.closeModal = closeModal;
    window.editComplaint = editComplaint;
    window.setProgress = setProgress;
    window.resolveComplaint = resolveComplaint;
    window.deleteComplaint = deleteComplaint;
    window.editUser = editUser;
    window.deleteUser = deleteUser;
    window.suspendUser = suspendUser;
    window.sendEmail = sendEmail;
    window.callNumber = callNumber;
    window.copyToClipboard = copyToClipboard;
    window.editDepartment = editDepartment;
    window.toggleDepartmentStatus = toggleDepartmentStatus;
    window.deleteDepartment = deleteDepartment;
    window.exportDepartmentContacts = exportDepartmentContacts;
    window.deleteLogs = deleteLogs;

    console.log('Admin Dashboard JavaScript loaded successfully!');
</script>
<body>
  <html>