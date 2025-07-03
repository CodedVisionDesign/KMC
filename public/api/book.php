<?php
/**
 * Enhanced Class Booking API with Age Restrictions and Performance Optimizations
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Start output buffering to prevent any accidental output
ob_start();

// Set error reporting to not display errors (they'll still be logged)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

try {
    // Include required files
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/../../config/user_auth.php';
    require_once __DIR__ . '/../../config/membership_functions.php';
    
    // Include simplified enhanced validation that works with current schema
    if (file_exists(__DIR__ . '/../../config/simple_enhanced_validation.php')) {
        require_once __DIR__ . '/../../config/simple_enhanced_validation.php';
    }
    
    // Clear any output that might have been generated
    ob_clean();
    
    // Check if user is logged in
    if (!isUserLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'You must be logged in to book a class',
            'redirect' => 'login.php'
        ]);
        exit();
    }
    
    // Get user info
    $userInfo = getUserInfo();
    if (!$userInfo) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid user session',
            'redirect' => 'login.php'
        ]);
        exit();
    }
    
    // Get POST data
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    
    // Log JSON decode error if any
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Booking API - JSON decode error: " . json_last_error_msg());
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
        exit;
    }
    
    $class_id = isset($data['class_id']) ? intval($data['class_id']) : 0;
    
    if ($class_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid class ID']);
        exit;
    }
    
    // Start transaction for atomic booking
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    try {
        // Get class details with enhanced information
        $stmt = $pdo->prepare("
            SELECT c.*, i.first_name as instructor_first_name, i.last_name as instructor_last_name
            FROM classes c
            LEFT JOIN instructors i ON c.instructor_id = i.id
            WHERE c.id = ?
        ");
        $stmt->execute([$class_id]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$class) {
            $pdo->rollback();
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Class not found']);
            exit;
        }
        
        // Check if class is in the past
        $classDateTime = new DateTime($class['date'] . ' ' . $class['time']);
        $now = new DateTime();
        
        if ($classDateTime < $now) {
            $pdo->rollback();
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'error' => 'Cannot book classes that have already started or passed',
                'class_datetime' => $classDateTime->format('M j, Y g:i A')
            ]);
            exit;
        }
        
        // Check if user has already booked this class
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM bookings WHERE class_id = ? AND user_id = ?');
        $stmt->execute([$class_id, $userInfo['id']]);
        $existingBooking = $stmt->fetch();
        
        if ($existingBooking['count'] > 0) {
            $pdo->rollback();
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'You have already booked this class']);
            exit;
        }
        
        // Check class capacity
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM bookings WHERE class_id = ?');
        $stmt->execute([$class_id]);
        $bookingCount = $stmt->fetch();
        
        if ($bookingCount['count'] >= $class['capacity']) {
            $pdo->rollback();
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Class is fully booked']);
            exit;
        }
        
        // Enhanced validation with age restrictions and weekly limits
        $canBook = null;
        
        // Use enhanced validation if available (checks age restrictions and weekly limits)
        if (function_exists('canUserBookSpecificClass')) {
            error_log("Booking API - Using enhanced validation with age/weekly limits");
            $canBook = canUserBookSpecificClass($userInfo['id'], $class_id);
        } else {
            error_log("Booking API - Using basic validation (no age/weekly limit checks)");
            $canBook = canUserBookClass($userInfo['id']);
        }
        
        error_log("Booking API - Validation result: " . json_encode($canBook));
        
        if (!$canBook['canBook']) {
            $pdo->rollback();
            error_log("Booking API - Booking denied: " . ($canBook['reason'] ?? 'Unknown reason'));
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'error' => $canBook['reason'] ?? 'Booking not allowed',
                'redirect' => (isset($canBook['reason']) && strpos($canBook['reason'], 'membership') !== false) ? 'user/membership.php' : null
            ]);
            exit;
        }
        
        // Prepare booking data
        $userId = $userInfo['id'];
        $membershipCycle = date('Y-m', strtotime($class['date']));
        $isTrialBooking = (isset($canBook['reason']) && $canBook['reason'] === 'free_trial');
        
        // Create booking with proper error handling
        $stmt = $pdo->prepare("
            INSERT INTO bookings (class_id, user_id, name, email, membership_cycle, is_free_trial, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'confirmed')
        ");
        
        $fullName = trim($userInfo['first_name'] . ' ' . ($userInfo['last_name'] ?? ''));
        
        $stmt->execute([
            $class_id,
            $userId,
            $fullName,
            $userInfo['email'],
            $membershipCycle,
            $isTrialBooking ? 1 : 0
        ]);
        
        // Mark trial as used if this was a trial booking
        if ($isTrialBooking) {
            try {
                $trialStmt = $pdo->prepare("UPDATE users SET free_trial_used = 1 WHERE id = ?");
                $trialStmt->execute([$userId]);
            } catch (Exception $e) {
                error_log('Error marking free trial as used: ' . $e->getMessage());
                // Don't fail the booking for this, just log it
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Prepare success response
        $response = [
            'success' => true, 
            'message' => 'Class booked successfully!'
        ];
        
        // Add context based on booking type
        if ($isTrialBooking) {
            $response['message'] = 'Free trial class booked successfully!';
            $response['trial_used'] = true;
        } else {
            // Check if we have limit information to display
            if (isset($canBook['current_count']) && isset($canBook['limit'])) {
                $remaining = $canBook['limit'] - ($canBook['current_count'] + 1);
                $period = $canBook['period'] ?? 'month';
                $response['message'] = "Class booked successfully! You have $remaining classes remaining this $period.";
                $response['remaining_classes'] = $remaining;
            } else {
                // Check for weekly limits via the enhanced membership system
                if (function_exists('getWeeklyClassesUsed') && function_exists('getUserActiveMembership')) {
                    $activeMembership = getUserActiveMembership($userInfo['id']);
                    if ($activeMembership && isset($activeMembership['weekly_class_limit'])) {
                        $weeklyUsed = getWeeklyClassesUsed($userInfo['id']);
                        $remaining = $activeMembership['weekly_class_limit'] - ($weeklyUsed + 1);
                        $response['message'] = "Class booked successfully! You have $remaining classes remaining this week.";
                        $response['remaining_classes'] = $remaining;
                        $response['period'] = 'week';
                    }
                }
            }
        }
        
        // Add class information to response
        $response['class_info'] = [
            'id' => $class['id'],
            'name' => $class['name'],
            'date' => $class['date'],
            'time' => $class['time'],
            'instructor' => trim(($class['instructor_first_name'] ?? '') . ' ' . ($class['instructor_last_name'] ?? ''))
        ];
        
        echo json_encode($response);
        
    } catch (PDOException $e) {
        $pdo->rollback();
        error_log('Database error in booking: ' . $e->getMessage());
        
        // Handle specific database errors
        if ($e->getCode() == 23000) { // Integrity constraint violation
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Booking conflict - please try again']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error occurred']);
        }
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        error_log('Error in booking process: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to process booking']);
        exit;
    }
    
} catch (Exception $e) {
    // Clear any output buffer
    ob_clean();
    
    error_log('Fatal error in booking API: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error occurred']);
    exit;
} finally {
    // End output buffering
    ob_end_flush();
}