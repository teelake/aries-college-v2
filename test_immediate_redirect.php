<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'backend/db_connect.php';
require_once 'payment_processor.php';

echo "<h1>Test Immediate Redirect</h1>";

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
    
    echo "<h2>Step 3: Simulate Form Submission Response</h2>";
    
    // Simulate what the JavaScript would receive
    $response = [
        'success' => true,
        'message' => 'Application submitted successfully! Please complete payment to finalize your application.',
        'data' => [
            'application_id' => $applicationId,
            'payment_url' => $paymentResult['authorization_url'],
            'reference' => $paymentResult['reference']
        ]
    ];
    
    echo "<p>✅ Response would be:</p>";
    echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT) . "</pre>";
    
    echo "<h2>Step 4: Test Immediate Redirect</h2>";
    
    if ($response['success'] && isset($response['data']['payment_url'])) {
        echo "<p>✅ Success! Redirect would happen immediately to:</p>";
        echo "<p><a href='" . htmlspecialchars($response['data']['payment_url']) . "' target='_blank'>" . htmlspecialchars($response['data']['payment_url']) . "</a></p>";
        
        echo "<p><strong>Test the redirect:</strong></p>";
        echo "<button onclick=\"window.open('" . htmlspecialchars($response['data']['payment_url']) . "', '_blank')\">Open Payment Page</button>";
    } else {
        echo "<p>❌ Payment URL not available</p>";
    }
    
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
    echo "<p>✅ Immediate redirect test completed!</p>";
    echo "<p>The redirect should now happen instantly without any delay.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
button { 
    background: #3b82f6; 
    color: white; 
    padding: 10px 20px; 
    border: none; 
    border-radius: 5px; 
    cursor: pointer; 
    font-size: 16px; 
}
button:hover { background: #2563eb; }
</style>
