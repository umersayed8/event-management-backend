<?php
// /contact/contact.php

header('Content-Type: application/json');

// This script simulates processing a contact form submission.
// In a real-world application, you would integrate an email service like PHPMailer here.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$fullName = $input['full_name'] ?? '';
$companyName = $input['company_name'] ?? '';
$email = $input['email'] ?? '';
$phone = $input['phone'] ?? '';
$message = $input['message'] ?? '';

// Basic validation to ensure no fields are empty
if (empty($fullName) || empty($companyName) || empty($email) || empty($phone) || empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required.']);
    exit;
}

// --- Email Sending Simulation ---
// In a real application, you would format and send an email here.
// For example:
// $to = 'sales@evently.com';
// $subject = 'New Contact Form Submission from ' . $fullName;
// $body = "Name: $fullName\nCompany: $companyName\nEmail: $email\nPhone: $phone\n\nMessage:\n$message";
// mail($to, $subject, $body);

// For this demo, we'll just return a success message.
http_response_code(200);
echo json_encode(['message' => 'Your message has been sent successfully! Our team will get back to you shortly.']);

?>
