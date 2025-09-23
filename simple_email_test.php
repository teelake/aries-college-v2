<?php
// Simple Email Test - Check if SMTP is working
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Email Test</h1>";

// Test 1: Check if PHPMailer is working
echo "<h2>Test 1: PHPMailer Basic Test</h2>";
try {
    require_once __DIR__ . '/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/phpmailer/SMTP.php';
    require_once __DIR__ . '/phpmailer/Exception.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    echo "<p style='color: green;'>✓ PHPMailer loaded successfully</p>";
    
    // Test SMTP connection
    $mail->isSMTP();
    $mail->Host = 'mail.achtech.org.ng';
    $mail->SMTPAuth = true;
    $mail->Username = 'no-reply@achtech.org.ng';
    $mail->Password = 'Temp_pass123';
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->SMTPDebug = 2; // Enable debug
    $mail->Debugoutput = function($str, $level) {
        echo "<p style='color: blue;'>DEBUG: $str</p>";
    };
    
    echo "<p>Attempting SMTP connection...</p>";
    $mail->smtpConnect();
    echo "<p style='color: green;'>✓ SMTP connection successful</p>";
    $mail->smtpClose();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

// Test 2: Try sending a simple email
echo "<h2>Test 2: Send Simple Email</h2>";
try {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'mail.achtech.org.ng';
    $mail->SMTPAuth = true;
    $mail->Username = 'no-reply@achtech.org.ng';
    $mail->Password = 'Temp_pass123';
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->SMTPDebug = 0;
    
    $mail->setFrom('no-reply@achtech.org.ng', 'Aries College Test');
    $mail->addAddress('test@example.com', 'Test User'); // Change this to your email
    
    $mail->Subject = 'Test Email from Aries College - ' . date('Y-m-d H:i:s');
    $mail->Body = 'This is a test email to verify SMTP is working.';
    
    if ($mail->send()) {
        echo "<p style='color: green;'>✓ Test email sent successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to send test email</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Email sending failed: " . $e->getMessage() . "</p>";
}

// Test 3: Alternative SMTP settings
echo "<h2>Test 3: Alternative SMTP Settings</h2>";
echo "<p>If the above tests fail, try these alternative settings:</p>";

echo "<h3>Option 1: Try Port 587 with TLS</h3>";
echo "<pre>
\$mail->Host = 'mail.achtech.org.ng';
\$mail->Port = 587;
\$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
\$mail->Username = 'no-reply@achtech.org.ng';
\$mail->Password = 'Temp_pass123';
</pre>";

echo "<h3>Option 2: Use Gmail SMTP (Recommended)</h3>";
echo "<pre>
\$mail->Host = 'smtp.gmail.com';
\$mail->Port = 587;
\$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
\$mail->Username = 'your-gmail@gmail.com';
\$mail->Password = 'your-app-password';
</pre>";

echo "<h3>Option 3: Use PHP Mail Function (Fallback)</h3>";
echo "<pre>
\$headers = \"From: no-reply@achtech.org.ng\\r\\n\";
\$headers .= \"Content-Type: text/html; charset=UTF-8\\r\\n\";
mail(\$email, \$subject, \$message, \$headers);
</pre>";

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Check the debug output above for specific error messages</li>";
echo "<li>If SMTP fails, try the alternative settings</li>";
echo "<li>Contact your hosting provider about SMTP configuration</li>";
echo "<li>Consider using Gmail SMTP for better reliability</li>";
echo "</ol>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2, h3 { color: #2563eb; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>
