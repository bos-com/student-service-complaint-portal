<?php
// C:\xampp\htdocs\campusvoice\includes\db.php

$host = 'localhost';
$db   = 'campusvoice';
$user = 'root';
$pass = '';

$dsn = "mysql:host=$host;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Connect without DB first
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Create DB if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db`");

    // === STUDENTS TABLE (YOUR EXACT SCHEMA) ===
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS students (
            id                 INT AUTO_INCREMENT PRIMARY KEY,
            name               VARCHAR(100) NOT NULL,
            email              VARCHAR(100) NOT NULL UNIQUE,
            student_id         VARCHAR(50) NOT NULL,
            contact            VARCHAR(20) NOT NULL,
            password           VARCHAR(255) NOT NULL,
            verification_token VARCHAR(255) NULL,
            token_expires      DATETIME NULL,
            verified           TINYINT(1) DEFAULT 0,
            created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    // === COMPLAINTS TABLE ===
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS complaints (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            student_id  INT NOT NULL,
            title       VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            category    VARCHAR(50) NOT NULL,
            location    VARCHAR(150),
            priority    ENUM('low','medium','high') DEFAULT 'medium',
            status      ENUM('pending','progress','resolved') DEFAULT 'pending',
            anonymous   TINYINT(1) DEFAULT 0,
            image       VARCHAR(255),
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    // === SAMPLE STUDENT (FOR TESTING) ===
    $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
    $stmt->execute(['john.doe@bugema.ac.ug']);
    if (!$stmt->fetch()) {
        $hash = password_hash('password123', PASSWORD_DEFAULT);
        $pdo->prepare("
            INSERT INTO students (name, email, student_id, contact, password, verified)
            VALUES (?, ?, ?, ?, ?, 1)
        ")->execute(['John Doe', 'john.doe@bugema.ac.ug', 'STU001', '0771234567', $hash]);
    }

} catch (PDOException $e) {
    // Show error only in development
    die("DB Connection Failed: " . $e->getMessage());
}
?>