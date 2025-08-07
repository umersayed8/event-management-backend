<?php
// /events/get_organizer_events.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

requireRole('organizer');

$organizerId = getCurrentUserId();

// 1. Get the list of events with calculated stats
$stmt = $conn->prepare("
    SELECT
        e.id,
        e.title,
        e.date,
        e.location,
        (SELECT COALESCE(SUM(t.quantity), 0) FROM tickets t WHERE t.event_id = e.id AND t.status = 'confirmed') as tickets_sold,
        (SELECT COALESCE(SUM(t.amount_paid), 0) FROM tickets t WHERE t.event_id = e.id AND t.status = 'confirmed') as revenue,
        -- This subquery determines the sponsorship status based on proposals
        (SELECT
            CASE
                WHEN COUNT(CASE WHEN s.status = 'accepted' THEN 1 END) > 0 THEN 'Sponsored'
                WHEN COUNT(CASE WHEN s.status = 'pending' THEN 1 END) > 0 THEN 'Pending'
                ELSE 'No Proposals'
            END
        FROM sponsorships s WHERE s.event_id = e.id) as sponsor_status
    FROM
        events e
    WHERE
        e.organizer_id = ?
    ORDER BY
        e.date DESC
");
$stmt->bind_param("i", $organizerId);
$stmt->execute();
$eventsResult = $stmt->get_result();
$events = [];
while ($row = $eventsResult->fetch_assoc()) {
    $events[] = $row;
}
$stmt->close();

// 2. Get the total number of unique accepted sponsors for the stat card
$sponsorsStmt = $conn->prepare("
    SELECT COUNT(DISTINCT s.sponsor_id) as total_sponsors
    FROM sponsorships s
    JOIN events e ON s.event_id = e.id
    WHERE e.organizer_id = ? AND s.status = 'accepted'
");
$sponsorsStmt->bind_param("i", $organizerId);
$sponsorsStmt->execute();
$totalSponsors = $sponsorsStmt->get_result()->fetch_assoc()['total_sponsors'];
$sponsorsStmt->close();

// 3. Return everything in a structured JSON object
echo json_encode([
    'events' => $events,
    'organizer_name' => $_SESSION['name'], // Also send organizer name
    'stats' => [
        'total_sponsors' => (int)$totalSponsors
    ]
]);

$conn->close();
?>