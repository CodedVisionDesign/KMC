<?php
// Start output buffering to prevent any unwanted output
ob_start();

// Disable HTML error display for API endpoints
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Clean any buffered output and set JSON header
ob_clean();
header('Content-Type: application/json');

// Create database connection directly
function getDBConnection() {
    $host = 'localhost';
    $db   = 'testbook';
    $user = 'root';
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

// Include user authentication
if (file_exists(__DIR__ . '/../../config/user_auth.php')) {
    require_once __DIR__ . '/../../config/user_auth.php';
} else {
    error_log('User authentication file not found');
    echo json_encode(['success' => false, 'error' => 'Authentication system not available']);
    exit;
}

// Check if user is logged in
if (!isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'You must be logged in to cancel bookings',
        'redirect' => '../login.php'
    ]);
    exit();
}

// Get user info and POST data
$userInfo = getUserInfo();
$data = json_decode(file_get_contents('php://input'), true);
$booking_id = isset($data['booking_id']) ? intval($data['booking_id']) : 0;

if ($booking_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid booking ID']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Verify booking belongs to the current user
    $stmt = $pdo->prepare('SELECT id FROM bookings WHERE id = ? AND user_id = ?');
    $stmt->execute([$booking_id, $userInfo['id']]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Booking not found or access denied']);
        exit;
    }
    
    // Delete the booking
    $stmt = $pdo->prepare('DELETE FROM bookings WHERE id = ? AND user_id = ?');
    $stmt->execute([$booking_id, $userInfo['id']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to cancel booking']);
    }
    
} catch (Exception $e) {
    error_log('Failed to cancel booking: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to cancel booking']);
}
?> 