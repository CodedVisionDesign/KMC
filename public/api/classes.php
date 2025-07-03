<?php
// Start output buffering to prevent any unwanted output
ob_start();

// Disable HTML error display for API endpoints
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

// Clean any buffered output and set JSON header
ob_clean();
header('Content-Type: application/json');

// Include error handling configuration
if (file_exists(__DIR__ . '/../../config/error_handling.php')) {
    require_once __DIR__ . '/../../config/error_handling.php';
}

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
    echo json_encode(['success' => false, 'error' => 'Database system not available']);
    exit;
}

try {
    // Enhanced query with recurring class generation and booking calculations
    try {
        // First get all classes including recurring ones
        $stmt = $pdo->query('
            SELECT 
                c.id, 
                c.name, 
                c.description, 
                c.date, 
                c.time, 
                c.capacity,
                c.recurring,
                c.days_of_week,
                c.day_specific_times,
                c.multiple_times,
                c.instructor_id,
                CONCAT(i.first_name, " ", i.last_name) as instructor_name,
                i.bio as instructor_bio,
                i.specialties as instructor_specialties
            FROM classes c 
            LEFT JOIN instructors i ON c.instructor_id = i.id
            WHERE (
                (c.date > CURDATE()) OR 
                (c.date = CURDATE() AND c.time > CURTIME()) OR 
                c.recurring = 1
            )
            ORDER BY c.date, c.time
        ');
        $allClasses = $stmt->fetchAll();
        
        // Process classes and generate recurring instances
        $processedClasses = [];
        $today = new DateTime();
        $startDate = new DateTime('-1 month'); // Show 1 month of history
        $endDate = new DateTime('+3 months'); // Generate 3 months ahead
        
        foreach ($allClasses as $class) {
            if ($class['recurring']) {
                $daysOfWeek = json_decode($class['days_of_week'] ?? '[]', true);
                $daySpecificTimes = json_decode($class['day_specific_times'] ?? '[]', true);
                
                if (!empty($daysOfWeek)) {
                    // Generate instances for each specified day of the week
                    $dayMapping = [
                        'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4,
                        'friday' => 5, 'saturday' => 6, 'sunday' => 7
                    ];
                    
                    foreach ($daysOfWeek as $dayName) {
                        if (!isset($dayMapping[$dayName])) continue;
                        
                        $targetDayOfWeek = $dayMapping[$dayName];
                        $currentDate = clone $startDate;
                        
                        // Find the first occurrence of this day within our date range
                        $startDayOfWeek = $currentDate->format('N');
                        if ($startDayOfWeek <= $targetDayOfWeek) {
                            $daysToAdd = $targetDayOfWeek - $startDayOfWeek;
                        } else {
                            $daysToAdd = 7 - $startDayOfWeek + $targetDayOfWeek;
                        }
                        
                        $currentDate->add(new DateInterval('P' . $daysToAdd . 'D'));
                        
                        // Generate weekly instances for this day
                        while ($currentDate <= $endDate) {
                            if ($currentDate >= $startDate) {
                                // Use day-specific time if available, otherwise use default time
                                $classTime = isset($daySpecificTimes[$dayName]) ? $daySpecificTimes[$dayName] : $class['time'];
                                
                                // *** ONLY ADD FUTURE CLASSES (CHECK DATE + TIME) ***
                                $instanceDateTime = new DateTime($currentDate->format('Y-m-d') . ' ' . $classTime);
                                $now = new DateTime();
                                
                                if ($instanceDateTime > $now) {
                                    $instanceClass = $class;
                                    $instanceClass['date'] = $currentDate->format('Y-m-d');
                                    $instanceClass['time'] = $classTime;
                                    $instanceClass['generated_id'] = $class['id'] . '_' . $currentDate->format('Y-m-d');
                                    $processedClasses[] = $instanceClass;
                                }
                            }
                            
                            // Move to next week
                            $currentDate->add(new DateInterval('P7D'));
                        }
                    }
                } else {
                    // Fallback to original logic for classes without days_of_week specified
                    $classDate = new DateTime($class['date']);
                    $currentDate = clone $startDate;
                    
                    // Find the first occurrence within our date range
                    $dayOfWeek = $classDate->format('N'); // 1 (Monday) to 7 (Sunday)
                    $startDayOfWeek = $currentDate->format('N');
                    
                    if ($startDayOfWeek <= $dayOfWeek) {
                        $daysToAdd = $dayOfWeek - $startDayOfWeek;
                    } else {
                        $daysToAdd = 7 - $startDayOfWeek + $dayOfWeek;
                    }
                    
                    $currentDate->add(new DateInterval('P' . $daysToAdd . 'D'));
                    
                                            // Generate instances from current date to 3 months ahead
                        $now = new DateTime();
                        while ($currentDate <= $endDate) {
                            if ($currentDate >= $startDate) {
                                // Check if this specific instance is in the future
                                $instanceDateTime = new DateTime($currentDate->format('Y-m-d') . ' ' . $class['time']);
                                
                                if ($instanceDateTime > $now) {
                                    $instanceClass = $class;
                                    $instanceClass['date'] = $currentDate->format('Y-m-d');
                                    $instanceClass['generated_id'] = $class['id'] . '_' . $currentDate->format('Y-m-d');
                                    $processedClasses[] = $instanceClass;
                                }
                            }
                            
                            // Move to next week
                            $currentDate->add(new DateInterval('P7D'));
                        }
                }
            } else {
                // Non-recurring class, add only if it's in the future
                $classDateTime = new DateTime($class['date'] . ' ' . $class['time']);
                $now = new DateTime();
                
                if ($classDateTime > $now) {
                    $processedClasses[] = $class;
                }
            }
        }
        
        // Now get booking counts for each processed class
        $classes = [];
        foreach ($processedClasses as $class) {
            try {
                // For recurring classes, we need to count bookings by date
                $bookingStmt = $pdo->prepare('
                    SELECT COUNT(b.id) as current_bookings
                    FROM bookings b 
                    WHERE b.class_id = ? AND DATE(b.class_date) = ?
                ');
                $bookingStmt->execute([$class['id'], $class['date']]);
                $bookingResult = $bookingStmt->fetch();
                
                $class['current_bookings'] = $bookingResult['current_bookings'] ?? 0;
                $class['spots_remaining'] = $class['capacity'] - $class['current_bookings'];
                
                // Calculate availability status
                if ($class['spots_remaining'] <= 0) {
                    $class['availability_status'] = 'full';
                } elseif ($class['spots_remaining'] <= ($class['capacity'] * 0.2)) {
                    $class['availability_status'] = 'low';
                } else {
                    $class['availability_status'] = 'available';
                }
                
                $class['availability_percentage'] = round(($class['spots_remaining'] / $class['capacity']) * 100, 0);
                
                $classes[] = $class;
            } catch (Exception $e) {
                // Fallback: assume no bookings
                $class['current_bookings'] = 0;
                $class['spots_remaining'] = $class['capacity'];
                $class['availability_status'] = 'available';
                $class['availability_percentage'] = 100;
                $classes[] = $class;
            }
        }
        
        // *** FINAL SAFETY FILTER: Remove any past classes that might have slipped through ***
        $now = new DateTime();
        $classes = array_filter($classes, function($class) use ($now) {
            $classDateTime = new DateTime($class['date'] . ' ' . $class['time']);
            return $classDateTime > $now;
        });
        
        // Sort by date and time
        usort($classes, function($a, $b) {
            if ($a['date'] === $b['date']) {
                return strcmp($a['time'], $b['time']);
            }
            return strcmp($a['date'], $b['date']);
        });
        
    } catch (Exception $sqlError) {
        // Fallback to simple query if enhanced query fails
        error_log('Enhanced query failed, falling back to simple query: ' . $sqlError->getMessage());
        $stmt = $pdo->query('
            SELECT 
                c.id, 
                c.name, 
                c.description, 
                c.date, 
                c.time, 
                c.capacity,
                c.recurring,
                c.days_of_week,
                c.day_specific_times,
                c.multiple_times,
                c.instructor_id,
                CONCAT(i.first_name, " ", i.last_name) as instructor_name
            FROM classes c 
            LEFT JOIN instructors i ON c.instructor_id = i.id
            WHERE c.date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
            ORDER BY c.date, c.time
        ');
        $classes = $stmt->fetchAll();
        
        // Add default availability data for compatibility
        foreach ($classes as &$class) {
            $class['current_bookings'] = 0;
            $class['spots_remaining'] = $class['capacity'];
            $class['availability_status'] = 'available';
            $class['availability_percentage'] = 100;
        }
    }
    
    // Add last_updated timestamp for cache management
    $response = [
        'classes' => $classes,
        'last_updated' => date('Y-m-d H:i:s'),
        'server_time' => time()
    ];
    
    echo json_encode(['success' => true, 'data' => $response]);
} catch (Exception $e) {
    error_log('Failed to fetch classes: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch classes']);
} 