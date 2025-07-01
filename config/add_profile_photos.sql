-- Add profile photo support to users and instructors tables
-- This migration adds profile_photo fields to support image uploads

-- Add profile_photo column to users table
ALTER TABLE `users` ADD COLUMN `profile_photo` varchar(255) DEFAULT NULL AFTER `gender`;

-- Add profile_photo column to instructors table  
ALTER TABLE `instructors` ADD COLUMN `profile_photo` varchar(255) DEFAULT NULL AFTER `bio`;

-- Create uploads directories if they don't exist (handled by PHP)
-- public/uploads/profiles/users/
-- public/uploads/profiles/instructors/ 