<?php
session_start();
require_once 'backend/db_connect.php';
require_once 'payment_processor.php';

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
    $resultStatus = clean($_POST['resultStatus'] ?? '', $conn);
    $yearCompleted = clean($_POST['yearCompleted'] ?? '', $conn);
    $course = clean($_POST['course'] ?? '', $conn);
    
    // Validate required fields
    if (!$fullName || !$email || !$phone || !$dateOfBirth || !$gender || !$address || !$state || !$lga || !$qualification || !$resultStatus || !$yearCompleted || !$course) {
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
    
    // Handle certificate upload based on result status
    if ($resultStatus === 'awaiting_result') {
        $certificatePath = ''; // No certificate required for awaiting result
    } else {
        if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK) {
            $certExt = pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION);
            $certificatePath = $certificatesDir . uniqid('cert_') . '.' . $certExt;
            if (!move_uploaded_file($_FILES['certificate']['tmp_name'], $certificatePath)) {
                throw new Exception('Failed to upload certificate.');
            }
        } else {
            throw new Exception('Certificate upload is required when result is available.');
        }
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
    $stmt = $conn->prepare("INSERT INTO applications (full_name, email, phone, date_of_birth, gender, address, state, lga, last_school, qualification, result_status, year_completed, program_applied, photo_path, certificate_path, payment_status, application_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'submitted', NOW())");
    $stmt->bind_param("sssssssssssssss", $fullName, $email, $phone, $dateOfBirth, $gender, $address, $state, $lga, $lastSchool, $qualification, $resultStatus, $yearCompleted, $course, $photoPath, $certificatePath);
    
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
    
    // Send email with application details and payment link
    try {
        sendApplicationEmailWithPaymentLink($applicationId, $email, $fullName, $paymentResult['authorization_url'], $paymentResult['reference']);
        error_log("Email sent successfully to: $email for application ID: $applicationId");
    } catch (Exception $emailError) {
        error_log("SMTP email failed for application ID $applicationId: " . $emailError->getMessage());
        
        // Try fallback email method
        try {
            sendFallbackEmail($applicationId, $email, $fullName, $paymentResult['authorization_url'], $paymentResult['reference']);
            error_log("Fallback email sent successfully to: $email for application ID: $applicationId");
        } catch (Exception $fallbackError) {
            error_log("Both SMTP and fallback email failed for application ID $applicationId: " . $fallbackError->getMessage());
            // Don't fail the application if email fails
        }
    }
    
    // Return success response with payment redirect
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully! Redirecting to payment...',
        'data' => [
            'application_id' => $applicationId,
            'payment_url' => $paymentResult['authorization_url'],
            'reference' => $paymentResult['reference'],
            'email_sent' => true
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

/**
 * Send application confirmation email with payment link
 */
function sendApplicationEmailWithPaymentLink($applicationId, $email, $fullName, $paymentUrl, $reference) {
    require_once __DIR__ . '/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/phpmailer/SMTP.php';
    require_once __DIR__ . '/phpmailer/Exception.php';
    
    // Get application details from database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    $stmt = $conn->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $application = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    if (!$application) {
        throw new Exception('Application not found');
    }
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'mail.achtech.org.ng';
        $mail->SMTPAuth = true;
        $mail->Username = 'no-reply@achtech.org.ng';
        $mail->Password = 'Temp_pass123';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->SMTPDebug = 2; // Enable debug to see what's happening
        $mail->Debugoutput = 'error_log';
        
        $mail->setFrom('no-reply@achtech.org.ng', 'Aries College');
        $mail->addAddress($email, $fullName);
        
        $mail->Subject = "Application Received - Complete Your Payment - Aries College";
        $mail->isHTML(true);
        
        // Generate HTML email content
        $htmlContent = generateApplicationEmailHTML($application, $paymentUrl, $reference);
        $mail->Body = $htmlContent;
        $mail->AltBody = generateApplicationEmailText($application, $paymentUrl, $reference);
        
        error_log("Attempting to send email to: $email");
        $result = $mail->send();
        error_log("Email send result: " . ($result ? 'Success' : 'Failed'));
        
    } catch (PHPMailer\PHPMailer\Exception $e) {
        error_log("PHPMailer Exception: " . $e->getMessage());
        error_log("PHPMailer Error Code: " . $e->getCode());
        throw new Exception("Email sending failed: " . $e->getMessage());
    } catch (Exception $e) {
        error_log("General Exception in email sending: " . $e->getMessage());
        throw new Exception("Email sending failed: " . $e->getMessage());
    }
}

/**
 * Generate HTML email content for application confirmation
 */
