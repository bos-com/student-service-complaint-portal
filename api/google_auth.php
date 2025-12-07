<?php
session_start();
require_once '../config/database.php';
require_once '../vendor/autoload.php'; // Google API

$GOOGLE_CLIENT_ID = "1010102242596-6976onteqlqigcmi0rnnc9f4t0908etj.apps.googleusercontent.com";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);
    $credential = $data['credential'] ?? '';

    if (!$credential) {
        echo json_encode(['success' => false, 'message' => 'Missing credential']);
        exit;
    }

    // Verify Google token
    $client = new Google_Client(['client_id' => $GOOGLE_CLIENT_ID]);
    $payload = $client->verifyIdToken($credential);

    if ($payload) {
        $email = $payload['email'];
        $name = $payload['name'] ?? '';
        $google_id = $payload['sub'];

        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Create user
            $stmt = $pdo->prepare("
                INSERT INTO students (name, email, google_id, verified)
                VALUES (?, ?, ?, 1)
            ");
            $stmt->execute([$name, $email, $google_id]);

            // Fetch new user
            $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
        }

        $_SESSION['student'] = $user;

        echo json_encode([
            'success' => true,
            'redirect' => '/campusvoice/feed.php'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
    }
}
?>
