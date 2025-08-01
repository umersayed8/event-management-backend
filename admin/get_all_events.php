<?php
// /admin/get_all_events.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: Only admins can access this list.
requireRole('admin');

// 2. Handle the search query
$searchTerm = $_GET['search'] ?? '';

// 3. The query joins events with users to get the organizer's name
$sql = "
    SELECT
        e.id,
        e.title,
        e.date,
        e.location,
        u.name as organizer_name
    FROM
        events e
    JOIN
        users u ON e.organizer_id = u.id
";

$params = [];
$types = '';

if (!empty($searchTerm)) {
    // 4. Add a WHERE clause to filter by event title, organizer name, or location
    $sql .= " WHERE e.title LIKE ? OR u.name LIKE ? OR e.location LIKE ?";
    $likeTerm = "%" . $searchTerm . "%";
    $params[] = &$likeTerm;
    $params[] = &$likeTerm;
    $params[] = &$likeTerm;
    $types .= "sss";
}

$sql .= " ORDER BY e.date DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

// 5. Return the JSON response
echo json_encode($events);

$stmt->close();
$conn->close();
?>