<?php
// /user/update_profile.php

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in.']);
    exit;
}

$userId = $_SESSION['user_id'];
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$photo_path_for_db = null;

// --- IMAGE UPLOAD LOGIC ---
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
    $allowed_types = ['image/jpeg', 'image/png'];
    $max_size = 2 * 1024 * 1024; // 2 MB

    if (in_array($_FILES['profile_photo']['type'], $allowed_types) && $_FILES['profile_photo']['size'] <= $max_size) {
        
        // This is the relative path for the SERVER to find the directory
        $upload_dir = '../uploads/profile_photos/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
        $new_filename = 'user_' . $userId . '_' . time() . '.' . $file_extension;
        
        // CORRECT: This is the web-accessible path to store in the database
        $photo_path_for_db = 'uploads/profile_photos/' . $new_filename;
        
        // This is the full server path to move the file
        $destination = $upload_dir . $new_filename;

        if (!move_uploaded_file($_FILES['profile_photo']['tmp_name'], $destination)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save the uploaded file.']);
            exit;
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type or size is too large (Max 2MB).']);
        exit;
    }
}

// --- DATABASE UPDATE LOGIC ---
if ($photo_path_for_db) {
    // If a new photo was uploaded, update the photo path
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, profile_photo_path = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $email, $photo_path_for_db, $userId);
} else {
    // If no new photo, just update name and email
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $email, $userId);
}

if ($stmt->execute()) {
    $response = ['message' => 'Profile updated successfully!'];
    if($photo_path_for_db) {
        // Send the correct, web-accessible path back to the frontend
        $response['new_photo_path'] = $photo_path_for_db;
    }
    echo json_encode($response);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update profile in database.']);
}

$stmt->close();
$conn->close();
?>