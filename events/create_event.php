<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

requireRole('organizer');

// Use $_POST and $_FILES (not JSON)
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$date = $_POST['date'] ?? '';
$location = trim($_POST['location'] ?? '');
$ticketPrice = $_POST['ticket_price'] ?? 0;
$banner = $_FILES['banner'] ?? null;

// Validation
if (empty($title) || empty($date) || empty($location)) {
    http_response_code(400);
    echo json_encode(['error' => 'Title, date, and location are required']);
    exit;
}

if (!is_numeric($ticketPrice) || $ticketPrice < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ticket price']);
    exit;
}

$dateObj = DateTime::createFromFormat('Y-m-d H:i:s', $date);
if (!$dateObj) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format (use Y-m-d H:i:s)']);
    exit;
}

// Handle image upload
$uploadPath = null;
if ($banner && $banner['error'] === UPLOAD_ERR_OK) {
    $targetDir = '../uploads/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $fileName = time() . '_' . basename($banner['name']);
    $uploadPath = $targetDir . $fileName;

    if (!move_uploaded_file($banner['tmp_name'], $uploadPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload image']);
        exit;
    }

    // Store only relative path
    $uploadPath = 'uploads/' . $fileName;
}

$organizerId = getCurrentUserId();

$stmt = $conn->prepare("INSERT INTO events (organizer_id, title, description, date, location, ticket_price, banner_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issssds", $organizerId, $title, $description, $date, $location, $ticketPrice, $uploadPath);

if ($stmt->execute()) {
    echo json_encode([
        'message' => 'Event created successfully',
        'event_id' => $conn->insert_id,
        'banner' => $uploadPath
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create event']);
}

$stmt->close();
$conn->close();
?>
