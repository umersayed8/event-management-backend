<?php
// /sponsorships/get_sponsor_events.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: Only sponsors can view their event calendar.
requireRole('sponsor');

$sponsorId = getCurrentUserId();

// 2. Fetch all events associated with this sponsor
$stmt = $conn->prepare("
    SELECT
        e.title,
        e.date,
        e.category,
        s.status
    FROM sponsorships s
    JOIN events e ON s.event_id = e.id
    WHERE s.sponsor_id = ?
");
$stmt->bind_param("i", $sponsorId);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

// 3. Return the list of events as JSON.
echo json_encode($events);

$stmt->close();
$conn->close();
?>