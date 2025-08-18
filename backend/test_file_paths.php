<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

echo "<h1>Test File Paths in Backend</h1>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    echo "<h2>Current Directory Structure</h2>";
    echo "<p>Current script location: " . __FILE__ . "</p>";
    echo "<p>Current working directory: " . getcwd() . "</p>";
    
    echo "<h2>Test Uploads Directory Access</h2>";
    
    // Test if we can access the uploads directory
    $uploadsPath = '../uploads/';
    if (is_dir($uploadsPath)) {
        echo "<p>✅ Uploads directory exists: $uploadsPath</p>";
        
        // List contents of uploads directory
        $uploadsContents = scandir($uploadsPath);
        echo "<p>Uploads directory contents:</p>";
        echo "<ul>";
        foreach ($uploadsContents as $item) {
            if ($item != '.' && $item != '..') {
                echo "<li>$item</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p>❌ Uploads directory not found: $uploadsPath</p>";
    }
    
    echo "<h2>Test File Paths from Database</h2>";
    
    // Get a sample application with file paths
    $result = $conn->query("SELECT id, full_name, photo_path, certificate_path FROM applications WHERE photo_path IS NOT NULL OR certificate_path IS NOT NULL LIMIT 3");
    
    if ($result && $result->num_rows > 0) {
        echo "<p>Sample applications with file paths:</p>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<h3>Application ID: " . $row['id'] . " - " . htmlspecialchars($row['full_name']) . "</h3>";
            
            if ($row['photo_path']) {
                $photoPath = '../' . $row['photo_path'];
                echo "<p>Photo Path (Database): " . htmlspecialchars($row['photo_path']) . "</p>";
                echo "<p>Photo Path (Corrected): " . htmlspecialchars($photoPath) . "</p>";
                
                if (file_exists($photoPath)) {
                    echo "<p>✅ Photo file exists</p>";
                    echo "<img src='" . htmlspecialchars($photoPath) . "' alt='Test Photo' style='max-width: 200px; height: auto; border: 1px solid #ccc;'>";
                } else {
                    echo "<p>❌ Photo file not found</p>";
                }
            }
            
            if ($row['certificate_path']) {
                $certPath = '../' . $row['certificate_path'];
                echo "<p>Certificate Path (Database): " . htmlspecialchars($row['certificate_path']) . "</p>";
                echo "<p>Certificate Path (Corrected): " . htmlspecialchars($certPath) . "</p>";
                
                if (file_exists($certPath)) {
                    echo "<p>✅ Certificate file exists</p>";
                    echo "<p><a href='" . htmlspecialchars($certPath) . "' target='_blank'>View Certificate</a></p>";
                } else {
                    echo "<p>❌ Certificate file not found</p>";
                }
            }
            
            echo "<hr>";
        }
    } else {
        echo "<p>No applications with file paths found in database.</p>";
    }
    
    echo "<h2>Path Resolution Test</h2>";
    
    // Test different path combinations
    $testPaths = [
        'uploads/passports/test.jpg' => '../uploads/passports/test.jpg',
        'uploads/certificates/test.pdf' => '../uploads/certificates/test.pdf',
        '../uploads/passports/test.jpg' => '../uploads/passports/test.jpg',
        '../uploads/certificates/test.pdf' => '../uploads/certificates/test.pdf'
    ];
    
    foreach ($testPaths as $originalPath => $correctedPath) {
        echo "<p>Original: $originalPath</p>";
        echo "<p>Corrected: $correctedPath</p>";
        
        if (file_exists($correctedPath)) {
            echo "<p>✅ File exists</p>";
        } else {
            echo "<p>❌ File not found</p>";
        }
        echo "<br>";
    }
    
    $conn->close();
    
    echo "<h2>Test Summary</h2>";
    echo "<p>✅ File path test completed!</p>";
    echo "<p>The corrected paths should now work correctly in the backend folder.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
img { margin: 10px 0; }
a { color: #3b82f6; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
