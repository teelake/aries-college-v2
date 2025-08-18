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

// Update application status to rejected
$updateResult = $conn->query("UPDATE applications SET application_status = 'rejected', updated_at = NOW() WHERE id = $id");

if ($updateResult) {
    // Send rejection email
    $to = $app['email'];
    $subject = "Application Update - Aries College of Health Management & Technology";
    $msg = "Dear {$app['full_name']},\n\n";
    $msg .= "Thank you for your interest in Aries College of Health Management & Technology.\n\n";
    $msg .= "Application Details:\n";
    $msg .= "Application ID: {$app['id']}\n";
    $msg .= "Program: {$app['program_applied']}\n";
    $msg .= "Status: NOT APPROVED\n\n";
    $msg .= "We regret to inform you that after careful review of your application, we are unable to offer you admission at this time.\n\n";
    $msg .= "This decision does not reflect on your potential, and we encourage you to:\n";
    $msg .= "1. Consider applying for future intakes\n";
    $msg .= "2. Explore other programs that may be suitable\n";
    $msg .= "3. Contact us for guidance on improving your application\n\n";
    $msg .= "If you have any questions, please contact us at admissions@achtech.org.ng\n\n";
    $msg .= "We wish you the best in your future endeavors.\n\n";
    $msg .= "Best regards,\n";
    $msg .= "Admissions Office\n";
    $msg .= "Aries College of Health Management & Technology";
    
    $headers = "From: Aries College <no-reply@achtech.org.ng>\r\nContent-type: text/plain; charset=UTF-8";
    mail($to, $subject, $msg, $headers);
    
    $_SESSION['admin_message'] = ['type' => 'success', 'text' => "Application for {$app['full_name']} has been rejected. Notification email sent."];
} else {
    $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Failed to update application status.'];
}

$conn->close();
header('Location: applicants.php');
exit;
?> 