<?php
// Database setup script
require_once 'backend/db_connect.php';

echo "<h1>Database Setup</h1>";

try {
    // Read and execute the SQL file
    $sqlFile = 'database_setup.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    if ($conn->query($statement)) {
                        echo "✅ Executed: " . substr($statement, 0, 50) . "...<br>";
                        $successCount++;
                    } else {
                        echo "❌ Error executing: " . $conn->error . "<br>";
                        $errorCount++;
                    }
                } catch (Exception $e) {
                    echo "❌ Exception: " . $e->getMessage() . "<br>";
                    $errorCount++;
                }
            }
        }
        
        echo "<h2>Setup Complete</h2>";
        echo "✅ Successful statements: $successCount<br>";
        echo "❌ Failed statements: $errorCount<br>";
        
        if ($errorCount == 0) {
            echo "<p style='color: green; font-weight: bold;'>Database setup completed successfully!</p>";
        } else {
            echo "<p style='color: orange; font-weight: bold;'>Database setup completed with some errors.</p>";
        }
        
    } else {
        echo "❌ SQL file not found: $sqlFile<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Setup error: " . $e->getMessage() . "<br>";
}

// Test the tables
echo "<h2>Testing Tables</h2>";
$tables = ['applications', 'transactions', 'admins'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "✅ Table '$table' exists<br>";
        
        // Show table structure
        $structure = $conn->query("DESCRIBE $table");
        if ($structure) {
            echo "<details><summary>Table structure for $table</summary><ul>";
            while ($row = $structure->fetch_assoc()) {
                echo "<li>{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Default']}</li>";
            }
            echo "</ul></details>";
        }
    } else {
        echo "❌ Table '$table' missing<br>";
    }
}

$conn->close();
?>
