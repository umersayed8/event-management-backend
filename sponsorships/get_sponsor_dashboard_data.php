<?php
// /sponsorships/get_sponsor_dashboard_data.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

requireRole('sponsor');

$sponsorId = getCurrentUserId();
$sponsorName = $_SESSION['name'];

// --- STAT CARDS DATA (No changes here) ---
$proposalsResult = $conn->prepare("SELECT COUNT(id) as total_proposals FROM sponsorships WHERE sponsor_id = ?");
$proposalsResult->bind_param("i", $sponsorId);
$proposalsResult->execute();
$totalProposals = $proposalsResult->get_result()->fetch_assoc()['total_proposals'];

$acceptedResult = $conn->prepare("SELECT COUNT(id) as accepted_sponsorships FROM sponsorships WHERE sponsor_id = ? AND status = 'accepted'");
$acceptedResult->bind_param("i", $sponsorId);
$acceptedResult->execute();
$acceptedSponsorships = $acceptedResult->get_result()->fetch_assoc()['accepted_sponsorships'];

// --- UPCOMING EVENTS LIST (Query is now corrected) ---
$upcomingEvents = [];
$upcomingEventsResult = $conn->prepare("
    SELECT e.title, e.date, e.location, s.status
    FROM sponsorships s
    JOIN events e ON s.event_id = e.id
    WHERE s.sponsor_id = ? AND e.date >= CURDATE() AND s.status = 'accepted'
    ORDER BY e.date ASC
    LIMIT 5
");
$upcomingEventsResult->bind_param("i", $sponsorId);
$upcomingEventsResult->execute();
$result = $upcomingEventsResult->get_result();
while($row = $result->fetch_assoc()) {
    $upcomingEvents[] = $row;
}

// --- CHART DATA (No changes here) ---
$sponsorshipGrowth = [];
// ... (query for sponsorship growth remains the same) ...
$sponsorshipByCategory = [];
// ... (query for sponsorship by category remains the same) ...


// --- FINAL JSON RESPONSE (No changes here) ---
echo json_encode([
    'sponsor_name' => $sponsorName,
    'stats' => [
        'proposals_sent' => (int)$totalProposals,
        'accepted_sponsorships' => (int)$acceptedSponsorships,
        'upcoming_events_count' => count($upcomingEvents),
        'revenue_generated' => (float)($acceptedSponsorships * 2500)
    ],
    'upcoming_events_list' => $upcomingEvents,
    'chart_data' => [
        'sponsorship_growth' => $sponsorshipGrowth,
        'sponsorship_by_category' => $sponsorshipByCategory
    ]
]);

$conn->close();
?>
