<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

requireRole('admin');

$query = "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($query);

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'email' => $row['email'],
        'role' => $row['role'],
        'created_at' => $row['created_at']
    ];
}

echo json_encode([
    'users' => $users,
    'count' => count($users)
]);

$conn->close();
?>