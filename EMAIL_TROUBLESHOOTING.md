# Email Troubleshooting Guide - Aries College

## 🔍 **Diagnosis Steps**

### Step 1: Run Email Test
1. Visit: `https://yourdomain.com/test_email.php`
2. Check all test results
3. Look for error messages

### Step 2: Check Server Logs
1. Check PHP error logs
2. Check web server error logs
3. Look for SMTP connection errors

### Step 3: Verify Email Configuration
Current settings in `process_application.php`:
- **Host**: mail.achtech.org.ng
- **Port**: 465
- **Encryption**: SSL
- **Username**: no-reply@achtech.org.ng
- **Password**: Temp_pass123

## 🛠️ **Common Issues & Solutions**

### Issue 1: SMTP Authentication Failed
**Symptoms**: "SMTP Error: Could not authenticate"
**Solutions**:
1. Verify username and password
2. Check if account is locked
3. Try different SMTP settings

### Issue 2: Connection Timeout
**Symptoms**: "Connection timed out"
**Solutions**:
1. Check if port 465 is blocked
2. Try port 587 with TLS
3. Check firewall settings

### Issue 3: SSL/TLS Issues
**Symptoms**: "SSL/TLS connection failed"
**Solutions**:
1. Try TLS instead of SSL
2. Check certificate validity
3. Disable SSL verification (temporary)

### Issue 4: Emails Going to Spam
**Symptoms**: Emails sent but not received
**Solutions**:
1. Check spam folder
2. Add SPF/DKIM records
3. Use a reputable email service

## 🔧 **Quick Fixes**

### Fix 1: Enable Debug Mode
In `process_application.php`, change:
```php
$mail->SMTPDebug = 2; // Enable verbose debug output
```

### Fix 2: Try Alternative SMTP
Replace SMTP settings in `process_application.php`:

**Gmail SMTP**:
```php
$mail->Host = 'smtp.gmail.com';
$mail->Port = 587;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Username = 'your-gmail@gmail.com';
$mail->Password = 'your-app-password';
```

**Outlook SMTP**:
```php
$mail->Host = 'smtp-mail.outlook.com';
$mail->Port = 587;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Username = 'your-email@outlook.com';
$mail->Password = 'your-password';
```

### Fix 3: Use PHP Mail Function (Fallback)
If SMTP fails, use PHP's built-in mail function:
```php
// Simple fallback
$headers = "From: no-reply@achtech.org.ng\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
mail($email, $subject, $htmlContent, $headers);
```

## 📧 **Alternative Email Services**

### 1. SendGrid
- Free tier: 100 emails/day
- Easy setup
- Good deliverability

### 2. Mailgun
- Free tier: 5,000 emails/month
- API-based
- Good for developers

### 3. Amazon SES
- Pay per use
- Very reliable
- Good for high volume

## 🔍 **Debug Commands**

### Check if SMTP port is open:
```bash
telnet mail.achtech.org.ng 465
```

### Check PHP mail function:
```php
if (mail('test@example.com', 'Test', 'Test message')) {
    echo "PHP mail works";
} else {
    echo "PHP mail failed";
}
```

### Check server configuration:
```php
phpinfo(); // Look for mail settings
```

## 📞 **Contact Information**

If issues persist:
1. Contact hosting provider
2. Check domain DNS settings
3. Verify email account settings
4. Consider using third-party email service

## 🚀 **Recommended Solution**

For immediate fix, try Gmail SMTP:
1. Create a Gmail account
2. Enable 2-factor authentication
3. Generate app password
4. Update SMTP settings
5. Test email functionality

This usually resolves most email delivery issues.
