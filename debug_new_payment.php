<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'backend/db_connect.php';
require_once 'payment_processor.php';

echo "<h1>Debug New Payment Flow</h1>";

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
    
    echo "<h2>Step 4: Simulate Payment Success URL</h2>";
    
    // Simulate the URL parameters that would come from Flutterwave
    $simulatedUrl = "payment_success.php?status=successful&tx_ref=$reference&transaction_id=999999";
    echo "<p>Simulated URL: $simulatedUrl</p>";
    
    // Test the lookup logic manually
    $testParams = [
        'status' => 'successful',
        'tx_ref' => $reference,
        'transaction_id' => '999999'
    ];
    
    echo "<h3>Testing URL Parameter Processing:</h3>";
    
    // Simulate the parameter extraction logic
    $testReference = $testParams['tx_ref'] ?? $testParams['reference'] ?? $testParams['trxref'] ?? null;
    echo "<p>Extracted reference: " . ($testReference ?: 'NULL') . "</p>";
    
    if (!$testReference && isset($testParams['transaction_id'])) {
        $transactionId = $testParams['transaction_id'];
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
        
        // Test the gateway reference update logic
        if (isset($testParams['transaction_id']) && $testReference) {
            $transactionId = $testParams['transaction_id'];
            echo "<p>Testing gateway reference update for transaction_id: $transactionId</p>";
            
            $stmt = $conn->prepare("UPDATE transactions SET gateway_reference = ? WHERE reference = ?");
            $stmt->bind_param("ss", $transactionId, $testReference);
            $stmt->execute();
            $stmt->close();
            echo "<p>✅ Gateway reference updated</p>";
        }
    } else {
        echo "<p>❌ No reference found</p>";
    }
    
    echo "<h2>Step 5: Test Payment Verification</h2>";
    
    // Test the verification (this will likely fail in test mode, but that's expected)
    try {
        $verificationResult = $paymentProcessor->verifyPayment($reference);
        echo "<p>Verification result:</p>";
        echo "<pre>" . print_r($verificationResult, true) . "</pre>";
    } catch (Exception $e) {
        echo "<p>❌ Verification failed (expected in test mode): " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>Step 6: Test Fallback Logic</h2>";
    
    // Test the fallback logic when verification fails but status is successful
    if (!$verificationResult['success'] && isset($testParams['status']) && $testParams['status'] === 'successful') {
        echo "<p>✅ Fallback logic would trigger (verification failed but status is successful)</p>";
        
        // Get transaction data directly from database
        $transactionData = $paymentProcessor->getTransactionByReference($reference);
        if ($transactionData) {
            echo "<p>✅ Transaction found in database for fallback processing</p>";
            echo "<p>Transaction data:</p>";
            echo "<pre>" . print_r($transactionData, true) . "</pre>";
        } else {
            echo "<p>❌ Transaction not found for fallback processing</p>";
        }
    }
    
    echo "<h2>Step 7: Cleanup</h2>";
    
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
    echo "<p>✅ New payment flow test completed!</p>";
    echo "<p>If you see any ❌ errors above, those indicate potential issues with the new payment flow.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>
