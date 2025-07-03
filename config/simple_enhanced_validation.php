<?php
/**
 * Simplified Enhanced Validation Functions
 * Works with current database schema
 */

require_once __DIR__ . '/user_auth.php';
require_once __DIR__ . '/membership_functions.php';

/**
 * Get user age from date_of_birth
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
 * Get weekly classes used by user for a specific week (Monday to Sunday)
 * Excludes private classes as they don't count toward weekly limits
 */
function getWeeklyClassesUsedForWeek($userId, $weekStart) {
    try {
        $pdo = connectUserDB();
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM bookings b
            JOIN classes c ON b.class_id = c.id
            WHERE b.user_id = ? 
            AND c.date >= ?
            AND c.date < DATE_ADD(?, INTERVAL 7 DAY)
            AND b.status != 'cancelled'
            AND c.name NOT LIKE '%Private%'
            AND c.name NOT LIKE '%1-1%'
            AND c.name NOT LIKE '%One-on-One%'
        ");
        $stmt->execute([$userId, $weekStart, $weekStart]);
        $result = $stmt->fetch();
        
        return $result ? (int)$result['count'] : 0;
    } catch (Exception $e) {
        error_log('Error getting weekly class count: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Get weekly classes used by user (Monday to Sunday) - for current week
 * Excludes private classes as they don't count toward weekly limits
 */
function getWeeklyClassesUsed($userId) {
    // Get start of current week (Monday)
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    return getWeeklyClassesUsedForWeek($userId, $weekStart);
}

/**
 * Get user's active membership details
 */
function getActiveMembership($userId) {
    try {
        $pdo = connectUserDB();
        
        $stmt = $pdo->prepare("
            SELECT um.*, mp.name as plan_name, mp.weekly_class_limit, mp.monthly_class_limit
            FROM user_memberships um
            JOIN membership_plans mp ON um.plan_id = mp.id
            WHERE um.user_id = ? 
            AND um.status = 'active'
            AND um.end_date > CURDATE()
            ORDER BY um.end_date DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return $result ? $result : null;
    } catch (Exception $e) {
        error_log('Error getting active membership: ' . $e->getMessage());
        return null;
    }
}

/**
 * Enhanced validation function that works with current schema
 */
function canUserBookSpecificClass($userId, $classId) {
    try {
        $pdo = connectUserDB();
        $userAge = getUserAge($userId);
        
        // Get class details (only columns that exist)
        $stmt = $pdo->prepare("
            SELECT age_min, age_max, trial_eligible, name, date
            FROM classes 
            WHERE id = ?
        ");
        $stmt->execute([$classId]);
        $class = $stmt->fetch();
        
        if (!$class) {
            return ['canBook' => false, 'reason' => 'Class not found'];
        }
        
        // Check if this is a private class (private classes don't count toward weekly limits)
        $isPrivateClass = (
            strpos($class['name'], 'Private') !== false ||
            strpos($class['name'], '1-1') !== false ||
            strpos($class['name'], 'One-on-One') !== false
        );
        
        // Get the week start date for the class being booked
        $classDate = $class['date'];
        $classWeekStart = date('Y-m-d', strtotime('monday this week', strtotime($classDate)));
        
        // Check age restrictions
        if ($class['age_min'] && $userAge < $class['age_min']) {
            return ['canBook' => false, 'reason' => "Age restriction: Must be at least {$class['age_min']} years old"];
        }
        
        if ($class['age_max'] && $userAge > $class['age_max']) {
            return ['canBook' => false, 'reason' => "Age restriction: Must be {$class['age_max']} years or younger"];
        }
        
        // Get user's active membership
        $activeMembership = getActiveMembership($userId);
        
        // Check if user has free trial available for trial-eligible classes
        if ($class['trial_eligible']) {
            $hasUsedTrial = hasUserUsedFreeTrial($userId);
            if (!$hasUsedTrial) {
                // Free trial available - allow booking
                return ['canBook' => true, 'reason' => 'free_trial'];
            }
        }
        
        // Check if membership is required (all non-trial classes require membership)
        if (!$activeMembership) {
            return ['canBook' => false, 'reason' => 'Active membership required'];
        }
        
        // Check weekly limits if membership has them (skip for private classes)
        if (!$isPrivateClass && $activeMembership && isset($activeMembership['weekly_class_limit']) && $activeMembership['weekly_class_limit'] > 0) {
            $weeklyUsed = getWeeklyClassesUsedForWeek($userId, $classWeekStart);
            if ($weeklyUsed >= $activeMembership['weekly_class_limit']) {
                return [
                    'canBook' => false, 
                    'reason' => 'Weekly class limit reached (' . $weeklyUsed . '/' . $activeMembership['weekly_class_limit'] . ' for week of ' . $classWeekStart . ')',
                    'current_count' => $weeklyUsed,
                    'limit' => $activeMembership['weekly_class_limit'],
                    'period' => 'week'
                ];
            }
            
            return [
                'canBook' => true, 
                'reason' => 'OK (' . $weeklyUsed . '/' . $activeMembership['weekly_class_limit'] . ' for week of ' . $classWeekStart . ')',
                'current_count' => $weeklyUsed,
                'limit' => $activeMembership['weekly_class_limit'],
                'period' => 'week'
            ];
        }
        
        // Check monthly limits if no weekly limit (skip for private classes)
        if (!$isPrivateClass && $activeMembership && isset($activeMembership['monthly_class_limit']) && $activeMembership['monthly_class_limit'] > 0) {
            $monthlyUsed = getUserMonthlyClassCount($userId);
            if ($monthlyUsed >= $activeMembership['monthly_class_limit']) {
                return [
                    'canBook' => false, 
                    'reason' => 'Monthly class limit reached',
                    'current_count' => $monthlyUsed,
                    'limit' => $activeMembership['monthly_class_limit'],
                    'period' => 'month'
                ];
            }
            
            return [
                'canBook' => true, 
                'reason' => 'OK',
                'current_count' => $monthlyUsed,
                'limit' => $activeMembership['monthly_class_limit'],
                'period' => 'month'
            ];
        }
        
        // Private classes are always allowed (if user has active membership) or unlimited membership or no limits
        if ($isPrivateClass) {
            return ['canBook' => true, 'reason' => 'Private class - no weekly limit'];
        }
        
        // Unlimited membership or no limits
        return ['canBook' => true, 'reason' => 'OK'];
        
    } catch (Exception $e) {
        error_log('Error checking enhanced class booking: ' . $e->getMessage());
        return ['canBook' => false, 'reason' => 'Error checking eligibility'];
    }
}
?> 