-- Migration to add instructors functionality
-- Run this script to add instructors to an existing database

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

-- Add instructor_id column to classes table if it doesn't exist
ALTER TABLE classes ADD COLUMN instructor_id INT NULL;

-- Add foreign key constraint for instructor_id
ALTER TABLE classes ADD CONSTRAINT fk_classes_instructor 
FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE SET NULL;

-- Insert sample instructors
INSERT INTO instructors (first_name, last_name, email, phone, bio, specialties) VALUES 
('Sarah', 'Johnson', 'sarah.johnson@studio.com', '555-0101', 'Certified yoga instructor with 10+ years experience. Specializes in Hatha and Vinyasa yoga.', 'Hatha Yoga, Vinyasa, Meditation'),
('Mike', 'Chen', 'mike.chen@studio.com', '555-0102', 'Personal trainer and Pilates instructor. Former athlete with expertise in strength training.', 'Pilates, HIIT, Strength Training'),
('Emma', 'Davis', 'emma.davis@studio.com', '555-0103', 'Mindfulness coach and meditation teacher. Creates calming environments for healing and growth.', 'Meditation, Mindfulness, Breathwork'),
('Alex', 'Rodriguez', 'alex.rodriguez@studio.com', '555-0104', 'High-intensity training specialist. Motivational coach focused on fitness transformations.', 'HIIT, CrossFit, Weight Training'),
('Lisa', 'Thompson', 'lisa.thompson@studio.com', '555-0105', 'Beginner-friendly yoga instructor. Patient and encouraging approach to wellness.', 'Beginner Yoga, Gentle Yoga, Seniors Fitness');

-- Update existing classes with instructors
-- You may need to adjust these based on your actual class data
UPDATE classes SET instructor_id = 1 WHERE name LIKE '%Yoga%' AND name LIKE '%Morning%';
UPDATE classes SET instructor_id = 2 WHERE name LIKE '%Pilates%';
UPDATE classes SET instructor_id = 3 WHERE name LIKE '%Meditation%';
UPDATE classes SET instructor_id = 4 WHERE name LIKE '%HIIT%';
UPDATE classes SET instructor_id = 5 WHERE name LIKE '%Beginner%' AND name LIKE '%Yoga%';

-- Create indexes for better performance
CREATE INDEX idx_instructors_email ON instructors(email);
CREATE INDEX idx_instructors_status ON instructors(status);
CREATE INDEX idx_classes_instructor_id ON classes(instructor_id); 