<?php
require_once 'payment_config.php';
require_once 'payment_processor.php';

// Test payment initialization
try {
    $paymentProcessor = new PaymentProcessor();
    
    // Test data
    $testApplicationId = 1;
    $testEmail = 'test@example.com';
    $testAmount = 10230;
    
    echo "<h2>Testing Flutterwave Payment Initialization</h2>";
    echo "<p><strong>Gateway:</strong> " . PAYMENT_GATEWAY . "</p>";
    echo "<p><strong>Flutterwave Base URL:</strong> " . FLUTTERWAVE_BASE_URL . "</p>";
    echo "<p><strong>Secret Key:</strong> " . substr(FLUTTERWAVE_SECRET_KEY, 0, 20) . "...</p>";
    echo "<p><strong>Test Amount:</strong> ₦" . number_format($testAmount) . "</p>";
    
    $result = $paymentProcessor->initializePayment($testApplicationId, $testEmail, $testAmount);
    
    echo "<h3>✅ Payment Initialization Successful!</h3>";
    echo "<p><strong>Reference:</strong> " . $result['reference'] . "</p>";
    echo "<p><strong>Payment URL:</strong> <a href='" . $result['authorization_url'] . "' target='_blank'>" . $result['authorization_url'] . "</a></p>";
    echo "<p><strong>Success:</strong> " . ($result['success'] ? 'Yes' : 'No') . "</p>";
    
    if (isset($result['access_code'])) {
        echo "<p><strong>Access Code:</strong> " . $result['access_code'] . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Payment Initialization Failed!</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
}

// Test HTTP request function
echo "<h2>Testing HTTP Request Function</h2>";
try {
    $testUrl = 'https://api.flutterwave.com/v3/transactions/verify/123456';
    $headers = [
        'Authorization: Bearer ' . FLUTTERWAVE_SECRET_KEY
    ];
    
    $paymentProcessor = new PaymentProcessor();
    $reflection = new ReflectionClass($paymentProcessor);
    $method = $reflection->getMethod('makeHttpRequest');
    $method->setAccessible(true);
    
    $response = $method->invoke($paymentProcessor, $testUrl, 'GET', null, $headers);
    
    echo "<p><strong>HTTP Request Test:</strong> " . (isset($response['status']) ? 'Response received' : 'No response') . "</p>";
    echo "<p><strong>Response:</strong> " . json_encode($response, JSON_PRETTY_PRINT) . "</p>";
    
} catch (Exception $e) {
    echo "<p><strong>HTTP Request Error:</strong> " . $e->getMessage() . "</p>";
}
?>
