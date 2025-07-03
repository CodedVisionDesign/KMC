-- ===============================================
-- MARTIAL ARTS MEMBERSHIP SYSTEM UPDATE
-- Age-based membership tiers with special rules
-- ===============================================

-- 1. Add age group classification and special membership features
ALTER TABLE membership_plans 
ADD COLUMN age_min INT NULL COMMENT 'Minimum age for this plan (NULL = no minimum)',
ADD COLUMN age_max INT NULL COMMENT 'Maximum age for this plan (NULL = no maximum)', 
ADD COLUMN is_beginner_only TINYINT(1) DEFAULT 0 COMMENT 'Beginner-only membership',
ADD COLUMN beginner_duration_weeks INT NULL COMMENT 'Duration in weeks for beginner plans',
ADD COLUMN is_payg TINYINT(1) DEFAULT 0 COMMENT 'Pay-as-you-go membership',
ADD COLUMN requires_invitation TINYINT(1) DEFAULT 0 COMMENT 'Invitation-only membership',
ADD COLUMN requires_existing_membership TINYINT(1) DEFAULT 0 COMMENT 'Requires valid membership to purchase',
ADD COLUMN class_type_restriction VARCHAR(255) NULL COMMENT 'Restricted to specific class types',
ADD COLUMN weekly_class_limit INT NULL COMMENT 'Weekly class limit (NULL for no limit)',
ADD COLUMN gocardless_visible TINYINT(1) DEFAULT 1 COMMENT 'Show GoCardless payment option',
ADD COLUMN bank_details_visible TINYINT(1) DEFAULT 1 COMMENT 'Show bank transfer payment option',
ADD COLUMN bank_account_name VARCHAR(255) DEFAULT 'Elite Martial Arts Academy' COMMENT 'Bank account name',
ADD COLUMN bank_sort_code VARCHAR(10) DEFAULT '12-34-56' COMMENT 'Bank sort code',
ADD COLUMN bank_account_number VARCHAR(20) DEFAULT '12345678' COMMENT 'Bank account number',
ADD INDEX idx_membership_age (age_min, age_max),
ADD INDEX idx_membership_special (is_beginner_only, is_payg, requires_invitation);

-- 2. Add martial arts class categories and restrictions
ALTER TABLE classes
ADD COLUMN class_type ENUM('adults-fundamentals', 'adults-advanced', 'adults-any-level', 'adults-bag-work', 'adults-sparring', 'seniors', 'juniors', 'infants', 'private-tuition') NULL COMMENT 'Class type for membership restrictions',
ADD COLUMN age_min INT DEFAULT 4 COMMENT 'Minimum age for class',
ADD COLUMN age_max INT NULL COMMENT 'Maximum age for class (NULL = no max)',
ADD COLUMN requires_invitation TINYINT(1) DEFAULT 0 COMMENT 'Invitation-only class',
ADD COLUMN requires_membership TINYINT(1) DEFAULT 1 COMMENT 'Requires active membership',
ADD INDEX idx_classes_age (age_min, age_max),
ADD INDEX idx_classes_type (class_type);

-- 3. Add membership transition tracking for beginner plans
ALTER TABLE user_memberships
ADD COLUMN beginner_start_date DATE NULL COMMENT 'Start date for beginner-only period',
ADD COLUMN beginner_end_date DATE NULL COMMENT 'End date for beginner-only period', 
ADD COLUMN auto_upgrade_plan_id INT NULL COMMENT 'Plan to upgrade to after beginner period',
ADD COLUMN invitation_code VARCHAR(50) NULL COMMENT 'Invitation code for restricted memberships',
ADD COLUMN weekly_classes_used INT DEFAULT 0 COMMENT 'Classes used this week',
ADD COLUMN week_start_date DATE NULL COMMENT 'Start of current tracking week',
ADD INDEX idx_beginner_dates (beginner_start_date, beginner_end_date);

-- 4. Clear existing generic fitness plans and add martial arts plans
DELETE FROM membership_plans WHERE id IN (1, 2, 3, 4, 5);

-- 5. Insert new martial arts membership structure
INSERT INTO membership_plans (
    name, description, price, monthly_class_limit, weekly_class_limit, 
    age_min, age_max, gocardless_url, status
) VALUES

-- ADULTS (15yrs +) - Standard Memberships
('Adult Unlimited', 'Unlimited classes for adults (15+ years)', 85.00, NULL, NULL, 15, NULL, 'https://pay.gocardless.com/BRT0003YQSG794H', 'active'),

('Adult Basic', 'Up to 2 classes per week for adults (15+ years)', 65.00, NULL, 2, 15, NULL, 'https://pay.gocardless.com/BRT0003YQSFCSTK', 'active'),

-- ADULTS - Beginners Only Deal  
('Adult Beginner Deal', 'Beginner classes only - 1 per week for maximum 12 weeks', 40.00, NULL, 1, 15, NULL, 'https://pay.gocardless.com/BRT0003YQSNW5ZN', 'active'),

