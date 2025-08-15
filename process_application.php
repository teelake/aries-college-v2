<?php
session_start();
require_once 'backend/db_connect.php';
require_once 'payment_processor.php';
require_once 'phpmailer/PHPMailer.php';
require_once 'phpmailer/SMTP.php';
require_once 'phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

header('Content-Type: application/json');

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    function clean($data, $conn) {
        return htmlspecialchars($conn->real_escape_string(trim($data)));
    }
    
    // Collect and validate form data
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
    
    // Validate required fields
    if (!$fullName || !$email || !$phone || !$dateOfBirth || !$gender || !$address || !$state || !$lga || !$qualification || !$yearCompleted || !$course) {
        throw new Exception('Please fill all required fields.');
    }
    
    // Handle file uploads
    $photoPath = '';
    $certificatePath = '';
    $uploadDir = 'uploads/';
    $passportsDir = $uploadDir . 'passports/';
    $certificatesDir = $uploadDir . 'certificates/';
    
    // Create directories if they don't exist
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    if (!is_dir($passportsDir)) mkdir($passportsDir, 0777, true);
    if (!is_dir($certificatesDir)) mkdir($certificatesDir, 0777, true);
    
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photoExt = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photoPath = $passportsDir . uniqid('photo_') . '.' . $photoExt;
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
            throw new Exception('Failed to upload photo.');
        }
    } else {
        throw new Exception('Photo upload is required.');
    }
    
    if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK) {
        $certExt = pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION);
        $certificatePath = $certificatesDir . uniqid('cert_') . '.' . $certExt;
        if (!move_uploaded_file($_FILES['certificate']['tmp_name'], $certificatePath)) {
            throw new Exception('Failed to upload certificate.');
        }
    } else {
        throw new Exception('Certificate upload is required.');
    }
    
    // Check for duplicate email or phone
    $dupStmt = $conn->prepare("SELECT id FROM applications WHERE email = ? OR phone = ? LIMIT 1");
    $dupStmt->bind_param("ss", $email, $phone);
    $dupStmt->execute();
    $dupStmt->store_result();
    if ($dupStmt->num_rows > 0) {
        throw new Exception('An application with this email or phone number already exists. Please use a different email or phone.');
    }
    $dupStmt->close();
    
    // Insert application into database with pending payment status
    $stmt = $conn->prepare("INSERT INTO applications (full_name, email, phone, date_of_birth, gender, address, state, lga, last_school, qualification, year_completed, program_applied, photo_path, certificate_path, payment_status, application_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending_payment', NOW())");
    $stmt->bind_param("ssssssssssssss", $fullName, $email, $phone, $dateOfBirth, $gender, $address, $state, $lga, $lastSchool, $qualification, $yearCompleted, $course, $photoPath, $certificatePath);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to save application: ' . $conn->error);
    }
    
    $applicationId = $conn->insert_id;
    $stmt->close();
    
    // Initialize payment
    try {
        $paymentProcessor = new PaymentProcessor();
        $paymentResult = $paymentProcessor->initializePayment($applicationId, $email);
        
        if (!$paymentResult || !isset($paymentResult['authorization_url'])) {
            throw new Exception('Payment initialization failed: No payment URL received');
        }
    } catch (Exception $paymentError) {
        // Log the payment error for debugging
        error_log("Payment initialization error: " . $paymentError->getMessage());
        throw new Exception('Payment initialization failed: ' . $paymentError->getMessage());
    }
    
    // Store payment reference in session
    $_SESSION['payment_reference'] = $paymentResult['reference'];
    $_SESSION['application_id'] = $applicationId;
    
    // Send confirmation email with payment link
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'mail.achtech.org.ng';
        $mail->SMTPAuth = true;
        $mail->Username = 'no-reply@achtech.org.ng';
        $mail->Password = 'Temp_pass123';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->setFrom('no-reply@achtech.org.ng', 'Aries College');
        $mail->addAddress($email, $fullName);
        
        $mail->Subject = "Application Received - Payment Required - Aries College";
        
        $msg = "Dear $fullName,\n\nYour application has been received successfully!\n\n";
        $msg .= "Application Summary:\n";
        $msg .= "Full Name: $fullName\n";
        $msg .= "Email: $email\n";
        $msg .= "Phone: $phone\n";
        $msg .= "Program Applied: $course\n";
        $msg .= "Application ID: $applicationId\n\n";
        $msg .= "---\n";
        $msg .= "PAYMENT REQUIRED\n";
        $msg .= "Application Fee: ₦10,230 (Ten Thousand Two Hundred and Thirty Naira)\n\n";
        $msg .= "To complete your application, please click the payment link below:\n";
        $msg .= $paymentResult['authorization_url'] . "\n\n";
        $msg .= "Payment Reference: " . $paymentResult['reference'] . "\n\n";
        $msg .= "IMPORTANT: Your application will only be processed after successful payment.\n";
        $msg .= "If you have any issues with payment, please contact us immediately.\n\n";
        $msg .= "Thank you for choosing Aries College of Health Management & Technology!\n\n";
        $msg .= "Best regards,\n";
        $msg .= "Admissions Team\n";
        $msg .= "Aries College";
        
        $mail->Body = $msg;
        $mail->send();
        
    } catch (PHPMailerException $e) {
        // Log email error but don't fail the process
        error_log("Application confirmation email failed: " . $e->getMessage());
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully! Please complete payment to finalize your application.',
        'data' => [
            'application_id' => $applicationId,
            'payment_url' => $paymentResult['authorization_url'],
            'reference' => $paymentResult['reference']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    
    // Log the full error for debugging
    error_log("Application submission error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>


