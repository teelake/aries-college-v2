<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Payment Integration Debug</h1>";

// Test 1: Check if required files exist
echo "<h2>Test 1: File Dependencies</h2>";
$requiredFiles = [
    'payment_config.php',
    'payment_processor.php',
    'backend/db_connect.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

// Test 2: Check configuration
echo "<h2>Test 2: Configuration</h2>";
try {
    require_once 'payment_config.php';
    echo "✅ Payment config loaded<br>";
    echo "Gateway: " . PAYMENT_GATEWAY . "<br>";
    echo "Flutterwave Base URL: " . FLUTTERWAVE_BASE_URL . "<br>";
    echo "Secret Key: " . substr(FLUTTERWAVE_SECRET_KEY, 0, 20) . "...<br>";
    echo "Application Fee: ₦" . number_format(APPLICATION_FEE) . "<br>";
} catch (Exception $e) {
    echo "❌ Config error: " . $e->getMessage() . "<br>";
}

// Test 3: Check database connection
echo "<h2>Test 3: Database Connection</h2>";
try {
    require_once 'backend/db_connect.php';
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        echo "❌ Database connection failed: " . $conn->connect_error . "<br>";
    } else {
        echo "✅ Database connection successful<br>";
        $conn->close();
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 4: Check PaymentProcessor class
echo "<h2>Test 4: PaymentProcessor Class</h2>";
try {
    require_once 'payment_processor.php';
    $paymentProcessor = new PaymentProcessor();
    echo "✅ PaymentProcessor instantiated successfully<br>";
} catch (Exception $e) {
    echo "❌ PaymentProcessor error: " . $e->getMessage() . "<br>";
}

// Test 5: Test Flutterwave API directly
echo "<h2>Test 5: Flutterwave API Test</h2>";
try {
    $testUrl = 'https://api.flutterwave.com/v3/transactions/verify/123456';
    $headers = [
        'Authorization: Bearer ' . FLUTTERWAVE_SECRET_KEY
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ cURL Error: " . $error . "<br>";
    } else {
        echo "✅ cURL request successful<br>";
        echo "HTTP Code: $httpCode<br>";
        echo "Response: " . substr($response, 0, 200) . "...<br>";
    }
} catch (Exception $e) {
    echo "❌ API test error: " . $e->getMessage() . "<br>";
}

// Test 6: Test payment initialization
echo "<h2>Test 6: Payment Initialization Test</h2>";
try {
    $paymentProcessor = new PaymentProcessor();
    $result = $paymentProcessor->initializePayment(999, 'test@example.com', 10230);
    
    echo "✅ Payment initialization successful<br>";
    echo "Reference: " . $result['reference'] . "<br>";
    echo "Payment URL: " . $result['authorization_url'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Payment initialization failed: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

// Test 7: Check PHP extensions
echo "<h2>Test 7: PHP Extensions</h2>";
$requiredExtensions = ['curl', 'json', 'mysqli'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext extension loaded<br>";
    } else {
        echo "❌ $ext extension missing<br>";
    }
}

// Test 8: Check error logs
echo "<h2>Test 8: Error Log Location</h2>";
$errorLog = ini_get('error_log');
if ($errorLog) {
    echo "Error log: $errorLog<br>";
    if (file_exists($errorLog)) {
        echo "✅ Error log file exists<br>";
        echo "Last 5 lines of error log:<br>";
        $lines = file($errorLog);
        $lastLines = array_slice($lines, -5);
        foreach ($lastLines as $line) {
            echo htmlspecialchars($line) . "<br>";
        }
    } else {
        echo "❌ Error log file not found<br>";
    }
} else {
    echo "❌ Error log not configured<br>";
}

echo "<h2>Debug Complete</h2>";
echo "<p>Check the output above to identify the issue with Flutterwave integration.</p>";
?>
