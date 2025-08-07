<?php
// /sponsorships/get_my_sponsorships.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: Only users with the 'sponsor' role can view their proposals.
requireRole('sponsor');

$sponsorId = getCurrentUserId();

// 2. Fetch all sponsorship proposals for the logged-in sponsor.
// We join with the events table to get the event title and date.
$stmt = $conn->prepare("
    SELECT
        e.title as event_title,
        e.date as event_date,
        s.status,
        s.proposal_text,
        s.created_at as proposal_date
    FROM
        sponsorships s
    JOIN
        events e ON s.event_id = e.id
    WHERE
        s.sponsor_id = ?
    ORDER BY
        s.created_at DESC
");
$stmt->bind_param("i", $sponsorId);
$stmt->execute();
$result = $stmt->get_result();

$sponsorships = [];
while ($row = $result->fetch_assoc()) {
    $sponsorships[] = $row;
}

// 3. Return the list of sponsorships as JSON.
echo json_encode($sponsorships);

$stmt->close();
$conn->close();
?>
