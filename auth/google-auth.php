<?php
// google-auth.php
header("Content-Type: application/json");
include '../config/db.php'; // Adjust path as needed

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'];
$name = $data['name'];
$google_id = $data['google_id'];
$role = $data['role'];

// Check if user already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // User exists, proceed with login
    $stmt->bind_result($user_id);
    $stmt->fetch();
} else {
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, google_id) VALUES (?, ?, '', ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $role, $google_id);
    if (!$stmt->execute()) {
        echo json_encode(["success" => false, "message" => "Error registering user"]);
        exit;
    }
    $user_id = $stmt->insert_id;
}

// Set session or token (example)
session_start();
$_SESSION['user_id'] = $user_id;

echo json_encode(["success" => true]);
?>
