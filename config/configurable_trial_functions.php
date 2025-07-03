<?php
/**
 * Configurable Trial System Functions
 * Admin-configurable trial limits and management
 */

require_once __DIR__ . '/user_auth.php';

/**
 * Get trial setting value
 */
function getTrialSetting($settingName, $defaultValue = null) {
    try {
        $pdo = connectUserDB();
        $stmt = $pdo->prepare("SELECT setting_value FROM trial_settings WHERE setting_name = ?");
        $stmt->execute([$settingName]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $defaultValue;
    } catch (Exception $e) {
        error_log('Error getting trial setting: ' . $e->getMessage());
        return $defaultValue;
    }
}

/**
 * Update trial setting
 */
function updateTrialSetting($settingName, $settingValue, $adminId = null) {
    try {
        $pdo = connectUserDB();
        $stmt = $pdo->prepare("
            UPDATE trial_settings 
            SET setting_value = ?, updated_by_admin_id = ?, updated_at = NOW() 
            WHERE setting_name = ?
        ");
        return $stmt->execute([$settingValue, $adminId, $settingName]);
    } catch (Exception $e) {
        error_log('Error updating trial setting: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check if trial system is enabled
 */
function isTrialSystemEnabled() {
    return (bool) getTrialSetting('trial_system_enabled', 1);
}

/**
 * Get maximum trial classes per user
 */
function getTrialClassesPerUser() {
    return (int) getTrialSetting('trial_classes_per_user', 1);
}

/**
 * Check if user has available trial classes (NEW CONFIGURABLE VERSION)
 */
function hasUserTrialClassesAvailable($userId) {
    try {
        // Check if trial system is enabled
        if (!isTrialSystemEnabled()) {
            return false;
        }
        
        $pdo = connectUserDB();
        $stmt = $pdo->prepare("SELECT trial_classes_used FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return false;
        }
        
        $classesUsed = (int) $result['trial_classes_used'];
        $classesAllowed = getTrialClassesPerUser();
        
        return $classesUsed < $classesAllowed;
        
    } catch (Exception $e) {
        error_log('Error checking trial availability: ' . $e->getMessage());
        return false; // Assume no trial available on error for safety
    }
}

/**
 * Get user's trial status details
 */
function getUserTrialStatus($userId) {
    try {
        $pdo = connectUserDB();
        $stmt = $pdo->prepare("
            SELECT trial_classes_used, trial_reset_count, trial_last_reset_date, free_trial_used 
            FROM users WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return null;
        }
        
        $maxClasses = getTrialClassesPerUser();
        $classesUsed = (int) $result['trial_classes_used'];
        $remaining = max(0, $maxClasses - $classesUsed);
        
        return [
            'classes_used' => $classesUsed,
            'classes_allowed' => $maxClasses,
            'classes_remaining' => $remaining,
            'reset_count' => (int) $result['trial_reset_count'],
            'last_reset_date' => $result['trial_last_reset_date'],
            'legacy_trial_used' => (bool) $result['free_trial_used'],
            'has_trial_available' => $remaining > 0
        ];
        
    } catch (Exception $e) {
        error_log('Error getting user trial status: ' . $e->getMessage());
        return null;
    }
}

/**
 * Reset user's trial eligibility (ADMIN FUNCTION)
 */
function resetUserTrial($userId, $adminId, $notes = '') {
    try {
        $pdo = connectUserDB();
        $pdo->beginTransaction();
        
        // Get current trial status for logging
        $currentStatus = getUserTrialStatus($userId);
        
        // Reset trial usage
        $stmt = $pdo->prepare("
            UPDATE users 
            SET trial_classes_used = 0, 
                trial_reset_count = trial_reset_count + 1,
                trial_last_reset_date = NOW(),
                trial_reset_by_admin_id = ?
            WHERE id = ?
        ");
        $stmt->execute([$adminId, $userId]);
        
        // Log the action
        $stmt = $pdo->prepare("
            INSERT INTO trial_management_log (user_id, admin_id, action_type, old_value, new_value, notes)
            VALUES (?, ?, 'reset_trial', ?, '0', ?)
        ");
        $stmt->execute([
            $userId, 
            $adminId, 
            $currentStatus ? $currentStatus['classes_used'] : '0',
            $notes
        ]);
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log('Error resetting user trial: ' . $e->getMessage());
        return false;
    }
}

/**
 * Bulk reset all user trials (ADMIN FUNCTION)
 */
function bulkResetAllTrials($adminId, $notes = '') {
    try {
        $pdo = connectUserDB();
        $pdo->beginTransaction();
        
        // Reset all users
        $stmt = $pdo->prepare("
            UPDATE users 
            SET trial_classes_used = 0,
                trial_reset_count = trial_reset_count + 1,
                trial_last_reset_date = NOW(),
                trial_reset_by_admin_id = ?
        ");
        $stmt->execute([$adminId]);
        $affectedRows = $stmt->rowCount();
        
        // Log the bulk action
        $stmt = $pdo->prepare("
            INSERT INTO trial_management_log (user_id, admin_id, action_type, old_value, new_value, notes)
            VALUES (0, ?, 'reset_trial', 'bulk_reset', ?, ?)
        ");
        $stmt->execute([$adminId, $affectedRows, $notes]);
        
        $pdo->commit();
        return $affectedRows;
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log('Error bulk resetting trials: ' . $e->getMessage());
        return false;
    }
}

/**
 * Process a trial class booking (UPDATED VERSION)
 */
function processTrialClassBooking($userId, $classId) {
    try {
        if (!hasUserTrialClassesAvailable($userId)) {
            throw new Exception('No trial classes available');
        }
        
        $pdo = connectUserDB();
        $pdo->beginTransaction();
        
        // Get user and class info
        $stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT date FROM classes WHERE id = ?");
        $stmt->execute([$classId]);
        $class = $stmt->fetch();
        
        $membershipCycle = date('Y-m', strtotime($class['date']));
        
        // Insert booking
        $stmt = $pdo->prepare("
            INSERT INTO bookings (class_id, user_id, name, email, membership_cycle, is_free_trial, status) 
            VALUES (?, ?, ?, ?, ?, 1, 'confirmed')
        ");
        $stmt->execute([
            $classId,
            $userId,
            $user['first_name'] . ' ' . $user['last_name'],
            $user['email'],
            $membershipCycle
        ]);
        
        // Update user's trial usage
        $stmt = $pdo->prepare("
            UPDATE users 
            SET trial_classes_used = trial_classes_used + 1,
                free_trial_used = 1
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log('Error processing trial booking: ' . $e->getMessage());
        throw new Exception('Failed to process trial booking: ' . $e->getMessage());
    }
}

/**
 * Updated canUserBookClass function with configurable trials
 */
function canUserBookClassConfigurable($userId) {
    // Check trial availability first
    if (hasUserTrialClassesAvailable($userId)) {
        $trialStatus = getUserTrialStatus($userId);
        return [
            'canBook' => true,
            'reason' => 'free_trial',
            'message' => "You can book your free trial class! ({$trialStatus['classes_remaining']} remaining)",
            'trial_remaining' => $trialStatus['classes_remaining'],
            'trial_total' => $trialStatus['classes_allowed']
        ];
    }
    
    // Check for active membership (existing logic)
    $membership = getUserActiveMembership($userId);
    if (!$membership) {
        return [
            'canBook' => false,
            'reason' => 'no_membership',
            'message' => 'You need to purchase a membership to book classes.'
        ];
    }
    
    // If unlimited plan
    if ($membership['weekly_class_limit'] === null && $membership['monthly_class_limit'] === null) {
        return [
            'canBook' => true,
            'reason' => 'unlimited',
            'message' => 'You can book unlimited classes!'
        ];
    }
    
    // Check weekly limit (priority over monthly)
    if ($membership['weekly_class_limit']) {
        $currentWeek = date('Y-W');
        $weeklyCount = getUserWeeklyClassCount($userId, $currentWeek);
        
        if ($weeklyCount >= $membership['weekly_class_limit']) {
            return [
                'canBook' => false,
                'reason' => 'weekly_limit_reached',
                'message' => 'You have reached your weekly class limit (' . $membership['weekly_class_limit'] . ' classes).',
                'current_count' => $weeklyCount,
                'limit' => $membership['weekly_class_limit']
            ];
        }
        
        return [
            'canBook' => true,
            'reason' => 'membership_valid',
            'message' => 'You can book classes! (' . $weeklyCount . '/' . $membership['weekly_class_limit'] . ' used this week)',
            'current_count' => $weeklyCount,
            'limit' => $membership['weekly_class_limit'],
            'period' => 'week'
        ];
    }
    
    // Check monthly limit
    if ($membership['monthly_class_limit']) {
        $currentMonth = date('Y-m');
        $monthlyCount = getUserMonthlyClassCount($userId, $currentMonth);
        
        if ($monthlyCount >= $membership['monthly_class_limit']) {
            return [
                'canBook' => false,
                'reason' => 'monthly_limit_reached',
                'message' => 'You have reached your monthly class limit (' . $membership['monthly_class_limit'] . ' classes).',
                'current_count' => $monthlyCount,
                'limit' => $membership['monthly_class_limit']
            ];
        }
        
        return [
            'canBook' => true,
            'reason' => 'membership_valid',
            'message' => 'You can book classes! (' . $monthlyCount . '/' . $membership['monthly_class_limit'] . ' used this month)',
            'current_count' => $monthlyCount,
            'limit' => $membership['monthly_class_limit'],
            'period' => 'month'
        ];
    }
    
    // Default to allowing booking if no limits set
    return [
        'canBook' => true,
        'reason' => 'membership_valid',
        'message' => 'You can book classes!'
    ];
}

/**
 * Get weekly class count for user
 */
function getUserWeeklyClassCount($userId, $weekYear) {
    try {
        $pdo = connectUserDB();
        
        // Convert week format to date range
        $year = substr($weekYear, 0, 4);
        $week = substr($weekYear, 5);
        
        $startDate = date('Y-m-d', strtotime($year . 'W' . $week . '1'));
        $endDate = date('Y-m-d', strtotime($year . 'W' . $week . '7'));
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM bookings b
            JOIN classes c ON b.class_id = c.id
            WHERE b.user_id = ? 
            AND c.date BETWEEN ? AND ?
            AND b.status = 'confirmed'
        ");
        $stmt->execute([$userId, $startDate, $endDate]);
        return (int) $stmt->fetchColumn();
        
    } catch (Exception $e) {
        error_log('Error getting weekly class count: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Get all trial settings for admin display
 */
function getAllTrialSettings() {
    try {
        $pdo = connectUserDB();
        $stmt = $pdo->query("SELECT * FROM trial_settings ORDER BY setting_name");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Error getting trial settings: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get trial management log
 */
function getTrialManagementLog($userId = null, $adminId = null, $limit = 50) {
    try {
        $pdo = connectUserDB();
        
        $where = [];
        $params = [];
        
        if ($userId) {
            $where[] = "tml.user_id = ?";
            $params[] = $userId;
        }
        
        if ($adminId) {
            $where[] = "tml.admin_id = ?";
            $params[] = $adminId;
        }
        
        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        
        $stmt = $pdo->prepare("
            SELECT tml.*, u.email as user_email, u.first_name, u.last_name,
                   a.username as admin_username
            FROM trial_management_log tml
            LEFT JOIN users u ON tml.user_id = u.id
            LEFT JOIN admin a ON tml.admin_id = a.id
            $whereClause
            ORDER BY tml.created_at DESC
            LIMIT ?
        ");
        
        $params[] = $limit;
        $stmt->execute($params);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log('Error getting trial log: ' . $e->getMessage());
        return [];
    }
}
?> 