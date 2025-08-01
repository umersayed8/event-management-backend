<?php
// /admin/get_all_users.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

// 1. Security: Only admins can access this list.
requireRole('admin');

// 2. Handle the search query
$searchTerm = $_GET['search'] ?? '';

$sql = "SELECT id, name, email, role, created_at, status FROM users";
$params = [];
$types = '';

if (!empty($searchTerm)) {
    // 3. Add a WHERE clause if a search term is provided
    $sql .= " WHERE name LIKE ? OR email LIKE ?";
    $likeTerm = "%" . $searchTerm . "%";
    $params[] = &$likeTerm;
    $params[] = &$likeTerm;
    $types .= "ss";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

// 4. Bind parameters dynamically if they exist
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// 5. Return the JSON response
echo json_encode($users);

$stmt->close();
$conn->close();
?>