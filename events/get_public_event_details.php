<?php
// /events/get_public_event_details.php

header('Access-Control-Allow-Origin: *'); // Allows public access
header('Content-Type: application/json');
require_once '../config/db.php';

// 1. Get Event ID from the URL
$eventId = $_GET['id'] ?? null;

if (!$eventId) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Event ID is required.']);
    exit;
}

// 2. Fetch the event from the database
// We join with the users table to get the organizer's name.
$stmt = $conn->prepare("
    SELECT
        e.id,
        e.title,
        e.description,
        e.date,
        e.location,
        e.ticket_price,
        e.banner_image,
        u.name as organizer_name
    FROM
        events e
    JOIN
        users u ON e.organizer_id = u.id
    WHERE
        e.id = ?
");
$stmt->bind_param("i", $eventId);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if ($event) {
    // 3. If event is found, send its data
    echo json_encode($event);
} else {
    // 4. If not found, send a 404 error
    http_response_code(404);
    echo json_encode(['error' => 'Event not found.']);
}

$stmt->close();
$conn->close();

?>