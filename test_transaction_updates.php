<?php
// Test script to verify transaction updates work correctly
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'payment_config.php';
require_once 'backend/db_connect.php';
require_once 'payment_processor.php';

echo "<h1>Testing Transaction Updates</h1>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h2>1. Database Connection</h2>";
    echo "✅ Database connection successful<br>";
    
    // Test 1: Check transactions table structure
    echo "<h2>2. Transactions Table Structure</h2>";
    $result = $conn->query("DESCRIBE transactions");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
        echo "Column: {$row['Field']} - Type: {$row['Type']}<br>";
    }
    
    $requiredColumns = ['status', 'paid_at', 'payment_method', 'gateway_reference'];
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columns)) {
            echo "✅ Column '$col' exists<br>";
        } else {
            echo "❌ Column '$col' missing<br>";
        }
    }
    
    // Test 2: Test PaymentProcessor updateTransactionStatus method
    echo "<h2>3. PaymentProcessor Update Test</h2>";
    
    try {
        $processor = new PaymentProcessor();
        echo "✅ PaymentProcessor instantiated successfully<br>";
        
        // Create a test transaction
        $testReference = 'TEST_' . time() . '_' . rand(1000, 9999);
        $testApplicationId = 1; // Assuming application ID 1 exists
        
        $stmt = $conn->prepare("INSERT INTO transactions (application_id, amount, currency, reference, status, payment_gateway, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $amount = 50000;
        $currency = 'NGN';
        $status = 'pending';
        $gateway = 'flutterwave';
        $stmt->bind_param("idssss", $testApplicationId, $amount, $currency, $testReference, $status, $gateway);
        
        if ($stmt->execute()) {
            echo "✅ Test transaction created with reference: $testReference<br>";
            
            // Test updating with payment method
            $processor->updateTransactionStatus($testReference, 'successful', 'FLW_TEST_123', 'Card Payment');
            echo "✅ Transaction status updated to successful<br>";
            
            // Verify the update
            $stmt = $conn->prepare("SELECT * FROM transactions WHERE reference = ?");
            $stmt->bind_param("s", $testReference);
            $stmt->execute();
            $result = $stmt->get_result();
            $transaction = $result->fetch_assoc();
            $stmt->close();
            
            if ($transaction) {
                echo "<h3>Transaction Details After Update:</h3>";
                echo "Status: {$transaction['status']}<br>";
                echo "Payment Method: {$transaction['payment_method']}<br>";
                echo "Gateway Reference: {$transaction['gateway_reference']}<br>";
                echo "Paid At: {$transaction['paid_at']}<br>";
                
                if ($transaction['status'] === 'successful' && 
                    $transaction['payment_method'] === 'Card Payment' && 
                    $transaction['gateway_reference'] === 'FLW_TEST_123' && 
                    !empty($transaction['paid_at'])) {
                    echo "✅ All fields updated correctly!<br>";
                } else {
                    echo "❌ Some fields not updated correctly<br>";
                }
            } else {
                echo "❌ Could not retrieve updated transaction<br>";
            }
            
            // Clean up test transaction
            $conn->query("DELETE FROM transactions WHERE reference = '$testReference'");
            echo "✅ Test transaction cleaned up<br>";
            
        } else {
            echo "❌ Failed to create test transaction<br>";
        }
        $stmt->close();
        
    } catch (Exception $e) {
        echo "❌ PaymentProcessor test failed: " . $e->getMessage() . "<br>";
    }
    
    // Test 3: Check existing transactions
    echo "<h2>4. Existing Transactions Analysis</h2>";
    $result = $conn->query("SELECT COUNT(*) as total FROM transactions");
    $totalTransactions = $result->fetch_assoc()['total'];
    echo "Total transactions: $totalTransactions<br>";
    
    if ($totalTransactions > 0) {
        $result = $conn->query("SELECT status, payment_method, COUNT(*) as count FROM transactions GROUP BY status, payment_method");
        echo "<h3>Transaction Status Distribution:</h3>";
        while ($row = $result->fetch_assoc()) {
            echo "Status: {$row['status']}, Method: {$row['payment_method']}, Count: {$row['count']}<br>";
        }
        
        // Check for transactions with missing payment_method
        $result = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE payment_method IS NULL OR payment_method = ''");
        $missingMethod = $result->fetch_assoc()['count'];
        if ($missingMethod > 0) {
            echo "⚠️ Found $missingMethod transactions with missing payment_method<br>";
        } else {
            echo "✅ All transactions have payment_method set<br>";
        }
        
        // Check for successful transactions without paid_at
        $result = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE status = 'successful' AND paid_at IS NULL");
        $missingPaidAt = $result->fetch_assoc()['count'];
        if ($missingPaidAt > 0) {
            echo "⚠️ Found $missingPaidAt successful transactions without paid_at<br>";
        } else {
            echo "✅ All successful transactions have paid_at set<br>";
        }
    }
    
    // Test 4: Test different payment methods
    echo "<h2>5. Payment Method Test</h2>";
    $testMethods = ['Card Payment', 'USSD', 'Bank Transfer', 'Mobile Money'];
    
    foreach ($testMethods as $method) {
        $testRef = 'TEST_METHOD_' . time() . '_' . rand(100, 999);
        
        $stmt = $conn->prepare("INSERT INTO transactions (application_id, amount, currency, reference, status, payment_gateway, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $amount = 50000;
        $currency = 'NGN';
        $status = 'pending';
        $gateway = 'flutterwave';
        $stmt->bind_param("idssss", $testApplicationId, $amount, $currency, $testRef, $status, $gateway);
        
        if ($stmt->execute()) {
            $processor->updateTransactionStatus($testRef, 'successful', 'FLW_METHOD_123', $method);
            
            // Verify
            $stmt = $conn->prepare("SELECT payment_method FROM transactions WHERE reference = ?");
            $stmt->bind_param("s", $testRef);
            $stmt->execute();
            $result = $stmt->get_result();
            $transaction = $result->fetch_assoc();
            $stmt->close();
            
            if ($transaction && $transaction['payment_method'] === $method) {
                echo "✅ Payment method '$method' updated correctly<br>";
            } else {
                echo "❌ Payment method '$method' not updated correctly<br>";
            }
            
            // Clean up
            $conn->query("DELETE FROM transactions WHERE reference = '$testRef'");
        }
        $stmt->close();
    }
    
    echo "<h2>6. Summary</h2>";
    echo "All tests completed. Check the results above for any issues.<br>";
    echo "If you see any ❌ marks, those issues need to be addressed.<br>";
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "❌ " . $e->getMessage();
}

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #2563eb; }
h2 { color: #1f2937; margin-top: 30px; }
h3 { color: #374151; margin-top: 20px; }
</style>
