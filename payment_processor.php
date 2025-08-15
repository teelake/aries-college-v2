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
        $stmt->bind_param("idssss", $applicationId, $amount, CURRENCY, $reference, $status, $this->gateway);
        
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
        
        $response = $this->makeHttpRequest($url, 'GET', null, $headers);
        
        if ($response['status'] === 'success' && $response['data']['status'] === 'successful') {
            return [
                'success' => true,
                'amount' => $response['data']['amount'],
                'reference' => $response['data']['tx_ref'],
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
     * Update transaction status
     */
    public function updateTransactionStatus($reference, $status, $gatewayReference = null) {
        $stmt = $this->conn->prepare("UPDATE transactions SET status = ?, gateway_reference = ?, updated_at = NOW() WHERE reference = ?");
        $stmt->bind_param("sss", $status, $gatewayReference, $reference);
        
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
            
        } catch (PHPMailerException $e) {
            error_log("Payment confirmation email failed: " . $e->getMessage());
        }
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
        
        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('HTTP error: ' . $httpCode);
        }
        
        return json_decode($response, true);
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


