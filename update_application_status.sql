-- Update application_status column to remove 'pending_payment' option
-- Since we have a separate payment_status column, we don't need pending_payment in application_status

-- First, update any existing 'pending_payment' status to 'submitted' for applications with paid payments
UPDATE applications 
SET application_status = 'submitted' 
WHERE application_status = 'pending_payment' 
AND payment_status = 'paid';

-- Update any remaining 'pending_payment' status to 'submitted' (for applications without payment)
UPDATE applications 
SET application_status = 'submitted' 
WHERE application_status = 'pending_payment';

-- Now modify the column to only allow the three main statuses
ALTER TABLE applications 
MODIFY COLUMN application_status ENUM('submitted', 'approved', 'rejected') NOT NULL DEFAULT 'submitted';

-- Add an index for better performance
ALTER TABLE applications 
ADD INDEX idx_application_status (application_status);

-- Verify the changes
SELECT application_status, COUNT(*) as count 
FROM applications 
GROUP BY application_status;

-- Show the new column definition
DESCRIBE applications;
