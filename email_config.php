<?php
/**
 * Email Configuration for Aries College
 * Update these settings if email is not working
 */

// SMTP Configuration
define('SMTP_HOST', 'mail.achtech.org.ng');
define('SMTP_USERNAME', 'no-reply@achtech.org.ng');
define('SMTP_PASSWORD', 'Temp_pass123');
define('SMTP_PORT', 465);
define('SMTP_ENCRYPTION', 'ssl'); // ssl or tls

// Email Settings
define('FROM_EMAIL', 'no-reply@achtech.org.ng');
define('FROM_NAME', 'Aries College');

// Alternative SMTP Settings (uncomment to use)
/*
// Gmail SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'your-gmail@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');

// Outlook/Hotmail SMTP
define('SMTP_HOST', 'smtp-mail.outlook.com');
define('SMTP_USERNAME', 'your-email@outlook.com');
define('SMTP_PASSWORD', 'your-password');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');
*/

/**
 * Get SMTP configuration array
 */
function getSMTPConfig() {
    return [
        'host' => SMTP_HOST,
        'username' => SMTP_USERNAME,
        'password' => SMTP_PASSWORD,
        'port' => SMTP_PORT,
        'encryption' => SMTP_ENCRYPTION,
        'from_email' => FROM_EMAIL,
        'from_name' => FROM_NAME
    ];
}

/**
 * Test email configuration
 */
function testEmailConfig() {
    $config = getSMTPConfig();
    echo "<h2>Current Email Configuration:</h2>";
    echo "<p><strong>SMTP Host:</strong> " . $config['host'] . "</p>";
    echo "<p><strong>SMTP Port:</strong> " . $config['port'] . "</p>";
    echo "<p><strong>SMTP Encryption:</strong> " . $config['encryption'] . "</p>";
    echo "<p><strong>From Email:</strong> " . $config['from_email'] . "</p>";
    echo "<p><strong>From Name:</strong> " . $config['from_name'] . "</p>";
}
?>
