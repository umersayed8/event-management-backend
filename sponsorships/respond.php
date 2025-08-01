<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

requireRole('sponsor');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

$proposalId = $input['proposal_id'] ?? null;
$response = $input['response'] ?? '';

if (!$proposalId || !in_array($response, ['accepted', 'rejected'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid proposal ID and response (accepted/rejected) required']);
    exit;
}

$sponsorId = getCurrentUserId();

// Verify the proposal exists and belongs to this sponsor
$stmt = $conn->prepare("SELECT id FROM sponsorships WHERE id = ? AND sponsor_id = ? AND status = 'pending'");
$stmt->bind_param("ii", $proposalId, $sponsorId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Proposal not found or already responded']);
    exit;
}

// Update proposal status
$updateStmt = $conn->prepare("UPDATE sponsorships SET status = ? WHERE id = ?");
$updateStmt->bind_param("si", $response, $proposalId);

if ($updateStmt->execute()) {
    echo json_encode([
        'message' => 'Proposal response saved successfully',
        'status' => $response
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save response']);
}

$stmt->close();
$updateStmt->close();
$conn->close();
?>