function generateApplicationEmailHTML($application, $paymentUrl, $reference) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Application Received - Aries College</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { text-align: center; border-bottom: 3px solid #2563eb; padding-bottom: 20px; margin-bottom: 30px; }
            .logo { width: 120px; height: auto; }
            .title { color: #2563eb; font-size: 24px; font-weight: bold; margin: 10px 0; }
            .subtitle { color: #64748b; font-size: 16px; }
            .section { margin-bottom: 25px; }
            .section-title { background: #f1f5f9; padding: 10px 15px; font-weight: bold; color: #1e293b; border-left: 4px solid #2563eb; margin-bottom: 15px; }
            .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
            .form-group { margin-bottom: 15px; }
            .form-label { font-weight: bold; color: #374151; font-size: 14px; margin-bottom: 5px; display: block; }
            .form-value { color: #1f2937; font-size: 14px; padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; }
            .full-width { grid-column: 1 / -1; }
            .payment-section { background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 20px; text-align: center; margin: 25px 0; }
            .payment-btn { background: #10b981; color: white; padding: 15px 30px; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; text-decoration: none; display: inline-block; margin: 10px 0; }
            .payment-btn:hover { background: #059669; }
            .payment-amount { font-size: 20px; font-weight: bold; color: #059669; }
            .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; color: #6b7280; font-size: 12px; }
            .warning { background: #fef2f2; border: 1px solid #fecaca; border-radius: 5px; padding: 15px; margin: 15px 0; color: #dc2626; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <img src="https://achtech.org.ng/img/logo.png" alt="Aries College Logo" class="logo">
                <div class="title">Aries College of Health Management & Technology</div>
                <div class="subtitle">Application Received</div>
            </div>
            
            <div class="section">
                <h2 style="color: #059669; text-align: center;">✅ Application Successfully Received!</h2>
                <p style="text-align: center; color: #64748b;">Dear ' . htmlspecialchars($application['full_name']) . ', your application has been successfully submitted and you should have been redirected to complete payment.</p>
            </div>
            
            <!-- Payment Section -->
            <div class="payment-section">
                <h3 style="color: #92400e; margin-top: 0;">💳 Complete Your Payment</h3>
                <p style="margin: 10px 0;">Application Fee: <span class="payment-amount">₦10,230</span></p>
                <p style="color: #dc2626; font-size: 16px; font-weight: bold; margin: 15px 0;">⚠️ If you have NOT completed your payment yet, please click the button below to complete your payment now!</p>
                <a href="' . generatePaymentPageUrl($applicationId, $email) . '" class="payment-btn">🛒 Complete Payment - ₦10,230</a>
                <p style="font-size: 12px; color: #92400e; margin-top: 10px;">Payment Reference: ' . $reference . '</p>
                <p style="color: #92400e; font-size: 14px; margin-top: 15px; font-style: italic;">Your application will only be processed after successful payment.</p>
            </div>
            
            <!-- Application Details -->
            <div class="section">
                <div class="section-title">📋 Your Application Details</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Application ID</label>
                        <div class="form-value">' . $application['id'] . '</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <div class="form-value">' . htmlspecialchars($application['full_name']) . '</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <div class="form-value">' . htmlspecialchars($application['email']) . '</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <div class="form-value">' . htmlspecialchars($application['phone']) . '</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <div class="form-value">' . date('F j, Y', strtotime($application['date_of_birth'])) . '</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <div class="form-value">' . htmlspecialchars($application['gender']) . '</div>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Address</label>
                        <div class="form-value">' . htmlspecialchars($application['address']) . '</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">State</label>
                        <div class="form-value">' . htmlspecialchars($application['state']) . '</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">LGA</label>
                        <div class="form-value">' . htmlspecialchars($application['lga']) . '</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last School Attended</label>
                        <div class="form-value">' . htmlspecialchars($application['last_school']) . '</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Qualification</label>
                        <div class="form-value">' . htmlspecialchars($application['qualification']) . '</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Result Status</label>
                        <div class="form-value">' . ucfirst(str_replace('_', ' ', $application['result_status'])) . '</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Year Completed</label>
                        <div class="form-value">' . date('F j, Y', strtotime($application['year_completed'])) . '</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Program Applied</label>
                        <div class="form-value">' . htmlspecialchars($application['program_applied']) . '</div>
                    </div>
                </div>
            </div>
            
            <!-- Important Information -->
            <div class="section">
                <div class="section-title">ℹ️ Important Information</div>
                <ul style="color: #374151; line-height: 1.6;">
                    <li><strong>Payment Status:</strong> If you have NOT completed payment yet, please use the payment link above</li>
                    <li><strong>Application Processing:</strong> Your application will only be processed after successful payment</li>
                    <li><strong>Payment Link:</strong> You can use the payment link in this email anytime to complete your payment</li>
                    <li><strong>Secure Payment:</strong> All payments are processed securely through Flutterwave/Paystack</li>
                    <li><strong>Support:</strong> Contact us at admissions@achtech.org.ng if you have any questions</li>
                </ul>
            </div>
            
            <div class="warning">
                <strong>⚠️ URGENT:</strong> If you have NOT completed your payment yet, please click the payment button above immediately. Your application will remain pending until payment is confirmed. This email serves as your backup payment link.
            </div>
            
            <div class="footer">
                <p>Aries College of Health Management & Technology</p>
                <p>Old Bambo Group of Schools, Falade Layout, Oluyole Extension, Apata, Ibadan</p>
                <p>Phone: 0901 216 4632 | Email: info@achtech.org.ng</p>
                <p>This email contains your application details and payment link. Please keep it safe.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

/**
 * Generate text version of application email
 */
function generateApplicationEmailText($application, $paymentUrl, $reference) {
    $text = "ARIES COLLEGE OF HEALTH MANAGEMENT & TECHNOLOGY\n";
    $text .= "Application Received - Complete Your Payment\n\n";
    
    $text .= "Dear " . $application['full_name'] . ",\n\n";
    $text .= "✅ Your application has been successfully submitted!\n\n";
    $text .= "Your application has been submitted and you should have been redirected to complete payment.\n\n";
    
    $text .= "⚠️ IMPORTANT - PAYMENT REQUIRED:\n";
    $text .= "If you have NOT completed your payment yet, please use the payment link below immediately!\n\n";
    $text .= "Application Fee: ₦10,230\n";
    $text .= "Payment Reference: " . $reference . "\n";
    $text .= "Payment Link: " . generatePaymentPageUrl($application['id'], $application['email']) . "\n\n";
    
    $text .= "APPLICATION DETAILS:\n";
    $text .= "Application ID: " . $application['id'] . "\n";
    $text .= "Full Name: " . $application['full_name'] . "\n";
    $text .= "Email: " . $application['email'] . "\n";
    $text .= "Phone: " . $application['phone'] . "\n";
    $text .= "Date of Birth: " . date('F j, Y', strtotime($application['date_of_birth'])) . "\n";
    $text .= "Gender: " . $application['gender'] . "\n";
    $text .= "Address: " . $application['address'] . "\n";
    $text .= "State: " . $application['state'] . "\n";
    $text .= "LGA: " . $application['lga'] . "\n";
    $text .= "Last School: " . $application['last_school'] . "\n";
    $text .= "Qualification: " . $application['qualification'] . "\n";
    $text .= "Result Status: " . ucfirst(str_replace('_', ' ', $application['result_status'])) . "\n";
    $text .= "Year Completed: " . date('F j, Y', strtotime($application['year_completed'])) . "\n";
    $text .= "Program Applied: " . $application['program_applied'] . "\n\n";
    
    $text .= "URGENT - IMPORTANT:\n";
    $text .= "- If you have NOT completed payment yet, please use the payment link above immediately!\n";
    $text .= "- Your application will only be processed after successful payment\n";
    $text .= "- This email serves as your backup payment link\n";
    $text .= "- All payments are processed securely\n";
    $text .= "- Contact admissions@achtech.org.ng if you have questions\n\n";
    
    $text .= "Aries College of Health Management & Technology\n";
    $text .= "Old Bambo Group of Schools, Falade Layout, Oluyole Extension, Apata, Ibadan\n";
    $text .= "Phone: 0901 216 4632 | Email: info@achtech.org.ng";
    
    return $text;
}

/**
 * Generate payment page URL
 */
function generatePaymentPageUrl($applicationId, $email) {
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    $paymentPageUrl = $baseUrl . $path . '/complete_payment.php';
    
    return $paymentPageUrl . '?app_id=' . $applicationId . '&email=' . urlencode($email);
}

/**
 * Fallback email function using PHP mail() function
 */
function sendFallbackEmail($applicationId, $email, $fullName, $paymentUrl, $reference) {
    // Get application details from database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    $stmt = $conn->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $application = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    if (!$application) {
        throw new Exception('Application not found');
    }
    
    $paymentPageUrl = generatePaymentPageUrl($applicationId, $email);
    
    // Prepare email content
    $subject = "Application Received - Complete Your Payment - Aries College";
    
    // Simple HTML email
    $htmlContent = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
            .header { text-align: center; border-bottom: 3px solid #2563eb; padding-bottom: 20px; margin-bottom: 30px; }
            .title { color: #2563eb; font-size: 24px; font-weight: bold; }
            .payment-section { background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 20px; text-align: center; margin: 25px 0; }
            .payment-btn { background: #10b981; color: white; padding: 15px 30px; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; text-decoration: none; display: inline-block; margin: 10px 0; }
            .payment-amount { font-size: 20px; font-weight: bold; color: #059669; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div class='title'>Aries College of Health Management & Technology</div>
                <div>Application Received</div>
            </div>
            
            <h2 style='color: #059669; text-align: center;'>✅ Application Successfully Received!</h2>
            <p style='text-align: center;'>Dear " . htmlspecialchars($application['full_name']) . ", thank you for submitting your application to Aries College. Please complete your payment to finalize your application.</p>
            
            <div class='payment-section'>
                <h3>💳 Complete Your Payment</h3>
                <p>Application Fee: <span class='payment-amount'>₦10,230</span></p>
                <p>Please click the button below to complete your payment securely and finalize your application.</p>
                <a href='" . $paymentPageUrl . "' class='payment-btn'>🛒 Pay Now - ₦10,230</a>
                <p style='font-size: 12px; color: #92400e; margin-top: 10px;'>Payment Reference: " . $reference . "</p>
                <p style='color: #92400e; font-size: 14px; margin-top: 15px; font-style: italic;'>Your application will only be processed after successful payment.</p>
            </div>
            
            <h3>📋 Your Application Details</h3>
            <p><strong>Application ID:</strong> " . $application['id'] . "</p>
            <p><strong>Full Name:</strong> " . htmlspecialchars($application['full_name']) . "</p>
            <p><strong>Email:</strong> " . htmlspecialchars($application['email']) . "</p>
            <p><strong>Phone:</strong> " . htmlspecialchars($application['phone']) . "</p>
            <p><strong>Program Applied:</strong> " . htmlspecialchars($application['program_applied']) . "</p>
            
            <h3>ℹ️ Important Information</h3>
            <ul>
                <li><strong>Next Step:</strong> Please complete your payment using the link above to finalize your application</li>
                <li><strong>Application Processing:</strong> Your application will only be processed after successful payment</li>
                <li><strong>Payment Link:</strong> You can use the payment link in this email anytime to complete your payment</li>
                <li><strong>Secure Payment:</strong> All payments are processed securely through Flutterwave/Paystack</li>
                <li><strong>Support:</strong> Contact us at admissions@achtech.org.ng if you have any questions</li>
            </ul>
            
            <p style='background: #fef3c7; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <strong>💡 Note:</strong> This email contains your application details and payment link. Please complete your payment as soon as possible to ensure your application is processed. You can use the payment link in this email at any time.
            </p>
            
            <div style='margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; color: #6b7280; font-size: 12px;'>
                <p>Aries College of Health Management & Technology</p>
                <p>Old Bambo Group of Schools, Falade Layout, Oluyole Extension, Apata, Ibadan</p>
                <p>Phone: 0901 216 4632 | Email: info@achtech.org.ng</p>
                <p>This email contains your application details and payment link. Please keep it safe.</p>
            </div>
        </div>
    </body>
    </html>";
    
    // Simple text version
    $textContent = "ARIES COLLEGE OF HEALTH MANAGEMENT & TECHNOLOGY
Application Received - Complete Your Payment

Dear " . $application['full_name'] . ",

✅ Your application has been successfully submitted!
Thank you for submitting your application to Aries College. Please complete your payment to finalize your application.

PAYMENT REQUIRED:
Application Fee: ₦10,230
Payment Reference: " . $reference . "
Payment Link: " . $paymentPageUrl . "

APPLICATION DETAILS:
Application ID: " . $application['id'] . "
Full Name: " . $application['full_name'] . "
Email: " . $application['email'] . "
Phone: " . $application['phone'] . "
Program Applied: " . $application['program_applied'] . "

IMPORTANT:
- Please complete your payment using the link above to finalize your application
- Your application will only be processed after successful payment
- You can use the payment link in this email anytime to complete your payment
- All payments are processed securely
- Contact admissions@achtech.org.ng if you have questions

Aries College of Health Management & Technology
Old Bambo Group of Schools, Falade Layout, Oluyole Extension, Apata, Ibadan
Phone: 0901 216 4632 | Email: info@achtech.org.ng";
    
    // Email headers
    $headers = "From: Aries College <no-reply@achtech.org.ng>\r\n";
    $headers .= "Reply-To: no-reply@achtech.org.ng\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    // Send email using PHP mail function
    if (!mail($email, $subject, $htmlContent, $headers)) {
        throw new Exception("PHP mail function failed");
    }
}
?>


