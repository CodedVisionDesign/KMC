-- Add trial eligibility to classes table
ALTER TABLE `classes` 
ADD COLUMN `trial_eligible` BOOLEAN DEFAULT TRUE COMMENT 'Whether this class allows trial bookings';

-- Update existing classes to be trial eligible by default
UPDATE `classes` SET `trial_eligible` = TRUE WHERE `trial_eligible` IS NULL;

-- Create index for better performance
CREATE INDEX idx_classes_trial_eligible ON classes(trial_eligible); 