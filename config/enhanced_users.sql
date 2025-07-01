-- Enhanced users table with DOB, Gender, and Health Questionnaire
-- Migration to add new fields to existing users table

-- Add new columns to users table
ALTER TABLE users 
ADD COLUMN date_of_birth DATE NULL AFTER phone,
ADD COLUMN gender ENUM('male', 'female', 'other', 'prefer_not_to_say') NULL AFTER date_of_birth,
ADD COLUMN health_questionnaire JSON NULL AFTER gender;

-- Create index on date_of_birth for age-based queries
CREATE INDEX idx_users_dob ON users(date_of_birth);

-- Health questionnaire structure will store data as JSON:
-- {
--   "has_medical_conditions": true/false,
--   "medical_conditions": "string description",
--   "takes_medication": true/false,
--   "medication_details": "string description", 
--   "has_injuries": true/false,
--   "injury_details": "string description",
--   "emergency_contact_name": "string",
--   "emergency_contact_phone": "string",
--   "emergency_contact_relationship": "string",
--   "fitness_level": "beginner/intermediate/advanced",
--   "exercise_limitations": "string description",
--   "has_allergies": true/false,
--   "allergy_details": "string description",
--   "consent_medical_emergency": true/false,
--   "completed_at": "timestamp"
-- } 