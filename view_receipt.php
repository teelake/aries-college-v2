<?php
session_start();
require_once 'backend/db_connect.php';

$message = '';
$application = null;
$transaction = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $applicationId = trim($_POST['application_id'] ?? '');
    $reference = trim($_POST['reference'] ?? '');
    
    if ($applicationId || $reference) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$conn->connect_error) {
            if ($reference) {
                // Search by payment reference
                $stmt = $conn->prepare("
                    SELECT a.*, t.amount, t.reference, t.status as payment_status
                    FROM applications a 
                    LEFT JOIN transactions t ON a.id = t.application_id 
                    WHERE t.reference = ? AND a.payment_status = 'paid'
                ");
                $stmt->bind_param("s", $reference);
            } else {
                // Search by application ID
                $stmt = $conn->prepare("
                    SELECT a.*, t.amount, t.reference, t.status as payment_status
                    FROM applications a 
                    LEFT JOIN transactions t ON a.id = t.application_id 
                    WHERE a.id = ? AND a.payment_status = 'paid'
                ");
                $stmt->bind_param("i", $applicationId);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $application = $result->fetch_assoc();
            $stmt->close();
            
            if (!$application) {
                $message = '<div class="alert alert-warning">No paid application found with the provided information.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Database connection error.</div>';
        }
        $conn->close();
    } else {
        $message = '<div class="alert alert-warning">Please provide either an Application ID or Payment Reference.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Receipt & Application Form - Aries College</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="img/logo.png">
    
    <style>
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            width: 120px;
            height: auto;
        }
        .title {
            color: #2563eb;
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }
        .subtitle {
            color: #64748b;
            font-size: 16px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            background: #f1f5f9;
            padding: 10px 15px;
            font-weight: bold;
            color: #1e293b;
            border-left: 4px solid #2563eb;
            margin-bottom: 15px;
        }
        .receipt-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .receipt-item {
            padding: 10px;
            background: #f8fafc;
            border-radius: 5px;
        }
        .receipt-label {
            font-weight: bold;
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
        }
        .receipt-value {
            color: #1e293b;
            font-size: 14px;
            margin-top: 5px;
        }
        .amount {
            font-size: 18px;
            font-weight: bold;
            color: #059669;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            font-weight: bold;
            color: #374151;
            font-size: 14px;
            margin-bottom: 5px;
            display: block;
        }
        .form-value {
            color: #1f2937;
            font-size: 14px;
            padding: 8px 12px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }
        .full-width {
            grid-column: 1 / -1;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
        .print-btn {
            background: #2563eb;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }
        .download-btn {
            background: #059669;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
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
        @media print { 
            body { background: white; }
            .receipt-container { box-shadow: none; }
            .print-btn, .download-btn { display: none; }
            .search-form { display: none; }
        }
    </style>
</head>
<body>
    <!-- Header Placeholder -->
    <div id="main-header"></div>
    
    <section class="section bg-light">
        <div class="container">
            <h2 class="section-title">View Receipt & Application Form</h2>
            <p class="section-subtitle">Enter your Application ID or Payment Reference to view your receipt</p>
            
            <?php echo $message; ?>
            
            <?php if (!$application): ?>
            <div class="row">
                <div class="col-md-6">
                    <form method="POST" class="card">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="application_id">Application ID</label>
                                <input type="text" id="application_id" name="application_id" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['application_id'] ?? ''); ?>" 
                                       placeholder="Enter your Application ID">
                            </div>
                            
                            <div class="form-group">
                                <label for="reference">OR Payment Reference</label>
                                <input type="text" id="reference" name="reference" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['reference'] ?? ''); ?>" 
                                       placeholder="Enter payment reference">
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i> View Receipt
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($application): ?>
            <div class="receipt-container">
                <div class="header">
                    <img src="img/logo.png" alt="Aries College Logo" class="logo">
                    <div class="title">Aries College of Health Management & Technology</div>
                    <div class="subtitle">Application Receipt & Form</div>
                </div>
                
                <!-- Payment Receipt Section -->
                <div class="section">
                    <div class="section-title">📄 PAYMENT RECEIPT</div>
                    <div class="receipt-grid">
                        <div class="receipt-item">
                            <div class="receipt-label">Receipt Number</div>
                            <div class="receipt-value"><?php echo $application['reference']; ?></div>
                        </div>
                        <div class="receipt-item">
                            <div class="receipt-label">Date</div>
                            <div class="receipt-value"><?php echo date('F j, Y \a\t g:i A'); ?></div>
                        </div>
                        <div class="receipt-item">
                            <div class="receipt-label">Amount Paid</div>
                            <div class="receipt-value amount">₦<?php echo number_format($application['amount']); ?></div>
                        </div>
                        <div class="receipt-item">
                            <div class="receipt-label">Payment Status</div>
                            <div class="receipt-value" style="color: #059669; font-weight: bold;">✓ PAID</div>
                        </div>
                    </div>
                </div>
                
                <!-- Application Form Section -->
                <div class="section">
                    <div class="section-title">📋 APPLICATION FORM</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Application ID</label>
                            <div class="form-value"><?php echo $application['id']; ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <div class="form-value"><?php echo htmlspecialchars($application['full_name']); ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <div class="form-value"><?php echo htmlspecialchars($application['email']); ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <div class="form-value"><?php echo htmlspecialchars($application['phone']); ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <div class="form-value"><?php echo date('F j, Y', strtotime($application['date_of_birth'])); ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <div class="form-value"><?php echo htmlspecialchars($application['gender']); ?></div>
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label">Address</label>
                            <div class="form-value"><?php echo htmlspecialchars($application['address']); ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">State</label>
                            <div class="form-value"><?php echo htmlspecialchars($application['state']); ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">LGA</label>
                            <div class="form-value"><?php echo htmlspecialchars($application['lga']); ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last School Attended</label>
                            <div class="form-value"><?php echo htmlspecialchars($application['last_school']); ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Qualification</label>
                            <div class="form-value"><?php echo htmlspecialchars($application['qualification']); ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Year Completed</label>
                            <div class="form-value"><?php echo date('F j, Y', strtotime($application['year_completed'])); ?></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Program Applied</label>
                            <div class="form-value"><?php echo htmlspecialchars($application['program_applied']); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Important Information -->
                <div class="section">
                    <div class="section-title">ℹ️ IMPORTANT INFORMATION</div>
                    <ul style="color: #374151; line-height: 1.6;">
                        <li>Keep this receipt and application form for your records</li>
                        <li>Your application is now under review by our admissions team</li>
                        <li>You will receive an update within 3-5 business days</li>
                        <li>Ensure your phone number is active for SMS notifications</li>
                        <li>For inquiries, contact: admissions@achtech.org.ng</li>
                    </ul>
                </div>
                
                <div class="footer">
                    <p>Aries College of Health Management & Technology</p>
                    <p>Old Bambo Group of Schools, Falade Layout, Oluyole Extension, Apata, Ibadan</p>
                    <p>Phone: 08108626169 | Email: info@achtech.org.ng</p>
                    <p>This is an official document. Please keep it safe.</p>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button onclick="window.print()" class="print-btn">
                        <i class="fas fa-print"></i> Print Receipt & Form
                    </button>
                    <button onclick="downloadPDF()" class="download-btn">
                        <i class="fas fa-download"></i> Download PDF
                    </button>
                </div>
            </div>
            <?php endif; ?>
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
    
    function downloadPDF() {
        // This would require a PDF generation library like TCPDF or DOMPDF
        // For now, we'll just trigger the print dialog
        window.print();
    }
    </script>
    
    <script src="js/main.js"></script>
</body>
</html>
