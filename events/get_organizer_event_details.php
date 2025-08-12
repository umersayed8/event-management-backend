<?php
// /events/get_organizer_event_details.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: Only organizers can view their own event details.
requireRole('organizer');

$organizerId = getCurrentUserId();
$eventId = $_GET['id'] ?? null;

if (!$eventId) {
    http_response_code(400);
    echo json_encode(['error' => 'Event ID is required.']);
    exit;
}

// 2. Fetch the event's main info, ensuring it belongs to the logged-in organizer.
$eventStmt = $conn->prepare("
    SELECT title, description, date, location 
    FROM events 
    WHERE id = ? AND organizer_id = ?
");
$eventStmt->bind_param("ii", $eventId, $organizerId);
$eventStmt->execute();
$eventInfo = $eventStmt->get_result()->fetch_assoc();

if (!$eventInfo) {
    http_response_code(404);
    echo json_encode(['error' => 'Event not found or you do not have permission to view it.']);
    exit;
}

// 3. Get ticket sales statistics for this event.
$ticketStmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(quantity), 0) as tickets_sold,
        COALESCE(SUM(amount_paid), 0) as total_revenue
    FROM tickets 
    WHERE event_id = ? AND status = 'confirmed'
");
$ticketStmt->bind_param("i", $eventId);
$ticketStmt->execute();
$ticketStats = $ticketStmt->get_result()->fetch_assoc();

// 4. Get all sponsorship proposals for this event.
$sponsorships = [];
$sponsorStmt = $conn->prepare("
    SELECT u.name as sponsor_name, s.proposal_text, s.status
    FROM sponsorships s
    JOIN users u ON s.sponsor_id = u.id
    WHERE s.event_id = ?
    ORDER BY s.created_at DESC
");
$sponsorStmt->bind_param("i", $eventId);
$sponsorStmt->execute();
$result = $sponsorStmt->get_result();
while ($row = $result->fetch_assoc()) {
    $sponsorships[] = $row;
}

// 5. Combine all data into a single response.
echo json_encode([
    'event_info' => $eventInfo,
    'ticket_stats' => $ticketStats,
    'sponsorship_proposals' => $sponsorships
]);

$conn->close();
?>
