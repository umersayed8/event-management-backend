<?php
// /favorites/toggle_favorite.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: User must be logged in to favorite an event.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in to favorite events.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];
$eventId = $input['event_id'] ?? null;

if (empty($eventId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Event ID is required.']);
    exit;
}

// 2. Check if the favorite already exists.
$stmt = $conn->prepare("SELECT id FROM favorite_events WHERE user_id = ? AND event_id = ?");
$stmt->bind_param("ii", $userId, $eventId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // 3. If it exists, delete it (unfavorite).
    $favorite = $result->fetch_assoc();
    $stmt = $conn->prepare("DELETE FROM favorite_events WHERE id = ?");
    $stmt->bind_param("i", $favorite['id']);
    $stmt->execute();
    echo json_encode(['status' => 'removed']);
} else {
    // 4. If it doesn't exist, insert it (favorite).
    $stmt = $conn->prepare("INSERT INTO favorite_events (user_id, event_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $userId, $eventId);
    $stmt->execute();
    echo json_encode(['status' => 'added']);
}

$stmt->close();
$conn->close();
?>