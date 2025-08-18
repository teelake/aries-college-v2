<?php
session_start();
require_once 'payment_processor.php';
require_once 'backend/db_connect.php';
require_once 'phpmailer/PHPMailer.php';
require_once 'phpmailer/SMTP.php';
require_once 'phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

$paymentStatus = 'pending';
$paymentMessage = '';
$applicationData = null;
$transactionData = null;

try {
    // Debug: Log all URL parameters
    error_log("Payment Success URL Parameters: " . json_encode($_GET));
    
    // Get reference from URL parameters (Flutterwave uses tx_ref)
    $reference = $_GET['tx_ref'] ?? $_GET['reference'] ?? $_GET['trxref'] ?? $_SESSION['payment_reference'] ?? null;
    
    // If we have transaction_id but no reference, try to find the transaction by ID
    if (!$reference && isset($_GET['transaction_id'])) {
        $transactionId = $_GET['transaction_id'];
        error_log("Looking for transaction with ID: $transactionId");
        
        // Try to find transaction by gateway_reference using PaymentProcessor
        $paymentProcessor = new PaymentProcessor();
        $transaction = $paymentProcessor->getTransactionByGatewayReference($transactionId);
        
        if ($transaction) {
            $reference = $transaction['reference'];
            error_log("Found reference $reference for transaction ID $transactionId");
        } else {
            error_log("No transaction found for ID: $transactionId");
            
            // Try alternative lookup methods
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if (!$conn->connect_error) {
                // Try looking in the reference column as well
                $stmt = $conn->prepare("SELECT * FROM transactions WHERE reference = ? OR gateway_reference = ?");
                $stmt->bind_param("ss", $transactionId, $transactionId);
                $stmt->execute();
                $result = $stmt->get_result();
                $altTransaction = $result->fetch_assoc();
                $stmt->close();
                
                if ($altTransaction) {
                    $reference = $altTransaction['reference'];
                    error_log("Found reference $reference using alternative lookup for ID $transactionId");
                } else {
                    error_log("No transaction found using alternative lookup for ID $transactionId");
                }
                $conn->close();
            }
        }
    }
    
    if (!$reference) {
        throw new Exception('Payment reference not found. Please check the URL parameters. Available parameters: ' . json_encode($_GET));
    }
    
    // Verify payment
    $paymentProcessor = new PaymentProcessor();
    $verificationResult = $paymentProcessor->verifyPayment($reference);
    
    if ($verificationResult['success']) {
        // Update transaction status
        $paymentProcessor->updateTransactionStatus(
            $reference, 
            $verificationResult['status'], 
            $verificationResult['gateway_reference']
        );
        
        $paymentStatus = 'success';
        $paymentMessage = 'Payment completed successfully!';
        
        // Get transaction and application data
        $transactionData = $paymentProcessor->getTransactionByReference($reference);
        if ($transactionData) {
            $applicationData = $paymentProcessor->getApplicationById($transactionData['application_id']);
        }
        
        // Send confirmation email
        if ($applicationData) {
            sendPaymentConfirmationEmail($applicationData, $transactionData);
            sendApplicationConfirmationEmail($applicationData, $transactionData);
        }
        
    } else {
        $paymentStatus = 'failed';
        $paymentMessage = $verificationResult['message'] ?? 'Payment verification failed';
    }
    
} catch (Exception $e) {
    $paymentStatus = 'error';
    $paymentMessage = $e->getMessage();
}

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
        // Log email error but don't fail the payment process
        error_log("Payment confirmation email failed: " . $e->getMessage());
    }
}

