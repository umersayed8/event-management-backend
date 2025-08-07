<?php
// /user/change_password.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: User must be logged in.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];

$currentPassword = $input['current_password'] ?? '';
$newPassword = $input['new_password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

// 2. Validation
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    http_response_code(400);
    echo json_encode(['error' => 'All password fields are required.']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(['error' => 'New passwords do not match.']);
    exit;
}

if (strlen($newPassword) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 6 characters.']);
    exit;
}

// 3. Verify the user's current password
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || !password_verify($currentPassword, $user['password'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Incorrect current password.']);
    exit;
}
$stmt->close();

// 4. Hash the new password and update the database
$hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
$updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$updateStmt->bind_param("si", $hashedNewPassword, $userId);

if ($updateStmt->execute()) {
    echo json_encode(['message' => 'Password changed successfully.']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to change password.']);
}

$updateStmt->close();
$conn->close();
?>
