<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'backend/db_connect.php';

echo "<h1>Transactions Table Structure Check</h1>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Check current table structure
    echo "<h2>Current Transactions Table Structure</h2>";
    $result = $conn->query("DESCRIBE transactions");
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
        echo "</tr>";
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
    
    // Check for duplicate reference columns
    echo "<h2>Reference Column Analysis</h2>";
    $result = $conn->query("SHOW COLUMNS FROM transactions LIKE '%reference%'");
    $referenceColumns = [];
    while ($row = $result->fetch_assoc()) {
        $referenceColumns[] = $row['Field'];
    }
    
    echo "<p><strong>Reference-related columns found:</strong></p>";
    echo "<ul>";
    foreach ($referenceColumns as $column) {
        echo "<li>$column</li>";
    }
    echo "</ul>";
    
    if (count($referenceColumns) > 2) {
        echo "<p style='color: orange;'>⚠️ <strong>Warning:</strong> Multiple reference columns detected. This may cause confusion.</p>";
    }
    
    // Check sample data
    echo "<h2>Sample Transaction Data</h2>";
    $result = $conn->query("SELECT * FROM transactions ORDER BY created_at DESC LIMIT 3");
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        $first = true;
        while ($row = $result->fetch_assoc()) {
            if ($first) {
                foreach ($row as $key => $value) {
                    echo "<th>$key</th>";
                }
                echo "</tr>";
                $first = false;
            }
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No transaction data found.</p>";
    }
    
    // Check foreign key constraints
    echo "<h2>Foreign Key Constraints</h2>";
    $result = $conn->query("
        SELECT 
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = '" . DB_NAME . "'
        AND TABLE_NAME = 'transactions'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Constraint Name</th><th>Column</th><th>Referenced Table</th><th>Referenced Column</th>";
        echo "</tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['CONSTRAINT_NAME'] . "</td>";
            echo "<td>" . $row['COLUMN_NAME'] . "</td>";
            echo "<td>" . $row['REFERENCED_TABLE_NAME'] . "</td>";
            echo "<td>" . $row['REFERENCED_COLUMN_NAME'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No foreign key constraints found.</p>";
    }
    
    // Test inserting a sample transaction
    echo "<h2>Test Transaction Insert</h2>";
    try {
        // First create a test application
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
            $testApplicationId = $conn->insert_id;
            $testStmt->close();
            
            echo "<p>✅ Test application created with ID: $testApplicationId</p>";
            
            // Test inserting transaction with different reference columns
            $testReference = 'TEST_' . time();
            $gatewayReference = 'FLW_' . time();
            $amount = 10230;
            $currency = 'NGN';
            $paymentMethod = 'ussd';
            $status = 'pending';
            
            // Try different insert statements based on available columns
            $insertQueries = [
                // Query 1: Using 'reference' column
                "INSERT INTO transactions (application_id, amount, currency, reference, status, payment_gateway, created_at) VALUES (?, ?, ?, ?, ?, 'flutterwave', NOW())",
                
                // Query 2: Using 'payment_reference' column
                "INSERT INTO transactions (application_id, payment_reference, amount, currency, status, payment_gateway, created_at) VALUES (?, ?, ?, ?, ?, 'flutterwave', NOW())",
                
                // Query 3: Using both reference columns
                "INSERT INTO transactions (application_id, payment_reference, gateway_reference, payment_gateway, amount, currency, payment_method, reference, status, created_at) VALUES (?, ?, ?, 'flutterwave', ?, ?, ?, ?, ?, NOW())"
            ];
            
            foreach ($insertQueries as $index => $query) {
                echo "<h4>Testing Insert Query " . ($index + 1) . "</h4>";
                echo "<p><strong>Query:</strong> $query</p>";
                
                try {
                    $stmt = $conn->prepare($query);
                    
                    if ($index == 0) {
                        // Query 1: reference column
                        $stmt->bind_param("idsss", $testApplicationId, $amount, $currency, $testReference, $status);
                    } elseif ($index == 1) {
                        // Query 2: payment_reference column
                        $stmt->bind_param("isds", $testApplicationId, $testReference, $amount, $currency, $status);
                    } else {
                        // Query 3: both reference columns
                        $stmt->bind_param("isssdsss", $testApplicationId, $testReference, $gatewayReference, $amount, $currency, $paymentMethod, $testReference, $status);
                    }
                    
                    if ($stmt->execute()) {
                        $transactionId = $conn->insert_id;
                        echo "<p style='color: green;'>✅ Transaction inserted successfully with ID: $transactionId</p>";
                        
                        // Show the inserted data
                        $result = $conn->query("SELECT * FROM transactions WHERE id = $transactionId");
                        if ($result && $row = $result->fetch_assoc()) {
                            echo "<p><strong>Inserted Data:</strong></p>";
                            echo "<pre>" . print_r($row, true) . "</pre>";
                        }
                        
                        // Clean up
                        $conn->query("DELETE FROM transactions WHERE id = $transactionId");
                        echo "<p>✅ Test transaction cleaned up</p>";
                        
                    } else {
                        echo "<p style='color: red;'>❌ Insert failed: " . $conn->error . "</p>";
                    }
                    
                    $stmt->close();
                    
                } catch (Exception $e) {
                    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
                }
            }
            
            // Clean up test application
            $conn->query("DELETE FROM applications WHERE id = $testApplicationId");
            echo "<p>✅ Test application cleaned up</p>";
            
        } else {
            echo "<p style='color: red;'>❌ Failed to create test application: " . $conn->error . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Test error: " . $e->getMessage() . "</p>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>Analysis Complete</h2>";
echo "<p>Check the output above to identify any issues with the transactions table structure.</p>";
?>
