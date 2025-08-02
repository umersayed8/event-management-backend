<?php
// /events/get_all_public_events.php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once '../config/db.php';

// Get filter parameters from the URL
$category = $_GET['category'] ?? null;
$date_range = $_GET['date_range'] ?? null;
$location = $_GET['location'] ?? null;

$sql = "
    SELECT
        e.id, e.title, e.date, e.location, e.banner_image, e.category,
        u.name as organizer_name
    FROM events e
    JOIN users u ON e.organizer_id = u.id
";

$whereClauses = [];
$params = [];
$types = '';

// Add filters to the query if they exist
if ($category) {
    $whereClauses[] = "e.category = ?";
    $params[] = &$category;
    $types .= 's';
}
if ($location) {
    $whereClauses[] = "e.location = ?";
    $params[] = &$location;
    $types .= 's';
}
if ($date_range) {
    if ($date_range === 'next_30_days') {
        $whereClauses[] = "e.date BETWEEN CURDATE() AND CURDATE() + INTERVAL 30 DAY";
    } elseif ($date_range === 'next_3_months') {
        $whereClauses[] = "e.date BETWEEN CURDATE() AND CURDATE() + INTERVAL 3 MONTH";
    }
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(' AND ', $whereClauses);
}

$sql .= " ORDER BY e.date ASC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$events = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

echo json_encode($events);
$conn->close();
?>