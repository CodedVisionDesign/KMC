-- Membership System Migration
-- Run this to add membership and video management functionality

-- Create membership plans table
CREATE TABLE IF NOT EXISTS membership_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    monthly_class_limit INT NULL COMMENT 'NULL for unlimited plans',
    price DECIMAL(8,2) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create user memberships table
CREATE TABLE IF NOT EXISTS user_memberships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    status ENUM('pending', 'active', 'expired', 'cancelled', 'rejected') DEFAULT 'pending',
    notes TEXT NULL,
    free_trial_used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES membership_plans(id) ON DELETE RESTRICT,
    INDEX idx_user_memberships_user_id (user_id),
    INDEX idx_user_memberships_status (status),
    INDEX idx_user_memberships_dates (start_date, end_date)
);

-- Create membership payments table
CREATE TABLE IF NOT EXISTS membership_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_membership_id INT NOT NULL,
    amount DECIMAL(8,2) NOT NULL,
    payment_method VARCHAR(50) NULL DEFAULT 'pending',
    reference_number VARCHAR(100) NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    notes TEXT,
    confirmed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_membership_id) REFERENCES user_memberships(id) ON DELETE CASCADE,
    INDEX idx_payments_user_membership (user_membership_id),
    INDEX idx_payments_status (status)
);

-- Create video series table
CREATE TABLE IF NOT EXISTS video_series (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    cover_image VARCHAR(255),
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_video_series_status (status),
    INDEX idx_video_series_sort (sort_order)
);

-- Create videos table
CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    series_id INT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT,
    format VARCHAR(20) NOT NULL,
    duration_seconds INT,
    thumbnail_path VARCHAR(500),
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (series_id) REFERENCES video_series(id) ON DELETE SET NULL,
    INDEX idx_videos_series (series_id),
    INDEX idx_videos_status (status),
    INDEX idx_videos_sort (sort_order)
);

-- Add membership tracking to bookings table
ALTER TABLE bookings 
ADD COLUMN membership_cycle VARCHAR(7) NULL COMMENT 'YYYY-MM format for tracking monthly limits',
ADD COLUMN is_free_trial TINYINT(1) DEFAULT 0,
ADD INDEX idx_bookings_membership_cycle (membership_cycle),
ADD INDEX idx_bookings_free_trial (is_free_trial);

-- Add free trial tracking to users table
ALTER TABLE users 
ADD COLUMN free_trial_used TINYINT(1) DEFAULT 0,
ADD INDEX idx_users_free_trial (free_trial_used);

-- Insert default membership plans (only if they don't exist)
INSERT IGNORE INTO membership_plans (name, description, monthly_class_limit, price) VALUES
('Free Trial', 'One free trial class for new members', 1, 0.00),
('Basic Plan', '4 classes per month - perfect for beginners', 4, 39.99),
('Standard Plan', '8 classes per month - great for regular attendees', 8, 69.99),
('Premium Plan', '12 classes per month - for fitness enthusiasts', 12, 89.99),
('Unlimited Plan', 'Unlimited classes - for serious practitioners', NULL, 129.99);

-- Insert sample video series (only if they don't exist)
INSERT IGNORE INTO video_series (name, description, sort_order) VALUES
('Beginner Fundamentals', 'Essential techniques and movements for beginners', 1),
('Advanced Techniques', 'Advanced movements for experienced practitioners', 2),
('Flexibility & Mobility', 'Stretching and mobility routines', 3),
('Nutrition & Wellness', 'Health tips and nutritional guidance', 4);

-- Create function to check user membership status
DELIMITER $$
CREATE FUNCTION GetUserActiveMembership(user_id INT) 
RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE membership_id INT DEFAULT NULL;
    
    SELECT um.id INTO membership_id
    FROM user_memberships um
    WHERE um.user_id = user_id 
    AND um.status = 'active'
    AND um.start_date <= CURDATE()
    AND um.end_date >= CURDATE()
    ORDER BY um.end_date DESC
    LIMIT 1;
    
    RETURN membership_id;
END$$
DELIMITER ;

-- Create function to get user's monthly class count
DELIMITER $$
CREATE FUNCTION GetUserMonthlyClassCount(user_id INT, year_month VARCHAR(7))
RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE class_count INT DEFAULT 0;
    
    SELECT COUNT(*) INTO class_count
    FROM bookings b
    WHERE b.user_id = user_id 
    AND b.membership_cycle = year_month;
    
    RETURN class_count;
END$$
DELIMITER ;

-- Create directories for video uploads (note: this needs to be done via PHP)
-- /uploads/videos/
-- /uploads/thumbnails/ 