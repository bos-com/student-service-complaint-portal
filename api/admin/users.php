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
        case 'get_user':
            getUser($pdo);
            break;
        case 'update_user':
            updateUser($pdo);
            break;
        case 'delete_user':
            deleteUser($pdo);
            break;
        case 'suspend_user':
            suspendUser($pdo);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Admin users error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Operation failed: ' . $e->getMessage()]);
}

function getUser($pdo) {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        die(json_encode(['success' => false, 'message' => 'User ID required']));
    }

    $stmt = $pdo->prepare("SELECT id, name, email, student_id, role, status, created_at FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
}

function updateUser($pdo) {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $student_id = $_POST['student_id'] ?? '';
    $role = $_POST['role'] ?? 'student';

    if (!$id || empty($name) || empty($email)) {
        die(json_encode(['success' => false, 'message' => 'Required fields missing']));
    }

    $stmt = $pdo->prepare("UPDATE students SET name = ?, email = ?, student_id = ?, role = ? WHERE id = ?");
    $stmt->execute([$name, $email, $student_id, $role, $id]);

    // Log the action
    logAction($pdo, 'update_user', "Updated user: $name ($email)");

    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
}

function deleteUser($pdo) {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        die(json_encode(['success' => false, 'message' => 'User ID required']));
    }

    // Prevent self-deletion
    if ($id == $_SESSION['student']['id']) {
        die(json_encode(['success' => false, 'message' => 'Cannot delete your own account']));
    }

    // Get user info for logging
    $stmt = $pdo->prepare("SELECT name, email FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$id]);

    // Log the action
    logAction($pdo, 'delete_user', "Deleted user: " . ($user['name'] ?? "#$id") . " (" . ($user['email'] ?? '') . ")");

    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
}

function suspendUser($pdo) {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        die(json_encode(['success' => false, 'message' => 'User ID required']));
    }

    // Get current status
    $stmt = $pdo->prepare("SELECT name, email, status FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die(json_encode(['success' => false, 'message' => 'User not found']));
    }

    $newStatus = $user['status'] === 'active' ? 'suspended' : 'active';
    
    $stmt = $pdo->prepare("UPDATE students SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);

    // Log the action
    logAction($pdo, 'suspend_user', "Changed user status to $newStatus: " . $user['name'] . " (" . $user['email'] . ")");

    echo json_encode(['success' => true, 'message' => "User $newStatus successfully"]);
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