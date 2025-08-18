<?php
// Test script to verify status filter works correctly
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'payment_config.php';
require_once 'backend/db_connect.php';

echo "<h1>Testing Application Status Filter</h1>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h2>1. Database Connection</h2>";
    echo "✅ Database connection successful<br>";
    
    // Check current application status distribution
    echo "<h2>2. Current Application Status Distribution</h2>";
    $result = $conn->query("SELECT application_status, COUNT(*) as count FROM applications GROUP BY application_status ORDER BY count DESC");
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Status</th><th>Count</th><th>Valid Filter Value</th></tr>";
        
        $validStatuses = ['pending_payment', 'submitted', 'under_review', 'admitted', 'not_admitted'];
        
        while ($row = $result->fetch_assoc()) {
            $isValid = in_array($row['application_status'], $validStatuses) ? '✅ Yes' : '❌ No';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['application_status']) . "</td>";
            echo "<td>" . $row['count'] . "</td>";
            echo "<td>$isValid</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No applications found in database<br>";
    }
    
    // Test status filter functionality
    echo "<h2>3. Status Filter Test</h2>";
    
    $testStatuses = ['pending_payment', 'submitted', 'under_review', 'admitted', 'not_admitted'];
    
    foreach ($testStatuses as $testStatus) {
        echo "<h3>Testing filter for status: $testStatus</h3>";
        
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE application_status = ?");
        $stmt->bind_param("s", $testStatus);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        $count = $row['count'];
        echo "Applications with status '$testStatus': $count<br>";
        
        if ($count > 0) {
            echo "✅ Filter will work for this status<br>";
        } else {
            echo "⚠️ No applications with this status (filter will show empty results)<br>";
        }
    }
    
    // Test invalid status handling
    echo "<h2>4. Invalid Status Test</h2>";
    $invalidStatuses = ['approved', 'rejected', 'invalid_status'];
    
    foreach ($invalidStatuses as $invalidStatus) {
        echo "<h3>Testing invalid status: $invalidStatus</h3>";
        
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE application_status = ?");
        $stmt->bind_param("s", $invalidStatus);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        $count = $row['count'];
        echo "Applications with invalid status '$invalidStatus': $count<br>";
        
        if ($count > 0) {
            echo "❌ Found applications with old/invalid status that need to be updated<br>";
        } else {
            echo "✅ No applications with invalid status<br>";
        }
    }
    
    // Show status labels mapping
    echo "<h2>5. Status Labels Mapping</h2>";
    $statusLabels = [
        'pending_payment' => 'Pending Payment',
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'admitted' => 'Admitted',
        'not_admitted' => 'Not Admitted'
    ];
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Database Value</th><th>Display Label</th><th>CSS Class</th></tr>";
    
    foreach ($statusLabels as $dbValue => $displayLabel) {
        $cssClass = "status-$dbValue";
        echo "<tr>";
        echo "<td>" . htmlspecialchars($dbValue) . "</td>";
        echo "<td>" . htmlspecialchars($displayLabel) . "</td>";
        echo "<td>" . htmlspecialchars($cssClass) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check for any applications that need status updates
    echo "<h2>6. Status Update Recommendations</h2>";
    
    $result = $conn->query("SELECT application_status, COUNT(*) as count FROM applications WHERE application_status NOT IN ('pending_payment', 'submitted', 'under_review', 'admitted', 'not_admitted')");
    
    if ($result->num_rows > 0) {
        echo "<h3>Applications with old/invalid status values:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Old Status</th><th>Count</th><th>Should Be Updated To</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            $oldStatus = $row['application_status'];
            $count = $row['count'];
            
            // Map old statuses to new ones
            $statusMapping = [
                'approved' => 'admitted',
                'rejected' => 'not_admitted'
            ];
            
            $newStatus = $statusMapping[$oldStatus] ?? 'submitted';
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($oldStatus) . "</td>";
            echo "<td>$count</td>";
            echo "<td>" . htmlspecialchars($newStatus) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p><strong>Recommendation:</strong> Run a database update to fix these old status values.</p>";
    } else {
        echo "✅ All applications have valid status values!<br>";
    }
    
    echo "<h2>7. Summary</h2>";
    echo "Status filter test completed. The filter should now work correctly with the updated status values.<br>";
    echo "Make sure to test the filter in the admin panel to verify it works as expected.<br>";
    
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
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
