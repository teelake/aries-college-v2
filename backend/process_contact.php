<?php
session_start();
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
    $_SESSION['form_message'] = ['type' => 'error', 'text' => 'Please fill all required fields with valid data.'];
    header('Location: ../contact.php');
    exit;
}

$stmt = $conn->prepare("INSERT INTO contact_messages (full_name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
if ($stmt->execute()) {
    // Send acknowledgment email
    $to = $email;
    $mail_subject = "Thank you for contacting Aries College";
    $mail_msg = "Dear $name,\n\nThank you for reaching out to Aries College. We have received your message and will get back to you soon.\n\nBest regards,\nAries College Team";
    $headers = "From: Aries College <no-reply@achtech.org.ng>\r\nContent-type: text/plain; charset=UTF-8";
    mail($to, $mail_subject, $mail_msg, $headers);
    $_SESSION['form_message'] = ['type' => 'success', 'text' => 'Thank you for contacting us! We have sent you an acknowledgment email.'];
} else {
    $_SESSION['form_message'] = ['type' => 'error', 'text' => 'Error: ' . $conn->error];
}
$stmt->close();
$conn->close();
header('Location: ../contact.php');
exit;
?> 