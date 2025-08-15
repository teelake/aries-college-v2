<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Form Submission Test</h1>";

// Simulate form data
$formData = [
    'full_name' => 'Test User',
    'email' => 'test@example.com',
    'phone' => '08012345678',
    'date_of_birth' => '1990-01-01',
    'gender' => 'Male',
    'address' => 'Test Address',
    'state' => 'Lagos',
    'lga' => 'Ikeja',
    'last_school' => 'Test School',
    'qualification' => 'SSCE',
    'year_completed' => '2010-01-01',
    'program_applied' => 'Medical Laboratory Science'
];

echo "<h2>Testing Form Submission</h2>";
echo "<p>Simulating form submission with test data...</p>";

// Create a temporary test file for photo
$testPhotoPath = 'uploads/passports/test_photo_' . time() . '.jpg';
$testCertPath = 'uploads/certificates/test_cert_' . time() . '.pdf';

// Create test directories if they don't exist
if (!is_dir('uploads/passports/')) {
    mkdir('uploads/passports/', 0755, true);
}
if (!is_dir('uploads/certificates/')) {
    mkdir('uploads/certificates/', 0755, true);
}

// Create dummy files
file_put_contents($testPhotoPath, 'dummy photo content');
file_put_contents($testCertPath, 'dummy certificate content');

echo "<p>✅ Test files created</p>";

// Simulate the form submission process
try {
    // Include the processing file
    ob_start();
    
    // Set POST data
    $_POST = $formData;
    $_POST['photo_path'] = $testPhotoPath;
    $_POST['certificate_path'] = $testCertPath;
    
    // Include the processing file
    include 'process_application.php';
    
    $output = ob_get_clean();
    
    echo "<h3>Response:</h3>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    // Try to decode JSON response
    $jsonResponse = json_decode($output, true);
    if ($jsonResponse) {
        echo "<h3>Decoded Response:</h3>";
        echo "<pre>" . print_r($jsonResponse, true) . "</pre>";
        
        if (isset($jsonResponse['success']) && $jsonResponse['success']) {
            echo "<p style='color: green; font-weight: bold;'>✅ Form submission successful!</p>";
            if (isset($jsonResponse['data']['payment_url'])) {
                echo "<p><strong>Payment URL:</strong> <a href='" . $jsonResponse['data']['payment_url'] . "' target='_blank'>" . $jsonResponse['data']['payment_url'] . "</a></p>";
            }
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ Form submission failed: " . ($jsonResponse['message'] ?? 'Unknown error') . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// Clean up test files
if (file_exists($testPhotoPath)) {
    unlink($testPhotoPath);
}
if (file_exists($testCertPath)) {
    unlink($testCertPath);
}

echo "<p>✅ Test files cleaned up</p>";

echo "<h2>Test Complete</h2>";
echo "<p>If the form submission test above shows success, then your application form should work correctly.</p>";
?>
