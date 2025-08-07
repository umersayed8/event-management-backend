<?php
// /user/update_profile.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in to update your profile.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');

if (empty($name) || empty($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Name and email cannot be empty.']);
    exit;
}

// Optional: Check if the new email is already taken by another user
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->bind_param("si", $email, $userId);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(['error' => 'This email address is already in use.']);
    exit;
}
$stmt->close();

// Update the user's information
$updateStmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
$updateStmt->bind_param("ssi", $name, $email, $userId);

if ($updateStmt->execute()) {
    // Update the session variables as well
    $_SESSION['name'] = $name;
    $_SESSION['email'] = $email;
    echo json_encode(['message' => 'Profile updated successfully.']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update profile.']);
}

$updateStmt->close();
$conn->close();
?>
