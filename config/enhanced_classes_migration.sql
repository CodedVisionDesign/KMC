-- Enhanced Classes Migration
-- Adds support for multiple days, age/gender restrictions, and improved scheduling

-- Add new columns to classes table
ALTER TABLE `classes` 
ADD COLUMN `days_of_week` JSON DEFAULT NULL COMMENT 'Array of days: ["monday", "tuesday", etc]',
ADD COLUMN `multiple_times` JSON DEFAULT NULL COMMENT 'Array of times for multiple sessions per day',
ADD COLUMN `age_min` INT DEFAULT NULL COMMENT 'Minimum age requirement',
ADD COLUMN `age_max` INT DEFAULT NULL COMMENT 'Maximum age limit',
ADD COLUMN `gender_restriction` ENUM('mixed', 'male_only', 'female_only') DEFAULT 'mixed' COMMENT 'Gender restrictions',
ADD COLUMN `prerequisites` TEXT DEFAULT NULL COMMENT 'Prerequisites or requirements',
ADD COLUMN `difficulty_level` ENUM('beginner', 'intermediate', 'advanced', 'all_levels') DEFAULT 'all_levels',
ADD COLUMN `duration_minutes` INT DEFAULT 60 COMMENT 'Class duration in minutes';

-- Update existing classes to have default values
UPDATE `classes` SET 
    `gender_restriction` = 'mixed',
    `difficulty_level` = 'all_levels',
    `duration_minutes` = 60
WHERE `gender_restriction` IS NULL;

-- Create index for better performance on searches
CREATE INDEX idx_classes_days ON classes(days_of_week(100));
CREATE INDEX idx_classes_age ON classes(age_min, age_max);
CREATE INDEX idx_classes_gender ON classes(gender_restriction); 