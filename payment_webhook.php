<?php
require_once 'payment_processor.php';
require_once 'backend/db_connect.php';
require_once 'phpmailer/PHPMailer.php';
require_once 'phpmailer/SMTP.php';
require_once 'phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// Set content type to JSON
header('Content-Type: application/json');

// Get the webhook payload
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_VERIF_HASH'] ?? '';

// Log webhook for debugging
error_log("Flutterwave Webhook Received: " . $payload);

try {
    // Verify webhook signature (optional but recommended for security)
    if (!verifyWebhookSignature($payload, $signature)) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
        exit;
    }
    
    $data = json_decode($payload, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON payload');
    }
    
    // Extract payment details
    $txRef = $data['data']['tx_ref'] ?? null;
    $status = $data['data']['status'] ?? null;
    $amount = $data['data']['amount'] ?? null;
    $currency = $data['data']['currency'] ?? null;
    $transactionId = $data['data']['id'] ?? null;
    $customerEmail = $data['data']['customer']['email'] ?? null;
    
    if (!$txRef || !$status) {
        throw new Exception('Missing required payment data');
    }
    
    // Initialize payment processor
    $paymentProcessor = new PaymentProcessor('flutterwave');
    
    // Get transaction from database
    $transaction = $paymentProcessor->getTransactionByReference($txRef);
    
    if (!$transaction) {
        throw new Exception('Transaction not found: ' . $txRef);
    }
    
    // Update transaction status based on webhook
    $newStatus = PAYMENT_STATUS_FAILED;
    if ($status === 'successful') {
        $newStatus = PAYMENT_STATUS_SUCCESS;
    } elseif ($status === 'cancelled') {
        $newStatus = PAYMENT_STATUS_CANCELLED;
    }
    
    // Update transaction in database
    $paymentProcessor->updateTransactionStatus($txRef, $newStatus, $transactionId);
    
    // If payment is successful, send confirmation email and receipt
    if ($newStatus === PAYMENT_STATUS_SUCCESS) {
        $application = $paymentProcessor->getApplicationById($transaction['application_id']);
        if ($application) {
            sendPaymentConfirmationEmail($application, $transaction);
            sendPaymentReceipt($application, $transaction, $data['data']);
        }
    }
    
    // Return success response to Flutterwave
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    error_log("Webhook Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/**
 * Verify webhook signature
 */
function verifyWebhookSignature($payload, $signature) {
    // For now, we'll skip signature verification
    // In production, you should implement proper signature verification
    // using Flutterwave's webhook secret
    return true;
}

/**
 * Send payment confirmation email
 */
function sendPaymentConfirmationEmail($application, $transaction) {
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
        $mail->addAddress($application['email'], $application['full_name']);
        
        $mail->Subject = "Payment Confirmation - Aries College Application";
        
        $msg = "Dear {$application['full_name']},\n\n";
        $msg .= "Your application fee payment has been confirmed successfully!\n\n";
        $msg .= "Payment Details:\n";
        $msg .= "Reference: {$transaction['reference']}\n";
        $msg .= "Amount: ₦" . number_format($transaction['amount'], 2) . "\n";
        $msg .= "Date: " . date('F j, Y g:i A', strtotime($transaction['updated_at'])) . "\n";
        $msg .= "Status: Successful\n\n";
        $msg .= "Application Details:\n";
        $msg .= "Program: {$application['program_applied']}\n";
        $msg .= "Application ID: {$application['id']}\n\n";
        $msg .= "Your application is now being processed. You will receive further updates via email.\n\n";
        $msg .= "Thank you for choosing Aries College of Health Management & Technology!\n\n";
        $msg .= "Best regards,\n";
        $msg .= "Admissions Team\n";
        $msg .= "Aries College";
        
        $mail->Body = $msg;
        $mail->send();
        
    } catch (PHPMailerException $e) {
        error_log("Payment confirmation email failed: " . $e->getMessage());
    }
}

/**
 * Send payment receipt
 */
function sendPaymentReceipt($application, $transaction, $paymentData) {
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
        $mail->addAddress($application['email'], $application['full_name']);
        
        $mail->Subject = "Payment Receipt - Aries College Application Fee";
        $mail->isHTML(true);
        
        // Generate HTML receipt
        $receiptHtml = generateReceiptHTML($application, $transaction, $paymentData);
        $mail->Body = $receiptHtml;
        $mail->AltBody = generateReceiptText($application, $transaction, $paymentData);
        
        $mail->send();
        
    } catch (PHPMailerException $e) {
        error_log("Payment receipt email failed: " . $e->getMessage());
    }
}

/**
 * Generate HTML receipt
 */
