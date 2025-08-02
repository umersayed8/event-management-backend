<?php
// /sponsorships/get_sponsorship_proposals.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: Only organizers can view proposals.
requireRole('organizer');

$organizerId = getCurrentUserId();

// 2. Query to get all proposals for events owned by this organizer.
// We join with the events and users tables to get all necessary details.
$stmt = $conn->prepare("
    SELECT
        s.id as sponsorship_id,
        s.proposal_text,
        s.status,
        e.title as event_title,
        u.name as sponsor_name
    FROM
        sponsorships s
    JOIN
        events e ON s.event_id = e.id
    JOIN
        users u ON s.sponsor_id = u.id
    WHERE
        e.organizer_id = ?
    ORDER BY
        s.created_at DESC
");
$stmt->bind_param("i", $organizerId);
$stmt->execute();
$result = $stmt->get_result();

$proposals = [];
while ($row = $result->fetch_assoc()) {
    $proposals[] = $row;
}

// 3. Return the list of proposals as JSON.
echo json_encode($proposals);

$stmt->close();
$conn->close();
?>