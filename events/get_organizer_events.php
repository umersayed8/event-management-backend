<?php
// /events/get_organizer_events.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Secure the Endpoint
requireRole('organizer');

// 2. Get the Organizer's ID and Name from the Session
$organizerId = getCurrentUserId();
$organizerName = $_SESSION['name'];

// 3. Prepare and Execute the Corrected Database Query
// This query now uses the correct columns: `quantity` and `amount_paid`
// from your `tickets` table.
$stmt = $conn->prepare("
    SELECT
        e.id,
        e.title,
        e.date,
        e.location,
        e.ticket_price,
        -- Correctly sum the quantity of all tickets sold for the event
        (SELECT SUM(t.quantity) FROM tickets t WHERE t.event_id = e.id) as tickets_sold,
        -- Correctly sum the amount paid for all tickets for the event
        (SELECT SUM(t.amount_paid) FROM tickets t WHERE t.event_id = e.id) as revenue
    FROM
        events e
    WHERE
        e.organizer_id = ?
    ORDER BY
        e.date DESC
");

$stmt->bind_param("i", $organizerId);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    // Handle cases where an event has no tickets sold yet (SUM returns NULL)
    $row['tickets_sold'] = $row['tickets_sold'] ?? 0;
    $row['revenue'] = $row['revenue'] ?? 0.00;
    $events[] = $row;
}

// 4. Send the JSON Response
echo json_encode([
    'organizer_name' => $organizerName,
    'events' => $events
]);

$stmt->close();
$conn->close();

?>