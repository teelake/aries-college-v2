<?php
require_once 'db_connect.php';

// Validate and sanitize input
function clean($data, $conn) {
    return htmlspecialchars($conn->real_escape_string(trim($data)));
}

$name = clean($_POST['name'] ?? '', $conn);
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ? clean($_POST['email'], $conn) : '';
$phone = clean($_POST['phone'] ?? '', $conn);
$subject = clean($_POST['subject'] ?? '', $conn);
$message = clean($_POST['message'] ?? '', $conn);

if (!$name || !$email || !$subject || !$message) {
    die("Please fill all required fields with valid data.");
}

$stmt = $conn->prepare("INSERT INTO contact_messages (full_name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
if ($stmt->execute()) {
    echo "Thank you for contacting us!";
} else {
    echo "Error: " . $conn->error;
}
$stmt->close();
$conn->close();
?> 