<?php
// /sponsorships/get_sponsored_event_details.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: User must be a sponsor.
requireRole('sponsor');

$sponsorId = getCurrentUserId();
$eventId = $_GET['id'] ?? null;

if (!$eventId) {
    http_response_code(400);
    echo json_encode(['error' => 'Event ID is required.']);
    exit;
}

// 2. CRITICAL SECURITY CHECK: Verify this sponsor has an ACCEPTED proposal for this event.
$verifyStmt = $conn->prepare("SELECT id FROM sponsorships WHERE event_id = ? AND sponsor_id = ? AND status = 'accepted'");
$verifyStmt->bind_param("ii", $eventId, $sponsorId);
$verifyStmt->execute();
if ($verifyStmt->get_result()->num_rows === 0) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'You do not have permission to view these event details.']);
    exit;
}

// 3. Fetch the event's main info.
$eventStmt = $conn->prepare("SELECT title, description, date, location FROM events WHERE id = ?");
$eventStmt->bind_param("i", $eventId);
$eventStmt->execute();
$eventInfo = $eventStmt->get_result()->fetch_assoc();

// 4. Fetch the event's performance stats.
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

// 5. Return all data.
echo json_encode([
    'event_info' => $eventInfo,
    'performance_stats' => $ticketStats
]);

$conn->close();
?>
