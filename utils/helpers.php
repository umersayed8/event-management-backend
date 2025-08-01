<?php
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function generateRandomToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function formatCurrency($amount) {
    return number_format($amount, 2);
}

function isValidDate($date, $format = 'Y-m-d H:i:s') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function logError($message, $file = 'error.log') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($file, $logMessage, FILE_APPEND | LOCK_EX);
}
?>
