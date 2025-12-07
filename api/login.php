<?php
// filepath: api/login.php
session_start();
header('Content-Type: application/json');

// Add CORS headers to prevent connection issues
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
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

// Function to add missing columns to students table
function ensureStudentsTableStructure($pdo) {
    try {
        // Check if role column exists
        $checkRole = $pdo->query("SHOW COLUMNS FROM students LIKE 'role'")->fetch();
        if (!$checkRole) {
            $pdo->exec("ALTER TABLE students ADD COLUMN role VARCHAR(20) DEFAULT 'student'");
        }
        
        // Check if status column exists
        $checkStatus = $pdo->query("SHOW COLUMNS FROM students LIKE 'status'")->fetch();
        if (!$checkStatus) {
            $pdo->exec("ALTER TABLE students ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
        }
        
        // Check if last_login column exists
        $checkLastLogin = $pdo->query("SHOW COLUMNS FROM students LIKE 'last_login'")->fetch();
        if (!$checkLastLogin) {
            $pdo->exec("ALTER TABLE students ADD COLUMN last_login TIMESTAMP NULL");
        }
        
        // Check if created_at column exists
        $checkCreatedAt = $pdo->query("SHOW COLUMNS FROM students LIKE 'created_at'")->fetch();
        if (!$checkCreatedAt) {
            $pdo->exec("ALTER TABLE students ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Table structure error: " . $e->getMessage());
        return false;
    }
}

// Function to safely log activity
function logActivity($pdo, $user_id, $action, $details) {
    try {
        // Check if system_logs table exists, if not create it
        $tableExists = $pdo->query("SHOW TABLES LIKE 'system_logs'")->fetch();
        if (!$tableExists) {
            $pdo->exec("CREATE TABLE system_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                action VARCHAR(100) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        }
        
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, $_SERVER['REMOTE_ADDR']]);
    } catch (Exception $e) {
        // Silently fail - don't break login because of logging
        error_log("Logging error: " . $e->getMessage());
    }
}

// Ensure table structure exists
ensureStudentsTableStructure($pdo);

// Check for superadmin credentials
if ($email === 'superadmin123@gmail.com' && $password === 'superadmin123') {
    try {
        // First, check if student exists by email (without role check)
        $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $superadmin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$superadmin) {
            // Create superadmin user if doesn't exist
            $hashed_password = password_hash('superadmin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO students (name, email, password, student_id, role, status, created_at) VALUES (?, ?, ?, ?, 'superadmin', 'active', NOW())");
            $stmt->execute(['Super Administrator', $email, $hashed_password, 'SUPER001']);
            
            // Get the newly created superadmin
            $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
            $stmt->execute([$email]);
            $superadmin = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // Update existing user to superadmin role
            $stmt = $pdo->prepare("UPDATE students SET role = 'superadmin', status = 'active' WHERE email = ?");
            $stmt->execute([$email]);
            $superadmin['role'] = 'superadmin';
        }
        
        // Update last login
        $update_stmt = $pdo->prepare("UPDATE students SET last_login = NOW() WHERE id = ?");
        $update_stmt->execute([$superadmin['id']]);
        
        // Set session for superadmin
        $_SESSION['student'] = [
            'id' => $superadmin['id'],
            'name' => $superadmin['name'],
            'email' => $superadmin['email'],
            'student_id' => $superadmin['student_id'] ?? 'SUPER001',
            'role' => 'superadmin'
        ];
        
        // Log the login activity
        logActivity($pdo, $superadmin['id'], 'login', 'Super admin logged in');
        
        echo json_encode([
            'success' => true, 
            'message' => 'Super admin login successful', 
            'role' => 'superadmin',
            'redirect' => 'superadmin_dashboard.php'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Super admin login error: ' . $e->getMessage()]);
    }
    exit;
}

// Check for admin credentials
if ($email === 'sportal395@gmail.com' && $password === 'sportal395') {
    try {
        // First, check if student exists by email (without role check)
        $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            // Create admin user if doesn't exist
            $hashed_password = password_hash('sportal395', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO students (name, email, password, student_id, role, status, created_at) VALUES (?, ?, ?, ?, 'admin', 'active', NOW())");
            $stmt->execute(['System Administrator', $email, $hashed_password, 'ADMIN001']);
            
            // Get the newly created admin
            $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // Update existing user to admin role
            $stmt = $pdo->prepare("UPDATE students SET role = 'admin', status = 'active' WHERE email = ?");
            $stmt->execute([$email]);
            $admin['role'] = 'admin';
        }
        
        // Update last login
        $update_stmt = $pdo->prepare("UPDATE students SET last_login = NOW() WHERE id = ?");
        $update_stmt->execute([$admin['id']]);
        
        // Set session for admin
        $_SESSION['student'] = [
            'id' => $admin['id'],
            'name' => $admin['name'],
            'email' => $admin['email'],
            'student_id' => $admin['student_id'] ?? 'ADMIN001',
            'role' => 'admin'
        ];
        
        // Log the login activity
        logActivity($pdo, $admin['id'], 'login', 'Admin logged in');
        
        echo json_encode([
            'success' => true, 
            'message' => 'Admin login successful', 
            'role' => 'admin',
            'redirect' => 'admindashboard.php'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Admin login error: ' . $e->getMessage()]);
    }
    exit;
}

// Regular user login process (for students)
try {
    // First try without role check to avoid column errors
    $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ? AND (status IS NULL OR status = 'active')");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        // Update last login
        $update_stmt = $pdo->prepare("UPDATE students SET last_login = NOW() WHERE id = ?");
        $update_stmt->execute([$user['id']]);
        
        // Ensure user has a role (default to student if not set)
        $userRole = $user['role'] ?? 'student';
        if (!isset($user['role']) || $userRole === 'student') {
            $stmt = $pdo->prepare("UPDATE students SET role = 'student' WHERE id = ?");
            $stmt->execute([$user['id']]);
        }
        
        // Set session
        $_SESSION['student'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'student_id' => $user['student_id'] ?? '',
            'role' => $userRole
        ];
        
        // Log the login activity
        logActivity($pdo, $user['id'], 'login', 'User logged in');
        
        // Determine redirect based on role
        $redirect = 'feed.php'; // CHANGED: Students now go to feed.php
        if ($userRole === 'admin') {
            $redirect = 'admindashboard.php';
        } elseif ($userRole === 'superadmin') {
            $redirect = 'superadmin_dashboard.php';
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Login successful', 
            'role' => $userRole,
            'redirect' => $redirect,
            'user' => [
                'name' => $user['name'],
                'email' => $user['email']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Login error: ' . $e->getMessage()]);
}
?>