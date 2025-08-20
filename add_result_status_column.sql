-- Add result_status column to applications table
ALTER TABLE `applications` 
ADD COLUMN `result_status` enum('available','awaiting_result') DEFAULT 'available' 
AFTER `qualification`;

-- Update existing records to have 'available' as default
UPDATE `applications` SET `result_status` = 'available' WHERE `result_status` IS NULL;
