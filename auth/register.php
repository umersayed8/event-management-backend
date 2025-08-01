<?php
header('Content-Type: application/json');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$role = $input['role'] ?? '';

// Validation
if (empty($name) || empty($email) || empty($password) || empty($role)) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

$allowedRoles = ['organizer', 'sponsor', 'ticket_buyer', 'admin'];
if (!in_array($role, $allowedRoles)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid role']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 6 characters']);
    exit;
}

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'Email already exists']);
    exit;
}

// Hash password and insert user
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

if ($stmt->execute()) {
    $userId = $conn->insert_id;
    echo json_encode([
        'message' => 'User registered successfully',
        'user_id' => $userId,
        'role' => $role
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed']);
}

$stmt->close();
$conn->close();
?>