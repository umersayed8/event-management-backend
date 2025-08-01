<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$sql = "SELECT id, title, location, date, banner_image FROM events ORDER BY date DESC";
$result = $conn->query($sql);

$events = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'details' => $row['location'] . ' | ' . date('M d, Y, h:i A', strtotime($row['date'])),
            'image' => 'http://localhost/event-management-backend/' . $row['banner_image'],
            'category' => 'General'
        ];
    }
}

echo json_encode($events);
$conn->close();
?>
