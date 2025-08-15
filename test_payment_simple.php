<?php
// Simple payment test without database
require_once 'payment_config.php';

echo "<h1>Simple Payment Test</h1>";

// Test 1: Check if we can make a direct Flutterwave API call
echo "<h2>Test 1: Direct Flutterwave API Call</h2>";

$testUrl = 'https://api.flutterwave.com/v3/payments';
$testData = [
    'tx_ref' => 'TEST_' . time(),
    'amount' => 10230,
    'currency' => 'NGN',
    'redirect_url' => 'https://achtech.org.ng/payment_success.php',
    'customer' => [
        'email' => 'test@example.com'
    ],
    'meta' => [
        'application_id' => 999
    ],
    'customizations' => [
        'title' => 'Aries College Application Fee',
        'description' => 'Application fee payment for Aries College of Health Management & Technology',
        'logo' => 'https://achtech.org.ng/img/logo.png'
    ]
];

$headers = [
    'Authorization: Bearer ' . FLUTTERWAVE_SECRET_KEY,
    'Content-Type: application/json'
];

echo "<p><strong>Request URL:</strong> $testUrl</p>";
echo "<p><strong>Request Data:</strong> " . json_encode($testData, JSON_PRETTY_PRINT) . "</p>";
echo "<p><strong>Secret Key:</strong> " . substr(FLUTTERWAVE_SECRET_KEY, 0, 20) . "...</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<p style='color: red;'>❌ cURL Error: $error</p>";
} else {
    echo "<p style='color: green;'>✅ cURL request successful</p>";
    echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $decodedResponse = json_decode($response, true);
    if ($decodedResponse) {
        echo "<p><strong>Decoded Response:</strong></p>";
        echo "<pre>" . json_encode($decodedResponse, JSON_PRETTY_PRINT) . "</pre>";
        
        if (isset($decodedResponse['status']) && $decodedResponse['status'] === 'success') {
            echo "<p style='color: green; font-weight: bold;'>✅ Flutterwave API call successful!</p>";
            if (isset($decodedResponse['data']['link'])) {
                echo "<p><strong>Payment URL:</strong> <a href='" . $decodedResponse['data']['link'] . "' target='_blank'>" . $decodedResponse['data']['link'] . "</a></p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Flutterwave API call failed</p>";
            if (isset($decodedResponse['message'])) {
                echo "<p><strong>Error:</strong> " . $decodedResponse['message'] . "</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ Invalid JSON response</p>";
    }
}

// Test 2: Check if we can create a PaymentProcessor without database
echo "<h2>Test 2: PaymentProcessor without Database</h2>";

try {
    // Temporarily modify the PaymentProcessor to skip database operations
    class TestPaymentProcessor {
        private $gateway;
        
        public function __construct($gateway = null) {
            $this->gateway = $gateway ?: PAYMENT_GATEWAY;
        }
        
        public function initializePayment($applicationId, $email, $amount = null) {
            $amount = $amount ?: APPLICATION_FEE;
            $reference = 'ACH_' . time() . '_' . $applicationId;
            
            // Skip database operations for testing
            echo "<p>✅ Skipped database operations</p>";
            
            // Initialize payment with selected gateway
            switch ($this->gateway) {
                case 'flutterwave':
                    return $this->initializeFlutterwavePayment($reference, $email, $amount, $applicationId);
                default:
                    throw new Exception('Unsupported payment gateway');
            }
        }
        
        private function initializeFlutterwavePayment($reference, $email, $amount, $applicationId) {
            $url = FLUTTERWAVE_BASE_URL . '/payments';
            
            $data = [
                'tx_ref' => $reference,
                'amount' => $amount,
                'currency' => CURRENCY,
                'redirect_url' => PAYMENT_SUCCESS_URL,
                'customer' => [
                    'email' => $email
                ],
                'meta' => [
                    'application_id' => $applicationId
                ],
                'customizations' => [
                    'title' => 'Aries College Application Fee',
                    'description' => 'Application fee payment for Aries College of Health Management & Technology',
                    'logo' => 'https://achtech.org.ng/img/logo.png'
                ]
            ];
            
            $headers = [
                'Authorization: Bearer ' . FLUTTERWAVE_SECRET_KEY,
                'Content-Type: application/json'
            ];
            
            $response = $this->makeHttpRequest($url, 'POST', $data, $headers);
            
            if ($response['status'] === 'success' && isset($response['data']['link'])) {
                return [
                    'success' => true,
                    'authorization_url' => $response['data']['link'],
                    'reference' => $reference
                ];
            } else {
                throw new Exception('Flutterwave payment initialization failed: ' . ($response['message'] ?? 'Unknown error'));
            }
        }
        
        private function makeHttpRequest($url, $method = 'GET', $data = null, $headers = []) {
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            if ($method === 'POST' && $data) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new Exception('cURL error: ' . $error);
            }
            
            if ($httpCode !== 200) {
                throw new Exception('HTTP error: ' . $httpCode . ' - Response: ' . $response);
            }
            
            $decodedResponse = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON response: ' . json_last_error_msg());
            }
            
            return $decodedResponse;
        }
    }
    
    $testProcessor = new TestPaymentProcessor();
    $result = $testProcessor->initializePayment(999, 'test@example.com', 10230);
    
    echo "<p style='color: green;'>✅ Test PaymentProcessor successful!</p>";
    echo "<p><strong>Reference:</strong> " . $result['reference'] . "</p>";
    echo "<p><strong>Payment URL:</strong> <a href='" . $result['authorization_url'] . "' target='_blank'>" . $result['authorization_url'] . "</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Test PaymentProcessor failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Test Complete</h2>";
echo "<p>This test shows if the Flutterwave integration works without database dependencies.</p>";
?>
