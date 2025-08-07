<?php
// /events/get_dashboard_events.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

$userId = $_SESSION['user_id'] ?? null;
$filter = $_GET['filter'] ?? 'upcoming'; // 'upcoming' or 'past'
$searchTerm = $_GET['search'] ?? '';

$sql = "
    SELECT
        e.id, e.title, e.date, e.location, e.banner_image, e.category,
        CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END as is_favorited
    FROM
        events e
    LEFT JOIN
        favorite_events f ON e.id = f.event_id AND f.user_id = ?
";

$whereClauses = [];
$params = [$userId];
$types = 'i';

// CORRECTED: This logic now correctly handles upcoming vs. past
if ($filter === 'upcoming') {
    $whereClauses[] = "e.date >= CURDATE()";
} elseif ($filter === 'past') {
    $whereClauses[] = "e.date < CURDATE()";
}

if (!empty($searchTerm)) {
    $whereClauses[] = "(e.title LIKE ? OR e.location LIKE ?)";
    $likeTerm = "%" . $searchTerm . "%";
    $params[] = $likeTerm;
    $params[] = $likeTerm;
    $types .= 'ss';
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(' AND ', $whereClauses);
}

// CORRECTED: Sort order changes based on the filter
$sql .= " ORDER BY e.date " . ($filter === 'upcoming' ? 'ASC' : 'DESC');

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $row['is_favorited'] = (bool)$row['is_favorited'];
    $events[] = $row;
}

echo json_encode($events);

$stmt->close();
$conn->close();
?>