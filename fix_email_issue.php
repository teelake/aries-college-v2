<?php
/**
 * Quick Fix for Email Issue
 * This file provides alternative email solutions
 */

echo "<h1>Email Issue Fix - Aries College</h1>";

echo "<h2>🔍 Problem Identified:</h2>";
echo "<p>The SMTP server <code>mail.achtech.org.ng</code> is configured to only send emails within the same domain or has restrictions on external email delivery.</p>";

echo "<h2>💡 Solutions:</h2>";

echo "<h3>Solution 1: Use Gmail SMTP (Recommended)</h3>";
echo "<p>This is the most reliable solution for sending emails to external domains.</p>";

echo "<h4>Steps to implement Gmail SMTP:</h4>";
echo "<ol>";
echo "<li>Create a Gmail account (or use existing)</li>";
echo "<li>Enable 2-Factor Authentication</li>";
echo "<li>Generate an App Password</li>";
echo "<li>Update the email configuration</li>";
echo "</ol>";

echo "<h4>Code to replace in process_application.php:</h4>";
echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
echo htmlspecialchars('// Replace the SMTP configuration in process_application.php with:
$mail->Host = \'smtp.gmail.com\';
$mail->Port = 587;
$mail->SMTPSecure = PHPMailer\\PHPMailer\\PHPMailer::ENCRYPTION_STARTTLS;
$mail->Username = \'your-gmail@gmail.com\'; // Your Gmail address
$mail->Password = \'your-app-password\'; // Gmail App Password

// Keep the sender as your domain email
$mail->setFrom(\'no-reply@achtech.org.ng\', \'Aries College\');
$mail->addAddress($email, $fullName); // This will now work!');
echo "</pre>";

echo "<h3>Solution 2: Contact Hosting Provider</h3>";
echo "<p>Ask your hosting provider to:</p>";
echo "<ul>";
echo "<li>Allow sending emails to external domains</li>";
echo "<li>Remove restrictions on the SMTP server</li>";
echo "<li>Configure proper mail routing</li>";
echo "</ul>";

echo "<h3>Solution 3: Use Alternative SMTP Service</h3>";
echo "<p>Consider using:</p>";
echo "<ul>";
echo "<li><strong>SendGrid:</strong> Free tier with 100 emails/day</li>";
echo "<li><strong>Mailgun:</strong> Free tier with 5,000 emails/month</li>";
echo "<li><strong>Amazon SES:</strong> Pay per use, very reliable</li>";
echo "</ul>";

echo "<h2>🚀 Quick Implementation (Gmail SMTP):</h2>";

echo "<h4>1. Gmail Setup:</h4>";
echo "<ol>";
echo "<li>Go to <a href='https://myaccount.google.com/security' target='_blank'>Google Account Security</a></li>";
echo "<li>Enable 2-Step Verification</li>";
echo "<li>Go to App Passwords</li>";
echo "<li>Generate password for 'Mail'</li>";
echo "<li>Copy the 16-character password</li>";
echo "</ol>";

echo "<h4>2. Update Code:</h4>";
echo "<p>Replace the SMTP configuration in <code>process_application.php</code> around line 198-209:</p>";

echo "<pre style='background: #e8f5e8; padding: 15px; border-radius: 5px; border-left: 4px solid #4caf50;'>";
echo htmlspecialchars('$mail->isSMTP();
$mail->Host = \'smtp.gmail.com\';
$mail->SMTPAuth = true;
$mail->Username = \'your-gmail@gmail.com\'; // Replace with your Gmail
$mail->Password = \'your-app-password\'; // Replace with App Password
$mail->SMTPSecure = PHPMailer\\PHPMailer\\PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
$mail->SMTPDebug = 0; // Set to 2 for debugging

$mail->setFrom(\'no-reply@achtech.org.ng\', \'Aries College\');
$mail->addAddress($email, $fullName);');
echo "</pre>";

echo "<h4>3. Test:</h4>";
echo "<p>After updating the code:</p>";
echo "<ol>";
echo "<li>Submit a test application</li>";
echo "<li>Check if email is received</li>";
echo "<li>Check spam folder if not received</li>";
echo "</ol>";

echo "<h2>🔧 Alternative: Use PHP Mail Function</h2>";
echo "<p>If SMTP continues to fail, you can use PHP's built-in mail function:</p>";

echo "<pre style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
echo htmlspecialchars('// Simple fallback using PHP mail function
$headers = "From: Aries College <no-reply@achtech.org.ng>\\r\\n";
$headers .= "Reply-To: no-reply@achtech.org.ng\\r\\n";
$headers .= "Content-Type: text/html; charset=UTF-8\\r\\n";

$subject = "Application Received - Complete Your Payment - Aries College";
$message = "Your HTML email content here...";

if (mail($email, $subject, $message, $headers)) {
    echo "Email sent successfully";
} else {
    echo "Email failed";
}');
echo "</pre>";

echo "<h2>📞 Need Help?</h2>";
echo "<p>If you need assistance implementing any of these solutions, I can help you:</p>";
echo "<ul>";
echo "<li>Set up Gmail SMTP</li>";
echo "<li>Configure alternative email services</li>";
echo "<li>Implement PHP mail function fallback</li>";
echo "<li>Debug email delivery issues</li>";
echo "</ul>";

echo "<p><strong>Recommended next step:</strong> Try the Gmail SMTP solution first as it's the most reliable for external email delivery.</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h1, h2, h3 { color: #2563eb; }
code { background: #f1f1f1; padding: 2px 6px; border-radius: 3px; }
pre { overflow-x: auto; }
ol, ul { padding-left: 20px; }
a { color: #007cba; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
