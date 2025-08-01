<?php
// /tickets/process_payment.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: User must be logged in to confirm a payment.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'You must be logged in to complete the payment.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];
$ticketId = $input['ticket_id'] ?? null;

if (empty($ticketId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ticket ID is required.']);
    exit;
}

// 2. This is the core logic: Update the ticket status to 'confirmed'.
// We ensure the ticket belongs to the current user for security.
$stmt = $conn->prepare("UPDATE tickets SET status = 'confirmed' WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $ticketId, $userId);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        // 3. Payment was "successful"
        echo json_encode(['message' => 'Payment successful! Your ticket is confirmed.']);
    } else {
        // This means the ticket ID was not found or didn't belong to the user.
        http_response_code(404);
        echo json_encode(['error' => 'Ticket not found or you do not have permission to modify it.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to process payment.']);
}

$stmt->close();
$conn->close();
?>