<?php
// Test Email Script - Debug Email Issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "<h1>Email Test - Aries College</h1>";

// Test email configuration
$testEmail = 'test@example.com'; // Change this to your email for testing
$smtpHost = 'mail.achtech.org.ng';
$smtpUsername = 'no-reply@achtech.org.ng';
$smtpPassword = 'Temp_pass123';
$smtpPort = 465;
$smtpFrom = 'no-reply@achtech.org.ng';
$smtpFromName = 'Aries College';

echo "<h2>Testing Email Configuration</h2>";
echo "<p><strong>SMTP Host:</strong> $smtpHost</p>";
echo "<p><strong>SMTP Port:</strong> $smtpPort</p>";
echo "<p><strong>SMTP Username:</strong> $smtpUsername</p>";
echo "<p><strong>From Email:</strong> $smtpFrom</p>";
echo "<p><strong>Test Email:</strong> $testEmail</p>";

// Test 1: Basic SMTP Connection
echo "<h2>Test 1: SMTP Connection Test</h2>";
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUsername;
    $mail->Password = $smtpPassword;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = $smtpPort;
    $mail->SMTPDebug = 2; // Enable verbose debug output
    
    echo "<p style='color: green;'>✓ SMTP Configuration loaded successfully</p>";
    
    // Test connection without sending
    $mail->smtpConnect();
    echo "<p style='color: green;'>✓ SMTP Connection successful</p>";
    $mail->smtpClose();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ SMTP Connection failed: " . $e->getMessage() . "</p>";
    echo "<p><strong>Error Details:</strong> " . $e->getTraceAsString() . "</p>";
}

// Test 2: Send Test Email
echo "<h2>Test 2: Send Test Email</h2>";
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUsername;
    $mail->Password = $smtpPassword;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = $smtpPort;
    $mail->SMTPDebug = 0; // Disable debug for actual sending
    
    $mail->setFrom($smtpFrom, $smtpFromName);
    $mail->addAddress($testEmail, 'Test User');
    
    $mail->Subject = 'Test Email from Aries College - ' . date('Y-m-d H:i:s');
    $mail->isHTML(true);
    $mail->Body = '<h1>Test Email</h1><p>This is a test email from Aries College system.</p><p>Time: ' . date('Y-m-d H:i:s') . '</p>';
    $mail->AltBody = 'Test Email - This is a test email from Aries College system. Time: ' . date('Y-m-d H:i:s');
    
    if ($mail->send()) {
        echo "<p style='color: green;'>✓ Test email sent successfully to $testEmail</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to send test email</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Email sending failed: " . $e->getMessage() . "</p>";
    echo "<p><strong>Error Details:</strong> " . $e->getTraceAsString() . "</p>";
}

// Test 3: Check PHP Mail Function
echo "<h2>Test 3: PHP Mail Function Test</h2>";
if (function_exists('mail')) {
    echo "<p style='color: green;'>✓ PHP mail() function is available</p>";
    
    $headers = "From: $smtpFrom\r\n";
    $headers .= "Reply-To: $smtpFrom\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $subject = 'PHP Mail Test - ' . date('Y-m-d H:i:s');
    $message = '<h1>PHP Mail Test</h1><p>This is a test using PHP mail() function.</p>';
    
    if (mail($testEmail, $subject, $message, $headers)) {
        echo "<p style='color: green;'>✓ PHP mail() function works</p>";
    } else {
        echo "<p style='color: red;'>✗ PHP mail() function failed</p>";
    }
} else {
    echo "<p style='color: red;'>✗ PHP mail() function is not available</p>";
}

// Test 4: Check Server Configuration
echo "<h2>Test 4: Server Configuration</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p><strong>OpenSSL:</strong> " . (extension_loaded('openssl') ? 'Available' : 'Not Available') . "</p>";
echo "<p><strong>cURL:</strong> " . (extension_loaded('curl') ? 'Available' : 'Not Available') . "</p>";

// Test 5: Check Error Logs
echo "<h2>Test 5: Recent Error Logs</h2>";
$errorLogPath = ini_get('error_log');
if ($errorLogPath && file_exists($errorLogPath)) {
    $errorLog = file_get_contents($errorLogPath);
    $recentErrors = array_slice(explode("\n", $errorLog), -10);
    echo "<p><strong>Recent errors:</strong></p>";
    echo "<pre>" . implode("\n", $recentErrors) . "</pre>";
} else {
    echo "<p>Error log not found or not accessible</p>";
}

// Test 6: Alternative Email Configuration
echo "<h2>Test 6: Alternative Configuration Suggestions</h2>";
echo "<h3>Try these alternative SMTP settings:</h3>";
echo "<div style='background: #f0f0f0; padding: 15px; margin: 10px 0;'>";
echo "<h4>Option 1: Gmail SMTP</h4>";
echo "<p>Host: smtp.gmail.com<br>";
echo "Port: 587<br>";
echo "Encryption: TLS<br>";
echo "Username: your-gmail@gmail.com<br>";
echo "Password: your-app-password</p>";
echo "</div>";

echo "<div style='background: #f0f0f0; padding: 15px; margin: 10px 0;'>";
echo "<h4>Option 2: Outlook/Hotmail SMTP</h4>";
echo "<p>Host: smtp-mail.outlook.com<br>";
echo "Port: 587<br>";
echo "Encryption: TLS<br>";
echo "Username: your-email@outlook.com<br>";
echo "Password: your-password</p>";
echo "</div>";

echo "<div style='background: #f0f0f0; padding: 15px; margin: 10px 0;'>";
echo "<h4>Option 3: Custom SMTP (Current)</h4>";
echo "<p>Host: mail.achtech.org.ng<br>";
echo "Port: 465<br>";
echo "Encryption: SSL<br>";
echo "Username: no-reply@achtech.org.ng<br>";
echo "Password: Temp_pass123</p>";
echo "</div>";

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Check if the email was sent to spam folder</li>";
echo "<li>Verify SMTP credentials with your hosting provider</li>";
echo "<li>Check if the email domain (achtech.org.ng) is properly configured</li>";
echo "<li>Try using a different email service (Gmail, Outlook)</li>";
echo "<li>Check server error logs for more details</li>";
echo "</ol>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2 { color: #2563eb; }
p { margin: 10px 0; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>
