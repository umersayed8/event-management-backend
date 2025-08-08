<?php
// /admin/get_sponsor_details.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// Security: Only admins can view these details.
requireRole('admin');

$sponsorId = $_GET['id'] ?? null;

if (!$sponsorId) {
    http_response_code(400);
    echo json_encode(['error' => 'Sponsor ID is required.']);
    exit;
}

// 1. Get the sponsor's basic information (name, email).
$userStmt = $conn->prepare("SELECT name, email, created_at FROM users WHERE id = ? AND role = 'sponsor'");
$userStmt->bind_param("i", $sponsorId);
$userStmt->execute();
$sponsorInfo = $userStmt->get_result()->fetch_assoc();

if (!$sponsorInfo) {
    http_response_code(404);
    echo json_encode(['error' => 'Sponsor not found.']);
    exit;
}

// 2. Get the history of all events this sponsor has engaged with.
$sponsorships = [];
$sponsorshipStmt = $conn->prepare("
    SELECT
        e.title as event_title,
        e.date as event_date,
        s.status
    FROM
        sponsorships s
    JOIN
        events e ON s.event_id = e.id
    WHERE
        s.sponsor_id = ?
    ORDER BY
        e.date DESC
");
$sponsorshipStmt->bind_param("i", $sponsorId);
$sponsorshipStmt->execute();
$result = $sponsorshipStmt->get_result();
while ($row = $result->fetch_assoc()) {
    $sponsorships[] = $row;
}

// 3. Combine all data into a single response.
echo json_encode([
    'sponsor_info' => $sponsorInfo,
    'sponsorship_history' => $sponsorships
]);

$conn->close();
?>
