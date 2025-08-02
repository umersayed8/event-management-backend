<?php
// /sponsorships/update_sponsorship_status.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: Only organizers can update a status.
requireRole('organizer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$organizerId = getCurrentUserId();

$sponsorshipId = $input['sponsorship_id'] ?? null;
$newStatus = $input['status'] ?? '';

// 2. Validate the input.
if (empty($sponsorshipId) || !in_array($newStatus, ['accepted', 'rejected'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input.']);
    exit;
}

// 3. Prepare the UPDATE query with a crucial security check.
// This query joins with the events table to ensure the `organizer_id`
// matches the logged-in user, preventing them from modifying proposals
// for events they do not own.
$stmt = $conn->prepare("
    UPDATE sponsorships s
    JOIN events e ON s.event_id = e.id
    SET s.status = ?
    WHERE s.id = ? AND e.organizer_id = ?
");
$stmt->bind_param("sii", $newStatus, $sponsorshipId, $organizerId);

if ($stmt->execute()) {
    // 4. Check if a row was actually affected.
    if ($stmt->affected_rows > 0) {
        echo json_encode(['message' => 'Proposal status updated successfully.']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Proposal not found or you do not have permission to modify it.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update proposal status.']);
}

$stmt->close();
$conn->close();
?>