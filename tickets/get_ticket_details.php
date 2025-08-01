<?php
// /tickets/get_ticket_details.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: User must be logged in to view their ticket.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'You must be logged in to view ticket details.']);
    exit;
}

$ticketId = $_GET['ticket_id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$ticketId) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Ticket ID is required.']);
    exit;
}

// 2. Prepare a query that joins tickets and events.
// The "AND t.user_id = ?" is the crucial security check to ensure
// a user can only see their own tickets.
// In /tickets/get_ticket_details.php

$stmt = $conn->prepare("
    SELECT
        t.quantity,
        t.amount_paid,
        e.title AS event_title,
        e.location AS event_location,
        e.date AS event_date,
        e.banner_image
    FROM
        tickets t
    JOIN
        events e ON t.event_id = e.id
    WHERE
        t.id = ? AND t.user_id = ?
");
$stmt->bind_param("ii", $ticketId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$ticket = $result->fetch_assoc();

if ($ticket) {
    // 3. If ticket is found, add the user's name and send the data.
    $ticket['ticket_holder_name'] = $_SESSION['name']; // Add user's name from session
    echo json_encode($ticket);
}else {
    // 4. If not found, send a 404 error.
    http_response_code(404);
    echo json_encode(['error' => 'Ticket not found or you do not have permission to view it.']);
}

$stmt->close();
$conn->close();
?>