<?php
require_once 'backend/db_connect.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    // Check if result_status column already exists
    $result = $conn->query("SHOW COLUMNS FROM applications LIKE 'result_status'");
    if ($result->num_rows == 0) {
        // Add the result_status column
        $sql = "ALTER TABLE applications ADD COLUMN result_status enum('available','awaiting_result') DEFAULT 'available' AFTER qualification";
        if ($conn->query($sql)) {
            echo "Successfully added result_status column to applications table.\n";
        } else {
            throw new Exception("Error adding result_status column: " . $conn->error);
        }
        
        // Update existing records to have 'available' as default
        $updateSql = "UPDATE applications SET result_status = 'available' WHERE result_status IS NULL";
        if ($conn->query($updateSql)) {
            echo "Successfully updated existing records with default result_status.\n";
        } else {
            throw new Exception("Error updating existing records: " . $conn->error);
        }
    } else {
        echo "result_status column already exists in applications table.\n";
    }
    
    echo "Database update completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
