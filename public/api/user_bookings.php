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

// Check if user is logged in
if (!isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to view bookings',
    ]);
    exit();
}

// Get user info
$userInfo = getUserInfo();
$userId = $userInfo['id'];

// Debug logging
error_log("User Bookings API - User ID: $userId, Email: " . $userInfo['email']);

try {
    // Get all user bookings
    $stmt = $pdo->prepare('
        SELECT b.id, b.class_id, b.booking_date, b.status,
               c.name as class_name, c.date as class_date, c.time as class_time
        FROM bookings b
        JOIN classes c ON b.class_id = c.id
        WHERE b.user_id = ? AND b.status != "cancelled"
        ORDER BY c.date, c.time
    ');
    $stmt->execute([$userId]);
    $bookings = $stmt->fetchAll();
    
    // Debug logging
    error_log("User Bookings API - Found " . count($bookings) . " bookings for user $userId");
    
    // Create a simple array of class IDs for quick lookup
    $bookedClassIds = array_map(function($booking) {
        return (int) $booking['class_id'];
    }, $bookings);
    
    echo json_encode([
        'success' => true,
        'bookings' => $bookings,
        'booked_class_ids' => $bookedClassIds
    ]);

} catch (Exception $e) {
    error_log('Failed to fetch user bookings: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch bookings']);
} 