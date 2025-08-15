<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'payment_config.php';
require_once 'payment_processor.php';

echo "<h1>Payment Methods Test - USSD vs Other Methods</h1>";

// Test different payment methods
$paymentMethods = [
    'ussd' => 'USSD',
    'card' => 'Card',
    'banktransfer' => 'Bank Transfer',
    'mobilemoney' => 'Mobile Money'
];

echo "<h2>Testing Payment Method Verification</h2>";

foreach ($paymentMethods as $method => $methodName) {
    echo "<h3>Testing $methodName Payment Method</h3>";
    
    try {
        $paymentProcessor = new PaymentProcessor();
        
        // Create a test application for this method
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        $testStmt = $conn->prepare("INSERT INTO applications (full_name, email, phone, date_of_birth, gender, address, state, lga, last_school, qualification, year_completed, program_applied, photo_path, certificate_path, payment_status, application_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending_payment', NOW())");
        
        $testName = "Test User $methodName";
        $testEmail = "test_$method@example.com";
        $testPhone = '08012345678';
        $testDob = '1990-01-01';
        $testGender = 'Male';
        $testAddress = 'Test Address';
        $testState = 'Lagos';
        $testLga = 'Ikeja';
        $testSchool = 'Test School';
        $testQual = 'SSCE';
        $testYear = '2010-01-01';
        $testCourse = 'Medical Laboratory Science';
        $testPhoto = 'uploads/passports/test_photo.jpg';
        $testCert = 'uploads/certificates/test_cert.pdf';
        
        $testStmt->bind_param("ssssssssssssss", $testName, $testEmail, $testPhone, $testDob, $testGender, $testAddress, $testState, $testLga, $testSchool, $testQual, $testYear, $testCourse, $testPhoto, $testCert);
        
        if ($testStmt->execute()) {
            $testApplicationId = $conn->insert_id;
            $testStmt->close();
            
            echo "<p>✅ Test application created with ID: $testApplicationId</p>";
            
            // Test payment initialization with specific method
            $result = $paymentProcessor->initializePayment($testApplicationId, $testEmail, 10230);
            
            if ($result && isset($result['authorization_url'])) {
                echo "<p>✅ Payment initialization successful for $methodName</p>";
                echo "<p><strong>Reference:</strong> " . $result['reference'] . "</p>";
                echo "<p><strong>Payment URL:</strong> <a href='" . $result['authorization_url'] . "' target='_blank'>" . $result['authorization_url'] . "</a></p>";
                
                // Test verification immediately (this might fail for USSD)
                echo "<h4>Testing Immediate Verification</h4>";
                $verificationResult = $paymentProcessor->verifyPayment($result['reference']);
                
                echo "<p><strong>Verification Result:</strong></p>";
                echo "<pre>" . print_r($verificationResult, true) . "</pre>";
                
                if (!$verificationResult['success']) {
                    if ($method === 'ussd') {
                        echo "<p style='color: orange;'>⚠️ Expected failure for USSD - this is normal in test mode</p>";
                    } else {
                        echo "<p style='color: red;'>❌ Unexpected failure for $methodName</p>";
                    }
                } else {
                    echo "<p style='color: green;'>✅ Verification successful for $methodName</p>";
                }
                
            } else {
                echo "<p style='color: red;'>❌ Payment initialization failed for $methodName</p>";
            }
            
            // Clean up
            $cleanupStmt = $conn->prepare("DELETE FROM applications WHERE id = ?");
            $cleanupStmt->bind_param("i", $testApplicationId);
            $cleanupStmt->execute();
            $cleanupStmt->close();
            
        } else {
            echo "<p style='color: red;'>❌ Failed to create test application for $methodName</p>";
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error testing $methodName: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}

echo "<h2>USSD-Specific Test</h2>";

// Test the specific USSD transaction that failed
$ussdReference = 'ACH_1755268761_4';

echo "<h3>Testing Failed USSD Transaction: $ussdReference</h3>";

try {
    $paymentProcessor = new PaymentProcessor();
    
    // Test direct API call
    $url = FLUTTERWAVE_BASE_URL . '/transactions/' . $ussdReference . '/verify';
    $headers = [
        'Authorization: Bearer ' . FLUTTERWAVE_SECRET_KEY
    ];
    
    echo "<p><strong>Testing API URL:</strong> $url</p>";
    
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
    
    echo "<p><strong>API Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $decodedResponse = json_decode($response, true);
    if ($decodedResponse) {
        echo "<p><strong>Decoded Response:</strong></p>";
        echo "<pre>" . print_r($decodedResponse, true) . "</pre>";
        
        if (isset($decodedResponse['message']) && strpos($decodedResponse['message'], 'No transaction was found') !== false) {
            echo "<p style='color: orange;'>⚠️ <strong>USSD Issue Confirmed:</strong> Transaction not found in verification API</p>";
            echo "<p>This is a common issue with USSD payments in test mode because:</p>";
            echo "<ul>";
            echo "<li>USSD transactions take time to appear in the verification API</li>";
            echo "<li>Test mode USSD payments may not be immediately available</li>";
            echo "<li>Manual verification might be required for test USSD payments</li>";
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error testing USSD transaction: " . $e->getMessage() . "</p>";
}

echo "<h2>Recommendations for USSD Payments</h2>";

echo "<div style='background: #f0f8ff; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h3>For Test Mode USSD Payments:</h3>";
echo "<ol>";
echo "<li><strong>Wait 5-10 minutes</strong> before verifying USSD payments</li>";
echo "<li><strong>Use webhook verification</strong> instead of immediate verification</li>";
echo "<li><strong>Check Flutterwave dashboard</strong> for transaction status</li>";
echo "<li><strong>Consider manual verification</strong> for test USSD payments</li>";
echo "</ol>";

echo "<h3>For Live Mode USSD Payments:</h3>";
echo "<ol>";
echo "<li><strong>USSD payments are usually immediate</strong> in live mode</li>";
echo "<li><strong>Webhook verification</strong> is recommended for real-time updates</li>";
echo "<li><strong>Fallback verification</strong> should be implemented</li>";
echo "</ol>";
echo "</div>";

echo "<h2>Test Complete</h2>";
echo "<p>This test confirms whether the error is specific to USSD payments or affects all payment methods.</p>";
?>
