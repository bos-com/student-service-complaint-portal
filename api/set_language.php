<?php
session_start();

// Enable CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Set language in session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Check both POST and JSON input
    if (isset($_POST['lang'])) {
        $lang = $_POST['lang'];
    } elseif (isset($data['lang'])) {
        $lang = $data['lang'];
    } else {
        echo json_encode(['success' => false, 'message' => 'No language specified']);
        exit;
    }
    
    // Validate language
    $validLanguages = ['en', 'sw', 'fr', 'lg'];
    if (in_array($lang, $validLanguages)) {
        $_SESSION['language'] = $lang;
        echo json_encode(['success' => true, 'message' => 'Language set to ' . $lang]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid language']);
    }
    exit;
}

// Get current language
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $lang = isset($_SESSION['language']) ? $_SESSION['language'] : 'en';
    echo json_encode(['success' => true, 'language' => $lang]);
    exit;
}
?>