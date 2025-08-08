<?php
// /sponsorships/get_sponsor_dashboard_data.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

requireRole('sponsor');

$sponsorId = getCurrentUserId();
$sponsorName = $_SESSION['name'];

// --- STATS QUERIES ---
$proposalsResult = $conn->prepare("SELECT COUNT(id) as total_proposals FROM sponsorships WHERE sponsor_id = ?");
$proposalsResult->bind_param("i", $sponsorId);
$proposalsResult->execute();
$totalProposals = $proposalsResult->get_result()->fetch_assoc()['total_proposals'];
$proposalsResult->close();

$acceptedResult = $conn->prepare("SELECT COUNT(id) as accepted_sponsorships FROM sponsorships WHERE sponsor_id = ? AND status = 'accepted'");
$acceptedResult->bind_param("i", $sponsorId);
$acceptedResult->execute();
$acceptedSponsorships = $acceptedResult->get_result()->fetch_assoc()['accepted_sponsorships'];
$acceptedResult->close();

// --- UPCOMING EVENTS LIST ---
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
$upcomingEventsResult->close();

// --- SUGGESTED EVENTS ---
$suggestedEvents = [];
$favCategories = [];
$catStmt = $conn->prepare("SELECT DISTINCT e.category FROM sponsorships s JOIN events e ON s.event_id = e.id WHERE s.sponsor_id = ? AND s.status = 'accepted' AND e.category IS NOT NULL");
$catStmt->bind_param("i", $sponsorId);
$catStmt->execute();
$catResult = $catStmt->get_result();
while($row = $catResult->fetch_assoc()) {
    $favCategories[] = $row['category'];
}
$catStmt->close();

if (!empty($favCategories)) {
    $placeholders = implode(',', array_fill(0, count($favCategories), '?'));
    $types = str_repeat('s', count($favCategories)) . 'i';
    $params = $favCategories;
    $params[] = $sponsorId;
    
    $suggestStmt = $conn->prepare("
        SELECT id, title, date, location, banner_image, category FROM events
        WHERE category IN ($placeholders)
        AND id NOT IN (SELECT event_id FROM sponsorships WHERE sponsor_id = ?)
        AND date >= CURDATE()
        ORDER BY date ASC
        LIMIT 4
    ");
    $suggestStmt->bind_param($types, ...$params);
    $suggestStmt->execute();
    $suggestResult = $suggestStmt->get_result();
    while($row = $suggestResult->fetch_assoc()) {
        $suggestedEvents[] = $row;
    }
    $suggestStmt->close();
}

// --- CHART DATA ---
$sponsorshipGrowth = [];
$growthResult = $conn->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as count
    FROM sponsorships
    WHERE sponsor_id = ? AND status = 'accepted' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
");
$growthResult->bind_param("i", $sponsorId);
$growthResult->execute();
$result = $growthResult->get_result();
while($row = $result->fetch_assoc()) {
    $sponsorshipGrowth[] = $row;
}
$growthResult->close();

$sponsorshipByCategory = [];
$categoryResult = $conn->prepare("
    SELECT e.category, COUNT(s.id) as count
    FROM sponsorships s
    JOIN events e ON s.event_id = e.id
    WHERE s.sponsor_id = ? AND s.status = 'accepted' AND e.category IS NOT NULL
    GROUP BY e.category
    ORDER BY count DESC
");
$categoryResult->bind_param("i", $sponsorId);
$categoryResult->execute();
$result = $categoryResult->get_result();
while($row = $result->fetch_assoc()) {
    $sponsorshipByCategory[] = $row;
}
$categoryResult->close();

// --- FINAL JSON RESPONSE ---
echo json_encode([
    'sponsor_name' => $sponsorName,
    'stats' => [
        'proposals_sent' => (int)$totalProposals,
        'accepted_sponsorships' => (int)$acceptedSponsorships,
        'upcoming_events_count' => count($upcomingEvents),
        'revenue_generated' => (float)($acceptedSponsorships * 2500) // Placeholder
    ],
    'upcoming_events_list' => $upcomingEvents,
    'suggested_events' => $suggestedEvents,
    'chart_data' => [
        'sponsorship_growth' => $sponsorshipGrowth,
        'sponsorship_by_category' => $sponsorshipByCategory
    ]
]);

$conn->close();
?>
