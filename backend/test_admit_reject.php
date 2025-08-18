<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

echo "<h1>Test Admit/Reject Functionality</h1>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    echo "<h2>Step 1: Check Current Applications</h2>";
    
    // Get current applications with their statuses
    $result = $conn->query("SELECT id, full_name, email, application_status, payment_status FROM applications ORDER BY created_at DESC LIMIT 5");
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Application Status</th><th>Payment Status</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['application_status']) . "</td>";
            echo "<td>" . htmlspecialchars($row['payment_status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No applications found.</p>";
    }
    
    echo "<h2>Step 2: Test Status Update Functions</h2>";
    
    // Test the admit function
    echo "<h3>Testing Admit Function</h3>";
    
    // Get a test application
    $testApp = $conn->query("SELECT id, full_name, application_status FROM applications WHERE application_status = 'submitted' LIMIT 1")->fetch_assoc();
    
    if ($testApp) {
        echo "<p>Test application found: ID {$testApp['id']} - {$testApp['full_name']} (Current status: {$testApp['application_status']})</p>";
        
        // Simulate the admit function
        $updateResult = $conn->query("UPDATE applications SET application_status = 'approved', updated_at = NOW() WHERE id = {$testApp['id']}");
        
        if ($updateResult) {
            echo "<p>✅ Application status updated to 'approved' successfully</p>";
            
            // Verify the update
            $updatedApp = $conn->query("SELECT application_status FROM applications WHERE id = {$testApp['id']}")->fetch_assoc();
            echo "<p>New status: " . $updatedApp['application_status'] . "</p>";
        } else {
            echo "<p>❌ Failed to update application status</p>";
        }
    } else {
        echo "<p>No applications with 'submitted' status found for testing.</p>";
    }
    
    echo "<h3>Testing Reject Function</h3>";
    
    // Get another test application
    $testApp2 = $conn->query("SELECT id, full_name, application_status FROM applications WHERE application_status = 'submitted' LIMIT 1")->fetch_assoc();
    
    if ($testApp2) {
        echo "<p>Test application found: ID {$testApp2['id']} - {$testApp2['full_name']} (Current status: {$testApp2['application_status']})</p>";
        
        // Simulate the reject function
        $updateResult = $conn->query("UPDATE applications SET application_status = 'rejected', updated_at = NOW() WHERE id = {$testApp2['id']}");
        
        if ($updateResult) {
            echo "<p>✅ Application status updated to 'rejected' successfully</p>";
            
            // Verify the update
            $updatedApp = $conn->query("SELECT application_status FROM applications WHERE id = {$testApp2['id']}")->fetch_assoc();
            echo "<p>New status: " . $updatedApp['application_status'] . "</p>";
        } else {
            echo "<p>❌ Failed to update application status</p>";
        }
    } else {
        echo "<p>No applications with 'submitted' status found for testing.</p>";
    }
    
    echo "<h2>Step 3: Test Message System</h2>";
    
    // Test the session message system
    session_start();
    $_SESSION['admin_message'] = ['type' => 'success', 'text' => 'This is a test success message'];
    
    echo "<p>✅ Test message set in session</p>";
    echo "<p>Message type: " . $_SESSION['admin_message']['type'] . "</p>";
    echo "<p>Message text: " . $_SESSION['admin_message']['text'] . "</p>";
    
    // Clear the test message
    unset($_SESSION['admin_message']);
    
    echo "<h2>Step 4: Updated Applications Status</h2>";
    
    // Show updated applications
    $result = $conn->query("SELECT id, full_name, email, application_status, payment_status FROM applications ORDER BY created_at DESC LIMIT 5");
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Application Status</th><th>Payment Status</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['application_status']) . "</td>";
            echo "<td>" . htmlspecialchars($row['payment_status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    $conn->close();
    
    echo "<h2>Test Summary</h2>";
    echo "<p>✅ Admit/Reject functionality test completed!</p>";
    echo "<p>The system should now:</p>";
    echo "<ul>";
    echo "<li>Use the correct 'application_status' column</li>";
    echo "<li>Update status to 'approved' when admitting</li>";
    echo "<li>Update status to 'rejected' when rejecting</li>";
    echo "<li>Display success/error messages</li>";
    echo "<li>Send notification emails</li>";
    echo "</ul>";
    
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
