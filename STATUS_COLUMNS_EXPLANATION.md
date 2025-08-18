# Status Columns Explanation - Aries College Application System

## Applications Table Status Columns

The `applications` table has two status columns that serve different purposes:

### 1. `payment_status` (ENUM)
**Purpose**: Tracks whether the application fee has been paid
**Values**:
- `pending` (default) - Application fee not yet paid
- `paid` - Application fee has been successfully paid
- `failed` - Payment attempt failed

**Usage**: This column is automatically updated by the payment system when a payment is processed.

### 2. `application_status` (ENUM)
**Purpose**: Tracks the overall application review status
**Values**:
- `pending_payment` (default) - Application submitted but payment not yet made
- `submitted` - Application complete and submitted for review (payment made)
- `under_review` - Application is being reviewed by admissions team
- `admitted` - Application approved for admission
- `not_admitted` - Application rejected

**Usage**: This column is updated by:
- Payment system: Sets to `submitted` when payment is successful
- Admin panel: Sets to `admitted` or `not_admitted` when decision is made

## Transactions Table Status Column

### `status` (ENUM)
**Purpose**: Tracks the payment transaction status
**Values**:
- `pending` (default) - Payment initiated but not yet completed
- `successful` - Payment completed successfully
- `failed` - Payment failed
- `cancelled` - Payment was cancelled by user

## Status Flow

1. **Application Submission**: `payment_status = pending`, `application_status = pending_payment`
2. **Payment Initiated**: Transaction `status = pending`
3. **Payment Successful**: 
   - Transaction `status = successful`, `paid_at = NOW()`
   - Application `payment_status = paid`, `application_status = submitted`
4. **Admin Review**: Application `application_status = under_review`
5. **Decision Made**: Application `application_status = admitted` or `not_admitted`

## Recommendations

### Keep Both Status Columns
**Yes, you should keep both status columns** because they serve different purposes:

1. **`payment_status`** - Tracks financial aspect (has the fee been paid?)
2. **`application_status`** - Tracks administrative aspect (what's the application status?)

### Default Values
- `payment_status`: `pending`
- `application_status`: `pending_payment`

### Status Values
The current ENUM values are appropriate and cover all necessary states. No changes needed.

## Database Queries Examples

```sql
-- Find all applications with paid fees
SELECT * FROM applications WHERE payment_status = 'paid';

-- Find all admitted students
SELECT * FROM applications WHERE application_status = 'admitted';

-- Find applications pending review
SELECT * FROM applications WHERE application_status = 'submitted';

-- Find applications with failed payments
SELECT * FROM applications WHERE payment_status = 'failed';

-- Find all successful transactions
SELECT * FROM transactions WHERE status = 'successful';
```

## Recent Fixes Applied

1. **Removed 3-5 days update message** from receipt emails
2. **Fixed transaction updates** to properly set `paid_at`, `payment_method`, and `gateway_reference`
3. **Fixed admit/reject redirects** with proper error handling and fallback JavaScript redirects
4. **Updated status values** to use correct ENUM values (`admitted` instead of `approved`, `not_admitted` instead of `rejected`)
