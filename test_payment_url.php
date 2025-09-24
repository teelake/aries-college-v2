<?php
/**
 * Test Payment URL Generation
 * This will help debug the empty parameters issue
 */

// Simulate the environment
$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_HOST'] = 'www.achtech.org.ng';
$_SERVER['PHP_SELF'] = '/process_application.php';

// Test data
$testApplicationId = 123;
$testEmail = 'test@gmail.com';

echo "<h1>Payment URL Generation Test</h1>";

// Test the URL generation function
function generatePaymentPageUrl($applicationId, $email) {
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    $paymentPageUrl = $baseUrl . $path . '/complete_payment.php';
    
    echo "<h3>Debug Information:</h3>";
    echo "<p><strong>HTTPS:</strong> " . ($_SERVER['HTTPS'] ?? 'Not set') . "</p>";
    echo "<p><strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "</p>";
    echo "<p><strong>PHP_SELF:</strong> " . ($_SERVER['PHP_SELF'] ?? 'Not set') . "</p>";
    echo "<p><strong>dirname(PHP_SELF):</strong> " . $path . "</p>";
    echo "<p><strong>Base URL:</strong> " . $baseUrl . "</p>";
    echo "<p><strong>Payment Page URL:</strong> " . $paymentPageUrl . "</p>";
    echo "<p><strong>Application ID:</strong> " . $applicationId . "</p>";
    echo "<p><strong>Email:</strong> " . $email . "</p>";
    
    $finalUrl = $paymentPageUrl . '?app_id=' . $applicationId . '&email=' . urlencode($email);
    echo "<p><strong>Final URL:</strong> " . $finalUrl . "</p>";
    
    return $finalUrl;
}

// Test with sample data
echo "<h2>Test 1: With Sample Data</h2>";
$testUrl = generatePaymentPageUrl($testApplicationId, $testEmail);

echo "<h2>Test 2: With Empty Values</h2>";
$emptyUrl = generatePaymentPageUrl('', '');

echo "<h2>Test 3: With NULL Values</h2>";
$nullUrl = generatePaymentPageUrl(null, null);

echo "<h2>Test 4: Check if variables are being passed correctly</h2>";
echo "<p>Let's simulate what happens in the email generation:</p>";

// Simulate application array
$application = [
    'id' => 456,
    'email' => 'applicant@example.com',
    'full_name' => 'John Doe'
];

echo "<p><strong>Application ID:</strong> " . $application['id'] . "</p>";
echo "<p><strong>Application Email:</strong> " . $application['email'] . "</p>";

$emailUrl = generatePaymentPageUrl($application['id'], $application['email']);

echo "<h2>✅ Expected Result:</h2>";
echo "<p>The URL should be: <code>https://www.achtech.org.ng/complete_payment.php?app_id=456&email=applicant%40example.com</code></p>";

echo "<h2>🔍 Common Issues:</h2>";
echo "<ul>";
echo "<li><strong>Empty app_id:</strong> Application ID not being passed correctly</li>";
echo "<li><strong>Empty email:</strong> Email parameter not being passed correctly</li>";
echo "<li><strong>URL encoding:</strong> Email not being URL encoded properly</li>";
echo "<li><strong>Variable scope:</strong> Variables not available in function scope</li>";
echo "</ul>";

echo "<h2>🛠️ Solutions:</h2>";
echo "<ol>";
echo "<li>Check if application data is being retrieved correctly from database</li>";
echo "<li>Verify that variables are being passed to email generation functions</li>";
echo "<li>Ensure proper variable scope in email HTML generation</li>";
echo "<li>Test with actual application submission</li>";
echo "</ol>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2, h3 { color: #2563eb; }
code { background: #f1f1f1; padding: 2px 6px; border-radius: 3px; }
ul, ol { padding-left: 20px; }
</style>
