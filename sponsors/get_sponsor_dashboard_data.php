<?php
// /sponsorships/get_sponsor_dashboard_data.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

requireRole('sponsor');

$sponsorId = getCurrentUserId();
$sponsorName = $_SESSION['name'];

// --- STATS & EVENT LISTS ---
$proposalsResult = $conn->prepare("SELECT COUNT(id) as total_proposals FROM sponsorships WHERE sponsor_id = ?");
$proposalsResult->bind_param("i", $sponsorId);
$proposalsResult->execute();
$totalProposals = $proposalsResult->get_result()->fetch_assoc()['total_proposals'];

$acceptedResult = $conn->prepare("SELECT COUNT(id) as accepted_sponsorships FROM sponsorships WHERE sponsor_id = ? AND status = 'accepted'");
$acceptedResult->bind_param("i", $sponsorId);
$acceptedResult->execute();
$acceptedSponsorships = $acceptedResult->get_result()->fetch_assoc()['accepted_sponsorships'];

// Fetch UPCOMING sponsored events
$upcomingEvents = [];
$upcomingEventsResult = $conn->prepare("
    SELECT e.id as event_id, e.title, e.date, e.location, s.status
    FROM sponsorships s JOIN events e ON s.event_id = e.id
    WHERE s.sponsor_id = ? AND e.date >= CURDATE() AND s.status = 'accepted'
    ORDER BY e.date ASC LIMIT 5
");
$upcomingEventsResult->bind_param("i", $sponsorId);
$upcomingEventsResult->execute();
$result = $upcomingEventsResult->get_result();
while($row = $result->fetch_assoc()) {
    $upcomingEvents[] = $row;
}

// NEW: Fetch PAST sponsored events
$pastEvents = [];
$pastEventsResult = $conn->prepare("
    SELECT e.id as event_id, e.title, e.date, e.location, s.status
    FROM sponsorships s JOIN events e ON s.event_id = e.id
    WHERE s.sponsor_id = ? AND e.date < CURDATE() AND s.status = 'accepted'
    ORDER BY e.date DESC LIMIT 5
");
$pastEventsResult->bind_param("i", $sponsorId);
$pastEventsResult->execute();
$result = $pastEventsResult->get_result();
while($row = $result->fetch_assoc()) {
    $pastEvents[] = $row;
}

// --- CHART DATA ---
// NEW: Data for Sponsorship Growth Chart (last 6 months)
$sponsorshipGrowth = [];
$growthResult = $conn->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as count
    FROM sponsorships
    WHERE sponsor_id = ? AND status = 'accepted' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month ORDER BY month ASC
");
$growthResult->bind_param("i", $sponsorId);
$growthResult->execute();
$result = $growthResult->get_result();
while($row = $result->fetch_assoc()) {
    $sponsorshipGrowth[] = $row;
}

// NEW: Data for Sponsorships by Category Chart
$sponsorshipByCategory = [];
$categoryResult = $conn->prepare("
    SELECT e.category, COUNT(s.id) as count
    FROM sponsorships s JOIN events e ON s.event_id = e.id
    WHERE s.sponsor_id = ? AND s.status = 'accepted' AND e.category IS NOT NULL
    GROUP BY e.category ORDER BY count DESC
");
$categoryResult->bind_param("i", $sponsorId);
$categoryResult->execute();
$result = $categoryResult->get_result();
while($row = $result->fetch_assoc()) {
    $sponsorshipByCategory[] = $row;
}

// --- FINAL JSON RESPONSE ---
echo json_encode([
    'sponsor_name' => $sponsorName,
    'stats' => [
        'proposals_sent' => (int)$totalProposals,
        'accepted_sponsorships' => (int)$acceptedSponsorships,
        'upcoming_events_count' => count($upcomingEvents)
    ],
    'upcoming_events_list' => $upcomingEvents,
    'past_events_list' => $pastEvents, // Added past events
    'chart_data' => [
        'sponsorship_growth' => $sponsorshipGrowth,
        'sponsorship_by_category' => $sponsorshipByCategory
    ]
]);

$conn->close();
?>
