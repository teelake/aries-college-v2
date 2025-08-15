<?php
// Payment Gateway Configuration
// You can use either Paystack or Flutterwave

// Payment Gateway Selection (uncomment one)
define('PAYMENT_GATEWAY', 'flutterwave'); // Options: 'paystack', 'flutterwave'

// Paystack Configuration
define('PAYSTACK_SECRET_KEY', 'sk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'); // Replace with your Paystack secret key
define('PAYSTACK_PUBLIC_KEY', 'pk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'); // Replace with your Paystack public key
define('PAYSTACK_BASE_URL', 'https://api.paystack.co');

// Flutterwave Configuration
define('FLUTTERWAVE_SECRET_KEY', 'FLWSECK_TEST-ab26e105e6330a39d5a2a61a695db309-X'); // Your Flutterwave secret key
define('FLUTTERWAVE_PUBLIC_KEY', 'FLWPUBK_TEST-7a252dcf06f4d1bc6f605a582f1b85b1-X'); // Your Flutterwave public key
define('FLUTTERWAVE_ENCRYPTION_KEY', 'FLWSECK_TESTaf2b99a9e5a3'); // Your Flutterwave encryption key
define('FLUTTERWAVE_BASE_URL', 'https://api.flutterwave.com/v3');

// Application Fee Configuration
define('APPLICATION_FEE', 10000); // ₦10,000 in Naira
define('CURRENCY', 'NGN');

// Payment Callback URLs
define('PAYMENT_SUCCESS_URL', 'https://achtech.org.ng/payment_success.php');
define('PAYMENT_FAILURE_URL', 'https://achtech.org.ng/payment_failure.php');
define('PAYMENT_WEBHOOK_URL', 'https://achtech.org.ng/payment_webhook.php');

// Database table names
define('TRANSACTIONS_TABLE', 'transactions');
define('APPLICATIONS_TABLE', 'applications');

// Email configuration for payment notifications
define('PAYMENT_NOTIFICATION_EMAIL', 'finance@achtech.org.ng');
define('PAYMENT_NOTIFICATION_NAME', 'Aries College Finance');

// Payment status constants
define('PAYMENT_STATUS_PENDING', 'pending');
define('PAYMENT_STATUS_SUCCESS', 'success');
define('PAYMENT_STATUS_FAILED', 'failed');
define('PAYMENT_STATUS_CANCELLED', 'cancelled');

// Payment method constants
define('PAYMENT_METHOD_CARD', 'card');
define('PAYMENT_METHOD_BANK', 'bank');
define('PAYMENT_METHOD_USSD', 'ussd');
define('PAYMENT_METHOD_MOBILE_MONEY', 'mobile_money');
?>
