<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
require_once 'db_connect.php';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Invalid applicant ID.'];
    header('Location: applicants.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$app = $result->fetch_assoc();
$stmt->close();

if (!$app) {
    $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Applicant not found.'];
    header('Location: applicants.php');
    exit;
}

// Update application status to approved
$stmt = $conn->prepare("UPDATE applications SET application_status = 'admitted', updated_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $id);
$updateResult = $stmt->execute();
$stmt->close();

if ($updateResult) {
    // Send admission email
    $to = $app['email'];
    $subject = "Admission Offer - Aries College of Health Management & Technology";
    $msg = "Dear {$app['full_name']},\n\n";
    $msg .= "🎉 CONGRATULATIONS! 🎉\n\n";
    $msg .= "We are pleased to inform you that your application to Aries College of Health Management & Technology has been APPROVED!\n\n";
    $msg .= "Application Details:\n";
    $msg .= "Application ID: {$app['id']}\n";
    $msg .= "Program: {$app['program_applied']}\n";
    $msg .= "Status: APPROVED\n\n";
    $msg .= "Next Steps:\n";
    $msg .= "1. You will receive detailed enrollment instructions within 3-5 business days\n";
    $msg .= "2. Please ensure your contact information is up to date\n";
    $msg .= "3. Prepare required documents for enrollment\n\n";
    $msg .= "If you have any questions, please contact us at admissions@achtech.org.ng\n\n";
    $msg .= "Welcome to Aries College!\n\n";
    $msg .= "Best regards,\n";
    $msg .= "Admissions Office\n";
    $msg .= "Aries College of Health Management & Technology";
    
    $headers = "From: Aries College <no-reply@achtech.org.ng>\r\nContent-type: text/plain; charset=UTF-8";
    mail($to, $subject, $msg, $headers);
    
    $_SESSION['admin_message'] = ['type' => 'success', 'text' => "Application for {$app['full_name']} has been approved successfully. Admission email sent."];
} else {
    $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Failed to update application status.'];
}

$conn->close();

// Ensure proper redirect
if (headers_sent()) {
    echo "<script>window.location.href = 'applicants.php';</script>";
} else {
    header('Location: applicants.php');
}
exit;
?> 