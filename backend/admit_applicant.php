<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
require_once 'db_connect.php';

$id = intval($_GET['id'] ?? 0);
$app = $conn->query("SELECT * FROM applications WHERE id = $id")->fetch_assoc();

if (!$app) {
    $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Applicant not found.'];
    header('Location: applicants.php');
    exit;
}

// Update application status to approved
$updateResult = $conn->query("UPDATE applications SET application_status = 'approved', updated_at = NOW() WHERE id = $id");

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
header('Location: applicants.php');
exit;
?> 