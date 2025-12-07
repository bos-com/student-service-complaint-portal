<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is superadmin
if (!isset($_SESSION['student']) || ($_SESSION['student']['role'] ?? '') !== 'superadmin') {
    http_response_code(401);
    header('Location: ../index.php#login');
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT 
            cr.id,
            s.name as student_name,
            s.email as student_email,
            c.title as complaint_title,
            cr.rating,
            cr.response_time,
            cr.resolution_quality,
            cr.would_recommend,
            cr.feedback,
            cr.created_at
        FROM complaint_ratings cr
        LEFT JOIN students s ON cr.student_id = s.id
        LEFT JOIN complaints c ON cr.complaint_id = c.id
        ORDER BY cr.created_at DESC
    ");
    $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=complaint_ratings_' . date('Y-m-d') . '.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add headers
    fputcsv($output, [
        'ID', 'Student Name', 'Student Email', 'Complaint Title', 
        'Rating (1-5)', 'Response Time', 'Resolution Quality', 
        'Would Recommend', 'Feedback', 'Date Submitted'
    ]);
    
    // Add data rows
    foreach ($ratings as $rating) {
        fputcsv($output, [
            $rating['id'],
            $rating['student_name'],
            $rating['student_email'],
            $rating['complaint_title'],
            $rating['rating'],
            $rating['response_time'],
            $rating['resolution_quality'],
            $rating['would_recommend'] ? 'Yes' : 'No',
            $rating['feedback'],
            $rating['created_at']
        ]);
    }
    
    fclose($output);
    
} catch (PDOException $e) {
    echo "Error exporting ratings: " . $e->getMessage();
}
?>