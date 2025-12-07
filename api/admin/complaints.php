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
        case 'get_complaint':
            getComplaint($pdo);
            break;
        case 'update_complaint':
            updateComplaint($pdo);
            break;
        case 'delete_complaint':
            deleteComplaint($pdo);
            break;
        case 'update_status':
            updateComplaintStatus($pdo);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Admin complaints error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Operation failed: ' . $e->getMessage()]);
}

function getComplaint($pdo) {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        die(json_encode(['success' => false, 'message' => 'Complaint ID required']));
    }

    $stmt = $pdo->prepare("
        SELECT c.*, s.name as student_name, s.email as student_email 
        FROM complaints c 
        LEFT JOIN students s ON c.student_id = s.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$id]);
    $complaint = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($complaint) {
        echo json_encode(['success' => true, 'complaint' => $complaint]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Complaint not found']);
    }
}

function updateComplaint($pdo) {
    $id = $_POST['id'] ?? null;
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $location = $_POST['location'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    $status = $_POST['status'] ?? 'pending';
    $description = $_POST['description'] ?? '';

    if (!$id || empty($title)) {
        die(json_encode(['success' => false, 'message' => 'Complaint ID and title are required']));
    }

    $stmt = $pdo->prepare("
        UPDATE complaints 
        SET title = ?, category = ?, location = ?, priority = ?, status = ?, description = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$title, $category, $location, $priority, $status, $description, $id]);

    // Log the action
    logAction($pdo, 'update_complaint', "Updated complaint #$id");

    echo json_encode(['success' => true, 'message' => 'Complaint updated successfully']);
}

function updateComplaintStatus($pdo) {
    $id = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? '';

    if (!$id || !in_array($status, ['pending', 'progress', 'resolved'])) {
        die(json_encode(['success' => false, 'message' => 'Invalid status or complaint ID']));
    }

    $stmt = $pdo->prepare("UPDATE complaints SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $id]);

    // Log the action
    logAction($pdo, 'update_complaint_status', "Changed complaint #$id status to $status");

    echo json_encode(['success' => true, 'message' => "Complaint marked as $status"]);
}

function deleteComplaint($pdo) {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        die(json_encode(['success' => false, 'message' => 'Complaint ID required']));
    }

    // Get complaint info for logging
    $stmt = $pdo->prepare("SELECT title FROM complaints WHERE id = ?");
    $stmt->execute([$id]);
    $complaint = $stmt->fetch(PDO::FETCH_ASSOC);

    // Manually delete dependent records to satisfy FK constraints
    $pdo->beginTransaction();
    try {
        // Delete related comments
        $stmt = $pdo->prepare("DELETE FROM comments WHERE complaint_id = ?");
        $stmt->execute([$id]);

        // Delete related likes
        $stmt = $pdo->prepare("DELETE FROM likes WHERE complaint_id = ?");
        $stmt->execute([$id]);

        // Delete related ratings (also has ON DELETE CASCADE but this is safe)
        $stmt = $pdo->prepare("DELETE FROM complaint_ratings WHERE complaint_id = ?");
        $stmt->execute([$id]);

        // Finally delete the complaint itself
        $stmt = $pdo->prepare("DELETE FROM complaints WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

    // Log the action
    logAction($pdo, 'delete_complaint', "Deleted complaint: " . ($complaint['title'] ?? "#$id"));

    echo json_encode(['success' => true, 'message' => 'Complaint deleted successfully']);
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