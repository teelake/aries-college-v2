<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'payment_config.php';
require_once 'payment_processor.php';

echo "<h1>USSD Payment Debug Test</h1>";

// Test the specific transaction reference from the error
$testReference = 'ACH_1755268761_4';

echo "<h2>Testing Transaction Reference: $testReference</h2>";

try {
    $paymentProcessor = new PaymentProcessor();
    
    echo "<h3>1. Testing Direct Flutterwave API Call</h3>";
    
    // Test the direct API call
    $url = FLUTTERWAVE_BASE_URL . '/transactions/' . $testReference . '/verify';
    $headers = [
        'Authorization: Bearer ' . FLUTTERWAVE_SECRET_KEY
    ];
    
    echo "<p><strong>API URL:</strong> $url</p>";
    echo "<p><strong>Headers:</strong> " . json_encode($headers) . "</p>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
    if ($error) {
        echo "<p style='color: red;'><strong>cURL Error:</strong> $error</p>";
    }
    
    echo "<p><strong>Raw Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $decodedResponse = json_decode($response, true);
    if ($decodedResponse) {
        echo "<p><strong>Decoded Response:</strong></p>";
        echo "<pre>" . print_r($decodedResponse, true) . "</pre>";
    }
    
    echo "<h3>2. Testing PaymentProcessor Verification</h3>";
    
    $verificationResult = $paymentProcessor->verifyPayment($testReference);
    
    echo "<p><strong>Verification Result:</strong></p>";
    echo "<pre>" . print_r($verificationResult, true) . "</pre>";
    
    echo "<h3>3. Testing Database Transaction Lookup</h3>";
    
    // Check if transaction exists in database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE reference = ?");
    $stmt->bind_param("s", $testReference);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();
    $stmt->close();
    
    if ($transaction) {
        echo "<p style='color: green;'>✅ Transaction found in database:</p>";
        echo "<pre>" . print_r($transaction, true) . "</pre>";
        
        // Check application
        $stmt = $conn->prepare("SELECT * FROM applications WHERE id = ?");
        $stmt->bind_param("i", $transaction['application_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $application = $result->fetch_assoc();
        $stmt->close();
        
        if ($application) {
            echo "<p style='color: green;'>✅ Application found:</p>";
            echo "<pre>" . print_r($application, true) . "</pre>";
        } else {
            echo "<p style='color: red;'>❌ Application not found for ID: " . $transaction['application_id'] . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Transaction not found in database</p>";
    }
    
    $conn->close();
    
    echo "<h3>4. Test Mode Considerations</h3>";
    echo "<p>In Flutterwave test mode, especially with USSD payments:</p>";
    echo "<ul>";
    echo "<li>Transactions may take time to appear in the verification API</li>";
    echo "<li>USSD payments might require manual verification</li>";
    echo "<li>Test transactions might not be immediately available</li>";
    echo "</ul>";
    
    echo "<h3>5. Recommended Solutions</h3>";
    echo "<ol>";
    echo "<li><strong>Wait and Retry:</strong> USSD payments can take 5-10 minutes to appear</li>";
    echo "<li><strong>Check Flutterwave Dashboard:</strong> Verify the transaction status manually</li>";
    echo "<li><strong>Use Webhook:</strong> Set up webhook for real-time status updates</li>";
    echo "<li><strong>Manual Verification:</strong> For test mode, consider manual verification</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
}

echo "<h2>Test Complete</h2>";
echo "<p>Check the output above to understand the USSD payment verification issue.</p>";
?>
