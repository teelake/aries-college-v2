<?php
/**
 * Test Email Functions
 * This will test if the email generation functions are working correctly
 */

require_once 'process_application.php';

echo "<h1>Email Functions Test</h1>";

// Test data
$testApplication = [
    'id' => 123,
    'full_name' => 'John Doe',
    'email' => 'john.doe@example.com',
    'phone' => '08012345678',
    'date_of_birth' => '1990-01-01',
    'gender' => 'Male',
    'address' => '123 Test Street',
    'state' => 'Lagos',
    'lga' => 'Ikeja',
    'last_school' => 'Test School',
    'qualification' => 'WAEC',
    'result_status' => 'available',
    'year_completed' => '2020-06-01',
    'program_applied' => 'Community Health'
];

$testPaymentUrl = 'https://checkout.flutterwave.com/test';
$testReference = 'ACH_1234567890_123';

echo "<h2>Test 1: Payment URL Generation</h2>";
try {
    $paymentPageUrl = generatePaymentPageUrl($testApplication['id'], $testApplication['email']);
    echo "<p style='color: green;'>✓ Payment URL generated successfully</p>";
    echo "<p><strong>URL:</strong> " . htmlspecialchars($paymentPageUrl) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Payment URL generation failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Test 2: HTML Email Generation</h2>";
try {
    $htmlContent = generateApplicationEmailHTML($testApplication, $testPaymentUrl, $testReference);
    echo "<p style='color: green;'>✓ HTML email generated successfully</p>";
    echo "<p><strong>Length:</strong> " . strlen($htmlContent) . " characters</p>";
    
    // Check if payment URL is in the HTML
    if (strpos($htmlContent, $paymentPageUrl) !== false) {
        echo "<p style='color: green;'>✓ Payment URL found in HTML email</p>";
    } else {
        echo "<p style='color: red;'>✗ Payment URL NOT found in HTML email</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ HTML email generation failed: " . $e->getMessage() . "</p>";
    echo "<p><strong>Error Details:</strong> " . $e->getTraceAsString() . "</p>";
}

echo "<h2>Test 3: Text Email Generation</h2>";
try {
    $textContent = generateApplicationEmailText($testApplication, $testPaymentUrl, $testReference);
    echo "<p style='color: green;'>✓ Text email generated successfully</p>";
    echo "<p><strong>Length:</strong> " . strlen($textContent) . " characters</p>";
    
    // Check if payment URL is in the text
    if (strpos($textContent, $paymentPageUrl) !== false) {
        echo "<p style='color: green;'>✓ Payment URL found in text email</p>";
    } else {
        echo "<p style='color: red;'>✗ Payment URL NOT found in text email</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Text email generation failed: " . $e->getMessage() . "</p>";
    echo "<p><strong>Error Details:</strong> " . $e->getTraceAsString() . "</p>";
}

echo "<h2>Test 4: PHP Syntax Check</h2>";
$syntaxCheck = shell_exec('php -l process_application.php 2>&1');
if (strpos($syntaxCheck, 'No syntax errors') !== false) {
    echo "<p style='color: green;'>✓ PHP syntax is correct</p>";
} else {
    echo "<p style='color: red;'>✗ PHP syntax errors found:</p>";
    echo "<pre>" . htmlspecialchars($syntaxCheck) . "</pre>";
}

echo "<h2>Test 5: Function Existence Check</h2>";
$functions = [
    'sendApplicationEmailWithPaymentLink',
    'generateApplicationEmailHTML',
    'generateApplicationEmailText',
    'generatePaymentPageUrl',
    'sendFallbackEmail'
];

foreach ($functions as $function) {
    if (function_exists($function)) {
        echo "<p style='color: green;'>✓ Function $function exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Function $function does not exist</p>";
    }
}

echo "<h2>Test 6: Check Error Logs</h2>";
echo "<p>Check your server error logs for recent email-related errors:</p>";
echo "<ul>";
echo "<li>PHP error log</li>";
echo "<li>Web server error log</li>";
echo "<li>Application-specific logs</li>";
echo "</ul>";

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>If all tests pass, the issue might be with SMTP configuration</li>";
echo "<li>Check server error logs for specific error messages</li>";
echo "<li>Test with a real application submission</li>";
echo "<li>Try the fallback email method if SMTP fails</li>";
echo "</ol>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2 { color: #2563eb; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>
