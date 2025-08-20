<?php
// Test file to verify result status functionality
require_once 'backend/db_connect.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    echo "=== Result Status Test ===\n";
    
    // Check if result_status column exists
    $result = $conn->query("SHOW COLUMNS FROM applications LIKE 'result_status'");
    if ($result->num_rows > 0) {
        echo "✓ result_status column exists\n";
        
        // Check current data
        $data = $conn->query("SELECT COUNT(*) as total, 
                             SUM(CASE WHEN result_status = 'available' THEN 1 ELSE 0 END) as available,
                             SUM(CASE WHEN result_status = 'awaiting_result' THEN 1 ELSE 0 END) as awaiting
                             FROM applications");
        $stats = $data->fetch_assoc();
        
        echo "Total applications: " . $stats['total'] . "\n";
        echo "Available results: " . $stats['available'] . "\n";
        echo "Awaiting results: " . $stats['awaiting'] . "\n";
        
    } else {
        echo "✗ result_status column does not exist\n";
    }
    
    // Test dashboard analytics
    echo "\n=== Dashboard Analytics Test ===\n";
    
    $totalApplicants = $conn->query("SELECT COUNT(*) FROM applications")->fetch_row()[0];
    $admitted = $conn->query("SELECT COUNT(*) FROM applications WHERE application_status='admitted'")->fetch_row()[0];
    $notAdmitted = $conn->query("SELECT COUNT(*) FROM applications WHERE application_status='not_admitted'")->fetch_row()[0];
    
    echo "Total applicants: " . $totalApplicants . "\n";
    echo "Admitted: " . $admitted . "\n";
    echo "Not admitted: " . $notAdmitted . "\n";
    
    echo "\n✓ Dashboard analytics queries are working correctly\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
