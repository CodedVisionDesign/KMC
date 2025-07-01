<?php
require_once 'api/db.php';

// Basic security - only allow from localhost
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1', 'localhost'])) {
    die('Access denied');
}

echo "<h2>Adding Sample Data...</h2>";

try {
    // First, let's check if we have instructors
    $stmt = $pdo->query("SELECT COUNT(*) FROM instructors WHERE status = 'active'");
    $instructorCount = $stmt->fetchColumn();
    echo "<p>Active instructors: $instructorCount</p>";
    
    // Get some instructor IDs
    $stmt = $pdo->query("SELECT id FROM instructors WHERE status = 'active' LIMIT 5");
    $instructorIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($instructorIds)) {
        echo "<p>No instructors found. Adding sample instructors...</p>";
        
        // Add sample instructors
        $instructors = [
            ['Alex', 'Rodriguez', 'alex.rodriguez@studio.com', '555-0104', 'High-intensity training specialist. Motivational coach focused on fitness transformations.', 'HIIT, CrossFit, Weight Training'],
            ['Emma', 'Davis', 'emma.davis@studio.com', '555-0103', 'Mindfulness coach and meditation teacher. Creates calming environments for healing and growth.', 'Meditation, Mindfulness, Breathwork'],
            ['Lisa', 'Thompson', 'lisa.thompson@studio.com', '555-0105', 'Beginner-friendly yoga instructor. Patient and encouraging approach to wellness.', 'Beginner Yoga, Gentle Yoga, Seniors Fitness'],
            ['Mike', 'Chen', 'mike.chen@studio.com', '555-0102', 'Personal trainer and Pilates instructor. Former athlete with expertise in strength training.', 'Pilates, HIIT, Strength Training'],
            ['Sarah', 'Johnson', 'sarah.johnson@studio.com', '555-0101', 'Certified yoga instructor with 10+ years experience. Specializes in Hatha and Vinyasa yoga.', 'Hatha Yoga, Vinyasa, Meditation']
        ];
        
        foreach ($instructors as $instructor) {
            $stmt = $pdo->prepare("INSERT INTO instructors (first_name, last_name, email, phone, bio, specialties, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute($instructor);
        }
        
        // Get the new instructor IDs
        $stmt = $pdo->query("SELECT id FROM instructors WHERE status = 'active' ORDER BY id DESC LIMIT 5");
        $instructorIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Added " . count($instructorIds) . " instructors</p>";
    }
    
    // Add sample classes
    echo "<p>Adding sample classes...</p>";
    
    // Clear existing classes to avoid duplicates
    $pdo->exec("DELETE FROM bookings");
    $pdo->exec("DELETE FROM classes");
    
    $classes = [
        ['Morning Yoga', 'Start your day with a peaceful yoga session', date('Y-m-d', strtotime('+1 day')), '09:00:00', 15, $instructorIds[0] ?? null],
        ['Evening Pilates', 'Core strengthening and flexibility training', date('Y-m-d', strtotime('+1 day')), '18:00:00', 12, $instructorIds[1] ?? null],
        ['HIIT Training', 'High-intensity interval training for all levels', date('Y-m-d', strtotime('+2 days')), '07:00:00', 10, $instructorIds[0] ?? null],
        ['Mindfulness Meditation', 'Guided meditation and relaxation techniques', date('Y-m-d', strtotime('+2 days')), '19:00:00', 20, $instructorIds[1] ?? null],
        ['Beginner Yoga', 'Perfect introduction to yoga practice', date('Y-m-d', strtotime('+3 days')), '10:00:00', 16, $instructorIds[2] ?? null],
        ['Advanced Pilates', 'Challenging Pilates workout for experienced practitioners', date('Y-m-d', strtotime('+3 days')), '17:30:00', 8, $instructorIds[3] ?? null],
        ['Hatha Yoga', 'Traditional yoga focusing on postures and breathing', date('Y-m-d', strtotime('+4 days')), '11:00:00', 14, $instructorIds[4] ?? null],
        ['Weekend Bootcamp', 'Full-body workout combining cardio and strength', date('Y-m-d', strtotime('+5 days')), '08:00:00', 12, $instructorIds[0] ?? null],
        ['Gentle Yoga', 'Slow-paced yoga perfect for beginners and seniors', date('Y-m-d', strtotime('+6 days')), '14:00:00', 18, $instructorIds[2] ?? null],
        ['Power Pilates', 'Dynamic Pilates class for strength and endurance', date('Y-m-d', strtotime('+7 days')), '16:00:00', 10, $instructorIds[3] ?? null]
    ];
    
    foreach ($classes as $class) {
        $stmt = $pdo->prepare("INSERT INTO classes (name, description, date, time, capacity, instructor_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute($class);
    }
    
    echo "<p>Added " . count($classes) . " classes</p>";
    
    // Add some sample bookings
    $stmt = $pdo->query("SELECT id FROM classes LIMIT 3");
    $classIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $bookings = [
        [$classIds[0] ?? 1, 'John Doe', 'john@example.com'],
        [$classIds[0] ?? 1, 'Jane Smith', 'jane@example.com'],
        [$classIds[1] ?? 2, 'Bob Johnson', 'bob@example.com'],
        [$classIds[2] ?? 3, 'Alice Brown', 'alice@example.com'],
        [$classIds[2] ?? 3, 'Charlie Wilson', 'charlie@example.com']
    ];
    
    foreach ($bookings as $booking) {
        $stmt = $pdo->prepare("INSERT INTO bookings (class_id, name, email) VALUES (?, ?, ?)");
        $stmt->execute($booking);
    }
    
    echo "<p>Added " . count($bookings) . " sample bookings</p>";
    echo "<h3 style='color: green;'>Sample data added successfully!</h3>";
    
    // Show summary
    $stmt = $pdo->query("SELECT COUNT(*) FROM instructors WHERE status = 'active'");
    $instructorCount = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM classes WHERE date >= CURDATE()");
    $classCount = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
    $bookingCount = $stmt->fetchColumn();
    
    echo "<h3>Summary:</h3>";
    echo "<ul>";
    echo "<li>Active instructors: $instructorCount</li>";
    echo "<li>Upcoming classes: $classCount</li>";
    echo "<li>Total bookings: $bookingCount</li>";
    echo "</ul>";
    
    echo "<p><a href='index.php'>View Main Page</a> | <a href='../admin/instructors.php'>Admin Instructors</a></p>";
    
} catch(Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 