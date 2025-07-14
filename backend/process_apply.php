<?php
session_start();
require_once 'db_connect.php';

function clean($data, $conn) {
    return htmlspecialchars($conn->real_escape_string(trim($data)));
}

// Collect and validate all fields
$fullName = clean($_POST['fullName'] ?? '', $conn);
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ? clean($_POST['email'], $conn) : '';
$phone = clean($_POST['phone'] ?? '', $conn);
$dateOfBirth = clean($_POST['dateOfBirth'] ?? '', $conn);
$gender = clean($_POST['gender'] ?? '', $conn);
$address = clean($_POST['address'] ?? '', $conn);
$state = clean($_POST['state'] ?? '', $conn);
$lga = clean($_POST['lga'] ?? '', $conn);
$lastSchool = clean($_POST['lastSchool'] ?? '', $conn);
$qualification = clean($_POST['qualification'] ?? '', $conn);
$yearCompleted = clean($_POST['yearCompleted'] ?? '', $conn);
$course = clean($_POST['course'] ?? '', $conn);
$paymentMethod = clean($_POST['paymentMethod'] ?? '', $conn);

// File uploads (photo, certificate)
$photoPath = '';
$certificatePath = '';
$uploadDir = '../uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $photoExt = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $photoPath = $uploadDir . uniqid('photo_') . '.' . $photoExt;
    move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath);
}
if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK) {
    $certExt = pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION);
    $certificatePath = $uploadDir . uniqid('cert_') . '.' . $certExt;
    move_uploaded_file($_FILES['certificate']['tmp_name'], $certificatePath);
}

if (!$fullName || !$email || !$phone || !$dateOfBirth || !$gender || !$address || !$state || !$lga || !$qualification || !$yearCompleted || !$course || !$photoPath || !$certificatePath) {
    $_SESSION['form_message'] = ['type' => 'error', 'text' => 'Please fill all required fields and upload required documents.'];
    header('Location: ../apply.html');
    exit;
}

$stmt = $conn->prepare("INSERT INTO applications (full_name, email, phone, date_of_birth, gender, address, state, lga, last_school, qualification, year_completed, program_applied, photo_path, certificate_path, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssssssssssss", $fullName, $email, $phone, $dateOfBirth, $gender, $address, $state, $lga, $lastSchool, $qualification, $yearCompleted, $course, $photoPath, $certificatePath, $paymentMethod);
if ($stmt->execute()) {
    // Send email to applicant with all form details
    $to = $email;
    $subject = "Application Received - Aries College";
    $msg = "Dear $fullName,\n\nYour application has been received. Here is a summary of your application:\n\n";
    $msg .= "Full Name: $fullName\n";
    $msg .= "Email: $email\n";
    $msg .= "Phone: $phone\n";
    $msg .= "Date of Birth: $dateOfBirth\n";
    $msg .= "Gender: $gender\n";
    $msg .= "Address: $address\n";
    $msg .= "State: $state\n";
    $msg .= "LGA: $lga\n";
    $msg .= "Last School: $lastSchool\n";
    $msg .= "Qualification: $qualification\n";
    $msg .= "Year Completed: $yearCompleted\n";
    $msg .= "Program Applied: $course\n";
    $msg .= "\n---\n";
    $msg .= "This is a confirmation of your application.\n";
    $msg .= "You will receive a payment receipt after your payment is confirmed.\n";
    $headers = "From: Aries College <no-reply@achtech.org.ng>\r\nContent-type: text/plain; charset=UTF-8";
    mail($to, $subject, $msg, $headers);
    $_SESSION['form_message'] = ['type' => 'success', 'text' => 'Application received! We have sent you an acknowledgment email.'];
} else {
    $_SESSION['form_message'] = ['type' => 'error', 'text' => 'Error: ' . $conn->error];
}
$stmt->close();
$conn->close();
header('Location: ../apply.html');
exit;
?> 