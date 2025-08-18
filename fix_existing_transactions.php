<?php
// Script to fix existing transactions with missing payment method or paid_at values
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'payment_config.php';
require_once 'backend/db_connect.php';

echo "<h1>Fixing Existing Transactions</h1>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h2>1. Database Connection</h2>";
    echo "✅ Database connection successful<br>";
    
    // Check for transactions with missing payment_method
    echo "<h2>2. Fixing Missing Payment Methods</h2>";
    $result = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE payment_method IS NULL OR payment_method = ''");
    $missingMethod = $result->fetch_assoc()['count'];
    
    if ($missingMethod > 0) {
        echo "Found $missingMethod transactions with missing payment_method<br>";
        
        // Update transactions with missing payment_method to default value
        $updateResult = $conn->query("UPDATE transactions SET payment_method = 'Card Payment' WHERE payment_method IS NULL OR payment_method = ''");
        
        if ($updateResult) {
            echo "✅ Updated $missingMethod transactions with default payment method<br>";
        } else {
            echo "❌ Failed to update transactions with payment method<br>";
        }
    } else {
        echo "✅ All transactions have payment_method set<br>";
    }
    
    // Check for successful transactions with missing paid_at
    echo "<h2>3. Fixing Missing Paid At Timestamps</h2>";
    $result = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE status = 'successful' AND paid_at IS NULL");
    $missingPaidAt = $result->fetch_assoc()['count'];
    
    if ($missingPaidAt > 0) {
        echo "Found $missingPaidAt successful transactions with missing paid_at<br>";
        
        // Update successful transactions with missing paid_at to use updated_at timestamp
        $updateResult = $conn->query("UPDATE transactions SET paid_at = updated_at WHERE status = 'successful' AND paid_at IS NULL");
        
        if ($updateResult) {
            echo "✅ Updated $missingPaidAt transactions with paid_at timestamp<br>";
        } else {
            echo "❌ Failed to update transactions with paid_at<br>";
        }
    } else {
        echo "✅ All successful transactions have paid_at set<br>";
    }
    
    // Check for transactions with missing gateway_reference
    echo "<h2>4. Fixing Missing Gateway References</h2>";
    $result = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE gateway_reference IS NULL OR gateway_reference = ''");
    $missingGatewayRef = $result->fetch_assoc()['count'];
    
    if ($missingGatewayRef > 0) {
        echo "Found $missingGatewayRef transactions with missing gateway_reference<br>";
        
        // For transactions without gateway_reference, we'll set it to the reference itself
        $updateResult = $conn->query("UPDATE transactions SET gateway_reference = reference WHERE gateway_reference IS NULL OR gateway_reference = ''");
        
        if ($updateResult) {
            echo "✅ Updated $missingGatewayRef transactions with gateway_reference<br>";
        } else {
            echo "❌ Failed to update transactions with gateway_reference<br>";
        }
    } else {
        echo "✅ All transactions have gateway_reference set<br>";
    }
    
    // Show final statistics
    echo "<h2>5. Final Statistics</h2>";
    $result = $conn->query("SELECT status, COUNT(*) as count FROM transactions GROUP BY status");
    echo "<h3>Transaction Status Distribution:</h3>";
    while ($row = $result->fetch_assoc()) {
        echo "Status: {$row['status']}, Count: {$row['count']}<br>";
    }
    
    $result = $conn->query("SELECT payment_method, COUNT(*) as count FROM transactions GROUP BY payment_method");
    echo "<h3>Payment Method Distribution:</h3>";
    while ($row = $result->fetch_assoc()) {
        echo "Method: {$row['payment_method']}, Count: {$row['count']}<br>";
    }
    
    // Verify all issues are fixed
    echo "<h2>6. Verification</h2>";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE payment_method IS NULL OR payment_method = ''");
    $stillMissingMethod = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE status = 'successful' AND paid_at IS NULL");
    $stillMissingPaidAt = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE gateway_reference IS NULL OR gateway_reference = ''");
    $stillMissingGatewayRef = $result->fetch_assoc()['count'];
    
    if ($stillMissingMethod == 0 && $stillMissingPaidAt == 0 && $stillMissingGatewayRef == 0) {
        echo "✅ All transaction issues have been fixed!<br>";
    } else {
        echo "⚠️ Some issues remain:<br>";
        if ($stillMissingMethod > 0) echo "- $stillMissingMethod transactions still missing payment_method<br>";
        if ($stillMissingPaidAt > 0) echo "- $stillMissingPaidAt transactions still missing paid_at<br>";
        if ($stillMissingGatewayRef > 0) echo "- $stillMissingGatewayRef transactions still missing gateway_reference<br>";
    }
    
    echo "<h2>7. Summary</h2>";
    echo "Transaction fix script completed successfully!<br>";
    echo "All transactions should now have proper payment_method, paid_at, and gateway_reference values.<br>";
    
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
