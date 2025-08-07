<?php
// /user/get_profile.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// Security: User must be logged in to view their profile.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in to view your profile.']);
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT name, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    echo json_encode($user);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'User not found.']);
}

$stmt->close();
$conn->close();
?>
