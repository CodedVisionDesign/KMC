-- Safe migration to add instructors functionality
-- This script handles existing columns and constraints gracefully

-- Create instructors table
CREATE TABLE IF NOT EXISTS instructors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    bio TEXT,
    specialties TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Check if instructor_id column exists before adding it
SET @col_exists = (SELECT COUNT(*) 
                   FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'classes' 
                   AND COLUMN_NAME = 'instructor_id');

-- Add instructor_id column only if it doesn't exist
SET @sql = IF(@col_exists = 0, 
              'ALTER TABLE classes ADD COLUMN instructor_id INT NULL', 
              'SELECT "Column instructor_id already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if foreign key constraint exists before adding it
SET @fk_exists = (SELECT COUNT(*) 
                  FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                  WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'classes' 
                  AND CONSTRAINT_NAME = 'fk_classes_instructor');

-- Add foreign key constraint only if it doesn't exist
SET @sql = IF(@fk_exists = 0, 
              'ALTER TABLE classes ADD CONSTRAINT fk_classes_instructor FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE SET NULL', 
              'SELECT "Foreign key constraint already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Insert sample instructors (only if table is empty)
INSERT IGNORE INTO instructors (first_name, last_name, email, phone, bio, specialties) VALUES 
('Sarah', 'Johnson', 'sarah.johnson@studio.com', '555-0101', 'Certified yoga instructor with 10+ years experience. Specializes in Hatha and Vinyasa yoga.', 'Hatha Yoga, Vinyasa, Meditation'),
('Mike', 'Chen', 'mike.chen@studio.com', '555-0102', 'Personal trainer and Pilates instructor. Former athlete with expertise in strength training.', 'Pilates, HIIT, Strength Training'),
('Emma', 'Davis', 'emma.davis@studio.com', '555-0103', 'Mindfulness coach and meditation teacher. Creates calming environments for healing and growth.', 'Meditation, Mindfulness, Breathwork'),
('Alex', 'Rodriguez', 'alex.rodriguez@studio.com', '555-0104', 'High-intensity training specialist. Motivational coach focused on fitness transformations.', 'HIIT, CrossFit, Weight Training'),
('Lisa', 'Thompson', 'lisa.thompson@studio.com', '555-0105', 'Beginner-friendly yoga instructor. Patient and encouraging approach to wellness.', 'Beginner Yoga, Gentle Yoga, Seniors Fitness');

-- Update existing classes with instructors (only if instructor_id is NULL)
UPDATE classes SET instructor_id = 1 WHERE (name LIKE '%Yoga%' OR name LIKE '%yoga%') AND name LIKE '%Morning%' AND instructor_id IS NULL;
UPDATE classes SET instructor_id = 2 WHERE (name LIKE '%Pilates%' OR name LIKE '%pilates%') AND instructor_id IS NULL;
UPDATE classes SET instructor_id = 3 WHERE (name LIKE '%Meditation%' OR name LIKE '%meditation%') AND instructor_id IS NULL;
UPDATE classes SET instructor_id = 4 WHERE (name LIKE '%HIIT%' OR name LIKE '%hiit%' OR name LIKE '%High%') AND instructor_id IS NULL;
UPDATE classes SET instructor_id = 5 WHERE (name LIKE '%Beginner%' OR name LIKE '%beginner%') AND (name LIKE '%Yoga%' OR name LIKE '%yoga%') AND instructor_id IS NULL;

-- Assign Sarah (Yoga specialist) to any remaining yoga classes
UPDATE classes SET instructor_id = 1 WHERE (name LIKE '%Yoga%' OR name LIKE '%yoga%') AND instructor_id IS NULL;

-- Assign Mike (Pilates/HIIT) to any remaining fitness classes
UPDATE classes SET instructor_id = 2 WHERE (name LIKE '%Fitness%' OR name LIKE '%fitness%' OR name LIKE '%Workout%' OR name LIKE '%workout%') AND instructor_id IS NULL;

-- Assign a default instructor to any remaining classes
UPDATE classes SET instructor_id = 1 WHERE instructor_id IS NULL;

-- Create indexes for better performance (only if they don't exist)
CREATE INDEX IF NOT EXISTS idx_instructors_email ON instructors(email);
CREATE INDEX IF NOT EXISTS idx_instructors_status ON instructors(status);
CREATE INDEX IF NOT EXISTS idx_classes_instructor_id ON classes(instructor_id); 