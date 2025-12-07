<?php
// test-db.php
$host = 'localhost';
$db   = 'campusvoice';
$user = 'root';
$pass = '';   // <-- change if you set a password

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<h2 style='color:green'>Connected!</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM students");
    $row  = $stmt->fetch();
    echo "<p>Students in table: <strong>{$row['total']}</strong></p>";
} catch (Exception $e) {
    echo "<h2 style='color:red'>Connection FAILED</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>