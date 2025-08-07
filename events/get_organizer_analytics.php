<?php
// /events/get_organizer_analytics.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// Security: Only organizers can view their analytics.
requireRole('organizer');
$organizerId = getCurrentUserId();

// 1. Revenue over the last 30 days
$revenueData = [];
$revenueResult = $conn->prepare("
    SELECT DATE(t.created_at) as date, SUM(t.amount_paid) as daily_revenue
    FROM tickets t
    JOIN events e ON t.event_id = e.id
    WHERE e.organizer_id = ? AND t.created_at >= CURDATE() - INTERVAL 30 DAY AND t.status = 'confirmed'
    GROUP BY DATE(t.created_at)
    ORDER BY date ASC
");
$revenueResult->bind_param("i", $organizerId);
$revenueResult->execute();
$result = $revenueResult->get_result();
while($row = $result->fetch_assoc()) {
    $revenueData[] = $row;
}

// 2. Ticket sales per event (top 10 events)
$ticketSalesData = [];
$ticketSalesResult = $conn->prepare("
    SELECT e.title, COUNT(t.id) as tickets_sold
    FROM tickets t
    JOIN events e ON t.event_id = e.id
    WHERE e.organizer_id = ? AND t.status = 'confirmed'
    GROUP BY e.id
    ORDER BY tickets_sold DESC
    LIMIT 10
");
$ticketSalesResult->bind_param("i", $organizerId);
$ticketSalesResult->execute();
$result = $ticketSalesResult->get_result();
while($row = $result->fetch_assoc()) {
    $ticketSalesData[] = $row;
}

// 3. Sponsorship status breakdown
$sponsorshipStatusData = [];
$sponsorshipStatusResult = $conn->prepare("
    SELECT s.status, COUNT(s.id) as count
    FROM sponsorships s
    JOIN events e ON s.event_id = e.id
    WHERE e.organizer_id = ?
    GROUP BY s.status
");
$sponsorshipStatusResult->bind_param("i", $organizerId);
$sponsorshipStatusResult->execute();
$result = $sponsorshipStatusResult->get_result();
while($row = $result->fetch_assoc()) {
    $sponsorshipStatusData[] = $row;
}

// 4. Return all data in a structured JSON object
echo json_encode([
    'revenue_over_time' => $revenueData,
    'ticket_sales_per_event' => $ticketSalesData,
    'sponsorship_status' => $sponsorshipStatusData
]);

$conn->close();
?>