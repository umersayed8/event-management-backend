<?php
header('Content-Type: application/json');
require_once '../config/session.php';

if (!isLoggedIn()) {
    http_response_code(400);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Destroy session
session_destroy();

echo json_encode(['message' => 'Logged out successfully']);
?>
