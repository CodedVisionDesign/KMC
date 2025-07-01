<?php
/**
 * Debug Classes and Recurring Events
 * This script helps debug the class display and recurring event issues
 */

require_once 'api/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Debug Classes</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>🔍 Debug Classes and Recurring Events</h1>";

echo "<div class='section'>";
echo "<h2>1. Database Classes Query Test</h2>";

try {
    // Test the actual query used in index.php
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
    $indexClasses = $stmt->fetchAll();
    
    echo "<p class='success'>✅ Index.php query successful</p>";
    echo "<p><strong>Result count:</strong> " . count($indexClasses) . " classes</p>";
    
    if (count($indexClasses) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Date</th><th>Time</th><th>Recurring</th><th>Instructor</th><th>Available Spots</th></tr>";
        foreach ($indexClasses as $class) {
            $recurringText = $class['recurring'] ? 'Yes' : 'No';
            echo "<tr>";
            echo "<td>{$class['id']}</td>";
            echo "<td>{$class['name']}</td>";
            echo "<td>{$class['date']}</td>";
            echo "<td>{$class['time']}</td>";
            echo "<td>{$recurringText}</td>";
            echo "<td>{$class['instructor_name']}</td>";
            echo "<td>{$class['available_spots']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>2. All Classes (No Date Filter)</h2>";

try {
    $stmt = $pdo->query("
        SELECT id, name, date, time, recurring, instructor_id, capacity
        FROM classes 
        ORDER BY date, time
    ");
    $allClasses = $stmt->fetchAll();
    
    echo "<p class='success'>✅ All classes query successful</p>";
    echo "<p><strong>Total classes in database:</strong> " . count($allClasses) . "</p>";
    
    if (count($allClasses) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Date</th><th>Time</th><th>Recurring</th><th>Instructor ID</th><th>Past/Future</th></tr>";
        $today = date('Y-m-d');
        foreach ($allClasses as $class) {
            $recurringText = $class['recurring'] ? 'Yes' : 'No';
            $pastFuture = $class['date'] >= $today ? 'Future' : 'Past';
            $rowClass = $class['date'] >= $today ? 'success' : 'warning';
            echo "<tr class='{$rowClass}'>";
            echo "<td>{$class['id']}</td>";
            echo "<td>{$class['name']}</td>";
            echo "<td>{$class['date']}</td>";
            echo "<td>{$class['time']}</td>";
            echo "<td>{$recurringText}</td>";
            echo "<td>{$class['instructor_id']}</td>";
            echo "<td>{$pastFuture}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>3. Recurring Classes Analysis</h2>";

try {
    $stmt = $pdo->query("
        SELECT id, name, date, time, recurring
        FROM classes 
        WHERE recurring = 1
        ORDER BY date, time
    ");
    $recurringClasses = $stmt->fetchAll();
    
    echo "<p><strong>Recurring classes found:</strong> " . count($recurringClasses) . "</p>";
    
    if (count($recurringClasses) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Original Date</th><th>Time</th><th>Day of Week</th></tr>";
        foreach ($recurringClasses as $class) {
            $dayOfWeek = date('l', strtotime($class['date']));
            echo "<tr>";
            echo "<td>{$class['id']}</td>";
            echo "<td>{$class['name']}</td>";
            echo "<td>{$class['date']}</td>";
            echo "<td>{$class['time']}</td>";
            echo "<td>{$dayOfWeek}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>🔥 Problem Identified</h3>";
        echo "<p class='warning'>⚠️ <strong>Recurring classes are only stored once in the database!</strong></p>";
        echo "<p>The system needs to generate future instances of recurring classes dynamically.</p>";
        echo "<p>Currently, if a recurring class has a past date, it won't show in the 'future classes' filter.</p>";
    } else {
        echo "<p>No recurring classes found in database.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>4. Suggested Solution</h2>";
echo "<div class='warning'>";
echo "<h3>🛠️ Issues Found:</h3>";
echo "<ol>";
echo "<li><strong>Limited Classes Display:</strong> The index page query is working correctly, but there might be date issues with recurring classes.</li>";
echo "<li><strong>Recurring Events:</strong> Recurring classes are stored only once with their original date. The system needs to generate future instances dynamically.</li>";
echo "</ol>";

echo "<h3>💡 Recommended Fixes:</h3>";
echo "<ol>";
echo "<li><strong>Update Classes Query:</strong> Generate future instances of recurring classes in the query.</li>";
echo "<li><strong>Update API:</strong> The calendar API should also generate recurring instances.</li>";
echo "<li><strong>Add Date Range:</strong> Generate recurring instances for the next 3-6 months.</li>";
echo "</ol>";
echo "</div>";
echo "</div>";

echo "</body></html>";
?> 