-- Database setup for Aries College Application System

-- Create applications table
CREATE TABLE IF NOT EXISTS `applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` varchar(10) NOT NULL,
  `address` text NOT NULL,
  `state` varchar(50) NOT NULL,
  `lga` varchar(50) NOT NULL,
  `last_school` varchar(100) NOT NULL,
  `qualification` varchar(50) NOT NULL,
  `year_completed` date NOT NULL,
  `program_applied` varchar(100) NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `certificate_path` varchar(255) NOT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `application_status` enum('pending_payment','submitted','under_review','admitted','not_admitted') DEFAULT 'pending_payment',
  `admin_comment` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create transactions table
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'NGN',
  `reference` varchar(100) NOT NULL,
  `gateway_reference` varchar(100),
  `payment_gateway` varchar(20) DEFAULT 'flutterwave',
  `status` enum('pending','success','failed','cancelled') DEFAULT 'pending',
  `paid_at` timestamp NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `application_id` (`application_id`),
  KEY `status` (`status`),
  FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create admins table (if needed for admin panel)
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','super_admin') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO `admins` (`username`, `email`, `password`, `full_name`, `role`) VALUES
('admin', 'admin@achtech.org.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin');

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_applications_payment_status` ON `applications` (`payment_status`);
CREATE INDEX IF NOT EXISTS `idx_applications_application_status` ON `applications` (`application_status`);
CREATE INDEX IF NOT EXISTS `idx_applications_created_at` ON `applications` (`created_at`);
CREATE INDEX IF NOT EXISTS `idx_transactions_status` ON `transactions` (`status`);
CREATE INDEX IF NOT EXISTS `idx_transactions_created_at` ON `transactions` (`created_at`);


