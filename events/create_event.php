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

// --- Receive all form data ---
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$date = $_POST['date'] ?? '';
$location = trim($_POST['location'] ?? '');
$ticketPrice = $_POST['ticket_price'] ?? 0;
$audienceSize = $_POST['audience_size'] ?? 0;
$category = $_POST['category'] ?? ''; // Changed default to empty string for consistency
$banner = $_FILES['banner'] ?? null;

// --- NEW: Server-Side Validation ---

// 1. Event Name Length
if (strlen($title) > 30) {
    http_response_code(400);
    echo json_encode(['error' => 'Event name must be 30 characters or less.']);
    exit;
}

// 2. Date Validation
try {
    $eventDate = new DateTime($date);
    $currentDate = new DateTime();
    if ($eventDate < $currentDate) {
        http_response_code(400);
        echo json_encode(['error' => 'Event date cannot be in the past.']);
        exit;
    }
    if ($eventDate->format('Y') > 2026) {
        http_response_code(400);
        echo json_encode(['error' => 'Event year cannot be after 2026.']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format.']);
    exit;
}

// 3. Audience Size
if (!is_numeric($audienceSize) || $audienceSize <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Audience size must be a number greater than 0.']);
    exit;
}

// 4. Banner Upload Validation
$uploadPath = null;
if ($banner && $banner['error'] === UPLOAD_ERR_OK) {
    // Check if file is a PNG
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($banner['tmp_name']);
    if ($mime_type !== 'image/png') {
        http_response_code(400);
        echo json_encode(['error' => 'Only PNG images are allowed for the banner.']);
        exit;
    }

    $targetDir = '../uploads/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $fileName = time() . '_' . basename($banner['name']);
    $uploadPath = $targetDir . $fileName;

    if (!move_uploaded_file($banner['tmp_name'], $uploadPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload image.']);
        exit;
    }
    $uploadPath = 'uploads/' . $fileName; // Store relative path
}

// --- End of Validation ---

$organizerId = getCurrentUserId();

// --- FIX 1: Add the 9th placeholder for audience_size ---
$stmt = $conn->prepare("INSERT INTO events (organizer_id, title, description, date, location, ticket_price, banner_image, category, audience_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

// --- FIX 2: Add 's' for the category and bind all 9 variables in the correct order ---
$stmt->bind_param("issssdssi", $organizerId, $title, $description, $date, $location, $ticketPrice, $uploadPath, $category, $audienceSize);

if ($stmt->execute()) {
    echo json_encode(['message' => 'Event created successfully', 'event_id' => $conn->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create event']);
}

$stmt->close();
$conn->close();
?>