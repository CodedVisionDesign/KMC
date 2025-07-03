<?php
/**
 * Martial Arts Membership System Functions
 * Handles age-based memberships, special rules, and class restrictions
 */

require_once __DIR__ . '/user_auth.php';

/**
 * Get user's age from date of birth
 */
function getUserAge($userId) {
    try {
        $pdo = connectUserDB();
        $stmt = $pdo->prepare("SELECT TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) as age FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result ? (int)$result['age'] : 0;
    } catch (Exception $e) {
        error_log('Error getting user age: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Get available membership plans for a user based on their age
 */
function getAvailablePlansForUser($userId) {
    try {
        $pdo = connectUserDB();
        $userAge = getUserAge($userId);
        
        $stmt = $pdo->prepare("
            SELECT *, 
                CASE 
                    WHEN age_min IS NULL AND age_max IS NULL THEN 'All Ages'
                    WHEN age_min IS NULL THEN CONCAT('Up to ', age_max, ' years')
                    WHEN age_max IS NULL THEN CONCAT(age_min, '+ years')
                    ELSE CONCAT(age_min, '-', age_max, ' years')
                END as age_range,
                CASE
                    WHEN weekly_class_limit IS NOT NULL THEN CONCAT(weekly_class_limit, ' per week')
                    WHEN monthly_class_limit IS NOT NULL THEN CONCAT(monthly_class_limit, ' per month')
                    ELSE 'Unlimited'
                END as limit_display
            FROM membership_plans 
            WHERE status = 'active' 
            AND (age_min IS NULL OR ? >= age_min)
            AND (age_max IS NULL OR ? <= age_max)
            ORDER BY price ASC
        ");
        $stmt->execute([$userAge, $userAge]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Error getting available plans: ' . $e->getMessage());
        return [];
    }
}

/**
 * Check if user can access a specific membership plan
 */
function canUserAccessPlan($userId, $planId) {
    try {
        $pdo = connectUserDB();
        $userAge = getUserAge($userId);
        
        $stmt = $pdo->prepare("
            SELECT age_min, age_max, requires_existing_membership 
            FROM membership_plans 
            WHERE id = ? AND status = 'active'
        ");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch();
        
        if (!$plan) {
            return ['canAccess' => false, 'reason' => 'Plan not found'];
        }
        
        // Check age restrictions
        if ($plan['age_min'] && $userAge < $plan['age_min']) {
            return ['canAccess' => false, 'reason' => "Must be at least {$plan['age_min']} years old"];
        }
        
        if ($plan['age_max'] && $userAge > $plan['age_max']) {
            return ['canAccess' => false, 'reason' => "Must be {$plan['age_max']} years or younger"];
        }
        
        // Check if requires existing membership (for PAYG)
        if ($plan['requires_existing_membership']) {
            $hasActiveMembership = getUserActiveMembership($userId);
            if (!$hasActiveMembership) {
                return ['canAccess' => false, 'reason' => 'Requires active membership'];
            }
        }
        
        return ['canAccess' => true, 'reason' => 'OK'];
    } catch (Exception $e) {
        error_log('Error checking plan access: ' . $e->getMessage());
        return ['canAccess' => false, 'reason' => 'Error checking eligibility'];
    }
}

/**
 * Get classes available for a specific user based on age and membership
 */
function getAvailableClassesForUser($userId) {
    try {
        $pdo = connectUserDB();
        $userAge = getUserAge($userId);
        $activeMembership = getUserActiveMembership($userId);
        
        $stmt = $pdo->prepare("
            SELECT c.*, i.first_name, i.last_name,
                CASE 
                    WHEN c.age_min IS NOT NULL AND c.age_max IS NOT NULL 
                    THEN CONCAT(c.age_min, '-', c.age_max, ' years')
                    WHEN c.age_min IS NOT NULL 
                    THEN CONCAT(c.age_min, '+ years')
                    ELSE 'All ages'
                END as age_range
            FROM classes c
            LEFT JOIN instructors i ON c.instructor_id = i.id
            WHERE c.date >= CURDATE()
            AND (c.age_min IS NULL OR ? >= c.age_min)
            AND (c.age_max IS NULL OR ? <= c.age_max)
            AND (c.requires_membership = 0 OR ? IS NOT NULL)
            ORDER BY c.date, c.time
        ");
        
        $membershipId = $activeMembership ? $activeMembership['id'] : null;
        $stmt->execute([$userAge, $userAge, $membershipId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Error getting available classes: ' . $e->getMessage());
        return [];
    }
}

/**
 * Check if user can book a specific class (with age and membership validation)
 */
function canUserBookSpecificClass($userId, $classId) {
    try {
        $pdo = connectUserDB();
        $userAge = getUserAge($userId);
        
        // Get class details
        $stmt = $pdo->prepare("
            SELECT age_min, age_max, requires_invitation, requires_membership, class_type
            FROM classes 
            WHERE id = ?
        ");
        $stmt->execute([$classId]);
        $class = $stmt->fetch();
        
        if (!$class) {
            return ['canBook' => false, 'reason' => 'Class not found'];
        }
        
        // Check age restrictions
        if ($class['age_min'] && $userAge < $class['age_min']) {
            return ['canBook' => false, 'reason' => "Age restriction: Must be at least {$class['age_min']} years old"];
        }
        
        if ($class['age_max'] && $userAge > $class['age_max']) {
            return ['canBook' => false, 'reason' => "Age restriction: Must be {$class['age_max']} years or younger"];
        }
        
        // Check membership requirement
        if ($class['requires_membership']) {
            $activeMembership = getUserActiveMembership($userId);
            if (!$activeMembership) {
                return ['canBook' => false, 'reason' => 'Active membership required'];
            }
            
            // Check if membership allows this class type
            if ($activeMembership['class_type_restriction']) {
                if ($activeMembership['class_type_restriction'] !== $class['class_type']) {
                    return ['canBook' => false, 'reason' => 'This membership does not allow access to this class type'];
                }
            }
            
            // Check weekly limits
            if ($activeMembership['weekly_class_limit']) {
                $weeklyUsed = getWeeklyClassesUsed($userId);
                if ($weeklyUsed >= $activeMembership['weekly_class_limit']) {
                    return ['canBook' => false, 'reason' => 'Weekly class limit reached'];
                }
            }
        }
        
        // Check invitation requirement
        if ($class['requires_invitation']) {
            // This would need additional invitation tracking logic
            return ['canBook' => false, 'reason' => 'Invitation required for this class'];
        }
        
        return ['canBook' => true, 'reason' => 'OK'];
    } catch (Exception $e) {
        error_log('Error checking class booking: ' . $e->getMessage());
        return ['canBook' => false, 'reason' => 'Error checking eligibility'];
    }
}

/**
 * Get weekly classes used by user
 */
function getWeeklyClassesUsed($userId) {
    try {
        $pdo = connectUserDB();
        
        // Get start of current week (Monday)
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM bookings b
            JOIN classes c ON b.class_id = c.id
            WHERE b.user_id = ? 
            AND c.date >= ?
            AND b.status = 'confirmed'
        ");
        $stmt->execute([$userId, $weekStart]);
        $result = $stmt->fetch();
        
        return $result ? (int)$result['count'] : 0;
    } catch (Exception $e) {
        error_log('Error getting weekly class count: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Create membership with age-based validation
 */
function createMembershipWithValidation($userId, $planId, $invitationCode = null) {
    try {
        $pdo = connectUserDB();
        $pdo->beginTransaction();
        
        // Check if user can access this plan
        $accessCheck = canUserAccessPlan($userId, $planId);
        if (!$accessCheck['canAccess']) {
            throw new Exception($accessCheck['reason']);
        }
        
        // Get plan details
        $stmt = $pdo->prepare("SELECT * FROM membership_plans WHERE id = ?");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch();
        
        if (!$plan) {
            throw new Exception('Plan not found');
        }
        
        // Calculate dates
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+1 month'));
        
        // Handle beginner plans
        $beginnerStartDate = null;
        $beginnerEndDate = null;
        $autoUpgradePlanId = null;
        
        if ($plan['is_beginner_only']) {
            $beginnerStartDate = $startDate;
            $beginnerEndDate = date('Y-m-d', strtotime("+{$plan['beginner_duration_weeks']} weeks"));
            $autoUpgradePlanId = $plan['auto_upgrade_plan_id'];
            $endDate = $beginnerEndDate; // Beginner plan ends after duration
        }
        
        // Create membership
        $stmt = $pdo->prepare("
            INSERT INTO user_memberships (
                user_id, plan_id, start_date, end_date, status,
                beginner_start_date, beginner_end_date, auto_upgrade_plan_id,
                invitation_code, week_start_date
            ) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId, $planId, $startDate, $endDate,
            $beginnerStartDate, $beginnerEndDate, $autoUpgradePlanId,
            $invitationCode, date('Y-m-d', strtotime('monday this week'))
        ]);
        
        $membershipId = $pdo->lastInsertId();
        $pdo->commit();
        
        return $membershipId;
    } catch (Exception $e) {
        $pdo->rollback();
        error_log('Error creating membership: ' . $e->getMessage());
        throw new Exception($e->getMessage());
    }
}

/**
 * Check and process beginner plan upgrades
 */
function processBeginnerUpgrades() {
    try {
        $pdo = connectUserDB();
        
        // Find memberships that need upgrading
        $stmt = $pdo->prepare("
            SELECT um.*, u.first_name, u.last_name, u.email
            FROM user_memberships um
            JOIN users u ON um.user_id = u.id
            JOIN membership_plans mp ON um.plan_id = mp.id
            WHERE mp.is_beginner_only = 1
            AND um.status = 'active'
            AND um.beginner_end_date <= CURDATE()
            AND um.auto_upgrade_plan_id IS NOT NULL
        ");
        $stmt->execute();
        $upgradeMembers = $stmt->fetchAll();
        
        foreach ($upgradeMembers as $member) {
            // Create new membership with upgraded plan
            $newStartDate = date('Y-m-d');
            $newEndDate = date('Y-m-d', strtotime('+1 month'));
            
            $stmt = $pdo->prepare("
                INSERT INTO user_memberships (
                    user_id, plan_id, start_date, end_date, status, week_start_date
                ) VALUES (?, ?, ?, ?, 'pending', ?)
            ");
            $stmt->execute([
                $member['user_id'], 
                $member['auto_upgrade_plan_id'],
                $newStartDate,
                $newEndDate,
                date('Y-m-d', strtotime('monday this week'))
            ]);
            
            // Expire old membership
            $stmt = $pdo->prepare("UPDATE user_memberships SET status = 'expired' WHERE id = ?");
            $stmt->execute([$member['id']]);
            
            // Log the upgrade
            error_log("Upgraded beginner membership for user {$member['user_id']} ({$member['email']})");
        }
        
        return count($upgradeMembers);
    } catch (Exception $e) {
        error_log('Error processing beginner upgrades: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Get user's current membership with age-appropriate details (enhanced version)
 */
function getUserActiveMembershipEnhanced($userId) {
    try {
        $pdo = connectUserDB();
        $stmt = $pdo->prepare("
            SELECT um.*, mp.name as plan_name, mp.description as plan_description, 
                   mp.monthly_class_limit, mp.weekly_class_limit, mp.price,
                   mp.is_beginner_only, mp.beginner_duration_weeks, mp.class_type_restriction,
                   mp.age_min, mp.age_max,
                   CASE 
                       WHEN mp.weekly_class_limit IS NOT NULL THEN CONCAT(mp.weekly_class_limit, ' per week')
                       WHEN mp.monthly_class_limit IS NOT NULL THEN CONCAT(mp.monthly_class_limit, ' per month')
                       ELSE 'Unlimited'
                   END as limit_display
            FROM user_memberships um
            JOIN membership_plans mp ON um.plan_id = mp.id
            WHERE um.user_id = ? 
            AND um.status = 'active'
            AND um.start_date <= CURDATE()
            AND um.end_date >= CURDATE()
            ORDER BY um.end_date DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Error getting user membership: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get membership statistics for admin dashboard
 */
function getMembershipStatistics() {
    try {
        $pdo = connectUserDB();
        
        $stats = [];
        
        // Total active memberships by age group
        $stmt = $pdo->query("
            SELECT 
                CASE 
                    WHEN mp.age_min IS NULL AND mp.age_max IS NULL THEN 'All Ages'
                    WHEN mp.age_min = 4 AND mp.age_max = 6 THEN 'Infants (4-6)'
                    WHEN mp.age_min = 7 AND mp.age_max = 11 THEN 'Juniors (7-11)'
                    WHEN mp.age_min = 11 AND mp.age_max = 15 THEN 'Seniors (11-15)'
                    WHEN mp.age_min = 15 THEN 'Adults (15+)'
                    ELSE 'Other'
                END as age_group,
                COUNT(*) as count,
                SUM(mp.price) as total_value
            FROM user_memberships um
            JOIN membership_plans mp ON um.plan_id = mp.id
            WHERE um.status = 'active'
            GROUP BY age_group
            ORDER BY mp.age_min ASC
        ");
        $stats['by_age_group'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Pending upgrades
        $stmt = $pdo->query("
            SELECT COUNT(*) as count
            FROM user_memberships um
            JOIN membership_plans mp ON um.plan_id = mp.id
            WHERE mp.is_beginner_only = 1
            AND um.status = 'active'
            AND um.beginner_end_date <= CURDATE()
        ");
        $result = $stmt->fetch();
        $stats['pending_upgrades'] = $result['count'];
        
        return $stats;
    } catch (Exception $e) {
        error_log('Error getting membership statistics: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get all membership plans with enhanced details for admin
 */
function getAllMembershipPlansForAdmin() {
    try {
        $pdo = connectUserDB();
        $stmt = $pdo->query("
            SELECT *,
                CASE 
                    WHEN age_min IS NULL AND age_max IS NULL THEN 'All Ages'
                    WHEN age_min IS NULL THEN CONCAT('Up to ', age_max, ' years')
                    WHEN age_max IS NULL THEN CONCAT(age_min, '+ years')
                    ELSE CONCAT(age_min, '-', age_max, ' years')
                END as age_range,
                CASE
                    WHEN weekly_class_limit IS NOT NULL THEN CONCAT(weekly_class_limit, ' per week')
                    WHEN monthly_class_limit IS NOT NULL THEN CONCAT(monthly_class_limit, ' per month')
                    ELSE 'Unlimited'
                END as limit_display,
                (SELECT COUNT(*) FROM user_memberships WHERE plan_id = membership_plans.id AND status = 'active') as active_members
            FROM membership_plans 
            ORDER BY age_min ASC, price ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Error getting admin membership plans: ' . $e->getMessage());
        return [];
    }
} 