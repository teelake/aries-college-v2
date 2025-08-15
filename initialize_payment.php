<?php
session_start();
require_once 'payment_processor.php';
require_once 'backend/db_connect.php';

header('Content-Type: application/json');

try {
    // Check if application ID is provided
    if (!isset($_POST['application_id']) || !isset($_POST['email'])) {
        throw new Exception('Application ID and email are required');
    }
    
    $applicationId = (int)$_POST['application_id'];
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        throw new Exception('Invalid email address');
    }
    
    // Verify application exists and belongs to the email
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    $stmt = $conn->prepare("SELECT id, full_name, email FROM applications WHERE id = ? AND email = ?");
    $stmt->bind_param("is", $applicationId, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $application = $result->fetch_assoc();
    $stmt->close();
    
    if (!$application) {
        throw new Exception('Application not found or email mismatch');
    }
    
    // Check if payment already exists for this application
    $stmt = $conn->prepare("SELECT id, status FROM transactions WHERE application_id = ? AND status IN ('success', 'pending')");
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingTransaction = $result->fetch_assoc();
    $stmt->close();
    
    if ($existingTransaction) {
        if ($existingTransaction['status'] === 'success') {
            throw new Exception('Payment already completed for this application');
        } else {
            throw new Exception('Payment already initiated for this application');
        }
    }
    
    // Initialize payment
    $paymentProcessor = new PaymentProcessor();
    $paymentResult = $paymentProcessor->initializePayment($applicationId, $email);
    
    // Store payment reference in session for verification
    $_SESSION['payment_reference'] = $paymentResult['reference'];
    $_SESSION['application_id'] = $applicationId;
    
    echo json_encode([
        'success' => true,
        'data' => $paymentResult,
        'message' => 'Payment initialized successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>


