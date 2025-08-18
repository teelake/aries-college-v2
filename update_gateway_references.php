<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'backend/db_connect.php';

echo "<h1>Update Gateway References</h1>";

// Array of transaction mappings: reference => gateway_reference
// Add your transaction mappings here based on your Flutterwave dashboard
$transactionMappings = [
    'ACH_1755525819_11' => '9569794', // This is the one from your screenshot
    // Add other mappings as needed
];

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    echo "<h2>Current Transactions</h2>";
    
    // Show current transactions
    $result = $conn->query("SELECT id, reference, gateway_reference, status FROM transactions ORDER BY created_at DESC");
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Reference</th><th>Gateway Reference</th><th>Status</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['reference']) . "</td>";
            echo "<td>" . htmlspecialchars($row['gateway_reference'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>Updating Gateway References</h2>";
    
    $updatedCount = 0;
    
    foreach ($transactionMappings as $reference => $gatewayReference) {
        $stmt = $conn->prepare("UPDATE transactions SET gateway_reference = ? WHERE reference = ?");
        $stmt->bind_param("ss", $gatewayReference, $reference);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo "<p>✅ Updated gateway_reference for $reference to $gatewayReference</p>";
                $updatedCount++;
            } else {
                echo "<p>⚠️ No transaction found with reference $reference</p>";
            }
        } else {
            echo "<p>❌ Error updating $reference: " . $conn->error . "</p>";
        }
        $stmt->close();
    }
    
    echo "<h2>Update Summary</h2>";
    echo "<p>Updated $updatedCount transactions</p>";
    
    echo "<h2>Updated Transactions</h2>";
    
    // Show updated transactions
    $result = $conn->query("SELECT id, reference, gateway_reference, status FROM transactions ORDER BY created_at DESC");
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Reference</th><th>Gateway Reference</th><th>Status</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['reference']) . "</td>";
            echo "<td>" . htmlspecialchars($row['gateway_reference'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
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
</style>

<p><strong>Instructions:</strong></p>
<ol>
    <li>Go to your Flutterwave dashboard</li>
    <li>Find the transaction IDs for each reference</li>
    <li>Update the $transactionMappings array above with the correct transaction IDs</li>
    <li>Refresh this page to apply the updates</li>
</ol>
