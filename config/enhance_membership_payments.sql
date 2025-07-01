-- Enhance membership system with payment tracking
ALTER TABLE `user_memberships` 
ADD COLUMN `payment_method` ENUM('pending', 'gocardless', 'bank_transfer', 'cash', 'card') DEFAULT 'pending' COMMENT 'Payment method used',
ADD COLUMN `payment_received` BOOLEAN DEFAULT FALSE COMMENT 'Whether payment has been received',
ADD COLUMN `payment_date` DATETIME NULL COMMENT 'When payment was received',
ADD COLUMN `payment_reference` VARCHAR(255) NULL COMMENT 'Payment reference/transaction ID',
ADD COLUMN `payment_notes` TEXT NULL COMMENT 'Admin notes about payment',
ADD COLUMN `admin_approved_by` INT NULL COMMENT 'Admin user who approved',
ADD COLUMN `admin_approved_at` DATETIME NULL COMMENT 'When admin approved membership',
ADD COLUMN `gocardless_visible` BOOLEAN DEFAULT FALSE COMMENT 'Whether GoCardless details are visible to user',
ADD COLUMN `bank_details_visible` BOOLEAN DEFAULT FALSE COMMENT 'Whether bank details are visible to user';

-- Add foreign key for admin approval tracking (if admins table exists)
-- ALTER TABLE `user_memberships` ADD FOREIGN KEY (`admin_approved_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL;

-- Create indexes for better performance
CREATE INDEX idx_user_memberships_payment_status ON user_memberships(payment_received, status);
CREATE INDEX idx_user_memberships_payment_method ON user_memberships(payment_method);
CREATE INDEX idx_user_memberships_admin_approved ON user_memberships(admin_approved_by, admin_approved_at); 