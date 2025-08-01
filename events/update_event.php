<?php
// /events/update_event.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// 1. Security: Ensure the user is a logged-in organizer
requireRole('organizer');

// 2. Get data from the POST request
$input = json_decode(file_get_contents('php://input'), true);
$organizerId = getCurrentUserId();

// Extract and validate data
$eventId = $input['id'] ?? null;
$title = trim($input['title'] ?? '');
$description = trim($input['description'] ?? '');
$date = $input['date'] ?? ''; // e.g., "2025-07-31"
$time = $input['time'] ?? ''; // e.g., "14:30"
$location = trim($input['location'] ?? '');
$ticketPrice = $input['ticket_price'] ?? 0;

if (empty($eventId) || empty($title) || empty($date) || empty($time) || empty($location)) {
    http_response_code(400);
    echo json_encode(['error' => 'All required fields must be filled.']);
    exit;
}

// Combine date and time into a single DATETIME string for the database
$dateTime = $date . ' ' . $time . ':00';

// 3. Update the database
// The "AND organizer_id = ?" is the critical security check to prevent
// one organizer from updating another's event.
$stmt = $conn->prepare(
    "UPDATE events SET title = ?, description = ?, date = ?, location = ?, ticket_price = ?
     WHERE id = ? AND organizer_id = ?"
);
$stmt->bind_param("ssssdii", $title, $description, $dateTime, $location, $ticketPrice, $eventId, $organizerId);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['message' => 'Event updated successfully!']);
    } else {
        // This can happen if the event ID is invalid or not owned by the user.
        http_response_code(404);
        echo json_encode(['error' => 'Event not found or no changes were made.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update event.']);
}

$stmt->close();
$conn->close();
?>