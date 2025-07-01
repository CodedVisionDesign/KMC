-- Migration to add recurring column to existing classes table
-- Run this SQL if you already have a classes table without the recurring column

ALTER TABLE classes 
ADD COLUMN recurring TINYINT(1) DEFAULT 0 AFTER instructor_id;

-- Add an index for better performance when filtering recurring classes
CREATE INDEX idx_classes_recurring ON classes(recurring);

-- Optional: Update existing classes to be non-recurring by default (already handled by DEFAULT 0)
-- UPDATE classes SET recurring = 0 WHERE recurring IS NULL; 