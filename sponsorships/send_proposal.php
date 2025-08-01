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

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

$eventId = $input['event_id'] ?? null;
$sponsorId = $input['sponsor_id'] ?? null;
$proposalText = trim($input['proposal_text'] ?? '');

if (!$eventId || !$sponsorId || empty($proposalText)) {
    http_response_code(400);
    echo json_encode(['error' => 'Event ID, sponsor ID, and proposal text are required']);
    exit;
}

$organizerId = getCurrentUserId();

// Verify the event belongs to this organizer
$stmt = $conn->prepare("SELECT id FROM events WHERE id = ? AND organizer_id = ?");
$stmt->bind_param("ii", $eventId, $organizerId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'You can only send proposals for your own events']);
    exit;
}

// Verify sponsor exists and has sponsor role
$sponsorStmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'sponsor'");
$sponsorStmt->bind_param("i", $sponsorId);
$sponsorStmt->execute();
$sponsorResult = $sponsorStmt->get_result();

if ($sponsorResult->num_rows === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid sponsor ID']);
    exit;
}

// Check if proposal already exists
$existingStmt = $conn->prepare("SELECT id FROM sponsorships WHERE event_id = ? AND sponsor_id = ?");
$existingStmt->bind_param("ii", $eventId, $sponsorId);
$existingStmt->execute();
$existingResult = $existingStmt->get_result();

if ($existingResult->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'Proposal already sent to this sponsor']);
    exit;
}

// Insert sponsorship proposal
$proposalStmt = $conn->prepare("INSERT INTO sponsorships (event_id, sponsor_id, proposal_text) VALUES (?, ?, ?)");
$proposalStmt->bind_param("iis", $eventId, $sponsorId, $proposalText);

if ($proposalStmt->execute()) {
    $proposalId = $conn->insert_id;
    echo json_encode([
        'message' => 'Sponsorship proposal sent successfully',
        'proposal_id' => $proposalId
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send proposal']);
}

$stmt->close();
$sponsorStmt->close();
$existingStmt->close();
$proposalStmt->close();
$conn->close();
?>