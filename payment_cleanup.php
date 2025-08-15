<?php
/**
 * Payment Cleanup Script
 * This script handles cleanup of incomplete applications and failed payments
 * Should be run periodically via cron job
 */

require_once 'backend/db_connect.php';
require_once 'payment_processor.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class PaymentCleanup {
    private $conn;
    private $paymentProcessor;
    
    public function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            throw new Exception('Database connection failed: ' . $this->conn->connect_error);
        }
        $this->paymentProcessor = new PaymentProcessor();
    }
    
    /**
     * Clean up incomplete applications older than specified days
     */
    public function cleanupIncompleteApplications($daysOld = 7) {
        $stmt = $this->conn->prepare("
            SELECT a.*, t.reference 
            FROM applications a 
            LEFT JOIN transactions t ON a.id = t.application_id 
            WHERE a.payment_status = 'pending' 
            AND a.application_status = 'pending_payment'
            AND a.created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->bind_param("i", $daysOld);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $cleanedCount = 0;
        while ($application = $result->fetch_assoc()) {
            $this->cleanupApplication($application);
            $cleanedCount++;
        }
        $stmt->close();
        
        return $cleanedCount;
    }
    
    /**
     * Clean up a specific application
     */
    private function cleanupApplication($application) {
        // Delete uploaded files
        if (!empty($application['photo_path']) && file_exists($application['photo_path'])) {
            unlink($application['photo_path']);
        }
        if (!empty($application['certificate_path']) && file_exists($application['certificate_path'])) {
            unlink($application['certificate_path']);
        }
        
        // Delete related transactions
        $stmt = $this->conn->prepare("DELETE FROM transactions WHERE application_id = ?");
        $stmt->bind_param("i", $application['id']);
        $stmt->execute();
        $stmt->close();
        
        // Delete application
        $stmt = $this->conn->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->bind_param("i", $application['id']);
        $stmt->execute();
        $stmt->close();
        
        // Log cleanup
        error_log("Cleaned up incomplete application ID: " . $application['id'] . " for email: " . $application['email']);
    }
    
    /**
     * Send reminder emails for pending payments
     */
    public function sendPaymentReminders($daysOld = 3) {
        $stmt = $this->conn->prepare("
            SELECT a.*, t.reference, t.amount 
            FROM applications a 
            LEFT JOIN transactions t ON a.id = t.application_id 
            WHERE a.payment_status = 'pending' 
            AND a.application_status = 'pending_payment'
            AND a.created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            AND a.reminder_sent = 0
        ");
        $stmt->bind_param("i", $daysOld);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reminderCount = 0;
        while ($application = $result->fetch_assoc()) {
            $this->sendReminderEmail($application);
            $reminderCount++;
        }
        $stmt->close();
        
        return $reminderCount;
    }
    
    /**
     * Send reminder email
     */
    private function sendReminderEmail($application) {
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
            
            $mail->Subject = "Payment Reminder - Complete Your Application - Aries College";
            
            $msg = "Dear " . $application['full_name'] . ",\n\n";
            $msg .= "We noticed that you haven't completed the payment for your application yet.\n\n";
            $msg .= "Application Details:\n";
            $msg .= "Application ID: " . $application['id'] . "\n";
            $msg .= "Program Applied: " . $application['program_applied'] . "\n";
            $msg .= "Payment Amount: ₦" . number_format($application['amount']) . "\n";
            $msg .= "Payment Reference: " . $application['reference'] . "\n\n";
            $msg .= "To complete your application, please click the payment link below:\n";
            $msg .= "https://achtech.org.ng/payment_processor.php?ref=" . $application['reference'] . "\n\n";
            $msg .= "IMPORTANT: Your application will be automatically deleted if payment is not completed within 7 days.\n\n";
            $msg .= "If you're having trouble with payment, please contact us immediately at support@achtech.org.ng\n\n";
            $msg .= "Thank you for choosing Aries College!\n\n";
            $msg .= "Best regards,\n";
            $msg .= "Admissions Team\n";
            $msg .= "Aries College";
            
            $mail->Body = $msg;
            $mail->send();
            
            // Mark reminder as sent
            $stmt = $this->conn->prepare("UPDATE applications SET reminder_sent = 1 WHERE id = ?");
            $stmt->bind_param("i", $application['id']);
            $stmt->execute();
            $stmt->close();
            
        } catch (PHPMailerException $e) {
            error_log("Payment reminder email failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get statistics
     */
    public function getStatistics() {
        $stats = [];
        
        // Pending payments
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM applications 
            WHERE payment_status = 'pending' AND application_status = 'pending_payment'
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['pending_payments'] = $result->fetch_assoc()['count'];
        $stmt->close();
        
        // Completed applications
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM applications 
            WHERE payment_status = 'paid' AND application_status = 'submitted'
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['completed_applications'] = $result->fetch_assoc()['count'];
        $stmt->close();
        
        // Failed payments
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM transactions 
            WHERE status = 'failed'
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['failed_payments'] = $result->fetch_assoc()['count'];
        $stmt->close();
        
        return $stats;
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

// CLI usage
if (php_sapi_name() === 'cli') {
    $cleanup = new PaymentCleanup();
    
    if (isset($argv[1])) {
        switch ($argv[1]) {
            case 'cleanup':
                $days = isset($argv[2]) ? (int)$argv[2] : 7;
                $count = $cleanup->cleanupIncompleteApplications($days);
                echo "Cleaned up $count incomplete applications\n";
                break;
                
            case 'reminders':
                $days = isset($argv[2]) ? (int)$argv[2] : 3;
                $count = $cleanup->sendPaymentReminders($days);
                echo "Sent $count payment reminders\n";
                break;
                
            case 'stats':
                $stats = $cleanup->getStatistics();
                echo "Statistics:\n";
                echo "Pending payments: " . $stats['pending_payments'] . "\n";
                echo "Completed applications: " . $stats['completed_applications'] . "\n";
                echo "Failed payments: " . $stats['failed_payments'] . "\n";
                break;
                
            default:
                echo "Usage: php payment_cleanup.php [cleanup|reminders|stats] [days]\n";
        }
    } else {
        echo "Usage: php payment_cleanup.php [cleanup|reminders|stats] [days]\n";
    }
}
?>