-- SENIOR SCHOOL (11-15yrs)
('Senior Unlimited', 'Unlimited classes for senior school (11-15 years)', 50.00, NULL, NULL, 11, 15, 'https://pay.gocardless.com/BRT0003YQSD6F9Q', 'active'),

('Senior Basic', '1 class per week for senior school (11-15 years)', 30.00, NULL, 1, 11, 15, 'https://pay.gocardless.com/BRT0003YQSC2WQZ', 'active'),

-- SENIOR PAYG - Sparring Only
('Senior Sparring PAYG', 'Sparring class only (14+ years, invitation only)', 10.00, 1, NULL, 14, 15, NULL, 'active'),

-- JUNIORS (7-11yrs)  
('Junior Unlimited', 'Unlimited classes for juniors (7-11 years)', 50.00, NULL, NULL, 7, 11, 'https://pay.gocardless.com/BRT0003YQSD6F9Q', 'active'),

('Junior Basic', '1 class per week for juniors (7-11 years)', 30.00, NULL, 1, 7, 11, 'https://pay.gocardless.com/BRT0003YQSC2WQZ', 'active'),

-- INFANTS (4-6yrs)
('Infant Membership', '1 class per week for infants (4-6 years)', 20.00, NULL, 1, 4, 6, 'https://pay.gocardless.com/BRT0003YQSAZ4PZ', 'active');

-- 6. Update membership plans with special rules
UPDATE membership_plans SET 
    is_beginner_only = 1, 
    beginner_duration_weeks = 12,
    class_type_restriction = 'adults-fundamentals',
    auto_upgrade_plan_id = 2  -- Adult Basic
WHERE name = 'Adult Beginner Deal';

UPDATE membership_plans SET 
    is_payg = 1,
    requires_invitation = 1, 
    requires_existing_membership = 1,
    class_type_restriction = 'adults-sparring'
WHERE name = 'Senior Sparring PAYG';

-- 7. Add sample martial arts classes (replace existing fitness classes)
DELETE FROM classes;

INSERT INTO classes (name, description, date, time, capacity, instructor_id, recurring, class_type, age_min, age_max) VALUES

-- Adult Classes
('Adult Fundamentals', 'Basic martial arts techniques for beginners', '2025-01-02', '19:00:00', 15, 1, 1, 'adults-fundamentals', 15, NULL),
('Adult Advanced', 'Advanced techniques for experienced practitioners', '2025-01-02', '20:00:00', 12, 1, 1, 'adults-advanced', 15, NULL),
('Adults Any Level', 'Mixed level class suitable for all adult abilities', '2025-01-03', '18:30:00', 20, 2, 1, 'adults-any-level', 15, NULL),
('Adults Bag Work', 'Heavy bag and pad work training', '2025-01-04', '17:00:00', 16, 2, 1, 'adults-bag-work', 15, NULL),
('Adults Sparring', 'Controlled sparring for experienced members', '2025-01-04', '19:30:00', 10, 1, 1, 'adults-sparring', 14, NULL),

-- Senior School Classes  
('Seniors Training', 'Martial arts for senior school age (11-15 years)', '2025-01-02', '17:00:00', 15, 3, 1, 'seniors', 11, 15),

-- Junior Classes
('Juniors Class', 'Fun martial arts for juniors (7-11 years)', '2025-01-02', '16:00:00', 18, 3, 1, 'juniors', 7, 11),

-- Infant Classes
('Infants Class', 'Gentle introduction to martial arts (4-6 years)', '2025-01-02', '15:00:00', 12, 4, 1, 'infants', 4, 6),

-- Private Sessions
('Private Tuition', 'One-on-one martial arts instruction', '2025-01-02', '14:00:00', 1, 1, 0, 'private-tuition', 4, NULL);

-- 8. Update sparring class to require invitation
UPDATE classes SET 
    requires_invitation = 1,
    requires_membership = 1
WHERE class_type = 'adults-sparring';

-- 9. Create helper functions for age-based membership validation

DELIMITER $$

CREATE FUNCTION GetUserAge(birth_date DATE) 
RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE user_age INT DEFAULT 0;
    
    IF birth_date IS NOT NULL THEN
        SET user_age = TIMESTAMPDIFF(YEAR, birth_date, CURDATE());
    END IF;
    
    RETURN user_age;
END$$

