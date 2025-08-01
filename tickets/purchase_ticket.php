<?php
// /tickets/purchase_ticket.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: Only logged-in users can buy tickets.
// We check if a session exists, but don't restrict to a specific role.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'You must be logged in to purchase tickets.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];
$eventId = $input['eventId'] ?? null;
$quantity = $input['quantity'] ?? 0;

if (empty($eventId) || empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid event ID or quantity.']);
    exit;
}

// 2. Fetch event price from the database to prevent price tampering.
$stmt = $conn->prepare("SELECT ticket_price FROM events WHERE id = ?");
$stmt->bind_param("i", $eventId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Event not found.']);
    exit;
}
$event = $result->fetch_assoc();
$ticketPrice = $event['ticket_price'];

// 3. Calculate total amount and insert into the database.
$amountPaid = $ticketPrice * $quantity;

$stmt = $conn->prepare("INSERT INTO tickets (user_id, event_id, quantity, amount_paid) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiid", $userId, $eventId, $quantity, $amountPaid);

if ($stmt->execute()) {
    echo json_encode([
        'message' => 'Ticket purchased successfully!',
        'ticket_id' => $conn->insert_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to process ticket purchase.']);
}

$stmt->close();
$conn->close();
?>