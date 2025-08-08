<?php
// /admin/get_admin_reports.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

requireRole('admin');

// --- STAT CARDS ---
// 1. Total Members
$totalMembersResult = $conn->query("SELECT COUNT(id) as count FROM users");
$totalMembers = $totalMembersResult->fetch_assoc()['count'];

// 2. New Members (Last 30 days)
$newMembersResult = $conn->query("SELECT COUNT(id) as count FROM users WHERE created_at >= CURDATE() - INTERVAL 30 DAY");
$newMembers = $newMembersResult->fetch_assoc()['count'];

// 3. Active Members (Last 30 days) - Defined as users who purchased a ticket
$activeMembersResult = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM tickets WHERE created_at >= CURDATE() - INTERVAL 30 DAY");
$activeMembers = $activeMembersResult->fetch_assoc()['count'];


// --- CHART DATA ---
// 1. Member Engagement (New Users per day for last 30 days)
$engagementData = [];
$engagementResult = $conn->query("
    SELECT DATE(created_at) as date, COUNT(id) as count
    FROM users
    WHERE created_at >= CURDATE() - INTERVAL 30 DAY
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
while($row = $engagementResult->fetch_assoc()) {
    $engagementData[] = $row;
}

// 2. User Roles Breakdown (Bar Chart)
$rolesData = [];
$rolesResult = $conn->query("
    SELECT role, COUNT(id) as count
    FROM users
    GROUP BY role
");
while($row = $rolesResult->fetch_assoc()) {
    $rolesData[] = $row;
}


// --- FINAL JSON RESPONSE ---
echo json_encode([
    'stats' => [
        'total_members' => (int)$totalMembers,
        'active_members' => (int)$activeMembers,
        'new_members' => (int)$newMembers
    ],
    'charts' => [
        'engagement_over_time' => $engagementData,
        'user_roles_breakdown' => $rolesData
    ]
]);

$conn->close();
?>
