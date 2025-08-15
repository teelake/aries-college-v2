-- Fix Transactions Table Structure
-- This script will standardize the transactions table to match the PaymentProcessor expectations

-- First, let's see the current structure
DESCRIBE transactions;

-- Check if we have duplicate reference columns
SHOW COLUMNS FROM transactions LIKE '%reference%';

-- Create a backup of existing data (if any)
CREATE TABLE transactions_backup AS SELECT * FROM transactions;

-- Drop the current table and recreate with proper structure
DROP TABLE IF EXISTS transactions;

-- Create the transactions table with the correct structure
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'NGN',
    reference VARCHAR(100) NOT NULL UNIQUE,
    gateway_reference VARCHAR(100) NULL,
    payment_gateway VARCHAR(50) NOT NULL DEFAULT 'flutterwave',
    payment_method VARCHAR(50) NULL,
    status ENUM('pending', 'successful', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_application_id (application_id),
    INDEX idx_reference (reference),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    
    -- Foreign key constraint
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert data back from backup (if any)
INSERT INTO transactions (
    application_id, 
    amount, 
    currency, 
    reference, 
    gateway_reference, 
    payment_gateway, 
    payment_method, 
    status, 
    paid_at, 
    created_at, 
    updated_at
)
SELECT 
    application_id,
    amount,
    COALESCE(currency, 'NGN') as currency,
    COALESCE(reference, payment_reference) as reference,
    gateway_reference,
    COALESCE(payment_gateway, 'flutterwave') as payment_gateway,
    payment_method,
    COALESCE(status, 'pending') as status,
    paid_at,
    created_at,
    COALESCE(updated_at, created_at) as updated_at
FROM transactions_backup;

-- Drop the backup table
DROP TABLE transactions_backup;

-- Show the final structure
DESCRIBE transactions;

-- Show sample data
SELECT * FROM transactions LIMIT 5;
