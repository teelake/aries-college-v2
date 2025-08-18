<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'backend/db_connect.php';
require_once 'payment_processor.php';

echo "<h1>Transaction Lookup Debug</h1>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    echo "<h2>Current Transactions in Database</h2>";
    
    // Show all transactions
    $result = $conn->query("SELECT id, application_id, reference, gateway_reference, payment_gateway, amount, status, created_at FROM transactions ORDER BY created_at DESC LIMIT 10");
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>App ID</th><th>Reference</th><th>Gateway Ref</th><th>Gateway</th><th>Amount</th><th>Status</th><th>Created</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['application_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['reference']) . "</td>";
            echo "<td>" . htmlspecialchars($row['gateway_reference']) . "</td>";
            echo "<td>" . htmlspecialchars($row['payment_gateway']) . "</td>";
            echo "<td>₦" . number_format($row['amount'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No transactions found in database.</p>";
    }
    
    echo "<h2>Test Transaction Lookup Methods</h2>";
    
    // Test with a sample transaction ID (you can modify this)
    $testTransactionId = "9563887"; // Replace with actual transaction ID from your error
    
    echo "<h3>Testing lookup for Transaction ID: $testTransactionId</h3>";
    
    // Method 1: Using PaymentProcessor
    echo "<h4>Method 1: PaymentProcessor::getTransactionByGatewayReference()</h4>";
    $paymentProcessor = new PaymentProcessor();
    $transaction1 = $paymentProcessor->getTransactionByGatewayReference($testTransactionId);
    
    if ($transaction1) {
        echo "<p>✅ Found transaction:</p>";
        echo "<pre>" . print_r($transaction1, true) . "</pre>";
    } else {
        echo "<p>❌ No transaction found using PaymentProcessor method.</p>";
    }
    
    // Method 2: Direct database lookup
    echo "<h4>Method 2: Direct Database Lookup</h4>";
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE gateway_reference = ?");
    $stmt->bind_param("s", $testTransactionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction2 = $result->fetch_assoc();
    $stmt->close();
    
    if ($transaction2) {
        echo "<p>✅ Found transaction:</p>";
        echo "<pre>" . print_r($transaction2, true) . "</pre>";
    } else {
        echo "<p>❌ No transaction found using direct database lookup.</p>";
    }
    
    // Method 3: Alternative lookup (reference column)
    echo "<h4>Method 3: Alternative Lookup (reference column)</h4>";
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE reference = ?");
    $stmt->bind_param("s", $testTransactionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction3 = $result->fetch_assoc();
    $stmt->close();
    
    if ($transaction3) {
        echo "<p>✅ Found transaction:</p>";
        echo "<pre>" . print_r($transaction3, true) . "</pre>";
    } else {
        echo "<p>❌ No transaction found using reference column lookup.</p>";
    }
    
    // Method 4: Combined lookup
    echo "<h4>Method 4: Combined Lookup (both columns)</h4>";
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE reference = ? OR gateway_reference = ?");
    $stmt->bind_param("ss", $testTransactionId, $testTransactionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction4 = $result->fetch_assoc();
    $stmt->close();
    
    if ($transaction4) {
        echo "<p>✅ Found transaction:</p>";
        echo "<pre>" . print_r($transaction4, true) . "</pre>";
    } else {
        echo "<p>❌ No transaction found using combined lookup.</p>";
    }
    
    echo "<h2>Simulate URL Parameters</h2>";
    
    // Simulate different URL parameter scenarios
    $testScenarios = [
        'tx_ref_only' => ['tx_ref' => 'ACH_1755268761_4'],
        'transaction_id_only' => ['transaction_id' => $testTransactionId],
        'both_params' => ['tx_ref' => 'ACH_1755268761_4', 'transaction_id' => $testTransactionId],
        'reference_only' => ['reference' => 'ACH_1755268761_4']
    ];
    
    foreach ($testScenarios as $scenario => $params) {
        echo "<h3>Scenario: $scenario</h3>";
        echo "<p>Parameters: " . json_encode($params) . "</p>";
        
        // Simulate the lookup logic
        $reference = $params['tx_ref'] ?? $params['reference'] ?? $params['trxref'] ?? null;
        
        if (!$reference && isset($params['transaction_id'])) {
            $transactionId = $params['transaction_id'];
            echo "<p>Looking for transaction with ID: $transactionId</p>";
            
            $transaction = $paymentProcessor->getTransactionByGatewayReference($transactionId);
            
            if ($transaction) {
                $reference = $transaction['reference'];
                echo "<p>✅ Found reference: $reference</p>";
            } else {
                echo "<p>❌ No transaction found for ID: $transactionId</p>";
                
                // Try alternative lookup
                $stmt = $conn->prepare("SELECT * FROM transactions WHERE reference = ? OR gateway_reference = ?");
                $stmt->bind_param("ss", $transactionId, $transactionId);
                $stmt->execute();
                $result = $stmt->get_result();
                $altTransaction = $result->fetch_assoc();
                $stmt->close();
                
                if ($altTransaction) {
                    $reference = $altTransaction['reference'];
                    echo "<p>✅ Found reference using alternative lookup: $reference</p>";
                } else {
                    echo "<p>❌ No transaction found using alternative lookup</p>";
                }
            }
        }
        
        if ($reference) {
            echo "<p>✅ Final reference: $reference</p>";
        } else {
            echo "<p>❌ No reference found</p>";
        }
        
        echo "<hr>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>
