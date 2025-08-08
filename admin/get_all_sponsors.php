<?php
// /admin/get_all_sponsors.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

requireRole('admin');

$searchTerm = $_GET['search'] ?? '';

// This query fetches all users who are sponsors.
$sql = "SELECT id, name, email FROM users WHERE role = 'sponsor'";
$params = [];
$types = '';

if (!empty($searchTerm)) {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $likeTerm = "%" . $searchTerm . "%";
    $params[] = &$likeTerm;
    $params[] = &$likeTerm;
    $types .= "ss";
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$sponsors = [];
while ($row = $result->fetch_assoc()) {
    $sponsorId = $row['id'];

    // For each sponsor, get their total number of accepted sponsorships.
    $sponsorshipCountStmt = $conn->prepare("SELECT COUNT(id) as count FROM sponsorships WHERE sponsor_id = ? AND status = 'accepted'");
    $sponsorshipCountStmt->bind_param("i", $sponsorId);
    $sponsorshipCountStmt->execute();
    $count = $sponsorshipCountStmt->get_result()->fetch_assoc()['count'];

    // Determine sponsorship level based on the count.
    if ($count >= 5) {
        $row['level'] = 'Gold';
    } elseif ($count >= 2) {
        $row['level'] = 'Silver';
    } else {
        $row['level'] = 'Bronze';
    }

    // Get the name of the most recent event they sponsored.
    $latestEventStmt = $conn->prepare("
        SELECT e.title FROM sponsorships s
        JOIN events e ON s.event_id = e.id
        WHERE s.sponsor_id = ? AND s.status = 'accepted'
        ORDER BY e.date DESC LIMIT 1
    ");
    $latestEventStmt->bind_param("i", $sponsorId);
    $latestEventStmt->execute();
    $latestEvent = $latestEventStmt->get_result()->fetch_assoc();
    $row['campaign'] = $latestEvent['title'] ?? 'N/A';

    $sponsors[] = $row;
}

echo json_encode($sponsors);

$conn->close();
?>
