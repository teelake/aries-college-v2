<?php
session_start();
require_once 'payment_processor.php';
require_once 'backend/db_connect.php';

$paymentStatus = 'failed';
$paymentMessage = '';
$applicationData = null;
$transactionData = null;

try {
    // Get reference from URL
    $reference = $_GET['reference'] ?? $_GET['tx_ref'] ?? $_SESSION['payment_reference'] ?? null;
    
    if ($reference) {
        // Get transaction from database
        $paymentProcessor = new PaymentProcessor();
        $transactionData = $paymentProcessor->getTransactionByReference($reference);
        
        if ($transactionData) {
            $applicationData = $paymentProcessor->getApplicationById($transactionData['application_id']);
            
            // Update transaction status to failed if not already
            if ($transactionData['status'] === 'pending') {
                // Get payment method from URL parameters or default to 'Card Payment'
                $paymentMethod = $_GET['payment_type'] ?? 'Card Payment';
                $paymentProcessor->updateTransactionStatus($reference, 'failed', null, $paymentMethod);
            }
        }
    }
    
    $paymentMessage = 'Your payment was not completed successfully. Please try again or contact support if the problem persists.';
    
} catch (Exception $e) {
    $paymentMessage = 'An error occurred while processing your payment. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - Aries College of Health Management & Technology</title>
    
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
            color: #ef4444;
        }
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
        .btn-retry {
            background: var(--accent-red);
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-retry:hover {
            background: #b91c1c;
            transform: translateY(-2px);
        }
        .btn-contact {
            background: #10b981;
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-contact:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        .help-section {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 2rem 0;
        }
        .help-section h4 {
            color: #dc2626;
            margin-bottom: 1rem;
        }
        .help-section ul {
            text-align: left;
            margin-left: 1rem;
        }
        .help-section li {
            margin-bottom: 0.5rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <!-- Header Placeholder -->
    <div id="main-header"></div>
    
    <section class="section bg-light">
        <div class="container">
            <div class="payment-status">
                <div class="payment-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h2 class="section-title">Payment Failed</h2>
                <p class="section-subtitle"><?php echo htmlspecialchars($paymentMessage); ?></p>
                
                <?php if ($transactionData && $applicationData): ?>
                    <div class="payment-details">
                        <h3>Transaction Details</h3>
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
                            <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($transactionData['created_at'])); ?></span>
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
                
                <div class="help-section">
                    <h4><i class="fas fa-question-circle"></i> Need Help?</h4>
                    <p>If you're experiencing payment issues, here are some common solutions:</p>
                    <ul>
                        <li>Ensure your card details are correct and the card is active</li>
                        <li>Check that you have sufficient funds in your account</li>
                        <li>Try using a different payment method (card, bank transfer, USSD)</li>
                        <li>Clear your browser cache and try again</li>
                        <li>Contact your bank if the transaction was declined</li>
                    </ul>
                </div>
                
                <div class="action-buttons">
                    <a href="index.html" class="btn-home">
                        <i class="fas fa-home"></i> Go to Homepage
                    </a>
                    <a href="apply.php" class="btn-retry">
                        <i class="fas fa-redo"></i> Try Again
                    </a>
                    <a href="contact.php" class="btn-contact">
                        <i class="fas fa-phone"></i> Contact Support
                    </a>
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


