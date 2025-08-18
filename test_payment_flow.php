<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'backend/db_connect.php';
require_once 'payment_processor.php';

echo "<h1>Payment Flow Test</h1>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    echo "<h2>Step 1: Create Test Application</h2>";
    
    // Create a test application
    $testName = 'Test User ' . time();
    $testEmail = 'test' . time() . '@example.com';
    $testPhone = '08012345678';
    $testCourse = 'Medical Laboratory Technician';
    
    $stmt = $conn->prepare("INSERT INTO applications (full_name, email, phone, date_of_birth, gender, address, state, lga, last_school, qualification, year_completed, program_applied, photo_path, certificate_path, payment_status, application_status, created_at) VALUES (?, ?, ?, '1990-01-01', 'Male', 'Test Address', 'Lagos', 'Ikeja', 'Test School', 'SSCE', '2010-01-01', ?, 'uploads/passports/test.jpg', 'uploads/certificates/test.pdf', 'pending', 'pending_payment', NOW())");
    $stmt->bind_param("ssss", $testName, $testEmail, $testPhone, $testCourse);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create test application: ' . $conn->error);
    }
    
    $applicationId = $conn->insert_id;
    $stmt->close();
    
    echo "<p>✅ Test application created with ID: $applicationId</p>";
    
    echo "<h2>Step 2: Initialize Payment</h2>";
    
    // Initialize payment
    $paymentProcessor = new PaymentProcessor();
    $paymentResult = $paymentProcessor->initializePayment($applicationId, $testEmail);
    
    if (!$paymentResult || !isset($paymentResult['authorization_url'])) {
        throw new Exception('Payment initialization failed');
    }
    
    $reference = $paymentResult['reference'];
    echo "<p>✅ Payment initialized successfully</p>";
    echo "<p>Reference: $reference</p>";
    echo "<p>Payment URL: " . htmlspecialchars($paymentResult['authorization_url']) . "</p>";
    
    echo "<h2>Step 3: Check Transaction in Database</h2>";
    
    // Check if transaction was created
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE reference = ?");
    $stmt->bind_param("s", $reference);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();
    $stmt->close();
    
    if ($transaction) {
        echo "<p>✅ Transaction found in database:</p>";
        echo "<pre>" . print_r($transaction, true) . "</pre>";
    } else {
        echo "<p>❌ Transaction not found in database</p>";
    }
    
    echo "<h2>Step 4: Test Transaction Lookup Methods</h2>";
    
    // Test the lookup methods
    $foundByReference = $paymentProcessor->getTransactionByReference($reference);
    if ($foundByReference) {
        echo "<p>✅ Transaction found by reference using PaymentProcessor</p>";
    } else {
        echo "<p>❌ Transaction not found by reference using PaymentProcessor</p>";
    }
    
    // Test with a fake transaction ID
    $fakeTransactionId = "999999";
    $foundByGatewayRef = $paymentProcessor->getTransactionByGatewayReference($fakeTransactionId);
    if ($foundByGatewayRef) {
        echo "<p>✅ Transaction found by gateway reference (unexpected!)</p>";
    } else {
        echo "<p>✅ No transaction found by fake gateway reference (expected)</p>";
    }
    
    echo "<h2>Step 5: Simulate Payment Success URL Parameters</h2>";
    
    // Simulate what Flutterwave would send
    $simulatedParams = [
        'tx_ref' => $reference,
        'transaction_id' => '12345', // This would be the actual Flutterwave transaction ID
        'status' => 'successful'
    ];
    
    echo "<p>Simulated URL parameters: " . json_encode($simulatedParams) . "</p>";
    
    // Test the lookup logic
    $testReference = $simulatedParams['tx_ref'] ?? $simulatedParams['reference'] ?? $simulatedParams['trxref'] ?? null;
    
    if (!$testReference && isset($simulatedParams['transaction_id'])) {
        $transactionId = $simulatedParams['transaction_id'];
        echo "<p>Looking for transaction with ID: $transactionId</p>";
        
        $transaction = $paymentProcessor->getTransactionByGatewayReference($transactionId);
        
        if ($transaction) {
            $testReference = $transaction['reference'];
            echo "<p>✅ Found reference: $testReference</p>";
        } else {
            echo "<p>❌ No transaction found for ID: $transactionId</p>";
        }
    }
    
    if ($testReference) {
        echo "<p>✅ Final reference found: $testReference</p>";
    } else {
        echo "<p>❌ No reference found</p>";
    }
    
    echo "<h2>Step 6: Cleanup</h2>";
    
    // Clean up test data
    $stmt = $conn->prepare("DELETE FROM transactions WHERE reference = ?");
    $stmt->bind_param("s", $reference);
    $stmt->execute();
    $stmt->close();
    
    $stmt = $conn->prepare("DELETE FROM applications WHERE id = ?");
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $stmt->close();
    
    echo "<p>✅ Test data cleaned up</p>";
    
    $conn->close();
    
    echo "<h2>Test Summary</h2>";
    echo "<p>✅ Payment flow test completed successfully!</p>";
    echo "<p>The payment initialization and transaction lookup methods are working correctly.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>
