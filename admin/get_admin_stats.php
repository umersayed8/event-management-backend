<?php
// /admin/get_admin_stats.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: This is a critical step. Only allow users with the 'admin' role.
requireRole('admin');

// 2. Fetch all required statistics using separate, simple queries.
// Get total users
$usersResult = $conn->query("SELECT COUNT(id) as total_users FROM users");
$totalUsers = $usersResult->fetch_assoc()['total_users'];

// Get total events
$eventsResult = $conn->query("SELECT COUNT(id) as total_events FROM events");
$totalEvents = $eventsResult->fetch_assoc()['total_events'];

// Get total revenue and tickets sold from confirmed tickets
$ticketsResult = $conn->query("SELECT SUM(quantity) as total_tickets_sold, SUM(amount_paid) as total_revenue FROM tickets WHERE status = 'confirmed'");
$ticketStats = $ticketsResult->fetch_assoc();
$totalTicketsSold = $ticketStats['total_tickets_sold'] ?? 0;
$totalRevenue = $ticketStats['total_revenue'] ?? 0;

// 3. Get the admin's name from the session for a personalized welcome.
$adminName = $_SESSION['name'];

// 4. Combine all stats into a single JSON response.
echo json_encode([
    'total_users' => (int)$totalUsers,
    'total_events' => (int)$totalEvents,
    'total_tickets_sold' => (int)$totalTicketsSold,
    'total_revenue' => (float)$totalRevenue,
    'admin_name' => $adminName
]);

$conn->close();
?>