function generateReceiptHTML($application, $transaction, $paymentData) {
    $receiptNumber = 'RCP-' . date('Y') . '-' . str_pad($transaction['id'], 6, '0', STR_PAD_LEFT);
    $paymentMethod = $paymentData['payment_type'] ?? 'Card Payment';
    $paymentChannel = $paymentData['payment_channel'] ?? 'Online';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Payment Receipt</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
            .receipt { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { text-align: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 20px; margin-bottom: 30px; }
            .logo { font-size: 24px; font-weight: bold; color: #1e3a8a; }
            .receipt-title { font-size: 28px; color: #333; margin: 10px 0; }
            .receipt-number { font-size: 16px; color: #666; }
            .section { margin-bottom: 25px; }
            .section-title { font-size: 18px; font-weight: bold; color: #1e3a8a; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
            .row { display: flex; justify-content: space-between; margin-bottom: 8px; }
            .label { font-weight: bold; color: #555; }
            .value { color: #333; }
            .amount { font-size: 20px; font-weight: bold; color: #10b981; }
            .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 14px; }
            .status { display: inline-block; background: #10b981; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='receipt'>
            <div class='header'>
                <div class='logo'>Aries College of Health Management & Technology</div>
                <div class='receipt-title'>PAYMENT RECEIPT</div>
                <div class='receipt-number'>Receipt #: {$receiptNumber}</div>
            </div>
            
            <div class='section'>
                <div class='section-title'>Payment Information</div>
                <div class='row'>
                    <span class='label'>Receipt Date:</span>
                    <span class='value'>" . date('F j, Y g:i A', strtotime($transaction['updated_at'])) . "</span>
                </div>
                <div class='row'>
                    <span class='label'>Transaction Reference:</span>
                    <span class='value'>{$transaction['reference']}</span>
                </div>
                <div class='row'>
                    <span class='label'>Payment Method:</span>
                    <span class='value'>{$paymentMethod}</span>
                </div>
                <div class='row'>
                    <span class='label'>Payment Channel:</span>
                    <span class='value'>{$paymentChannel}</span>
                </div>
                <div class='row'>
                    <span class='label'>Status:</span>
                    <span class='value'><span class='status'>PAID</span></span>
                </div>
            </div>
            
            <div class='section'>
                <div class='section-title'>Applicant Information</div>
                <div class='row'>
                    <span class='label'>Full Name:</span>
                    <span class='value'>{$application['full_name']}</span>
                </div>
                <div class='row'>
                    <span class='label'>Email:</span>
                    <span class='value'>{$application['email']}</span>
                </div>
                <div class='row'>
                    <span class='label'>Phone:</span>
                    <span class='value'>{$application['phone']}</span>
                </div>
                <div class='row'>
                    <span class='label'>Application ID:</span>
                    <span class='value'>{$application['id']}</span>
                </div>
                <div class='row'>
                    <span class='label'>Program Applied:</span>
                    <span class='value'>{$application['program_applied']}</span>
                </div>
            </div>
            
            <div class='section'>
                <div class='section-title'>Payment Details</div>
                <div class='row'>
                    <span class='label'>Description:</span>
                    <span class='value'>Application Fee - {$application['program_applied']}</span>
                </div>
                <div class='row'>
                    <span class='label'>Amount:</span>
                    <span class='value amount'>₦" . number_format($transaction['amount'], 2) . "</span>
                </div>
                <div class='row'>
                    <span class='label'>Currency:</span>
                    <span class='value'>{$transaction['currency']}</span>
                </div>
            </div>
            
            <div class='footer'>
                <p>This is an official receipt from Aries College of Health Management & Technology</p>
                <p>For any queries, please contact: finance@achtech.org.ng</p>
                <p>Thank you for choosing Aries College!</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Generate text receipt
 */
function generateReceiptText($application, $transaction, $paymentData) {
    $receiptNumber = 'RCP-' . date('Y') . '-' . str_pad($transaction['id'], 6, '0', STR_PAD_LEFT);
    $paymentMethod = $paymentData['payment_type'] ?? 'Card Payment';
    
    $text = "ARIES COLLEGE OF HEALTH MANAGEMENT & TECHNOLOGY\n";
    $text .= "================================================\n\n";
    $text .= "PAYMENT RECEIPT\n";
    $text .= "Receipt #: {$receiptNumber}\n";
    $text .= "Date: " . date('F j, Y g:i A', strtotime($transaction['updated_at'])) . "\n\n";
    
    $text .= "PAYMENT INFORMATION:\n";
    $text .= "Transaction Reference: {$transaction['reference']}\n";
    $text .= "Payment Method: {$paymentMethod}\n";
    $text .= "Status: PAID\n\n";
    
    $text .= "APPLICANT INFORMATION:\n";
    $text .= "Full Name: {$application['full_name']}\n";
    $text .= "Email: {$application['email']}\n";
    $text .= "Phone: {$application['phone']}\n";
    $text .= "Application ID: {$application['id']}\n";
    $text .= "Program Applied: {$application['program_applied']}\n\n";
    
    $text .= "PAYMENT DETAILS:\n";
    $text .= "Description: Application Fee - {$application['program_applied']}\n";
    $text .= "Amount: ₦" . number_format($transaction['amount'], 2) . "\n";
    $text .= "Currency: {$transaction['currency']}\n\n";
    
    $text .= "This is an official receipt from Aries College of Health Management & Technology\n";
    $text .= "For any queries, please contact: finance@achtech.org.ng\n";
    $text .= "Thank you for choosing Aries College!";
    
    return $text;
}
?>


