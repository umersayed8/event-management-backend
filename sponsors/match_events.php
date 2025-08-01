<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

requireRole('sponsor');

$sponsorId = getCurrentUserId();

// Get events that this sponsor hasn't already proposed to
$stmt = $conn->prepare("SELECT e.*, u.name as organizer_name 
                       FROM events e 
                       JOIN users u ON e.organizer_id = u.id 
                       LEFT JOIN sponsorships s ON e.id = s.event_id AND s.sponsor_id = ?
                       WHERE s.id IS NULL AND e.date > NOW()
                       ORDER BY e.date ASC");
$stmt->bind_param("i", $sponsorId);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'date' => $row['date'],
        'location' => $row['location'],
        'organizer_name' => $row['organizer_name']
    ];
}

echo json_encode([
    'available_events' => $events,
    'count' => count($events)
]);

$stmt->close();
$conn->close();
?>