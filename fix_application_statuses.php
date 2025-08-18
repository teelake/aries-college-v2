<?php
// Script to fix existing applications with old status values
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'payment_config.php';
require_once 'backend/db_connect.php';

echo "<h1>Fixing Application Status Values</h1>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h2>1. Database Connection</h2>";
    echo "✅ Database connection successful<br>";
    
    // Check current status distribution
    echo "<h2>2. Current Status Distribution (Before Fix)</h2>";
    $result = $conn->query("SELECT application_status, COUNT(*) as count FROM applications GROUP BY application_status ORDER BY count DESC");
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Status</th><th>Count</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['application_status']) . "</td>";
            echo "<td>" . $row['count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Define status mapping
    $statusMapping = [
        'approved' => 'admitted',
        'rejected' => 'not_admitted'
    ];
    
    echo "<h2>3. Status Mapping</h2>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Old Status</th><th>New Status</th></tr>";
    
    foreach ($statusMapping as $oldStatus => $newStatus) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($oldStatus) . "</td>";
        echo "<td>" . htmlspecialchars($newStatus) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Update old statuses
    echo "<h2>4. Updating Old Status Values</h2>";
    
    $totalUpdated = 0;
    
    foreach ($statusMapping as $oldStatus => $newStatus) {
        // Check if there are applications with old status
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE application_status = ?");
        $stmt->bind_param("s", $oldStatus);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        $count = $row['count'];
        
        if ($count > 0) {
            echo "Found $count applications with status '$oldStatus'<br>";
            
            // Update the status
            $stmt = $conn->prepare("UPDATE applications SET application_status = ?, updated_at = NOW() WHERE application_status = ?");
            $stmt->bind_param("ss", $newStatus, $oldStatus);
            $updateResult = $stmt->execute();
            $stmt->close();
            
            if ($updateResult) {
                echo "✅ Updated $count applications from '$oldStatus' to '$newStatus'<br>";
                $totalUpdated += $count;
            } else {
                echo "❌ Failed to update applications from '$oldStatus' to '$newStatus'<br>";
            }
        } else {
            echo "No applications found with status '$oldStatus'<br>";
        }
    }
    
    // Check for any other invalid statuses
    echo "<h2>5. Checking for Other Invalid Statuses</h2>";
    
    $validStatuses = ['pending_payment', 'submitted', 'under_review', 'admitted', 'not_admitted'];
    $validStatusesStr = "'" . implode("','", $validStatuses) . "'";
    
    $result = $conn->query("SELECT application_status, COUNT(*) as count FROM applications WHERE application_status NOT IN ($validStatusesStr)");
    
    if ($result->num_rows > 0) {
        echo "<h3>Applications with other invalid status values:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Invalid Status</th><th>Count</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['application_status']) . "</td>";
            echo "<td>" . $row['count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p><strong>Note:</strong> These applications will be updated to 'submitted' status.</p>";
        
        // Update invalid statuses to 'submitted'
        $stmt = $conn->prepare("UPDATE applications SET application_status = 'submitted', updated_at = NOW() WHERE application_status NOT IN ($validStatusesStr)");
        $updateResult = $stmt->execute();
        $stmt->close();
        
        if ($updateResult) {
            $affectedRows = $conn->affected_rows;
            echo "✅ Updated $affectedRows applications with invalid status to 'submitted'<br>";
            $totalUpdated += $affectedRows;
        } else {
            echo "❌ Failed to update applications with invalid status<br>";
        }
    } else {
        echo "✅ No applications with invalid status values found<br>";
    }
    
    // Show final status distribution
    echo "<h2>6. Final Status Distribution (After Fix)</h2>";
    $result = $conn->query("SELECT application_status, COUNT(*) as count FROM applications GROUP BY application_status ORDER BY count DESC");
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Status</th><th>Count</th><th>Display Label</th></tr>";
        
        $statusLabels = [
            'pending_payment' => 'Pending Payment',
            'submitted' => 'Submitted',
            'under_review' => 'Under Review',
            'admitted' => 'Admitted',
            'not_admitted' => 'Not Admitted'
        ];
        
        while ($row = $result->fetch_assoc()) {
            $status = $row['application_status'];
            $count = $row['count'];
            $label = $statusLabels[$status] ?? ucfirst($status);
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($status) . "</td>";
            echo "<td>$count</td>";
            echo "<td>" . htmlspecialchars($label) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Verification
    echo "<h2>7. Verification</h2>";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM applications WHERE application_status NOT IN ($validStatusesStr)");
    $invalidCount = $result->fetch_assoc()['count'];
    
    if ($invalidCount == 0) {
        echo "✅ All applications now have valid status values!<br>";
    } else {
        echo "❌ Still found $invalidCount applications with invalid status values<br>";
    }
    
    echo "<h2>8. Summary</h2>";
    echo "Total applications updated: $totalUpdated<br>";
    echo "Status fix script completed successfully!<br>";
    echo "The admin panel status filter should now work correctly with all applications.<br>";
    
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
