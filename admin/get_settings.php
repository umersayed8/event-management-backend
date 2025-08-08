<?php
// /admin/get_settings.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

requireRole('admin');

$result = $conn->query("SELECT setting_key, setting_value FROM settings");

$settings = [];
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

echo json_encode($settings);

$conn->close();
?>
