<?php
// /admin/update_settings.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'No settings provided.']);
    exit;
}

// Use INSERT ... ON DUPLICATE KEY UPDATE for efficiency
$stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

foreach ($input as $key => $value) {
    // Convert boolean values from toggle switches to 'true'/'false' strings for storage
    if (is_bool($value)) {
        $value = $value ? 'true' : 'false';
    }
    $stmt->bind_param("ss", $key, $value);
    $stmt->execute();
}

$stmt->close();
$conn->close();

echo json_encode(['message' => 'Settings saved successfully.']);
?>