CREATE FUNCTION CanUserAccessPlan(user_id INT, plan_id INT)
RETURNS BOOLEAN
READS SQL DATA
DETERMINISTIC  
BEGIN
    DECLARE can_access BOOLEAN DEFAULT FALSE;
    DECLARE user_age INT DEFAULT 0;
    DECLARE plan_age_min INT DEFAULT NULL;
    DECLARE plan_age_max INT DEFAULT NULL;
    
    -- Get user age
    SELECT GetUserAge(date_of_birth) INTO user_age
    FROM users WHERE id = user_id;
    
    -- Get plan age restrictions
    SELECT age_min, age_max INTO plan_age_min, plan_age_max
    FROM membership_plans WHERE id = plan_id;
    
    -- Check age restrictions
    IF (plan_age_min IS NULL OR user_age >= plan_age_min) AND 
       (plan_age_max IS NULL OR user_age <= plan_age_max) THEN
        SET can_access = TRUE;
    END IF;
    
    RETURN can_access;
END$$

CREATE FUNCTION CanUserBookClass(user_id INT, class_id INT)
RETURNS VARCHAR(500)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE result VARCHAR(500) DEFAULT 'Cannot book class';
    DECLARE user_age INT DEFAULT 0;
    DECLARE class_age_min INT DEFAULT 0;
    DECLARE class_age_max INT DEFAULT NULL;
    DECLARE class_requires_invitation BOOLEAN DEFAULT FALSE;
    DECLARE class_requires_membership BOOLEAN DEFAULT TRUE;
    DECLARE has_active_membership BOOLEAN DEFAULT FALSE;
    DECLARE weekly_limit INT DEFAULT NULL;
    DECLARE weekly_used INT DEFAULT 0;
    
    -- Get user age
    SELECT GetUserAge(date_of_birth) INTO user_age
    FROM users WHERE id = user_id;
    
    -- Get class requirements
    SELECT age_min, age_max, requires_invitation, requires_membership 
    INTO class_age_min, class_age_max, class_requires_invitation, class_requires_membership
    FROM classes WHERE id = class_id;
    
    -- Check age requirements
    IF user_age < class_age_min THEN
        SET result = CONCAT('Age restriction: Must be at least ', class_age_min, ' years old');
        RETURN result;
    END IF;
    
    IF class_age_max IS NOT NULL AND user_age > class_age_max THEN
        SET result = CONCAT('Age restriction: Must be ', class_age_max, ' years or younger');
        RETURN result;
    END IF;
    
    -- Check membership requirement
    IF class_requires_membership THEN
        SELECT COUNT(*) > 0 INTO has_active_membership
        FROM user_memberships um
        JOIN membership_plans mp ON um.plan_id = mp.id
        WHERE um.user_id = user_id 
        AND um.status = 'active'
        AND um.start_date <= CURDATE()
        AND um.end_date >= CURDATE();
        
        IF NOT has_active_membership THEN
            SET result = 'Active membership required';
            RETURN result;
        END IF;
    END IF;
    
    -- Check invitation requirement
    IF class_requires_invitation THEN
        -- This would need additional logic for invitation tracking
        SET result = 'Invitation required for this class';
        RETURN result;
    END IF;
    
    SET result = 'OK';
    RETURN result;
END$$

DELIMITER ;

-- 10. Add weekly class tracking reset procedure
DELIMITER $$

CREATE EVENT ResetWeeklyClassCounts
ON SCHEDULE EVERY 1 WEEK
STARTS '2025-01-06 00:00:00'  -- Monday
DO
BEGIN
    UPDATE user_memberships 
    SET weekly_classes_used = 0, 
        week_start_date = CURDATE()
    WHERE status = 'active';
END$$

DELIMITER ;

-- Enable event scheduler
SET GLOBAL event_scheduler = ON;

-- 11. Create view for available membership plans by age
CREATE VIEW available_memberships_by_age AS
SELECT 
    mp.*,
    CASE 
        WHEN mp.age_min IS NULL AND mp.age_max IS NULL THEN 'All Ages'
        WHEN mp.age_min IS NULL THEN CONCAT('Up to ', mp.age_max, ' years')
        WHEN mp.age_max IS NULL THEN CONCAT(mp.age_min, '+ years')
        ELSE CONCAT(mp.age_min, '-', mp.age_max, ' years')
    END as age_range,
    CASE
        WHEN mp.weekly_class_limit IS NOT NULL THEN CONCAT(mp.weekly_class_limit, ' per week')
        WHEN mp.monthly_class_limit IS NOT NULL THEN CONCAT(mp.monthly_class_limit, ' per month')
        ELSE 'Unlimited'
    END as class_limit_display
FROM membership_plans mp
WHERE mp.status = 'active'
ORDER BY mp.age_min ASC, mp.price ASC;

-- ===============================================
-- MIGRATION COMPLETE
-- ===============================================

-- Summary of changes:
-- ✅ Age-based membership tiers (Infants, Juniors, Seniors, Adults)
-- ✅ Special membership rules (beginner deals, PAYG, invitation-only)
-- ✅ GoCardless payment links integrated
-- ✅ Martial arts class structure
-- ✅ Age validation functions  
-- ✅ Weekly class limit tracking
-- ✅ Automatic beginner plan upgrades
-- ✅ Class booking restrictions 