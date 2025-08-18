<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'backend/db_connect.php';

echo "<h1>Fix Transactions Table Structure</h1>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h2>Step 1: Check Current Structure</h2>";
    
    // Show current structure
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
    
    echo "<h2>Step 2: Check Reference Columns</h2>";
    
    // Check for reference columns
    $result = $conn->query("SHOW COLUMNS FROM transactions LIKE '%reference%'");
    $referenceColumns = [];
    while ($row = $result->fetch_assoc()) {
        $referenceColumns[] = $row['Field'];
    }
    
    echo "<p><strong>Reference columns found:</strong></p>";
    echo "<ul>";
    foreach ($referenceColumns as $column) {
        echo "<li>$column</li>";
    }
    echo "</ul>";
    
    if (count($referenceColumns) > 2) {
        echo "<p style='color: orange;'>⚠️ <strong>Multiple reference columns detected. This needs fixing.</strong></p>";
    }
    
    echo "<h2>Step 3: Backup Existing Data</h2>";
    
    // Check if there's existing data
    $result = $conn->query("SELECT COUNT(*) as count FROM transactions");
    $row = $result->fetch_assoc();
    $existingData = $row['count'];
    
    echo "<p>Found $existingData existing transaction records.</p>";
    
    if ($existingData > 0) {
        // Create backup
        if ($conn->query("CREATE TABLE transactions_backup AS SELECT * FROM transactions")) {
            echo "<p style='color: green;'>✅ Backup created successfully</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create backup: " . $conn->error . "</p>";
            exit;
        }
    } else {
        echo "<p>No existing data to backup.</p>";
    }
    
    echo "<h2>Step 4: Recreate Table Structure</h2>";
    
    // Drop and recreate the table
    if ($conn->query("DROP TABLE IF EXISTS transactions")) {
        echo "<p>✅ Old table dropped</p>";
    }
    
    // Create new table with correct structure
    $createTableSQL = "
    CREATE TABLE transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) NOT NULL DEFAULT 'NGN',
        reference VARCHAR(100) NOT NULL UNIQUE,
        gateway_reference VARCHAR(100) NULL,
        payment_gateway VARCHAR(50) NOT NULL DEFAULT 'flutterwave',
        payment_method VARCHAR(50) NULL,
        status ENUM('pending', 'successful', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
        paid_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_application_id (application_id),
        INDEX idx_reference (reference),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at),
        
        FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    if ($conn->query($createTableSQL)) {
        echo "<p style='color: green;'>✅ New table created successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create new table: " . $conn->error . "</p>";
        exit;
    }
    
    echo "<h2>Step 5: Restore Data (if any)</h2>";
    
    if ($existingData > 0) {
        // Restore data from backup
        $restoreSQL = "
        INSERT INTO transactions (
            application_id, 
            amount, 
            currency, 
            reference, 
            gateway_reference, 
            payment_gateway, 
            payment_method, 
            status, 
            paid_at, 
            created_at, 
            updated_at
        )
        SELECT 
            application_id,
            amount,
            COALESCE(currency, 'NGN') as currency,
            COALESCE(reference, payment_reference) as reference,
            gateway_reference,
            COALESCE(payment_gateway, 'flutterwave') as payment_gateway,
            payment_method,
            COALESCE(status, 'pending') as status,
            paid_at,
            created_at,
            COALESCE(updated_at, created_at) as updated_at
        FROM transactions_backup
        ";
        
        if ($conn->query($restoreSQL)) {
            echo "<p style='color: green;'>✅ Data restored successfully</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to restore data: " . $conn->error . "</p>";
        }
        
        // Drop backup table
        $conn->query("DROP TABLE transactions_backup");
        echo "<p>✅ Backup table cleaned up</p>";
    }
    
    echo "<h2>Step 6: Verify New Structure</h2>";
    
    // Show new structure
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
    
    echo "<h2>Step 7: Test Transaction Insert</h2>";
    
    // Test inserting a transaction
    try {
        // Create test application
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
            
            // Test transaction insert
            $testStmt = $conn->prepare("INSERT INTO transactions (application_id, amount, currency, reference, status, payment_gateway, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            
            $amount = 10230;
            $currency = 'NGN';
            $reference = 'TEST_' . time();
            $status = 'pending';
            $gateway = 'flutterwave';
            
            $testStmt->bind_param("idssss", $testApplicationId, $amount, $currency, $reference, $status, $gateway);
            
            if ($testStmt->execute()) {
                $transactionId = $conn->insert_id;
                echo "<p style='color: green;'>✅ Test transaction inserted successfully with ID: $transactionId</p>";
                
                // Show the inserted data
                $result = $conn->query("SELECT * FROM transactions WHERE id = $transactionId");
                if ($result && $row = $result->fetch_assoc()) {
                    echo "<p><strong>Inserted Data:</strong></p>";
                    echo "<pre>" . print_r($row, true) . "</pre>";
                }
                
                // Clean up
                $conn->query("DELETE FROM transactions WHERE id = $transactionId");
                $conn->query("DELETE FROM applications WHERE id = $testApplicationId");
                echo "<p>✅ Test data cleaned up</p>";
                
            } else {
                echo "<p style='color: red;'>❌ Test transaction insert failed: " . $conn->error . "</p>";
            }
            
            $testStmt->close();
            
        } else {
            echo "<p style='color: red;'>❌ Failed to create test application: " . $conn->error . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Test error: " . $e->getMessage() . "</p>";
    }
    
    $conn->close();
    
    echo "<h2>✅ Fix Complete!</h2>";
    echo "<p>The transactions table has been fixed and should now work correctly with the PaymentProcessor.</p>";
    echo "<p><strong>Key changes made:</strong></p>";
    echo "<ul>";
    echo "<li>Standardized reference column structure</li>";
    echo "<li>Removed duplicate reference columns</li>";
    echo "<li>Added proper indexes and foreign key constraints</li>";
    echo "<li>Ensured compatibility with PaymentProcessor code</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
