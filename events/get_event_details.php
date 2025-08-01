<?php
// /events/get_event_details.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: Ensure the user is a logged-in organizer.
requireRole('organizer');

// 2. Get Event ID from URL and Organizer ID from Session
$eventId = $_GET['id'] ?? null;
$organizerId = getCurrentUserId();

if (!$eventId) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Event ID is required.']);
    exit;
}

// 3. Fetch event from DB, ensuring it belongs to the logged-in organizer
// The "AND organizer_id = ?" is a crucial security check.
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ? AND organizer_id = ?");
$stmt->bind_param("ii", $eventId, $organizerId);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if ($event) {
    // Event found and owned by the organizer
    echo json_encode($event);
} else {
    // Event not found or not owned by this organizer
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Event not found or you do not have permission to edit it.']);
}

$stmt->close();
$conn->close();
?>