<?php
/**
 * Membership System Functions
 * Handles membership validation, free trial logic, and booking limits
 */

require_once __DIR__ . '/user_auth.php';

/**
 * Check if user has used their free trial
 */
function hasUserUsedFreeTrial($userId) {
    try {
        $pdo = connectUserDB();
        $stmt = $pdo->prepare("SELECT free_trial_used FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result ? (bool)$result['free_trial_used'] : false;
    } catch (Exception $e) {
        error_log('Error checking free trial status: ' . $e->getMessage());
        return true; // Assume used on error for safety
    }
}

/**
 * Mark user's free trial as used
 */
function markFreeTrialAsUsed($userId) {
    try {
        $pdo = connectUserDB();
        $stmt = $pdo->prepare("UPDATE users SET free_trial_used = 1 WHERE id = ?");
        return $stmt->execute([$userId]);
    } catch (Exception $e) {
        error_log('Error marking free trial as used: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get user's active membership
 */
function getUserActiveMembership($userId) {
    try {
        $pdo = connectUserDB();
        $stmt = $pdo->prepare("
            SELECT um.*, mp.name as plan_name, mp.description as plan_description, 
                   mp.monthly_class_limit, mp.price
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
 * Get user's monthly class count for current or specified month
 */
function getUserMonthlyClassCount($userId, $yearMonth = null) {
    if ($yearMonth === null) {
        $yearMonth = date('Y-m');
    }
    
    try {
        $pdo = connectUserDB();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM bookings b
            JOIN classes c ON b.class_id = c.id
            WHERE b.user_id = ? 
            AND DATE_FORMAT(c.date, '%Y-%m') = ?
        ");
        $stmt->execute([$userId, $yearMonth]);
        $result = $stmt->fetch();
        return $result ? (int)$result['count'] : 0;
    } catch (Exception $e) {
        error_log('Error getting monthly class count: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Check if user can book a class
 */
function canUserBookClass($userId) {
    $hasUsedTrial = hasUserUsedFreeTrial($userId);
    
    // If hasn't used free trial, they can book
    if (!$hasUsedTrial) {
        return [
            'canBook' => true,
            'reason' => 'free_trial',
            'message' => 'You can book your free trial class!'
        ];
    }
    
    // Check for active membership
    $membership = getUserActiveMembership($userId);
    if (!$membership) {
        return [
            'canBook' => false,
            'reason' => 'no_membership',
            'message' => 'You need to purchase a membership to book classes.'
        ];
    }
    
    // If unlimited plan
    if ($membership['monthly_class_limit'] === null) {
        return [
            'canBook' => true,
            'reason' => 'unlimited',
            'message' => 'You can book unlimited classes!'
        ];
    }
    
    // Check monthly limit
    $currentMonth = date('Y-m');
    $classCount = getUserMonthlyClassCount($userId, $currentMonth);
    
    if ($classCount >= $membership['monthly_class_limit']) {
        return [
            'canBook' => false,
            'reason' => 'limit_reached',
            'message' => 'You have reached your monthly class limit (' . $membership['monthly_class_limit'] . ' classes).',
            'current_count' => $classCount,
            'limit' => $membership['monthly_class_limit']
        ];
    }
    
    return [
        'canBook' => true,
        'reason' => 'membership_valid',
        'message' => 'You can book classes! (' . $classCount . '/' . $membership['monthly_class_limit'] . ' used this month)',
        'current_count' => $classCount,
        'limit' => $membership['monthly_class_limit']
    ];
}

/**
 * Process a class booking with membership validation
 */
function processClassBooking($userId, $classId) {
    $bookingCheck = canUserBookClass($userId);
    
    if (!$bookingCheck['canBook']) {
        throw new Exception($bookingCheck['message']);
    }
    
    try {
        $pdo = connectUserDB();
        $pdo->beginTransaction();
        
        // Get user info
        $stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        // Get class date for membership cycle tracking
        $stmt = $pdo->prepare("SELECT date FROM classes WHERE id = ?");
        $stmt->execute([$classId]);
        $class = $stmt->fetch();
        $membershipCycle = date('Y-m', strtotime($class['date']));
        
        // Determine if this is a free trial booking
        $isFreeTrial = ($bookingCheck['reason'] === 'free_trial') ? 1 : 0;
        
        // Insert booking
        $stmt = $pdo->prepare("
            INSERT INTO bookings (class_id, user_id, name, email, membership_cycle, is_free_trial) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $classId,
            $userId,
            $user['first_name'] . ' ' . $user['last_name'],
            $user['email'],
            $membershipCycle,
            $isFreeTrial
        ]);
        
        // If this was a free trial, mark it as used
        if ($isFreeTrial) {
            markFreeTrialAsUsed($userId);
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log('Error processing class booking: ' . $e->getMessage());
        throw new Exception('Failed to process booking: ' . $e->getMessage());
    }
}

/**
 * Get all available membership plans
 */
function getAvailableMembershipPlans() {
    try {
        $pdo = connectUserDB();
        $stmt = $pdo->prepare("
            SELECT * FROM membership_plans 
            WHERE status = 'active' 
            ORDER BY price ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Error getting membership plans: ' . $e->getMessage());
        return [];
    }
}

/**
 * Create a new membership for a user
 */
function createUserMembership($userId, $planId, $startDate = null, $duration = 1) {
    if ($startDate === null) {
        $startDate = date('Y-m-d');
    }
    
    $endDate = date('Y-m-d', strtotime($startDate . ' +' . $duration . ' month'));
    
    try {
        $pdo = connectUserDB();
        
        // Check if user already has a pending or active membership
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM user_memberships 
            WHERE user_id = ? AND status IN ('pending', 'active')
        ");
        $stmt->execute([$userId]);
        $existingMemberships = $stmt->fetch()['count'];
        
        if ($existingMemberships > 0) {
            throw new Exception('You already have a pending or active membership request. Please wait for it to be processed or contact support.');
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO user_memberships (user_id, plan_id, start_date, end_date, status) 
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$userId, $planId, $startDate, $endDate]);
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log('Error creating user membership: ' . $e->getMessage());
        throw new Exception($e->getMessage());
    }
}

/**
 * Record a membership payment
 */
function recordMembershipPayment($userMembershipId, $amount, $paymentMethod, $adminId = null, $notes = '') {
    try {
        $pdo = connectUserDB();
        $stmt = $pdo->prepare("
            INSERT INTO membership_payments (
                user_membership_id, amount, payment_date, payment_method, 
                status, notes, recorded_by_admin_id
            ) VALUES (?, ?, NOW(), ?, 'paid', ?, ?)
        ");
        $stmt->execute([$userMembershipId, $amount, $paymentMethod, $notes, $adminId]);
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log('Error recording payment: ' . $e->getMessage());
        throw new Exception('Failed to record payment: ' . $e->getMessage());
    }
}

/**
 * Get user's membership status for display
 */
function getUserMembershipStatus($userId) {
    $membership = getUserActiveMembership($userId);
    $hasUsedTrial = hasUserUsedFreeTrial($userId);
    
    if (!$hasUsedTrial) {
        return [
            'status' => 'free_trial_available',
            'message' => 'Free trial available',
            'classes_remaining' => 1
        ];
    }
    
    if (!$membership) {
        return null; // Return null when no membership, let the page handle it
    }
    
    $currentMonth = date('Y-m');
    $classCount = getUserMonthlyClassCount($userId, $currentMonth);
    
    // Return complete membership details expected by the UI
    return [
        'status' => 'active',
        'plan_id' => $membership['plan_id'],
        'plan_name' => $membership['plan_name'],
        'plan_description' => isset($membership['plan_description']) ? $membership['plan_description'] : '',
        'start_date' => $membership['start_date'],
        'end_date' => $membership['end_date'],
        'monthly_class_limit' => $membership['monthly_class_limit'],
        'classes_used_this_month' => $classCount,
        'classes_remaining' => $membership['monthly_class_limit'] ? max(0, $membership['monthly_class_limit'] - $classCount) : null
    ];
}

/**
 * Get user's simple membership status for dashboard display
 */
function getUserMembershipStatusSimple($userId) {
    $membership = getUserActiveMembership($userId);
    $hasUsedTrial = hasUserUsedFreeTrial($userId);
    
    if (!$hasUsedTrial) {
        return [
            'status' => 'free_trial_available',
            'message' => 'Free trial available',
            'classes_remaining' => 1
        ];
    }
    
    if (!$membership) {
        return [
            'status' => 'no_membership',
            'message' => 'No active membership',
            'classes_remaining' => 0
        ];
    }
    
    $currentMonth = date('Y-m');
    $classCount = getUserMonthlyClassCount($userId, $currentMonth);
    
    if ($membership['monthly_class_limit'] === null) {
        return [
            'status' => 'unlimited',
            'message' => 'Unlimited membership',
            'plan_name' => $membership['plan_name'],
            'classes_used' => $classCount,
            'end_date' => $membership['end_date']
        ];
    }
    
    $remaining = max(0, $membership['monthly_class_limit'] - $classCount);
    
    return [
        'status' => 'active',
        'message' => $membership['plan_name'],
        'plan_name' => $membership['plan_name'],
        'classes_used' => $classCount,
        'classes_limit' => $membership['monthly_class_limit'],
        'classes_remaining' => $remaining,
        'end_date' => $membership['end_date']
    ];
}

/**
 * Check if user has access to video content
 */
function userHasVideoAccess($userId) {
    // Free trial users get video access
    if (!hasUserUsedFreeTrial($userId)) {
        return true;
    }
    
    // Users with active membership get access
    $membership = getUserActiveMembership($userId);
    return $membership !== null;
}
?> 