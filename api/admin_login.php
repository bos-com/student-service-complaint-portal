<?php
// filepath: api/admin_login.php
session_start();
header('Content-Type: application/json');

// Add CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
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

// Function to ensure admins table exists
function ensureAdminsTable($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('superadmin', 'admin') DEFAULT 'admin',
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            permissions JSON,
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Check if default admin accounts exist
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE email IN (?, ?)");
        $stmt->execute(['superadmin123@gmail.com', 'sportal395@gmail.com']);
        $count = $stmt->fetchColumn();
        
        if ($count < 2) {
            // Create superadmin
            $superadminExists = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE email = ?")->execute(['superadmin123@gmail.com'])->fetchColumn();
            if (!$superadminExists) {
                $hashed_superadmin = password_hash('superadmin123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admins (name, email, password, role, permissions) VALUES (?, ?, ?, 'superadmin', ?)");
                $stmt->execute([
                    'Super Administrator', 
                    'superadmin123@gmail.com', 
                    $hashed_superadmin,
                    json_encode(['all_permissions' => true])
                ]);
            }
            
            // Create admin
            $adminExists = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE email = ?")->execute(['sportal395@gmail.com'])->fetchColumn();
            if (!$adminExists) {
                $hashed_admin = password_hash('sportal395', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admins (name, email, password, role, permissions) VALUES (?, ?, ?, 'admin', ?)");
                $stmt->execute([
                    'System Administrator', 
                    'sportal395@gmail.com', 
                    $hashed_admin,
                    json_encode([
                        'manage_complaints' => true,
                        'manage_users' => true,
                        'view_reports' => true,
                        'send_notifications' => true
                    ])
                ]);
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Admins table error: " . $e->getMessage());
        return false;
    }
}

// Ensure admins table exists
ensureAdminsTable($pdo);

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && password_verify($password, $admin['password'])) {
        // Update last login
        $update_stmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
        $update_stmt->execute([$admin['id']]);
        
        // Set session
        $_SESSION['admin'] = [
            'id' => $admin['id'],
            'name' => $admin['name'],
            'email' => $admin['email'],
            'role' => $admin['role'],
            'permissions' => json_decode($admin['permissions'] ?? '{}', true)
        ];
        
        // Log the login activity
        logAdminActivity($pdo, $admin['id'], 'login', 'Admin logged in');
        
        // Determine redirect based on role
        $redirect = $admin['role'] === 'superadmin' ? 'superadmin_dashboard.php' : 'admindashboard.php';
        
        echo json_encode([
            'success' => true, 
            'message' => 'Login successful', 
            'role' => $admin['role'],
            'redirect' => $redirect
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Login error: ' . $e->getMessage()]);
}

function logAdminActivity($pdo, $admin_id, $action, $details) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
        )");
        
        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$admin_id, $action, $details, $_SERVER['REMOTE_ADDR']]);
    } catch (Exception $e) {
        error_log("Admin logging error: " . $e->getMessage());
    }
}
?>