function sendApplicationConfirmationEmail($application, $transaction) {
    
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
        
        $mail->Subject = "Application Confirmed - Aries College";
        
        $msg = "Dear {$application['full_name']},\n\n";
        $msg .= "Your application has been confirmed and is now being processed!\n\n";
        $msg .= "Application Summary:\n";
        $msg .= "Full Name: {$application['full_name']}\n";
        $msg .= "Email: {$application['email']}\n";
        $msg .= "Phone: {$application['phone']}\n";
        $msg .= "Program Applied: {$application['program_applied']}\n";
        $msg .= "Application ID: {$application['id']}\n\n";
        $msg .= "Payment Details:\n";
        $msg .= "Reference: {$transaction['reference']}\n";
        $msg .= "Amount: ₦" . number_format($transaction['amount'], 2) . "\n";
        $msg .= "Date: " . date('F j, Y g:i A', strtotime($transaction['updated_at'])) . "\n";
        $msg .= "Status: Successful\n\n";
        $msg .= "Your application is now complete and will be reviewed by our admissions team.\n";
        $msg .= "You will receive further updates via email regarding your application status.\n\n";
        $msg .= "Thank you for choosing Aries College of Health Management & Technology!\n\n";
        $msg .= "Best regards,\n";
        $msg .= "Admissions Team\n";
        $msg .= "Aries College";
        
        $mail->Body = $msg;
        $mail->send();
        
    } catch (PHPMailerException $e) {
        // Log email error but don't fail the payment process
        error_log("Application confirmation email failed: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status - Aries College of Health Management & Technology</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="img/logo.png">
    <style>
        .payment-status {
            text-align: center;
            padding: 3rem 0;
        }
        .payment-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .payment-icon.success { color: #10b981; }
        .payment-icon.failed { color: #ef4444; }
        .payment-icon.pending { color: #f59e0b; }
        .payment-icon.error { color: #6b7280; }
        .payment-details {
            background: #fff;
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .payment-details h3 {
            color: var(--primary-blue);
            margin-bottom: 1.5rem;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: var(--dark-gray);
        }
        .detail-value {
            color: var(--text-gray);
        }
        .action-buttons {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-home {
            background: var(--primary-blue);
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-home:hover {
            background: var(--secondary-blue);
            transform: translateY(-2px);
        }
        .btn-apply {
            background: var(--accent-red);
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-apply:hover {
            background: #b91c1c;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Header Placeholder -->
    <div id="main-header"></div>
    
    <section class="section bg-light">
        <div class="container">
            <div class="payment-status">
                <?php if ($paymentStatus === 'success'): ?>
                    <div class="payment-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="section-title">Payment Successful!</h2>
                    <p class="section-subtitle">Your application fee has been paid successfully. Your application is now being processed.</p>
                    
                    <?php if ($transactionData && $applicationData): ?>
                        <div class="payment-details">
                            <h3>Payment Details</h3>
                            <div class="detail-row">
                                <span class="detail-label">Reference:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($transactionData['reference']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Amount:</span>
                                <span class="detail-value">₦<?php echo number_format($transactionData['amount'], 2); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Date:</span>
                                <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($transactionData['updated_at'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Application ID:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($applicationData['id']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Program:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($applicationData['program_applied']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Applicant:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($applicationData['full_name']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                <?php elseif ($paymentStatus === 'failed'): ?>
                    <div class="payment-icon failed">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h2 class="section-title">Payment Failed</h2>
                    <p class="section-subtitle"><?php echo htmlspecialchars($paymentMessage); ?></p>
                    
                <?php elseif ($paymentStatus === 'pending'): ?>
                    <div class="payment-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h2 class="section-title">Payment Pending</h2>
                    <p class="section-subtitle">Your payment is being processed. Please wait a moment and refresh this page.</p>
                    
                <?php else: ?>
                    <div class="payment-icon error">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h2 class="section-title">Payment Error</h2>
                    <p class="section-subtitle"><?php echo htmlspecialchars($paymentMessage); ?></p>
                    
                    <?php if (strpos($paymentMessage, 'No transaction was found') !== false): ?>
                        <div class="payment-details">
                            <h3>USSD Payment Information</h3>
                            <p><strong>This is a common issue with USSD payments in test mode.</strong></p>
                            <ul style="text-align: left; max-width: 600px; margin: 0 auto;">
                                <li>USSD payments can take 5-10 minutes to appear in the verification system</li>
                                <li>Test mode transactions may require manual verification</li>
                                <li>Please wait a few minutes and try refreshing this page</li>
                                <li>Check your Flutterwave dashboard for transaction status</li>
                            </ul>
                            <p style="margin-top: 1rem;"><strong>Reference:</strong> <?php echo htmlspecialchars($reference ?? 'N/A'); ?></p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <a href="index.html" class="btn-home">
                        <i class="fas fa-home"></i> Go to Homepage
                    </a>
                    <?php if ($paymentStatus !== 'success'): ?>
                        <?php if (strpos($paymentMessage, 'No transaction was found') !== false): ?>
                            <a href="javascript:location.reload();" class="btn-apply">
                                <i class="fas fa-sync-alt"></i> Refresh Page
                            </a>
                        <?php else: ?>
                            <a href="apply.php" class="btn-apply">
                                <i class="fas fa-edit"></i> Try Again
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer Placeholder -->
    <div id="main-footer"></div>
    
    <!-- Dynamic Header/Footer Loading Script -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Load header
        fetch('header.html')
            .then(response => response.text())
            .then(data => {
                document.getElementById('main-header').innerHTML = data;
                // Set active nav link for current page
                const currentPage = window.location.pathname.split('/').pop() || 'index.html';
                const navLinks = document.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    if (link.getAttribute('href') === currentPage) {
                        link.classList.add('active');
                    }
                });
                // Re-initialize navbar after header is loaded
                if (typeof initNavbar === 'function') {
                    initNavbar();
                }
            })
            .catch(error => console.error('Error loading header:', error));
        
        // Load footer
        fetch('footer.html')
            .then(response => response.text())
            .then(data => {
                document.getElementById('main-footer').innerHTML = data;
            })
            .catch(error => console.error('Error loading footer:', error));
    });
    </script>
    <script src="js/main.js"></script>
</body>
</html>
