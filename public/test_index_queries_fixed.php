<?php
/**
 * Test script to verify index page database queries are working
 * Run this to test the fixed queries before visiting the index page
 */

// Database connection
require_once __DIR__ . '/api/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Index Page Database Query Test - Fixed</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🧪 Index Page Database Query Test - Fixed Version</h1>
";

echo "<div class='section'>";
echo "<h2>1. Testing Membership Plans Query (Fixed)</h2>";
try {
    $stmt = $pdo->query("
        SELECT id, name, description, price, monthly_class_limit 
        FROM membership_plans 
        WHERE status = 'active' 
        ORDER BY price ASC
    ");
    $membershipPlans = $stmt->fetchAll();
    echo "<p class='success'>✅ Query successful! Found " . count($membershipPlans) . " active membership plans</p>";
    
    if (!empty($membershipPlans)) {
        echo "<h3>Available Plans:</h3>";
        foreach ($membershipPlans as $plan) {
            $price = number_format($plan['price'], 2);
            $limit = $plan['monthly_class_limit'] ? $plan['monthly_class_limit'] . ' classes' : 'Unlimited';
            echo "<li><strong>{$plan['name']}</strong> - £{$price}/month ({$limit})</li>";
        }
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>2. Testing Instructors Query (Fixed)</h2>";
try {
    $stmt = $pdo->query("
        SELECT id, first_name, last_name, email, phone, bio, specialties, status,
               created_at
        FROM instructors 
        WHERE status = 'active' 
        ORDER BY first_name, last_name
    ");
    $instructors = $stmt->fetchAll();
    echo "<p class='success'>✅ Query successful! Found " . count($instructors) . " active instructors</p>";
    
    if (!empty($instructors)) {
        echo "<h3>Active Instructors:</h3>";
        echo "<ul>";
        foreach ($instructors as $instructor) {
            echo "<li><strong>{$instructor['first_name']} {$instructor['last_name']}</strong> ({$instructor['email']})";
            if (!empty($instructor['specialties'])) {
                echo " - Specialties: " . htmlspecialchars($instructor['specialties']);
            }
            echo "</li>";
        }
        echo "</ul>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>3. Testing Classes Query (Fixed)</h2>";
try {
    $stmt = $pdo->query("
        SELECT c.id, c.name, c.description, c.date, c.time, c.capacity, c.recurring,
               CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
               i.email as instructor_email,
               COUNT(b.id) as booked_count,
               (c.capacity - COUNT(b.id)) as available_spots
        FROM classes c 
        LEFT JOIN instructors i ON c.instructor_id = i.id 
        LEFT JOIN bookings b ON c.id = b.class_id 
        WHERE c.date >= CURDATE() 
        GROUP BY c.id, c.name, c.description, c.date, c.time, c.capacity, c.recurring,
                 i.first_name, i.last_name, i.email
        ORDER BY c.date, c.time
    ");
    $classes = $stmt->fetchAll();
    echo "<p class='success'>✅ Query successful! Found " . count($classes) . " upcoming classes</p>";
    
    if (!empty($classes)) {
        echo "<h3>Upcoming Classes:</h3>";
        echo "<ul>";
        foreach ($classes as $class) {
            $formattedDate = date('M d, Y', strtotime($class['date']));
            $formattedTime = date('g:i A', strtotime($class['time']));
            $instructorText = !empty($class['instructor_name']) ? $class['instructor_name'] : 'TBA';
            $recurringText = $class['recurring'] ? ' (Recurring)' : '';
            
            echo "<li><strong>{$class['name']}</strong>{$recurringText} - {$formattedDate} at {$formattedTime}";
            echo "<br>Instructor: {$instructorText}";
            echo "<br>Capacity: {$class['booked_count']}/{$class['capacity']} ({$class['available_spots']} spots available)";
            echo "</li>";
        }
        echo "</ul>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>4. Database Table Status</h2>";
$tables = ['membership_plans', 'instructors', 'classes', 'bookings', 'users', 'user_memberships'];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch();
        echo "<p class='success'>✅ Table '$table' exists with {$result['count']} records</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Table '$table' error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>5. Sample Data Check (Fixed)</h2>";
try {
    // Check membership plans with correct column name
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM membership_plans WHERE status = 'active'");
    $result = $stmt->fetch();
    echo "<p class='success'>✅ Found {$result['count']} active membership plans</p>";
    
    // Show sample membership plans
    $stmt = $pdo->query("SELECT name, price, monthly_class_limit FROM membership_plans WHERE status = 'active' ORDER BY price LIMIT 3");
    $plans = $stmt->fetchAll();
    if (!empty($plans)) {
        echo "<h3>Sample Membership Plans:</h3>";
        echo "<ul>";
        foreach ($plans as $plan) {
            $price = number_format($plan['price'], 2);
            $limit = $plan['monthly_class_limit'] ? $plan['monthly_class_limit'] . ' classes' : 'Unlimited';
            echo "<li>{$plan['name']} - £{$price}/month ({$limit})</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error checking sample data: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>6. Summary</h2>";
echo "<p class='info'>All database queries have been fixed to match the actual table schema:</p>";
echo "<ul>";
echo "<li>✅ Removed non-existent 'features' column from membership_plans query</li>";
echo "<li>✅ Changed 'is_active' to 'status = active' for membership_plans</li>";
echo "<li>✅ Removed non-existent 'updated_at' column from classes query</li>";
echo "<li>✅ Fixed JOIN condition to use c.instructor_id = i.id</li>";
echo "<li>✅ Updated GROUP BY clause to match selected columns</li>";
echo "</ul>";
echo "<p class='success'>🎉 The index page should now load without database errors!</p>";
echo "</div>";

echo "</body></html>";
?> 