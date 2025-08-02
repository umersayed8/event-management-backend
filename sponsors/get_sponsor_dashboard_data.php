<?php
// /sponsorships/get_sponsor_dashboard_data.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: Only users with the 'sponsor' role can access this.
requireRole('sponsor');

$sponsorId = getCurrentUserId();
$sponsorName = $_SESSION['name'];

// 2. Get total number of proposals sent (all statuses)
$proposalsResult = $conn->prepare("SELECT COUNT(id) as total_proposals FROM sponsorships WHERE sponsor_id = ?");
$proposalsResult->bind_param("i", $sponsorId);
$proposalsResult->execute();
$totalProposals = $proposalsResult->get_result()->fetch_assoc()['total_proposals'];

// 3. NEW: Get total number of ACCEPTED sponsorships
$acceptedResult = $conn->prepare("SELECT COUNT(id) as accepted_sponsorships FROM sponsorships WHERE sponsor_id = ? AND status = 'accepted'");
$acceptedResult->bind_param("i", $sponsorId);
$acceptedResult->execute();
$acceptedSponsorships = $acceptedResult->get_result()->fetch_assoc()['accepted_sponsorships'];

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

// Placeholder for revenue
$revenueGenerated = $acceptedSponsorships * 2500;

// 5. Combine all data into a single response with the new fields
echo json_encode([
    'sponsor_name' => $sponsorName,
    'stats' => [
        'proposals_sent' => (int)$totalProposals,
        'accepted_sponsorships' => (int)$acceptedSponsorships,
        'upcoming_events_count' => count($upcomingEvents),
        'revenue_generated' => (float)$revenueGenerated
    ],
    'upcoming_events_list' => $upcomingEvents
]);

$conn->close();
?>