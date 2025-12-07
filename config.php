<?php
// config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // change
define('DB_PASS', '');            // change
define('DB_NAME', 'campusvoice'); // change

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=utf8mb4;dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('DB connection failed: ' . $e->getMessage());
}
?>