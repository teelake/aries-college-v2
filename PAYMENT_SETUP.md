# Payment Integration Setup Guide

This guide will help you set up the Flutterwave payment integration for the Aries College application system.

## Prerequisites

1. **Flutterwave Account**: You need a Flutterwave account (test or live)
2. **Web Server**: PHP 7.4+ with cURL extension enabled
3. **Database**: MySQL/MariaDB
4. **SSL Certificate**: Required for webhook security

## Setup Steps

### 1. Database Setup

Run the SQL commands in `database_setup.sql` to create the necessary tables:

```sql
-- Run this in your MySQL database
source database_setup.sql;
```

This will create:
- `transactions` table for payment records
- Add payment columns to `applications` table
- Set up proper indexes and foreign keys

### 2. Configuration

Update `payment_config.php` with your Flutterwave credentials:

#### For Testing (Current Setup):
```php
define('FLUTTERWAVE_SECRET_KEY', 'FLWSECK_TEST-ab26e105e6330a39d5a2a61a695db309-X');
define('FLUTTERWAVE_PUBLIC_KEY', 'FLWPUBK_TEST-7a252dcf06f4d1bc6f605a582f1b85b1-X');
define('FLUTTERWAVE_ENCRYPTION_KEY', 'FLWSECK_TESTaf2b99a9e5a3');
```

#### For Production:
```php
define('FLUTTERWAVE_SECRET_KEY', 'FLWSECK-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('FLUTTERWAVE_PUBLIC_KEY', 'FLWPUBK-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('FLUTTERWAVE_ENCRYPTION_KEY', 'xxxxxxxxxxxxxxxxxxxxxxxx');
```

### 3. Webhook Configuration

In your Flutterwave dashboard:

1. Go to **Settings > Webhooks**
2. Add webhook URL: `https://yourdomain.com/payment_webhook.php`
3. Select events: `charge.completed`, `charge.failed`
4. Save the webhook

### 4. File Permissions

Ensure the following directories are writable:
```bash
chmod 755 uploads/
chmod 644 *.php
```

### 5. URL Configuration

Update the callback URLs in `payment_config.php`:

```php
define('PAYMENT_SUCCESS_URL', 'https://yourdomain.com/payment_success.php');
define('PAYMENT_FAILURE_URL', 'https://yourdomain.com/payment_failure.php');
define('PAYMENT_WEBHOOK_URL', 'https://yourdomain.com/payment_webhook.php');
```

## Payment Flow

### 1. Application Submission
- User fills application form
- Form is submitted to `process_application.php`
- Application is saved to database
- Payment is initialized via Flutterwave
- User is redirected to Flutterwave payment page

### 2. Payment Processing
- User completes payment on Flutterwave
- Flutterwave sends webhook to `payment_webhook.php`
- Payment status is updated in database
- Confirmation email and receipt are sent

### 3. Payment Completion
- User is redirected to `payment_success.php` or `payment_failure.php`
- Payment status is verified
- Success/failure page is displayed

## Testing

### Test Cards (Flutterwave Test Mode)

#### Successful Payment:
- Card Number: `5531886652142950`
- Expiry: `09/32`
- CVV: `564`
- PIN: `3310`
- OTP: `12345`

#### Failed Payment:
- Card Number: `4000000000000002`
- Expiry: `09/32`
- CVV: `564`
- PIN: `3310`
- OTP: `12345`

### Test Bank Transfer
- Bank: `Test Bank`
- Account Number: `1234567890`
- Amount: `10000`

## Files Overview

### Core Payment Files:
- `payment_config.php` - Configuration settings
- `payment_processor.php` - Payment processing class
- `payment_webhook.php` - Webhook handler
- `payment_success.php` - Success page
- `payment_failure.php` - Failure page
- `process_application.php` - Application processing

### Database Files:
- `database_setup.sql` - Database schema
- `backend/db_connect.php` - Database connection

### Integration Files:
- `apply.php` - Updated application form
- `initialize_payment.php` - Payment initialization endpoint

## Security Considerations

### 1. Webhook Verification
The webhook signature verification is currently disabled for testing. For production:

```php
function verifyWebhookSignature($payload, $signature) {
    $secretHash = FLUTTERWAVE_ENCRYPTION_KEY;
    $computedHash = hash_hmac('sha256', $payload, $secretHash);
    return hash_equals($computedHash, $signature);
}
```

### 2. Environment Variables
For production, move sensitive data to environment variables:

```php
define('FLUTTERWAVE_SECRET_KEY', $_ENV['FLUTTERWAVE_SECRET_KEY']);
define('FLUTTERWAVE_PUBLIC_KEY', $_ENV['FLUTTERWAVE_PUBLIC_KEY']);
```

### 3. SSL Certificate
Ensure your domain has a valid SSL certificate for secure payment processing.

## Troubleshooting

### Common Issues:

1. **Webhook Not Receiving**: Check webhook URL and server logs
2. **Payment Not Updating**: Verify database connection and table structure
3. **Email Not Sending**: Check SMTP settings in payment files
4. **Redirect Issues**: Verify callback URLs in Flutterwave dashboard

### Debug Mode:
Enable error logging in PHP:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Log Files:
Check these locations for errors:
- PHP error log
- Web server error log
- Application error log (if configured)

## Support

For technical support:
- Email: support@achtech.org.ng
- Phone: +234 810 862 6169

## Live Mode Setup

When ready for production:

1. Switch to live Flutterwave keys
2. Update webhook URLs to production domain
3. Enable webhook signature verification
4. Test with small amounts first
5. Monitor transactions in Flutterwave dashboard

## Features Included

✅ **Complete Payment Flow**
✅ **Webhook Integration**
✅ **Email Notifications**
✅ **Payment Receipts**
✅ **Success/Failure Pages**
✅ **Database Integration**
✅ **Security Features**
✅ **Error Handling**
✅ **Mobile Responsive**
✅ **Admin Dashboard Integration**

The payment system is now fully integrated and ready for testing!


