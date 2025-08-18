<?php
require_once 'backend/db_connect.php';

echo "<h1>Database Table Structure Check</h1>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Check applications table
    echo "<h2>Applications Table</h2>";
    $result = $conn->query("DESCRIBE applications");
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check transactions table
    echo "<h2>Transactions Table</h2>";
    $result = $conn->query("DESCRIBE transactions");
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check foreign key constraints
    echo "<h2>Foreign Key Constraints</h2>";
    $result = $conn->query("
        SELECT 
            CONSTRAINT_NAME,
            TABLE_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_SCHEMA = '" . DB_NAME . "'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Constraint Name</th><th>Table</th><th>Column</th><th>Referenced Table</th><th>Referenced Column</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['CONSTRAINT_NAME'] . "</td>";
            echo "<td>" . $row['TABLE_NAME'] . "</td>";
            echo "<td>" . $row['COLUMN_NAME'] . "</td>";
            echo "<td>" . $row['REFERENCED_TABLE_NAME'] . "</td>";
            echo "<td>" . $row['REFERENCED_COLUMN_NAME'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No foreign key constraints found.</p>";
    }
    
    // Check if there are any existing applications
    echo "<h2>Existing Data</h2>";
    $result = $conn->query("SELECT COUNT(*) as count FROM applications");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>Applications: " . $row['count'] . "</p>";
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM transactions");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>Transactions: " . $row['count'] . "</p>";
    }
    
    // Test inserting a sample application
    echo "<h2>Test Insert</h2>";
    try {
        $testStmt = $conn->prepare("INSERT INTO applications (full_name, email, phone, date_of_birth, gender, address, state, lga, last_school, qualification, year_completed, program_applied, photo_path, certificate_path, payment_status, application_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending_payment', NOW())");
        
        $testName = 'Test User';
        $testEmail = 'test@example.com';
        $testPhone = '08012345678';
        $testDob = '1990-01-01';
        $testGender = 'Male';
        $testAddress = 'Test Address';
        $testState = 'Lagos';
        $testLga = 'Ikeja';
        $testSchool = 'Test School';
        $testQual = 'SSCE';
        $testYear = '2010-01-01';
        $testCourse = 'Medical Laboratory Technician';
        $testPhoto = 'uploads/passports/test_photo.jpg';
        $testCert = 'uploads/certificates/test_cert.pdf';
        
        $testStmt->bind_param("ssssssssssssss", $testName, $testEmail, $testPhone, $testDob, $testGender, $testAddress, $testState, $testLga, $testSchool, $testQual, $testYear, $testCourse, $testPhoto, $testCert);
        
        if ($testStmt->execute()) {
            $testId = $conn->insert_id;
            echo "<p style='color: green;'>✅ Test application inserted successfully with ID: $testId</p>";
            
            // Test inserting a transaction
            $transStmt = $conn->prepare("INSERT INTO transactions (application_id, amount, currency, reference, status, payment_gateway, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $amount = 10230;
            $currency = 'NGN';
            $reference = 'TEST_' . time();
            $status = 'pending';
            $gateway = 'flutterwave';
            
            $transStmt->bind_param("idssss", $testId, $amount, $currency, $reference, $status, $gateway);
            
            if ($transStmt->execute()) {
                echo "<p style='color: green;'>✅ Test transaction inserted successfully</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to insert transaction: " . $conn->error . "</p>";
            }
            
            // Clean up
            $conn->query("DELETE FROM transactions WHERE application_id = $testId");
            $conn->query("DELETE FROM applications WHERE id = $testId");
            echo "<p style='color: green;'>✅ Test data cleaned up</p>";
            
        } else {
            echo "<p style='color: red;'>❌ Failed to insert test application: " . $conn->error . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Test insert error: " . $e->getMessage() . "</p>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
