<?php
/**
 * Email Settings Configuration
 * Choose the email method that works best for your server
 */

// Email Configuration Options
$email_configs = [
    // Option 1: Gmail SMTP (Recommended - Most Reliable)
    'gmail' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'your-gmail@gmail.com', // Change this to your Gmail
        'password' => 'your-app-password', // Gmail App Password
        'from_email' => 'no-reply@achtech.org.ng',
        'from_name' => 'Aries College'
    ],
    
    // Option 2: Outlook/Hotmail SMTP
    'outlook' => [
        'host' => 'smtp-mail.outlook.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'your-email@outlook.com',
        'password' => 'your-password',
        'from_email' => 'no-reply@achtech.org.ng',
        'from_name' => 'Aries College'
    ],
    
    // Option 3: Custom SMTP (Your current settings)
    'custom' => [
        'host' => 'mail.achtech.org.ng',
        'port' => 465,
        'encryption' => 'ssl',
        'username' => 'no-reply@achtech.org.ng',
        'password' => 'Temp_pass123',
        'from_email' => 'no-reply@achtech.org.ng',
        'from_name' => 'Aries College'
    ],
    
    // Option 4: PHP Mail Function (Fallback)
    'php_mail' => [
        'method' => 'php_mail',
        'from_email' => 'no-reply@achtech.org.ng',
        'from_name' => 'Aries College'
    ]
];

// Current active configuration
// Change this to: 'gmail', 'outlook', 'custom', or 'php_mail'
$active_config = 'gmail'; // Change this to test different options

/**
 * Get current email configuration
 */
function getCurrentEmailConfig() {
    global $email_configs, $active_config;
    return $email_configs[$active_config];
}

/**
 * Test email configuration
 */
function testEmailConfig($config_name = null) {
    global $email_configs;
    
    if (!$config_name) {
        global $active_config;
        $config_name = $active_config;
    }
    
    $config = $email_configs[$config_name];
    
    echo "<h3>Testing: $config_name</h3>";
    echo "<p><strong>Host:</strong> " . ($config['host'] ?? 'N/A') . "</p>";
    echo "<p><strong>Port:</strong> " . ($config['port'] ?? 'N/A') . "</p>";
    echo "<p><strong>Encryption:</strong> " . ($config['encryption'] ?? 'N/A') . "</p>";
    echo "<p><strong>Username:</strong> " . ($config['username'] ?? 'N/A') . "</p>";
    
    if ($config['method'] === 'php_mail') {
        echo "<p><strong>Method:</strong> PHP mail() function</p>";
    }
    
    return $config;
}
?>

<!-- Email Configuration Test Page -->
<!DOCTYPE html>
<html>
<head>
    <title>Email Configuration Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .config-option { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .config-option.active { border-color: #007cba; background: #f0f8ff; }
        .config-option h3 { margin-top: 0; color: #007cba; }
        .test-button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .test-button:hover { background: #005a87; }
    </style>
</head>
<body>
    <h1>Email Configuration Test - Aries College</h1>
    
    <p><strong>Current Active Configuration:</strong> <?php echo $active_config; ?></p>
    
    <?php foreach ($email_configs as $name => $config): ?>
    <div class="config-option <?php echo $name === $active_config ? 'active' : ''; ?>">
        <h3><?php echo ucfirst($name); ?> Configuration</h3>
        <?php testEmailConfig($name); ?>
        <?php if ($name === $active_config): ?>
            <p style="color: green; font-weight: bold;">✓ Currently Active</p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    
    <h2>Instructions:</h2>
    <ol>
        <li><strong>Gmail Setup:</strong>
            <ul>
                <li>Create a Gmail account or use existing one</li>
                <li>Enable 2-factor authentication</li>
                <li>Generate an App Password</li>
                <li>Update the username and password in this file</li>
            </ul>
        </li>
        <li><strong>Outlook Setup:</strong>
            <ul>
                <li>Use your Outlook/Hotmail email and password</li>
                <li>Update the username and password in this file</li>
            </ul>
        </li>
        <li><strong>Custom SMTP:</strong>
            <ul>
                <li>Contact your hosting provider about external email restrictions</li>
                <li>Ask them to allow sending emails to external domains</li>
            </ul>
        </li>
        <li><strong>PHP Mail:</strong>
            <ul>
                <li>Uses server's built-in mail function</li>
                <li>May have deliverability issues</li>
                <li>Good as fallback option</li>
            </ul>
        </li>
    </ol>
    
    <h2>Recommended Solution:</h2>
    <p><strong>Use Gmail SMTP</strong> - It's the most reliable for sending emails to external domains like Gmail, Yahoo, Outlook, etc.</p>
    
    <h3>Quick Gmail Setup:</h3>
    <ol>
        <li>Create Gmail account: <code>your-email@gmail.com</code></li>
        <li>Go to Google Account Settings → Security</li>
        <li>Enable 2-Factor Authentication</li>
        <li>Generate App Password for "Mail"</li>
        <li>Update this file with your Gmail credentials</li>
        <li>Change <code>$active_config = 'gmail';</code></li>
    </ol>
</body>
</html>
