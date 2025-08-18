<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'backend/db_connect.php';
require_once 'payment_processor.php';

echo "<h1>Test Payment Methods Verification</h1>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    echo "<h2>Step 1: Test Different Status Values</h2>";
    
    // Test different status values that Flutterwave might send
    $testStatuses = [
        'successful' => 'USSD/Card success',
        'success' => 'Alternative success',
        'completed' => 'Bank transfer success',
        'paid' => 'Payment completed',
        'approved' => 'Payment approved',
        'pending' => 'Payment pending',
        'failed' => 'Payment failed',
        'cancelled' => 'Payment cancelled'
    ];
    
    $successIndicators = ['successful', 'success', 'completed', 'paid', 'approved'];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Status Value</th><th>Description</th><th>Is Success?</th><th>Action</th></tr>";
    
    foreach ($testStatuses as $status => $description) {
        $isSuccess = in_array(strtolower($status), $successIndicators);
        $action = $isSuccess ? 'Process as Success' : 'Process as Failed';
        $color = $isSuccess ? 'green' : 'red';
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($status) . "</td>";
        echo "<td>" . htmlspecialchars($description) . "</td>";
        echo "<td style='color: $color; font-weight: bold;'>" . ($isSuccess ? 'YES' : 'NO') . "</td>";
        echo "<td>" . htmlspecialchars($action) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>Step 2: Test Payment Verification Logic</h2>";
    
    // Get a recent transaction for testing
    $result = $conn->query("SELECT * FROM transactions ORDER BY created_at DESC LIMIT 1");
    $testTransaction = $result->fetch_assoc();
    
    if ($testTransaction) {
        echo "<p>Testing with transaction: " . $testTransaction['reference'] . "</p>";
        
        // Test the verification
        $paymentProcessor = new PaymentProcessor();
        
        try {
            $verificationResult = $paymentProcessor->verifyPayment($testTransaction['reference']);
            echo "<p><strong>Verification Result:</strong></p>";
            echo "<pre>" . json_encode($verificationResult, JSON_PRETTY_PRINT) . "</pre>";
            
            if ($verificationResult['success']) {
                echo "<p style='color: green;'>✅ Verification successful</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Verification failed: " . $verificationResult['message'] . "</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Verification error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>No transactions found for testing</p>";
    }
    
    echo "<h2>Step 3: Test Fallback Logic</h2>";
    
    // Simulate different URL scenarios
    $testScenarios = [
        [
            'name' => 'USSD Success',
            'params' => ['status' => 'successful', 'tx_ref' => 'TEST_123', 'transaction_id' => '12345'],
            'verification_success' => false
        ],
        [
            'name' => 'Bank Transfer Success',
            'params' => ['status' => 'completed', 'tx_ref' => 'TEST_456', 'transaction_id' => '12346'],
            'verification_success' => false
        ],
        [
            'name' => 'Card Payment Success',
            'params' => ['status' => 'success', 'tx_ref' => 'TEST_789', 'transaction_id' => '12347'],
            'verification_success' => false
        ],
        [
            'name' => 'Payment Failed',
            'params' => ['status' => 'failed', 'tx_ref' => 'TEST_999'],
            'verification_success' => false
        ]
    ];
    
    foreach ($testScenarios as $scenario) {
        echo "<h3>" . $scenario['name'] . "</h3>";
        echo "<p>Parameters: " . json_encode($scenario['params']) . "</p>";
        
        // Simulate the logic
        $urlStatus = $scenario['params']['status'] ?? '';
        $isUrlStatusSuccessful = in_array(strtolower($urlStatus), $successIndicators);
        
        echo "<p>URL Status: $urlStatus</p>";
        echo "<p>Is URL Status Successful: " . ($isUrlStatusSuccessful ? 'YES' : 'NO') . "</p>";
        
        if (!$scenario['verification_success'] && $isUrlStatusSuccessful) {
            echo "<p style='color: green;'>✅ Fallback logic would trigger - Process as SUCCESS</p>";
        } elseif ($scenario['verification_success']) {
            echo "<p style='color: green;'>✅ Normal verification successful - Process as SUCCESS</p>";
        } else {
            echo "<p style='color: red;'>❌ Payment would be marked as FAILED</p>";
        }
        
        echo "<hr>";
    }
    
    echo "<h2>Step 4: Recommendations</h2>";
    
    echo "<p><strong>For different payment methods:</strong></p>";
    echo "<ul>";
    echo "<li><strong>USSD:</strong> Usually sends 'successful' status</li>";
    echo "<li><strong>Bank Transfer:</strong> May send 'completed' or 'successful' status</li>";
    echo "<li><strong>Card Payment:</strong> Usually sends 'successful' status</li>";
    echo "<li><strong>Mobile Money:</strong> May send 'success' or 'completed' status</li>";
    echo "</ul>";
    
    echo "<p><strong>Current Logic:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Accepts multiple success indicators: " . implode(', ', $successIndicators) . "</li>";
    echo "<li>✅ Has fallback logic when verification fails but URL indicates success</li>";
    echo "<li>✅ Logs detailed information for debugging</li>";
    echo "</ul>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>
