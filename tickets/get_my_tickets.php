<?php
// /tickets/get_my_tickets.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: Ensure the user is logged in.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'You must be logged in to view your tickets.']);
    exit;
}

$userId = $_SESSION['user_id'];

// 2. Fetch all confirmed tickets for the user, joining with the events table
//    to get event details. We order by the event date.
$stmt = $conn->prepare("
    SELECT
        t.id as ticket_id,
        e.title,
        e.date,
        e.location,
        e.banner_image
    FROM
        tickets t
    JOIN
        events e ON t.event_id = e.id
    WHERE
        t.user_id = ? AND t.status = 'confirmed'
    ORDER BY
        e.date DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$tickets = [];
while ($row = $result->fetch_assoc()) {
    $tickets[] = $row;
}

// 3. Return the list of tickets as JSON.
echo json_encode($tickets);

$stmt->close();
$conn->close();
?>