<?php
// Test script to verify the fixes work correctly
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'payment_config.php';
require_once 'backend/db_connect.php';

echo "<h1>Testing Payment System Fixes</h1>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h2>1. Database Connection Test</h2>";
    echo "✅ Database connection successful<br>";
    
    // Test 1: Check applications table structure
    echo "<h2>2. Applications Table Structure</h2>";
    $result = $conn->query("DESCRIBE applications");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $requiredColumns = ['payment_status', 'application_status'];
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columns)) {
            echo "✅ Column '$col' exists<br>";
        } else {
            echo "❌ Column '$col' missing<br>";
        }
    }
    
    // Test 2: Check transactions table structure
    echo "<h2>3. Transactions Table Structure</h2>";
    $result = $conn->query("DESCRIBE transactions");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $requiredColumns = ['status', 'paid_at', 'payment_method', 'gateway_reference'];
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columns)) {
            echo "✅ Column '$col' exists<br>";
        } else {
            echo "❌ Column '$col' missing<br>";
        }
    }
    
    // Test 3: Check ENUM values
    echo "<h2>4. ENUM Values Test</h2>";
    
    // Check application_status ENUM values
    $result = $conn->query("SHOW COLUMNS FROM applications LIKE 'application_status'");
    $row = $result->fetch_assoc();
    $enumValues = str_replace(['enum(', ')', "'"], '', $row['Type']);
    $enumArray = explode(',', $enumValues);
    
    $expectedValues = ['pending_payment', 'submitted', 'under_review', 'admitted', 'not_admitted'];
    foreach ($expectedValues as $value) {
        if (in_array($value, $enumArray)) {
            echo "✅ application_status ENUM value '$value' exists<br>";
        } else {
            echo "❌ application_status ENUM value '$value' missing<br>";
        }
    }
    
    // Test 4: Test PaymentProcessor class
    echo "<h2>5. PaymentProcessor Class Test</h2>";
    if (class_exists('PaymentProcessor')) {
        echo "✅ PaymentProcessor class exists<br>";
        
        // Test updateTransactionStatus method
        try {
            $processor = new PaymentProcessor();
            echo "✅ PaymentProcessor instantiated successfully<br>";
        } catch (Exception $e) {
            echo "❌ PaymentProcessor instantiation failed: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ PaymentProcessor class not found<br>";
    }
    
    // Test 5: Check if fix_transactions_table.sql needs to be run
    echo "<h2>6. Database Structure Check</h2>";
    $result = $conn->query("SHOW COLUMNS FROM transactions LIKE 'payment_method'");
    if ($result->num_rows > 0) {
        echo "✅ payment_method column exists in transactions table<br>";
    } else {
        echo "⚠️ payment_method column missing. Run fix_transactions_table.sql<br>";
    }
    
    echo "<h2>7. Summary</h2>";
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
</style>
