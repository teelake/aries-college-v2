<?php
/**
 * Simple Email Test - Check if emails are working after the fixes
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Email Test After Fixes</h1>";

// Test if PHPMailer is working
echo "<h2>Test 1: PHPMailer Basic Test</h2>";
try {
    require_once __DIR__ . '/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/phpmailer/SMTP.php';
    require_once __DIR__ . '/phpmailer/Exception.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    echo "<p style='color: green;'>✓ PHPMailer loaded successfully</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ PHPMailer failed: " . $e->getMessage() . "</p>";
}

// Test the email generation functions
echo "<h2>Test 2: Email Generation Functions</h2>";
try {
    // Test data
    $testApplication = [
        'id' => 999,
        'full_name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '08012345678',
        'date_of_birth' => '1990-01-01',
        'gender' => 'Male',
        'address' => 'Test Address',
        'state' => 'Lagos',
        'lga' => 'Ikeja',
        'last_school' => 'Test School',
        'qualification' => 'WAEC',
        'result_status' => 'available',
        'year_completed' => '2020-06-01',
        'program_applied' => 'Community Health'
    ];
    
    // Test URL generation
    if (function_exists('generatePaymentPageUrl')) {
        $paymentUrl = generatePaymentPageUrl($testApplication['id'], $testApplication['email']);
        echo "<p style='color: green;'>✓ Payment URL generated: " . htmlspecialchars($paymentUrl) . "</p>";
    } else {
        echo "<p style='color: red;'>✗ generatePaymentPageUrl function not found</p>";
    }
    
    // Test HTML generation
    if (function_exists('generateApplicationEmailHTML')) {
        $htmlContent = generateApplicationEmailHTML($testApplication, 'https://test.com', 'TEST_REF_123');
        echo "<p style='color: green;'>✓ HTML email generated (" . strlen($htmlContent) . " chars)</p>";
    } else {
        echo "<p style='color: red;'>✗ generateApplicationEmailHTML function not found</p>";
    }
    
    // Test text generation
    if (function_exists('generateApplicationEmailText')) {
        $textContent = generateApplicationEmailText($testApplication, 'https://test.com', 'TEST_REF_123');
        echo "<p style='color: green;'>✓ Text email generated (" . strlen($textContent) . " chars)</p>";
    } else {
        echo "<p style='color: red;'>✗ generateApplicationEmailText function not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Email generation test failed: " . $e->getMessage() . "</p>";
    echo "<p><strong>Error Details:</strong> " . $e->getTraceAsString() . "</p>";
}

// Test SMTP connection
echo "<h2>Test 3: SMTP Connection Test</h2>";
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
    
    echo "<p>Attempting SMTP connection...</p>";
    $mail->smtpConnect();
    echo "<p style='color: green;'>✓ SMTP connection successful</p>";
    $mail->smtpClose();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ SMTP connection failed: " . $e->getMessage() . "</p>";
}

// Test sending a simple email
echo "<h2>Test 4: Send Simple Email</h2>";
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
    
    $mail->Subject = 'Test Email After Fixes - ' . date('Y-m-d H:i:s');
    $mail->Body = 'This is a test email to verify the fixes are working.';
    
    if ($mail->send()) {
        echo "<p style='color: green;'>✓ Test email sent successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to send test email</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Email sending failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Summary:</h2>";
echo "<p>If all tests pass, the email system should be working. If emails are still not being sent:</p>";
echo "<ol>";
echo "<li>Check server error logs for specific error messages</li>";
echo "<li>Verify that the application submission is reaching the email sending code</li>";
echo "<li>Check if the fallback email method is being triggered</li>";
echo "<li>Consider using Gmail SMTP for better reliability</li>";
echo "</ol>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2 { color: #2563eb; }
</style>
