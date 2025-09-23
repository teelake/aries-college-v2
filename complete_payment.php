<?php
session_start();
require_once 'backend/db_connect.php';
require_once 'payment_processor.php';

// Get application ID and email from URL parameters
$applicationId = isset($_GET['app_id']) ? (int)$_GET['app_id'] : 0;
$email = isset($_GET['email']) ? filter_var($_GET['email'], FILTER_VALIDATE_EMAIL) : '';

// Validate parameters
if (!$applicationId || !$email) {
    die('Invalid payment link. Please check your email for the correct link.');
}

// Get application details
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed');
}

$stmt = $conn->prepare("SELECT * FROM applications WHERE id = ? AND email = ?");
$stmt->bind_param("is", $applicationId, $email);
$stmt->execute();
$result = $stmt->get_result();
$application = $result->fetch_assoc();
$stmt->close();

if (!$application) {
    die('Application not found. Please check your email for the correct link.');
}

// Check if payment is already completed
if ($application['payment_status'] === 'paid') {
    $paymentCompleted = true;
} else {
    $paymentCompleted = false;
}

// Check if there's an existing pending transaction
$stmt = $conn->prepare("SELECT * FROM transactions WHERE application_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $applicationId);
$stmt->execute();
$result = $stmt->get_result();
$existingTransaction = $result->fetch_assoc();
$stmt->close();
$conn->close();

$paymentUrl = '';
$paymentReference = '';

// If payment not completed and no existing transaction, create new one
if (!$paymentCompleted && !$existingTransaction) {
    try {
        $paymentProcessor = new PaymentProcessor();
        $paymentResult = $paymentProcessor->initializePayment($applicationId, $email);
        $paymentUrl = $paymentResult['authorization_url'];
        $paymentReference = $paymentResult['reference'];
    } catch (Exception $e) {
        $paymentError = 'Failed to initialize payment: ' . $e->getMessage();
    }
} elseif ($existingTransaction) {
    // Use existing transaction - need to get payment URL from payment processor
    $paymentReference = $existingTransaction['reference'];
    try {
        $paymentProcessor = new PaymentProcessor();
        // For existing transactions, we need to generate a new payment URL
        $paymentResult = $paymentProcessor->initializePayment($applicationId, $email);
        $paymentUrl = $paymentResult['authorization_url'];
    } catch (Exception $e) {
        $paymentError = 'Failed to get payment URL: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Payment - Aries College</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="img/logo.png">
    
    <style>
        .payment-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .payment-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .payment-header {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .payment-body {
            padding: 40px;
        }
        .application-summary {
            background: #f8fafc;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .summary-item:last-child {
            border-bottom: none;
        }
        .summary-label {
            font-weight: 600;
            color: #374151;
        }
        .summary-value {
            color: #6b7280;
        }
        .payment-section {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            margin: 25px 0;
        }
        .payment-amount {
            font-size: 28px;
            font-weight: bold;
            color: #059669;
            margin: 10px 0;
        }
        .payment-btn {
            background: #10b981;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin: 15px 0;
            transition: background 0.3s ease;
        }
        .payment-btn:hover {
            background: #059669;
            color: white;
        }
        .payment-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }
    </style>
</head>
<body>
    <!-- Header Placeholder -->
    <div id="main-header"></div>
    
    <div class="payment-container">
        <div class="payment-card">
            <div class="payment-header">
                <i class="fas fa-credit-card" style="font-size: 48px; margin-bottom: 15px;"></i>
                <h1>Complete Your Payment</h1>
                <p>Aries College of Health Management & Technology</p>
            </div>
            
            <div class="payment-body">
                <?php if ($paymentCompleted): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <strong>Payment Already Completed!</strong><br>
                        Your payment has already been processed successfully. You will receive a confirmation email shortly.
                    </div>
                    
                    <div class="application-summary">
                        <h3><i class="fas fa-receipt"></i> Payment Summary</h3>
                        <div class="summary-grid">
                            <div class="summary-item">
                                <span class="summary-label">Application ID:</span>
                                <span class="summary-value"><?php echo htmlspecialchars($application['id']); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Amount Paid:</span>
                                <span class="summary-value payment-amount">₦10,230</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Payment Status:</span>
                                <span class="summary-value">
                                    <span class="status-badge status-paid">✓ PAID</span>
                                </span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Application Status:</span>
                                <span class="summary-value">
                                    <span class="status-badge status-paid">✓ SUBMITTED</span>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <?php if (isset($paymentError)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Payment Error:</strong><br>
                            <?php echo htmlspecialchars($paymentError); ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Payment Required:</strong><br>
                            Complete your payment to finalize your application. Your application will only be processed after successful payment.
                        </div>
                    <?php endif; ?>
                    
                    <!-- Application Summary -->
                    <div class="application-summary">
                        <h3><i class="fas fa-user"></i> Application Summary</h3>
                        <div class="summary-grid">
                            <div class="summary-item">
                                <span class="summary-label">Application ID:</span>
                                <span class="summary-value"><?php echo htmlspecialchars($application['id']); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Full Name:</span>
                                <span class="summary-value"><?php echo htmlspecialchars($application['full_name']); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Email:</span>
                                <span class="summary-value"><?php echo htmlspecialchars($application['email']); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Program Applied:</span>
                                <span class="summary-value"><?php echo htmlspecialchars($application['program_applied']); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Application Status:</span>
                                <span class="summary-value">
                                    <span class="status-badge status-pending">Pending Payment</span>
                                </span>
                            </div>
                            <?php if ($paymentReference): ?>
                            <div class="summary-item">
                                <span class="summary-label">Payment Reference:</span>
                                <span class="summary-value"><?php echo htmlspecialchars($paymentReference); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Payment Section -->
                    <?php if ($paymentUrl): ?>
                    <div class="payment-section">
                        <h3><i class="fas fa-credit-card"></i> Complete Payment</h3>
                        <p>Application Fee</p>
                        <div class="payment-amount">₦10,230</div>
                        <p style="color: #92400e; margin: 15px 0;">Click the button below to complete your payment securely</p>
                        <a href="<?php echo htmlspecialchars($paymentUrl); ?>" class="payment-btn">
                            <i class="fas fa-lock"></i> Pay Now - ₦10,230
                        </a>
                        <p style="font-size: 12px; color: #6b7280; margin-top: 15px;">
                            <i class="fas fa-shield-alt"></i> Secure payment powered by Flutterwave/Paystack
                        </p>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- Important Information -->
                <div class="alert alert-info">
                    <h4><i class="fas fa-info-circle"></i> Important Information</h4>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>Your application will only be processed after successful payment</li>
                        <li>All payments are processed securely through our payment partners</li>
                        <li>You will receive a confirmation email after successful payment</li>
                        <li>Contact admissions@achtech.org.ng if you have any questions</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
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
</body>
</html>
