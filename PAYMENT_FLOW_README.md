# Aries College Payment Integration - Improved Flow

## Overview

This document describes the improved payment integration for the Aries College application system. The new flow ensures that applications are only processed after successful payment verification.

## Key Improvements

### 1. Payment-First Application Flow
- Applications are saved with `pending_payment` status initially
- Only marked as `submitted` after successful payment verification
- Prevents incomplete applications from being processed

### 2. Enhanced Payment Processing
- Supports both Paystack and Flutterwave payment gateways
- Automatic payment verification via webhooks
- Email notifications for payment status updates

### 3. Payment Status Management
- Payment status checker page for applicants
- Automatic cleanup of incomplete applications
- Payment reminder system

## File Structure

```
├── apply.php                    # Main application form
├── process_application.php      # Handles form submission
├── payment_processor.php        # Payment processing logic
├── payment_webhook.php          # Webhook handler for payment verification
├── payment_cleanup.php          # Cleanup script for incomplete applications
├── check_payment_status.php     # Payment status checker
├── payment_config.php           # Payment gateway configuration
├── payment_success.php          # Success page
├── payment_failure.php          # Failure page
└── PAYMENT_FLOW_README.md       # This documentation
```

## Application Flow

### 1. Application Submission
1. User fills out application form on `apply.php`
2. Form is submitted to `process_application.php`
3. Application is saved with `payment_status = 'pending'` and `application_status = 'pending_payment'`
4. Payment is initialized via Flutterwave/Paystack
5. User receives email with payment link
6. User is redirected to payment gateway

### 2. Payment Processing
1. User completes payment on Flutterwave/Paystack
2. Payment gateway sends webhook to `payment_webhook.php`
3. Payment is verified and transaction status is updated
4. If payment is successful:
   - Application status is updated to `submitted`
   - Payment status is updated to `paid`
   - Confirmation email is sent to applicant
5. If payment fails:
   - Application remains in `pending_payment` status
   - User can retry payment

### 3. Status Checking
- Users can check their payment status at `check_payment_status.php`
- Can search by email or payment reference
- Shows application details and payment status

## Database Schema

### Applications Table
```sql
ALTER TABLE applications ADD COLUMN payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending';
ALTER TABLE applications ADD COLUMN application_status ENUM('pending_payment', 'submitted', 'approved', 'rejected') DEFAULT 'pending_payment';
ALTER TABLE applications ADD COLUMN payment_date TIMESTAMP NULL;
ALTER TABLE applications ADD COLUMN reminder_sent TINYINT(1) DEFAULT 0;
ALTER TABLE applications ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE applications ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
```

### Transactions Table
```sql
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'NGN',
    reference VARCHAR(100) UNIQUE NOT NULL,
    gateway_reference VARCHAR(100),
    status ENUM('pending', 'success', 'failed', 'cancelled') DEFAULT 'pending',
    payment_gateway VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
);
```

## Configuration

### Payment Gateway Setup
Edit `payment_config.php` to configure your payment gateway:

```php
// Choose payment gateway
define('PAYMENT_GATEWAY', 'flutterwave'); // or 'paystack'

// Flutterwave Configuration
define('FLUTTERWAVE_SECRET_KEY', 'your_secret_key');
define('FLUTTERWAVE_PUBLIC_KEY', 'your_public_key');
define('FLUTTERWAVE_BASE_URL', 'https://api.flutterwave.com/v3');

// Application Fee
define('APPLICATION_FEE', 10230); // ₦10,230
```

## Maintenance

### Automated Cleanup
Set up cron jobs to run the cleanup script:

```bash
# Clean up incomplete applications older than 7 days (daily at 2 AM)
0 2 * * * /usr/bin/php /path/to/payment_cleanup.php cleanup 7

# Send payment reminders for applications older than 3 days (daily at 10 AM)
0 10 * * * /usr/bin/php /path/to/payment_cleanup.php reminders 3

# Get statistics (weekly on Sunday at 6 AM)
0 6 * * 0 /usr/bin/php /path/to/payment_cleanup.php stats
```

### Manual Cleanup Commands
```bash
# Clean up incomplete applications
php payment_cleanup.php cleanup 7

# Send payment reminders
php payment_cleanup.php reminders 3

# View statistics
php payment_cleanup.php stats
```

## Security Features

1. **Payment Verification**: All payments are verified via webhook before marking as successful
2. **Database Transactions**: Uses prepared statements to prevent SQL injection
3. **File Upload Security**: Validates file types and sizes
4. **Session Management**: Secure session handling for payment references
5. **Email Validation**: Proper email validation and sanitization

## Error Handling

1. **Payment Failures**: Applications remain in pending status, allowing retry
2. **Webhook Failures**: Logged for manual review
3. **Email Failures**: Logged but don't break the payment flow
4. **Database Errors**: Proper error messages and rollback

## Testing

### Test Payment Flow
1. Submit a test application
2. Use test payment credentials
3. Verify webhook receives payment confirmation
4. Check application status is updated
5. Verify confirmation email is sent

### Test Cleanup
1. Create incomplete applications
2. Run cleanup script
3. Verify incomplete applications are removed
4. Check reminder emails are sent

## Support

For issues or questions:
- Check error logs in your web server
- Verify payment gateway configuration
- Test webhook endpoints
- Review database for incomplete transactions

## Future Enhancements

1. **SMS Notifications**: Add SMS reminders for pending payments
2. **Payment Analytics**: Dashboard for payment statistics
3. **Multiple Payment Methods**: Support for more payment options
4. **Refund Processing**: Automated refund handling
5. **Payment Plans**: Support for installment payments
