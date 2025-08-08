<?php
// /admin/get_user_details.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// Security: Only admins can view user details.
requireRole('admin');

$userId = $_GET['id'] ?? null;

if (!$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID is required.']);
    exit;
}

// 1. Get the user's basic information.
$userStmt = $conn->prepare("SELECT id, name, email, role, created_at, status FROM users WHERE id = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userInfo = $userStmt->get_result()->fetch_assoc();

if (!$userInfo) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found.']);
    exit;
}

// 2. Get activity history based on the user's role.
$activity = [];
$role = $userInfo['role'];

if ($role === 'organizer') {
    $activityStmt = $conn->prepare("SELECT id, title, date, location FROM events WHERE organizer_id = ? ORDER BY date DESC");
} elseif ($role === 'sponsor') {
    $activityStmt = $conn->prepare("SELECT e.title, e.date, s.status FROM sponsorships s JOIN events e ON s.event_id = e.id WHERE s.sponsor_id = ? ORDER BY e.date DESC");
} elseif ($role === 'ticket_buyer') {
    $activityStmt = $conn->prepare("SELECT e.title, e.date, t.quantity, t.amount_paid FROM tickets t JOIN events e ON t.event_id = e.id WHERE t.user_id = ? AND t.status = 'confirmed' ORDER BY e.date DESC");
}

if (isset($activityStmt)) {
    $activityStmt->bind_param("i", $userId);
    $activityStmt->execute();
    $result = $activityStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $activity[] = $row;
    }
    $activityStmt->close();
}


// 3. Combine all data into a single response.
echo json_encode([
    'user_info' => $userInfo,
    'activity_history' => $activity
]);

$conn->close();
?>
