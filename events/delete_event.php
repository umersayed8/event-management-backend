<?php
// /events/delete_event.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: Only logged-in organizers can delete events.
requireRole('organizer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$eventId = $input['event_id'] ?? null;
$organizerId = getCurrentUserId();

if (empty($eventId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Event ID is required.']);
    exit;
}

// 2. The "AND organizer_id = ?" clause is a critical security measure.
// It prevents one organizer from deleting another's event.
$stmt = $conn->prepare("DELETE FROM events WHERE id = ? AND organizer_id = ?");
$stmt->bind_param("ii", $eventId, $organizerId);

if ($stmt->execute()) {
    // 3. Check if a row was actually deleted.
    if ($stmt->affected_rows > 0) {
        echo json_encode(['message' => 'Event deleted successfully.']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Event not found or you do not have permission to delete it.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete event.']);
}

$stmt->close();
$conn->close();
?>