<?php
// /admin/get_event_details.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// Security: Only admins can view these details.
requireRole('admin');

$eventId = $_GET['id'] ?? null;

if (!$eventId) {
    http_response_code(400);
    echo json_encode(['error' => 'Event ID is required.']);
    exit;
}

// 1. Get the event's main information and organizer's name.
$eventStmt = $conn->prepare("
    SELECT e.title, e.description, e.date, e.location, u.name as organizer_name
    FROM events e
    JOIN users u ON e.organizer_id = u.id
    WHERE e.id = ?
");
$eventStmt->bind_param("i", $eventId);
$eventStmt->execute();
$eventInfo = $eventStmt->get_result()->fetch_assoc();

if (!$eventInfo) {
    http_response_code(404);
    echo json_encode(['error' => 'Event not found.']);
    exit;
}

// 2. Get ticket sales statistics for this event.
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

// 3. Get sponsorship statistics for this event.
$sponsorStmt = $conn->prepare("
    SELECT 
        COUNT(id) as total_proposals,
        COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted_sponsors
    FROM sponsorships 
    WHERE event_id = ?
");
$sponsorStmt->bind_param("i", $eventId);
$sponsorStmt->execute();
$sponsorStats = $sponsorStmt->get_result()->fetch_assoc();

// 4. Combine all data into a single response.
echo json_encode([
    'event_info' => $eventInfo,
    'ticket_stats' => $ticketStats,
    'sponsor_stats' => $sponsorStats
]);

$conn->close();
?>