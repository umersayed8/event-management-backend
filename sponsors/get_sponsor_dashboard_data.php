<?php
// /sponsorships/get_sponsor_dashboard_data.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: Only users with the 'sponsor' role can access this.
requireRole('sponsor');

$sponsorId = getCurrentUserId();
$sponsorName = $_SESSION['name'];

// 2. Get total number of sponsorships (pending, accepted, etc.)
$sponsorshipsResult = $conn->prepare("SELECT COUNT(id) as total_sponsorships FROM sponsorships WHERE sponsor_id = ?");
$sponsorshipsResult->bind_param("i", $sponsorId);
$sponsorshipsResult->execute();
$totalSponsorships = $sponsorshipsResult->get_result()->fetch_assoc()['total_sponsorships'];

// 3. Get total unique events sponsored
$eventsResult = $conn->prepare("SELECT COUNT(DISTINCT event_id) as total_events FROM sponsorships WHERE sponsor_id = ?");
$eventsResult->bind_param("i", $sponsorId);
$eventsResult->execute();
$totalEvents = $eventsResult->get_result()->fetch_assoc()['total_events'];

// 4. Get upcoming events that this sponsor is involved with
$upcomingEvents = [];
$upcomingEventsResult = $conn->prepare("
    SELECT e.title, e.date, e.location, s.status
    FROM sponsorships s
    JOIN events e ON s.event_id = e.id
    WHERE s.sponsor_id = ? AND e.date >= CURDATE()
    ORDER BY e.date ASC
    LIMIT 5
");
$upcomingEventsResult->bind_param("i", $sponsorId);
$upcomingEventsResult->execute();
$result = $upcomingEventsResult->get_result();
while($row = $result->fetch_assoc()) {
    $upcomingEvents[] = $row;
}

// Note: "Revenue Generated" is not tracked in the current database schema.
// For this demonstration, we'll create a placeholder value.
$revenueGenerated = $totalSponsorships * 2500; // Placeholder calculation

// 5. Combine all data into a single response
echo json_encode([
    'sponsor_name' => $sponsorName,
    'stats' => [
        'total_sponsorships' => (int)$totalSponsorships,
        'total_events' => (int)$totalEvents,
        'upcoming_events_count' => count($upcomingEvents),
        'revenue_generated' => (float)$revenueGenerated
    ],
    'upcoming_events_list' => $upcomingEvents
]);

$conn->close();
?>