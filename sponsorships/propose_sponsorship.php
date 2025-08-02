<?php
// /sponsorships/propose_sponsorship.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: Only users with the 'sponsor' role can make a proposal.
requireRole('sponsor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$sponsorId = getCurrentUserId();
$eventId = $input['event_id'] ?? null;
$proposalText = trim($input['proposal_text'] ?? '');

if (empty($eventId) || empty($proposalText)) {
    http_response_code(400);
    echo json_encode(['error' => 'Event ID and proposal text are required.']);
    exit;
}

// 2. Check if a proposal already exists to prevent duplicates.
$stmt = $conn->prepare("SELECT id FROM sponsorships WHERE event_id = ? AND sponsor_id = ?");
$stmt->bind_param("ii", $eventId, $sponsorId);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(['error' => 'You have already submitted a proposal for this event.']);
    exit;
}

// 3. Insert the new sponsorship proposal into the database.
$stmt = $conn->prepare("INSERT INTO sponsorships (event_id, sponsor_id, proposal_text) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $eventId, $sponsorId, $proposalText);

if ($stmt->execute()) {
    echo json_encode(['message' => 'Sponsorship proposal submitted successfully!']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit proposal.']);
}

$stmt->close();
$conn->close();
?>