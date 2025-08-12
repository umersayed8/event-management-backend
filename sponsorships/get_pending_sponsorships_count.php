<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

requireRole('organizer');
$organizerId = getCurrentUserId();

$sql = "
    SELECT COUNT(*) AS pending_count
    FROM sponsorships s
    JOIN events e ON s.event_id = e.id
    WHERE e.organizer_id = ? AND s.status = 'pending'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $organizerId);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo json_encode(['pending_count' => (int)$data['pending_count']]);

$stmt->close();
$conn->close();
?>
