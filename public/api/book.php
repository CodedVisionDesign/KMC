<?php
// Include error handling configuration first
require_once __DIR__ . '/../../config/error_handling.php';

header('Content-Type: application/json');

// Create database connection directly
function getDBConnection() {
    $host = 'localhost';
    $db   = 'testbook'; // Change to your database name
    $user = 'root';    // Change to your DB user
    $pass = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        throw new Exception('Database connection failed');
    }
}

try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    echo ErrorMessages::apiError(ErrorMessages::DATABASE_ERROR, 500);
    exit;
}

// Include required files
if (file_exists(__DIR__ . '/../../config/user_auth.php')) {
    require_once __DIR__ . '/../../config/user_auth.php';
} else {
    error_log('User authentication file not found');
    echo ErrorMessages::apiError(ErrorMessages::SYSTEM_UNAVAILABLE, 500);
    exit;
}

if (file_exists(__DIR__ . '/../../config/membership_functions.php')) {
    require_once __DIR__ . '/../../config/membership_functions.php';
} else {
    error_log('Membership functions file not found');
    echo ErrorMessages::apiError(ErrorMessages::SYSTEM_UNAVAILABLE, 500);
    exit;
}

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

// Get user info and POST data
$userInfo = getUserInfo();
$data = json_decode(file_get_contents('php://input'), true);
$class_id = isset($data['class_id']) ? intval($data['class_id']) : 0;

// Debug logging
error_log("Booking API - User ID: " . $userInfo['id'] . ", Email: " . $userInfo['email'] . ", Class ID: $class_id");

if ($class_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid class ID']);
    exit;
}

try {
    // Start transaction for atomic booking
    $pdo->beginTransaction();
    
    // Check class exists and get details
    $stmt = $pdo->prepare('SELECT * FROM classes WHERE id = ?');
    $stmt->execute([$class_id]);
    $class = $stmt->fetch();
    if (!$class) {
        $pdo->rollback();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Class not found']);
        exit;
    }
    
    $capacity = $class['capacity'];
    $classDate = $class['date'];

    // Check if user already booked this class (with FOR UPDATE lock to prevent race conditions)
    $stmt = $pdo->prepare('SELECT id FROM bookings WHERE class_id = ? AND user_id = ? FOR UPDATE');
    $stmt->execute([$class_id, $userInfo['id']]);
    $existingBooking = $stmt->fetch();
    if ($existingBooking) {
        error_log("Booking API - Duplicate booking detected: User " . $userInfo['id'] . " already has booking ID " . $existingBooking['id'] . " for class $class_id");
        $pdo->rollback();
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'You have already booked this class']);
        exit;
    }
    
    error_log("Booking API - No existing booking found for user " . $userInfo['id'] . " and class $class_id");

    // Count current bookings (with FOR UPDATE lock)
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE class_id = ? FOR UPDATE');
    $stmt->execute([$class_id]);
    $count = $stmt->fetchColumn();
    if ($count >= $capacity) {
        $pdo->rollback();
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Class is fully booked']);
        exit;
    }

    // *** MEMBERSHIP VALIDATION ***
    $userId = $userInfo['id'];
    
    // Check if user can book the class (handles free trial and membership limits)
    $canBook = canUserBookClass($userId);
    
    if (!$canBook['canBook']) {
        $pdo->rollback();
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'error' => $canBook['message'],
            'redirect' => ($canBook['reason'] === 'no_membership') ? 'user/membership.php' : null
        ]);
        exit;
    }

    // Create booking directly here instead of using processClassBooking to avoid duplicate checks
    try {
        // Get user info
        $stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        // Determine membership cycle and if this is a free trial
        $membershipCycle = date('Y-m', strtotime($classDate));
        $isFreeTrial = ($canBook['reason'] === 'free_trial') ? 1 : 0;
        
        // Insert booking
        $stmt = $pdo->prepare("
            INSERT INTO bookings (class_id, user_id, name, email, membership_cycle, is_free_trial, booking_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), 'confirmed')
        ");
        $stmt->execute([
            $class_id,
            $userId,
            $user['first_name'] . ' ' . $user['last_name'],
            $user['email'],
            $membershipCycle,
            $isFreeTrial
        ]);
        
        // If this was a free trial, mark it as used
        if ($isFreeTrial) {
            $stmt = $pdo->prepare("UPDATE users SET free_trial_used = 1 WHERE id = ?");
            $stmt->execute([$userId]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Success response
        $response = [
            'success' => true, 
            'message' => 'Class booked successfully!'
        ];
        
        // Add context based on booking reason
        if ($canBook['reason'] === 'free_trial') {
            $response['message'] = 'Free trial class booked successfully!';
            $response['free_trial_used'] = true;
        } elseif (isset($canBook['current_count']) && isset($canBook['limit'])) {
            $remaining = $canBook['limit'] - ($canBook['current_count'] + 1);
            $response['message'] = 'Class booked successfully! You have ' . $remaining . ' classes remaining this month.';
            $response['remaining_classes'] = $remaining;
        }
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log('Failed to create booking: ' . $e->getMessage());
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Failed to create booking: ' . $e->getMessage()]);
        exit;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    error_log('Failed to process booking: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to process booking']);
} 