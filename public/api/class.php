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
    http_response_code(500);
    echo json_encode(['error' => 'Database system not available']);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid class ID']);
    exit;
}

try {
    // Get class details with instructor info and booking stats
    $stmt = $pdo->prepare('
        SELECT 
            c.id, 
            c.name, 
            c.description, 
            c.date, 
            c.time, 
            c.capacity,
            c.instructor_id,
            CONCAT(i.first_name, " ", i.last_name) as instructor_name,
            i.bio as instructor_bio,
            i.specialties as instructor_specialties,
            COALESCE(COUNT(b.id), 0) as current_bookings,
            (c.capacity - COALESCE(COUNT(b.id), 0)) as spots_remaining,
            CASE 
                WHEN (c.capacity - COALESCE(COUNT(b.id), 0)) <= 0 THEN "full"
                WHEN (c.capacity - COALESCE(COUNT(b.id), 0)) <= (c.capacity * 0.2) THEN "low"
                ELSE "available"
            END as availability_status,
            ROUND(((c.capacity - COALESCE(COUNT(b.id), 0)) / c.capacity) * 100, 0) as availability_percentage
        FROM classes c 
        LEFT JOIN instructors i ON c.instructor_id = i.id
        LEFT JOIN bookings b ON c.id = b.class_id 
        WHERE c.id = ?
        GROUP BY c.id, c.name, c.description, c.date, c.time, c.capacity, c.instructor_id, i.first_name, i.last_name, i.bio, i.specialties
    ');
    $stmt->execute([$id]);
    $class = $stmt->fetch();
    
    if ($class) {
        echo json_encode(['success' => true, 'class' => $class]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Class not found']);
    }
} catch (Exception $e) {
    error_log('Failed to fetch class details: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch class details']);
} 