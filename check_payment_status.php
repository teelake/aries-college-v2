<?php
session_start();
require_once 'backend/db_connect.php';
require_once 'payment_processor.php';

$message = '';
$application = null;
$transaction = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $reference = trim($_POST['reference'] ?? '');
    
    if ($email || $reference) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$conn->connect_error) {
            if ($reference) {
                // Search by payment reference
                $stmt = $conn->prepare("
                    SELECT a.*, t.status as payment_status, t.reference, t.amount, t.created_at as payment_date
                    FROM applications a 
                    LEFT JOIN transactions t ON a.id = t.application_id 
                    WHERE t.reference = ?
                ");
                $stmt->bind_param("s", $reference);
            } else {
                // Search by email
                $stmt = $conn->prepare("
                    SELECT a.*, t.status as payment_status, t.reference, t.amount, t.created_at as payment_date
                    FROM applications a 
                    LEFT JOIN transactions t ON a.id = t.application_id 
                    WHERE a.email = ?
                    ORDER BY a.created_at DESC
                    LIMIT 1
                ");
                $stmt->bind_param("s", $email);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $application = $result->fetch_assoc();
            $stmt->close();
            
            if (!$application) {
                $message = '<div class="alert alert-warning">No application found with the provided information.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Database connection error.</div>';
        }
        $conn->close();
    } else {
        $message = '<div class="alert alert-warning">Please provide either an email address or payment reference.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Payment Status - Aries College</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="img/logo.png">
    
    <style>
        .status-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
        }
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
        }
        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }
        .application-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .detail-item {
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
        }
        .detail-label {
            font-weight: 600;
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        .detail-value {
            color: #1e293b;
            font-weight: 500;
        }
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            border-left: 4px solid;
            margin-bottom: 1rem;
        }
        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border-left-color: #f59e0b;
        }
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left-color: #ef4444;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left-color: #10b981;
        }
    </style>
</head>
<body>
    <!-- Header Placeholder -->
    <div id="main-header"></div>
    
    <section class="section bg-light">
        <div class="container">
            <h2 class="section-title">Check Payment Status</h2>
            <p class="section-subtitle">Enter your email or payment reference to check your application status</p>
            
            <?php echo $message; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <form method="POST" class="card">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                       placeholder="Enter your email address">
                            </div>
                            
                            <div class="form-group">
                                <label for="reference">OR Payment Reference</label>
                                <input type="text" id="reference" name="reference" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['reference'] ?? ''); ?>" 
                                       placeholder="Enter payment reference">
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i> Check Status
                            </button>
                        </div>
                    </form>
                </div>
                
                <?php if ($application): ?>
                <div class="col-md-6">
                    <div class="status-card">
                        <div class="text-center mb-4">
                            <h4>Application Status</h4>
                            <?php
                            $statusClass = '';
                            $statusText = '';
                            if ($application['payment_status'] === 'success') {
                                $statusClass = 'status-paid';
                                $statusText = 'Payment Successful';
                            } elseif ($application['payment_status'] === 'pending') {
                                $statusClass = 'status-pending';
                                $statusText = 'Payment Pending';
                            } else {
                                $statusClass = 'status-failed';
                                $statusText = 'Payment Failed';
                            }
                            ?>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo $statusText; ?>
                            </span>
                        </div>
                        
                        <div class="application-details">
                            <div class="detail-item">
                                <div class="detail-label">Application ID</div>
                                <div class="detail-value"><?php echo $application['id']; ?></div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-label">Full Name</div>
                                <div class="detail-value"><?php echo htmlspecialchars($application['full_name']); ?></div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-label">Program Applied</div>
                                <div class="detail-value"><?php echo htmlspecialchars($application['program_applied']); ?></div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-label">Application Date</div>
                                <div class="detail-value"><?php echo date('M j, Y', strtotime($application['created_at'])); ?></div>
                            </div>
                            
                            <?php if ($application['reference']): ?>
                            <div class="detail-item">
                                <div class="detail-label">Payment Reference</div>
                                <div class="detail-value"><?php echo $application['reference']; ?></div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-label">Payment Amount</div>
                                <div class="detail-value">₦<?php echo number_format($application['amount']); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($application['payment_status'] === 'pending'): ?>
                        <div class="mt-4">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Payment Required:</strong> Your application is pending payment. 
                                Please complete the payment to finalize your application.
                            </div>
                            <a href="payment_processor.php?ref=<?php echo $application['reference']; ?>" 
                               class="btn btn-primary btn-block">
                                <i class="fas fa-credit-card"></i> Complete Payment
                            </a>
                        </div>
                        <?php elseif ($application['payment_status'] === 'success'): ?>
                        <div class="mt-4">
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <strong>Application Complete:</strong> Your payment has been confirmed and your application is being reviewed.
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
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
