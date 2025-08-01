<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

requireLogin();

$userId = getCurrentUserId();

$stmt = $conn->prepare("SELECT t.*, e.title, e.date, e.location 
                       FROM tickets t 
                       JOIN events e ON t.event_id = e.id 
                       WHERE t.user_id = ? 
                       ORDER BY t.created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$tickets = [];
while ($row = $result->fetch_assoc()) {
    $tickets[] = [
        'ticket_id' => $row['id'],
        'event_title' => $row['title'],
        'event_date' => $row['date'],
        'event_location' => $row['location'],
        'quantity' => $row['quantity'],
        'amount_paid' => $row['amount_paid'],
        'booked_at' => $row['created_at']
    ];
}

echo json_encode([
    'tickets' => $tickets,
    'count' => count($tickets)
]);

$stmt->close();
$conn->close();
?>
