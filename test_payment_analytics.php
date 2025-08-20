<?php
require_once 'backend/db_connect.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    echo "=== Payment Analytics Debug ===\n";
    
    // Check transactions table structure
    echo "1. Checking transactions table structure:\n";
    $result = $conn->query("DESCRIBE transactions");
    while($row = $result->fetch_assoc()) {
        echo "   - {$row['Field']}: {$row['Type']}\n";
    }
    
    // Check total transactions
    echo "\n2. Total transactions count:\n";
    $total = $conn->query("SELECT COUNT(*) FROM transactions")->fetch_row()[0];
    echo "   Total transactions: $total\n";
    
    // Check transactions by status
    echo "\n3. Transactions by status:\n";
    $statuses = $conn->query("SELECT status, COUNT(*) as count FROM transactions GROUP BY status");
    while($row = $statuses->fetch_assoc()) {
        echo "   - {$row['status']}: {$row['count']}\n";
    }
    
    // Check successful transactions
    echo "\n4. Successful transactions:\n";
    $successful = $conn->query("SELECT COUNT(*) FROM transactions WHERE status='success'")->fetch_row()[0];
    echo "   Successful transactions: $successful\n";
    
    // Check total amount from successful transactions
    echo "\n5. Total amount from successful transactions:\n";
    $totalAmount = $conn->query("SELECT IFNULL(SUM(amount),0) FROM transactions WHERE status='success'")->fetch_row()[0];
    echo "   Total amount: ₦" . number_format($totalAmount, 2) . "\n";
    
    // Check individual successful transactions
    echo "\n6. Individual successful transactions:\n";
    $transactions = $conn->query("SELECT id, amount, reference, created_at FROM transactions WHERE status='success' ORDER BY created_at DESC LIMIT 5");
    while($row = $transactions->fetch_assoc()) {
        echo "   - ID: {$row['id']}, Amount: ₦{$row['amount']}, Ref: {$row['reference']}, Date: {$row['created_at']}\n";
    }
    
    // Test the exact query used in dashboard
    echo "\n7. Testing dashboard query:\n";
    $dashboardQuery = "SELECT IFNULL(SUM(amount),0) FROM transactions WHERE status='success'";
    $dashboardResult = $conn->query($dashboardQuery)->fetch_row()[0];
    echo "   Dashboard query result: ₦" . number_format($dashboardResult, 2) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
