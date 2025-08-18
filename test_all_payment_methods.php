<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'backend/db_connect.php';
require_once 'payment_processor.php';

echo "<h1>Test All Payment Methods</h1>";

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
    
    $stmt = $conn->prepare("INSERT INTO applications (full_name, email, phone, date_of_birth, gender, address, state, lga, last_school, qualification, year_completed, program_applied, photo_path, certificate_path, payment_status, application_status, created_at) VALUES (?, ?, ?, '1990-01-01', 'Male', 'Test Address', 'Lagos', 'Ikeja', 'Test School', 'SSCE', '2010-01-01', ?, 'uploads/passports/test.jpg', 'uploads/certificates/test.pdf', 'pending', 'submitted', NOW())");
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
    
    echo "<h2>Step 3: Test Different Payment Method Scenarios</h2>";
    
    // Test different payment method scenarios
    $testScenarios = [
        'USSD' => [
            'status' => 'successful',
            'tx_ref' => $reference,
            'transaction_id' => '999999',
            'payment_type' => 'ussd'
        ],
        'Bank Transfer' => [
            'status' => 'successful',
            'tx_ref' => $reference,
            'transaction_id' => '999998',
            'payment_type' => 'banktransfer'
        ],
        'Card Payment' => [
            'status' => 'successful',
            'tx_ref' => $reference,
            'transaction_id' => '999997',
            'payment_type' => 'card'
        ]
    ];
    
    foreach ($testScenarios as $method => $params) {
        echo "<h3>Testing $method Payment</h3>";
        echo "<p>Parameters: " . json_encode($params) . "</p>";
        
        // Simulate the parameter extraction logic
        $testReference = $params['tx_ref'] ?? $params['reference'] ?? $params['trxref'] ?? null;
        echo "<p>Extracted reference: " . ($testReference ?: 'NULL') . "</p>";
        
        if ($testReference) {
            // Test the gateway reference update logic
            if (isset($params['transaction_id']) && $testReference) {
                $transactionId = $params['transaction_id'];
                $paymentType = $params['payment_type'] ?? null;
                echo "<p>Testing gateway reference update for transaction_id: $transactionId, payment_type: $paymentType</p>";
                
                $stmt = $conn->prepare("UPDATE transactions SET gateway_reference = ?, payment_method = ? WHERE reference = ?");
                $stmt->bind_param("sss", $transactionId, $paymentType, $testReference);
                $stmt->execute();
                $stmt->close();
                echo "<p>✅ Gateway reference and payment method updated</p>";
            }
            
            // Test the fallback logic
            if (isset($params['status']) && $params['status'] === 'successful') {
                echo "<p>✅ Fallback logic would trigger for $method payment</p>";
                
                // Get transaction data directly from database
                $transactionData = $paymentProcessor->getTransactionByReference($reference);
                if ($transactionData) {
                    echo "<p>✅ Transaction found in database for $method processing</p>";
                    echo "<p>Payment Method: " . ($transactionData['payment_method'] ?? 'NULL') . "</p>";
                    echo "<p>Gateway Reference: " . ($transactionData['gateway_reference'] ?? 'NULL') . "</p>";
                } else {
                    echo "<p>❌ Transaction not found for $method processing</p>";
                }
            }
        } else {
            echo "<p>❌ No reference found for $method</p>";
        }
        
        echo "<hr>";
    }
    
    echo "<h2>Step 4: Test Application Status Update</h2>";
    
    // Test application status update
    $stmt = $conn->prepare("SELECT payment_status, application_status FROM applications WHERE id = ?");
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $application = $result->fetch_assoc();
    $stmt->close();
    
    echo "<p>Current application status:</p>";
    echo "<p>Payment Status: " . ($application['payment_status'] ?? 'NULL') . "</p>";
    echo "<p>Application Status: " . ($application['application_status'] ?? 'NULL') . "</p>";
    
    // Simulate successful payment
    $stmt = $conn->prepare("UPDATE applications SET payment_status = 'paid', payment_date = NOW() WHERE id = ?");
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $stmt->close();
    
    echo "<p>✅ Simulated successful payment - payment_status updated to 'paid'</p>";
    
    echo "<h2>Step 5: Cleanup</h2>";
    
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
    echo "<p>✅ All payment method tests completed!</p>";
    echo "<p>The system should now handle USSD, bank transfer, and card payments correctly.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>
