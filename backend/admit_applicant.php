<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
require_once 'db_connect.php';
$id = intval($_GET['id'] ?? 0);
$app = $conn->query("SELECT * FROM applications WHERE id = $id")->fetch_assoc();
if (!$app) die('Applicant not found.');
$conn->query("UPDATE applications SET status = 'admitted' WHERE id = $id");
// Send admission email
$to = $app['email'];
$subject = "Admission Offer - Aries College";
$msg = "Dear {$app['full_name']},\n\nCongratulations! You have been admitted to Aries College of Health Management & Technology. Further instructions will be sent to you soon.\n\nBest regards,\nAdmissions Office";
$headers = "From: Aries College <no-reply@achtech.org.ng>\r\nContent-type: text/plain; charset=UTF-8";
mail($to, $subject, $msg, $headers);
header('Location: applicants.php');
exit;
?> 