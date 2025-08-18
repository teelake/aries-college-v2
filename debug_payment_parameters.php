<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log all incoming parameters from Flutterwave
$logFile = 'payment_parameters.log';
$timestamp = date('Y-m-d H:i:s');
$parameters = [
    'timestamp' => $timestamp,
    'method' => $_SERVER['REQUEST_METHOD'],
    'url' => $_SERVER['REQUEST_URI'],
    'get_params' => $_GET,
    'post_params' => $_POST,
    'server_params' => [
        'HTTP_REFERER' => $_SERVER['HTTP_REFERER'] ?? 'N/A',
        'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
    ]
];

$logEntry = json_encode($parameters, JSON_PRETTY_PRINT) . "\n" . str_repeat('-', 80) . "\n";
file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

// Also display the parameters on screen for immediate debugging
echo "<h1>Payment Parameters Debug</h1>";
echo "<p><strong>Timestamp:</strong> $timestamp</p>";
echo "<p><strong>Request Method:</strong> " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p><strong>URL:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";

echo "<h2>GET Parameters</h2>";
if (!empty($_GET)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Parameter</th><th>Value</th></tr>";
    foreach ($_GET as $key => $value) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($key) . "</td>";
        echo "<td>" . htmlspecialchars($value) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No GET parameters</p>";
}

echo "<h2>POST Parameters</h2>";
if (!empty($_POST)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Parameter</th><th>Value</th></tr>";
    foreach ($_POST as $key => $value) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($key) . "</td>";
        echo "<td>" . htmlspecialchars($value) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No POST parameters</p>";
}

echo "<h2>Server Information</h2>";
echo "<p><strong>Referer:</strong> " . ($_SERVER['HTTP_REFERER'] ?? 'N/A') . "</p>";
echo "<p><strong>User Agent:</strong> " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . "</p>";
echo "<p><strong>Remote IP:</strong> " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "</p>";

echo "<h2>Analysis</h2>";

// Analyze the parameters
$status = $_GET['status'] ?? $_POST['status'] ?? 'N/A';
$txRef = $_GET['tx_ref'] ?? $_POST['tx_ref'] ?? 'N/A';
$transactionId = $_GET['transaction_id'] ?? $_POST['transaction_id'] ?? 'N/A';
$paymentType = $_GET['payment_type'] ?? $_POST['payment_type'] ?? 'N/A';

echo "<p><strong>Status:</strong> $status</p>";
echo "<p><strong>Transaction Reference:</strong> $txRef</p>";
echo "<p><strong>Transaction ID:</strong> $transactionId</p>";
echo "<p><strong>Payment Type:</strong> $paymentType</p>";

// Check if this looks like a successful payment
$successIndicators = ['successful', 'success', 'completed', 'paid', 'approved'];
$isSuccessful = false;
$successReason = '';

if (in_array(strtolower($status), $successIndicators)) {
    $isSuccessful = true;
    $successReason = "Status is '$status' which indicates success";
} elseif ($status === 'cancelled' || $status === 'failed') {
    $isSuccessful = false;
    $successReason = "Status is '$status' which indicates failure";
} else {
    $successReason = "Status '$status' is unclear - needs investigation";
}

echo "<p><strong>Payment Success Analysis:</strong> " . ($isSuccessful ? 'YES' : 'NO') . "</p>";
echo "<p><strong>Reason:</strong> $successReason</p>";

echo "<h2>Recommendations</h2>";
if ($isSuccessful) {
    echo "<p style='color: green;'>✅ This appears to be a successful payment. The system should process it as successful.</p>";
} else {
    echo "<p style='color: red;'>❌ This appears to be a failed payment or unclear status.</p>";
}

echo "<p><strong>Log File:</strong> $logFile</p>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>Check the log file for detailed parameter history</li>";
echo "<li>Test with different payment methods to see all possible status values</li>";
echo "<li>Update the payment success logic based on these findings</li>";
echo "</ul>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
