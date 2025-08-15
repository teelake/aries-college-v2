<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'backend/db_connect.php';

echo "<h1>Payment Success Debug</h1>";

// Simulate the URL parameters that Flutterwave might send
$testUrls = [
    'http://your-domain.com/payment_success.php?tx_ref=ACH_1755268761_4&status=successful',
    'http://your-domain.com/payment_success.php?transaction_id=9563887&status=successful',
    'http://your-domain.com/payment_success.php?reference=ACH_1755268761_4&status=successful',
    'http://your-domain.com/payment_success.php?tx_ref=ACH_1755268761_4&transaction_id=9563887&status=successful'
];

echo "<h2>Testing Different URL Parameter Scenarios</h2>";

foreach ($testUrls as $index => $url) {
    echo "<h3>Test " . ($index + 1) . ": $url</h3>";
    
    // Parse the URL to get parameters
    $parsedUrl = parse_url($url);
    parse_str($parsedUrl['query'], $params);
    
    echo "<p><strong>Parameters:</strong></p>";
    echo "<pre>" . print_r($params, true) . "</pre>";
    
    // Simulate the payment success logic
    try {
        $reference = $params['tx_ref'] ?? $params['reference'] ?? $params['trxref'] ?? null;
        
        echo "<p><strong>Initial reference:</strong> " . ($reference ?? 'NULL') . "</p>";
        
        // If we have transaction_id but no reference, try to find the transaction by ID
        if (!$reference && isset($params['transaction_id'])) {
            $transactionId = $params['transaction_id'];
            echo "<p><strong>Looking for transaction with ID:</strong> $transactionId</p>";
            
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            // Check if transaction exists by gateway_reference
            $stmt = $conn->prepare("SELECT * FROM transactions WHERE gateway_reference = ?");
            $stmt->bind_param("s", $transactionId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                echo "<p style='color: green;'>✅ Found transaction by gateway_reference:</p>";
                echo "<pre>" . print_r($row, true) . "</pre>";
                $reference = $row['reference'];
            } else {
                echo "<p style='color: orange;'>⚠️ No transaction found by gateway_reference</p>";
                
                // Try by ID
                $stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ?");
                $stmt->bind_param("i", $transactionId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    echo "<p style='color: green;'>✅ Found transaction by ID:</p>";
                    echo "<pre>" . print_r($row, true) . "</pre>";
                    $reference = $row['reference'];
                } else {
                    echo "<p style='color: red;'>❌ No transaction found by ID either</p>";
                }
            }
            
            $stmt->close();
            $conn->close();
        }
        
        echo "<p><strong>Final reference:</strong> " . ($reference ?? 'NULL') . "</p>";
        
        if ($reference) {
            echo "<p style='color: green;'>✅ Reference found - payment success page should work</p>";
        } else {
            echo "<p style='color: red;'>❌ No reference found - payment success page will fail</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}

echo "<h2>Current Database Transactions</h2>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    $result = $conn->query("SELECT id, reference, gateway_reference, status, created_at FROM transactions ORDER BY created_at DESC LIMIT 5");
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Reference</th><th>Gateway Reference</th><th>Status</th><th>Created</th>";
        echo "</tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['reference'] . "</td>";
            echo "<td>" . ($row['gateway_reference'] ?? 'NULL') . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No transactions found in database.</p>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>Recommendations</h2>";

echo "<div style='background: #f0f8ff; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h3>To Fix the Payment Success Issue:</h3>";
echo "<ol>";
echo "<li><strong>Check the actual URL parameters</strong> that Flutterwave is sending</li>";
echo "<li><strong>Ensure gateway_reference is being saved</strong> when payment is verified</li>";
echo "<li><strong>Update the success page logic</strong> to handle both reference and transaction_id</li>";
echo "<li><strong>Add proper error handling</strong> for missing parameters</li>";
echo "</ol>";

echo "<h3>Expected URL Format:</h3>";
echo "<p>Flutterwave should redirect to:</p>";
echo "<code>https://your-domain.com/payment_success.php?tx_ref=ACH_1755268761_4&status=successful&transaction_id=9563887</code>";

echo "<h3>Current Fix Applied:</h3>";
echo "<ul>";
echo "<li>Added logic to find transaction by transaction_id if reference is not available</li>";
echo "<li>Added debug logging to track URL parameters</li>";
echo "<li>Enhanced error messages to show available parameters</li>";
echo "</ul>";
echo "</div>";

echo "<h2>Test Complete</h2>";
echo "<p>This debug script helps identify what parameters Flutterwave is sending and how to handle them properly.</p>";
?>
