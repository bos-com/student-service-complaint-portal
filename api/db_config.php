<?php
// Database configuration
$host = 'localhost';
$db = 'campusvoice';
$user = 'root';
$pass = '';

function getDBConnection() {
    global $host, $db, $user, $pass;
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Database connection failed']));
    }
}
?>