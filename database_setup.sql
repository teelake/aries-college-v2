-- Database setup for payment integration
-- Run this SQL to create the necessary tables and columns

-- Create transactions table
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'NGN',
  `reference` varchar(100) NOT NULL,
  `gateway_reference` varchar(100) DEFAULT NULL,
  `status` enum('pending','success','failed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_gateway` varchar(20) NOT NULL DEFAULT 'flutterwave',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_channel` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `application_id` (`application_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add payment-related columns to applications table if they don't exist
ALTER TABLE `applications` 
ADD COLUMN IF NOT EXISTS `payment_status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending' AFTER `certificate_path`,
ADD COLUMN IF NOT EXISTS `payment_date` timestamp NULL DEFAULT NULL AFTER `payment_status`;

-- Add indexes for better performance
ALTER TABLE `applications` 
ADD INDEX IF NOT EXISTS `idx_payment_status` (`payment_status`),
ADD INDEX IF NOT EXISTS `idx_payment_date` (`payment_date`);

-- Add foreign key constraint for transactions table
ALTER TABLE `transactions` 
ADD CONSTRAINT `fk_transactions_application` 
FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) 
ON DELETE CASCADE ON UPDATE CASCADE;


