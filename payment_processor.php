<?php
require_once 'payment_config.php';
require_once 'backend/db_connect.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class PaymentProcessor {
    private $gateway;
    private $conn;
    
    public function __construct($gateway = null) {
        $this->gateway = $gateway ?: PAYMENT_GATEWAY;
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            throw new Exception('Database connection failed: ' . $this->conn->connect_error);
        }
    }
    
    /**
     * Initialize payment transaction
     */
    public function initializePayment($applicationId, $email, $amount = null) {
        $amount = $amount ?: APPLICATION_FEE;
        
        // Generate unique reference
        $reference = 'ACH_' . time() . '_' . $applicationId;
        
        // Create transaction record
        $stmt = $this->conn->prepare("INSERT INTO transactions (application_id, amount, currency, reference, status, payment_gateway, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $status = PAYMENT_STATUS_PENDING;
        $currency = CURRENCY;
        $gateway = $this->gateway;
        $stmt->bind_param("idssss", $applicationId, $amount, $currency, $reference, $status, $gateway);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create transaction record: ' . $this->conn->error);
        }
        
        $transactionId = $this->conn->insert_id;
        $stmt->close();
        
        // Initialize payment with selected gateway
        switch ($this->gateway) {
            case 'paystack':
                return $this->initializePaystackPayment($reference, $email, $amount, $applicationId);
            case 'flutterwave':
                return $this->initializeFlutterwavePayment($reference, $email, $amount, $applicationId);
            default:
                throw new Exception('Unsupported payment gateway');
        }
    }
    
    /**
     * Initialize Paystack payment
     */
    private function initializePaystackPayment($reference, $email, $amount, $applicationId) {
        $url = PAYSTACK_BASE_URL . '/transaction/initialize';
        
        $data = [
            'email' => $email,
            'amount' => $amount * 100, // Convert to kobo
            'reference' => $reference,
            'callback_url' => PAYMENT_SUCCESS_URL,
            'metadata' => [
                'application_id' => $applicationId,
                'custom_fields' => [
                    [
                        'display_name' => 'Application ID',
                        'variable_name' => 'application_id',
                        'value' => $applicationId
                    ]
                ]
            ]
        ];
        
        $headers = [
            'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
            'Content-Type: application/json'
        ];
        
        $response = $this->makeHttpRequest($url, 'POST', $data, $headers);
        
        if ($response['status'] && isset($response['data']['authorization_url'])) {
            return [
                'success' => true,
                'authorization_url' => $response['data']['authorization_url'],
                'reference' => $reference,
                'access_code' => $response['data']['access_code']
            ];
        } else {
            throw new Exception('Paystack payment initialization failed: ' . ($response['message'] ?? 'Unknown error'));
        }
    }
    
    /**
     * Initialize Flutterwave payment
     */
    private function initializeFlutterwavePayment($reference, $email, $amount, $applicationId) {
        $url = FLUTTERWAVE_BASE_URL . '/payments';
        
        $data = [
            'tx_ref' => $reference,
            'amount' => $amount,
            'currency' => CURRENCY,
            'redirect_url' => PAYMENT_SUCCESS_URL,
            'customer' => [
                'email' => $email
            ],
            'meta' => [
                'application_id' => $applicationId
            ],
            'customizations' => [
                'title' => 'Aries College Application Fee',
                'description' => 'Application fee payment for Aries College of Health Management & Technology',
                'logo' => 'https://achtech.org.ng/img/logo.png'
            ]
        ];
        
        $headers = [
            'Authorization: Bearer ' . FLUTTERWAVE_SECRET_KEY,
            'Content-Type: application/json'
        ];
        
        $response = $this->makeHttpRequest($url, 'POST', $data, $headers);
        
        if ($response['status'] === 'success' && isset($response['data']['link'])) {
            return [
                'success' => true,
                'authorization_url' => $response['data']['link'],
                'reference' => $reference
            ];
        } else {
            throw new Exception('Flutterwave payment initialization failed: ' . ($response['message'] ?? 'Unknown error'));
        }
    }
    
    /**
     * Verify payment
     */
    public function verifyPayment($reference) {
        switch ($this->gateway) {
            case 'paystack':
                return $this->verifyPaystackPayment($reference);
            case 'flutterwave':
                return $this->verifyFlutterwavePayment($reference);
            default:
                throw new Exception('Unsupported payment gateway');
        }
    }
    
    /**
     * Verify Paystack payment
     */
    private function verifyPaystackPayment($reference) {
        $url = PAYSTACK_BASE_URL . '/transaction/verify/' . $reference;
        
        $headers = [
            'Authorization: Bearer ' . PAYSTACK_SECRET_KEY
        ];
        
        $response = $this->makeHttpRequest($url, 'GET', null, $headers);
        
        if ($response['status'] && $response['data']['status'] === 'success') {
            return [
                'success' => true,
                'amount' => $response['data']['amount'] / 100, // Convert from kobo
                'reference' => $response['data']['reference'],
                'gateway_reference' => $response['data']['id'],
                'status' => PAYMENT_STATUS_SUCCESS
            ];
        } else {
            return [
                'success' => false,
                'status' => PAYMENT_STATUS_FAILED,
                'message' => $response['message'] ?? 'Payment verification failed'
            ];
        }
    }
    
    /**
     * Verify Flutterwave payment
     */
    private function verifyFlutterwavePayment($reference) {
        $url = FLUTTERWAVE_BASE_URL . '/transactions/' . $reference . '/verify';
        
        $headers = [
            'Authorization: Bearer ' . FLUTTERWAVE_SECRET_KEY
        ];
        
        try {
        $response = $this->makeHttpRequest($url, 'GET', null, $headers);
        
            error_log("Flutterwave verification response for $reference: " . json_encode($response));
            
            if ($response['status'] === 'success' && isset($response['data']['status'])) {
                // Handle different payment method statuses
                $paymentStatus = $response['data']['status'];
                $successfulStatuses = ['successful', 'completed', 'success', 'paid'];
                
                if (in_array(strtolower($paymentStatus), $successfulStatuses)) {
                    return [
                        'success' => true,
                        'amount' => $response['data']['amount'],
                        'reference' => $response['data']['tx_ref'],
                        'gateway_reference' => $response['data']['id'],
                        'status' => PAYMENT_STATUS_SUCCESS,
                        'payment_method' => $response['data']['payment_type'] ?? 'unknown'
                    ];
                } else {
                    return [
                        'success' => false,
                        'status' => PAYMENT_STATUS_FAILED,
                        'message' => 'Payment status: ' . $paymentStatus
                    ];
                }
        } else {
            return [
                'success' => false,
                'status' => PAYMENT_STATUS_FAILED,
                'message' => $response['message'] ?? 'Payment verification failed'
                ];
            }
        } catch (Exception $e) {
            error_log("Flutterwave verification error for $reference: " . $e->getMessage());
            return [
                'success' => false,
                'status' => PAYMENT_STATUS_FAILED,
                'message' => 'Verification error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update transaction status
     */
    public function updateTransactionStatus($reference, $status, $gatewayReference = null, $paymentMethod = null) {
        if ($gatewayReference && $paymentMethod) {
            // Update status, gateway_reference, and payment_method
            $stmt = $this->conn->prepare("UPDATE transactions SET status = ?, gateway_reference = ?, payment_method = ?, updated_at = NOW() WHERE reference = ?");
            $stmt->bind_param("ssss", $status, $gatewayReference, $paymentMethod, $reference);
        } elseif ($gatewayReference) {
            // Update both status and gateway_reference
            $stmt = $this->conn->prepare("UPDATE transactions SET status = ?, gateway_reference = ?, updated_at = NOW() WHERE reference = ?");
            $stmt->bind_param("sss", $status, $gatewayReference, $reference);
        } else {
            // Update only status
            $stmt = $this->conn->prepare("UPDATE transactions SET status = ?, updated_at = NOW() WHERE reference = ?");
            $stmt->bind_param("ss", $status, $reference);
        }
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update transaction status: ' . $this->conn->error);
        }
        
        $stmt->close();
        
        // If payment is successful, update application status
        if ($status === PAYMENT_STATUS_SUCCESS) {
            $this->updateApplicationPaymentStatus($reference);
        }
    }
    
    /**
     * Update application payment status
     */
    private function updateApplicationPaymentStatus($reference) {
        $stmt = $this->conn->prepare("
            UPDATE applications a 
            JOIN transactions t ON a.id = t.application_id 
            SET a.payment_status = 'paid', 
                a.payment_date = NOW(),
                a.application_status = 'submitted',
                a.updated_at = NOW()
            WHERE t.reference = ?
        ");
        $stmt->bind_param("s", $reference);
        $stmt->execute();
        $stmt->close();
        
        // Send confirmation email to applicant
        $this->sendPaymentConfirmationEmail($reference);
    }
    
    /**
     * Send payment confirmation email
     */
    private function sendPaymentConfirmationEmail($reference) {
        $stmt = $this->conn->prepare("
            SELECT a.*, t.amount, t.reference 
            FROM applications a 
            JOIN transactions t ON a.id = t.application_id 
            WHERE t.reference = ?
        ");
        $stmt->bind_param("s", $reference);
        $stmt->execute();
        $result = $stmt->get_result();
        $application = $result->fetch_assoc();
        $stmt->close();
        
        if (!$application) {
            return;
        }
        
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
            
            $mail->Subject = "Payment Confirmed - Application Complete - Aries College";
            
            $msg = "Dear " . $application['full_name'] . ",\n\n";
            $msg .= "🎉 CONGRATULATIONS! Your payment has been confirmed successfully!\n\n";
            $msg .= "Application Details:\n";
            $msg .= "Application ID: " . $application['id'] . "\n";
            $msg .= "Full Name: " . $application['full_name'] . "\n";
            $msg .= "Email: " . $application['email'] . "\n";
            $msg .= "Program Applied: " . $application['program_applied'] . "\n";
            $msg .= "Payment Amount: ₦" . number_format($application['amount']) . "\n";
            $msg .= "Payment Reference: " . $application['reference'] . "\n";
            $msg .= "Payment Date: " . date('F j, Y \a\t g:i A') . "\n\n";
            $msg .= "Your application is now complete and has been submitted for review.\n";
            $msg .= "Our admissions team will review your application and contact you within 3-5 business days.\n\n";
            $msg .= "Next Steps:\n";
            $msg .= "1. Keep this email for your records\n";
            $msg .= "2. Check your email regularly for updates\n";
            $msg .= "3. Ensure your phone number is active for SMS notifications\n\n";
            $msg .= "If you have any questions, please contact us at admissions@achtech.org.ng\n\n";
            $msg .= "Thank you for choosing Aries College of Health Management & Technology!\n\n";
            $msg .= "Best regards,\n";
            $msg .= "Admissions Team\n";
            $msg .= "Aries College";
            
            $mail->Body = $msg;
            $mail->send();
            
            // Send detailed receipt and application form
            $this->sendDetailedReceiptAndForm($application);
            
        } catch (PHPMailerException $e) {
            error_log("Payment confirmation email failed: " . $e->getMessage());
        }
    }
    
    /**
     * Send detailed receipt and application form
     */
    private function sendDetailedReceiptAndForm($application) {
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
            
            $mail->Subject = "Application Receipt & Form - Aries College";
            $mail->isHTML(true);
            
            // Generate HTML receipt and form
            $htmlContent = $this->generateReceiptAndFormHTML($application);
            $mail->Body = $htmlContent;
            $mail->AltBody = $this->generateReceiptAndFormText($application);
            
            $mail->send();
            
        } catch (PHPMailerException $e) {
            error_log("Detailed receipt email failed: " . $e->getMessage());
        }
    }
    
    /**
     * Generate HTML receipt and application form
     */
    private function generateReceiptAndFormHTML($application) {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Application Receipt & Form - Aries College</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
                .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { text-align: center; border-bottom: 3px solid #2563eb; padding-bottom: 20px; margin-bottom: 30px; }
                .logo { width: 120px; height: auto; }
                .title { color: #2563eb; font-size: 24px; font-weight: bold; margin: 10px 0; }
                .subtitle { color: #64748b; font-size: 16px; }
                .section { margin-bottom: 30px; }
                .section-title { background: #f1f5f9; padding: 10px 15px; font-weight: bold; color: #1e293b; border-left: 4px solid #2563eb; margin-bottom: 15px; }
                .receipt-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
                .receipt-item { padding: 10px; background: #f8fafc; border-radius: 5px; }
                .receipt-label { font-weight: bold; color: #64748b; font-size: 12px; text-transform: uppercase; }
                .receipt-value { color: #1e293b; font-size: 14px; margin-top: 5px; }
                .amount { font-size: 18px; font-weight: bold; color: #059669; }
                .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
                .form-group { margin-bottom: 15px; }
                .form-label { font-weight: bold; color: #374151; font-size: 14px; margin-bottom: 5px; display: block; }
                .form-value { color: #1f2937; font-size: 14px; padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; }
                .full-width { grid-column: 1 / -1; }
                .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; color: #6b7280; font-size: 12px; }
                .print-btn { background: #2563eb; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
                @media print { 
                    body { background: white; }
                    .container { box-shadow: none; }
                    .print-btn { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <img src="https://achtech.org.ng/img/logo.png" alt="Aries College Logo" class="logo">
                    <div class="title">Aries College of Health Management & Technology</div>
                    <div class="subtitle">Application Receipt & Form</div>
                </div>
                
                <!-- Payment Receipt Section -->
                <div class="section">
                    <div class="section-title">📄 PAYMENT RECEIPT</div>
                    <div class="receipt-grid">
                        <div class="receipt-item">
                            <div class="receipt-label">Receipt Number</div>
                            <div class="receipt-value">' . $application['reference'] . '</div>
                        </div>
                        <div class="receipt-item">
                            <div class="receipt-label">Date</div>
                            <div class="receipt-value">' . date('F j, Y \a\t g:i A') . '</div>
                        </div>
                        <div class="receipt-item">
                            <div class="receipt-label">Amount Paid</div>
                            <div class="receipt-value amount">₦' . number_format($application['amount']) . '</div>
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
                
                <button onclick="window.print()" class="print-btn">🖨️ Print Receipt & Form</button>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Generate text version of receipt and form
     */
    private function generateReceiptAndFormText($application) {
        $text = "ARIES COLLEGE OF HEALTH MANAGEMENT & TECHNOLOGY\n";
        $text .= "Application Receipt & Form\n\n";
        
        $text .= "PAYMENT RECEIPT:\n";
        $text .= "Receipt Number: " . $application['reference'] . "\n";
        $text .= "Date: " . date('F j, Y \a\t g:i A') . "\n";
        $text .= "Amount Paid: ₦" . number_format($application['amount']) . "\n";
        $text .= "Payment Status: PAID\n\n";
        
        $text .= "APPLICATION FORM:\n";
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
        $text .= "Year Completed: " . date('F j, Y', strtotime($application['year_completed'])) . "\n";
        $text .= "Program Applied: " . $application['program_applied'] . "\n\n";
        
        $text .= "IMPORTANT: Keep this receipt and form for your records.\n";
        $text .= "Your application is under review. You will receive an update within 3-5 business days.\n\n";
        
        $text .= "Aries College of Health Management & Technology\n";
        $text .= "Old Bambo Group of Schools, Falade Layout, Oluyole Extension, Apata, Ibadan\n";
        $text .= "Phone: 08108626169 | Email: info@achtech.org.ng";
        
        return $text;
    }
    
    /**
     * Make HTTP request
     */
    private function makeHttpRequest($url, $method = 'GET', $data = null, $headers = []) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing only
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // For testing only
        
        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Log the request and response for debugging
        error_log("Payment API Request - URL: $url, Method: $method, Data: " . json_encode($data));
        error_log("Payment API Response - HTTP Code: $httpCode, Response: $response");
        
        if ($error) {
            error_log("cURL Error: " . $error);
            throw new Exception('cURL error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            error_log("HTTP Error: $httpCode - Response: $response");
            throw new Exception('HTTP error: ' . $httpCode . ' - Response: ' . $response);
        }
        
        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON Decode Error: " . json_last_error_msg() . " - Response: $response");
            throw new Exception('Invalid JSON response: ' . json_last_error_msg());
        }
        
        return $decodedResponse;
    }
    
    /**
     * Get transaction by reference
     */
    public function getTransactionByReference($reference) {
        $stmt = $this->conn->prepare("SELECT * FROM transactions WHERE reference = ?");
        $stmt->bind_param("s", $reference);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result->fetch_assoc();
        $stmt->close();
        
        return $transaction;
    }
    
    /**
     * Get transaction by gateway reference (Flutterwave transaction ID)
     */
    public function getTransactionByGatewayReference($gatewayReference) {
        $stmt = $this->conn->prepare("SELECT * FROM transactions WHERE gateway_reference = ?");
        $stmt->bind_param("s", $gatewayReference);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result->fetch_assoc();
        $stmt->close();
        
        return $transaction;
    }
    
    /**
     * Get application by ID
     */
    public function getApplicationById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM applications WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $application = $result->fetch_assoc();
        $stmt->close();
        
        return $application;
    }
    
    /**
     * Close database connection
     */
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>


