<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

requireRole('admin');

$query = "SELECT e.*, u.name as organizer_name, 
          (SELECT COUNT(*) FROM tickets t WHERE t.event_id = e.id) as tickets_count,
          (SELECT SUM(t.quantity) FROM tickets t WHERE t.event_id = e.id) as total_tickets_sold,
          (SELECT COUNT(*) FROM sponsorships s WHERE s.event_id = e.id) as sponsorship_count
          FROM events e 
          JOIN users u ON e.organizer_id = u.id 
          ORDER BY e.created_at DESC";

$result = $conn->query($query);

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'date' => $row['date'],
        'location' => $row['location'],
        'ticket_price' => $row['ticket_price'],
        'organizer_name' => $row['organizer_name'],
        'tickets_count' => $row['tickets_count'],
        'total_tickets_sold' => $row['total_tickets_sold'] ?? 0,
        'sponsorship_count' => $row['sponsorship_count'],
        'created_at' => $row['created_at']
    ];
}

echo json_encode([
    'events' => $events,
    'count' => count($events)
]);

$conn->close();
?>