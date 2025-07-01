<?php
/**
 * Test script to verify the database queries used in index.php work correctly
 */

require_once 'api/db.php';

echo "<h2>Testing Index Page Database Queries</h2>";

// Test 1: Fetch membership plans (FIXED)
echo "<h3>1. Testing Membership Plans Query</h3>";
try {
    $stmt = $pdo->query("
        SELECT id, name, description, price, monthly_class_limit 
        FROM membership_plans 
        WHERE status = 'active' 
        ORDER BY price ASC
    ");
    $membershipPlans = $stmt->fetchAll();
    echo "✅ Found " . count($membershipPlans) . " membership plans<br>";
    foreach ($membershipPlans as $plan) {
        $limit = $plan['monthly_class_limit'] ? $plan['monthly_class_limit'] . ' classes' : 'Unlimited';
        echo "- {$plan['name']}: £{$plan['price']}/month ({$limit})<br>";
    }
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Test 2: Fetch instructors (FIXED)
echo "<h3>2. Testing Instructors Query</h3>";
try {
    $stmt = $pdo->query("
        SELECT id, first_name, last_name, email, phone, bio, specialties, status,
               created_at
        FROM instructors 
        WHERE status = 'active' 
        ORDER BY first_name, last_name
    ");
    $instructors = $stmt->fetchAll();
    echo "✅ Found " . count($instructors) . " active instructors<br>";
    foreach ($instructors as $instructor) {
        echo "- {$instructor['first_name']} {$instructor['last_name']} ({$instructor['email']})<br>";
    }
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Test 3: Fetch classes with enhanced details (FIXED)
echo "<h3>3. Testing Classes Query</h3>";
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
    echo "✅ Found " . count($classes) . " upcoming classes<br>";
    foreach ($classes as $class) {
        $instructor = $class['instructor_name'] ?: 'TBA';
        $recurringText = $class['recurring'] ? ' (Recurring)' : '';
        echo "- {$class['name']}{$recurringText} on {$class['date']} at {$class['time']} with {$instructor} ({$class['booked_count']}/{$class['capacity']} booked, {$class['available_spots']} available)<br>";
    }
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Test 4: Check if tables exist
echo "<h3>4. Database Table Status</h3>";
$tables = ['membership_plans', 'instructors', 'classes', 'bookings', 'users', 'user_memberships'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch();
        echo "✅ Table '$table' exists with {$result['count']} records<br>";
    } catch (PDOException $e) {
        echo "❌ Table '$table' error: " . $e->getMessage() . "<br>";
    }
}

echo "<br><h3>5. Sample Data Check (FIXED)</h3>";

// Check if we have sample data (FIXED)
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM membership_plans WHERE status = 'active'");
    $plans = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM instructors WHERE status = 'active'");
    $instructors = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM classes WHERE date >= CURDATE()");
    $classes = $stmt->fetch()['count'];
    
    echo "Database Status:<br>";
    echo "- Active membership plans: $plans<br>";
    echo "- Active instructors: $instructors<br>";
    echo "- Upcoming classes: $classes<br>";
    
    if ($plans > 0 && $instructors > 0 && $classes > 0) {
        echo "<br>✅ <strong>All systems ready! Index page should display correctly.</strong><br>";
    } else {
        echo "<br>⚠️ <strong>Missing sample data. Some sections may appear empty.</strong><br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Error checking sample data: " . $e->getMessage() . "<br>";
}

echo "<br><h3>6. Duplicate Membership Check</h3>";

// Check for duplicate membership plans
try {
    $stmt = $pdo->query("
        SELECT name, COUNT(*) as count 
        FROM membership_plans 
        GROUP BY name 
        HAVING COUNT(*) > 1
        ORDER BY count DESC
    ");
    $duplicates = $stmt->fetchAll();
    
    if (empty($duplicates)) {
        echo "✅ No duplicate membership plans found<br>";
    } else {
        echo "⚠️ Found duplicate membership plans:<br>";
        foreach ($duplicates as $duplicate) {
            echo "- '{$duplicate['name']}' appears {$duplicate['count']} times<br>";
        }
        echo "<br>🔧 <strong>Recommendation:</strong> Run the duplicate cleanup script to remove duplicates.<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Error checking duplicates: " . $e->getMessage() . "<br>";
}

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
h3 { color: #555; margin-top: 20px; }
</style> 