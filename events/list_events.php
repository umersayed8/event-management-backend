<?php
header('Content-Type: application/json');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$query = "SELECT e.*, u.name as organizer_name FROM events e 
          JOIN users u ON e.organizer_id = u.id 
          ORDER BY e.date ASC";

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
        'created_at' => $row['created_at']
    ];
}

echo json_encode([
    'events' => $events,
    'count' => count($events)
]);

$conn->close();
?>

// events/event_details.php
<?php
header('Content-Type: application/json');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$eventId = $_GET['id'] ?? null;

if (!$eventId || !is_numeric($eventId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid event ID required']);
    exit;
}

$stmt = $conn->prepare("SELECT e.*, u.name as organizer_name, u.email as organizer_email 
                       FROM events e 
                       JOIN users u ON e.organizer_id = u.id 
                       WHERE e.id = ?");
$stmt->bind_param("i", $eventId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Event not found']);
    exit;
}

$event = $result->fetch_assoc();

// Get ticket count
$ticketStmt = $conn->prepare("SELECT SUM(quantity) as total_tickets FROM tickets WHERE event_id = ?");
$ticketStmt->bind_param("i", $eventId);
$ticketStmt->execute();
$ticketResult = $ticketStmt->get_result();
$ticketData = $ticketResult->fetch_assoc();

$event['tickets_sold'] = $ticketData['total_tickets'] ?? 0;

echo json_encode(['event' => $event]);

$stmt->close();
$ticketStmt->close();
$conn->close();
?>
