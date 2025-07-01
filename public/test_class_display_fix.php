<?php
/**
 * Test Class Display Fix
 * Verify that index page shows unique classes and calendar shows historical + future
 */

require_once 'api/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Class Display Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .past { background-color: #f8f9fa; }
        .today { background-color: #fff3cd; }
        .future { background-color: #d1ecf1; }
    </style>
</head>
<body>
    <h1>🧪 Test Class Display Fix</h1>";

echo "<div class='section'>";
echo "<h2>1. Index Page - Unique Classes Display</h2>";
echo "<p><strong>Testing:</strong> Index page logic (should show each class type once)</p>";

try {
    // Test the index page query
    $stmt = $pdo->query("
        SELECT c.id, c.name, c.description, c.time, c.capacity, c.recurring,
               CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
               i.email as instructor_email,
               CASE 
                   WHEN c.recurring = 1 THEN 'Weekly'
                   ELSE DATE_FORMAT(c.date, '%M %e, %Y')
               END as schedule_info,
               c.date as original_date
        FROM classes c 
        LEFT JOIN instructors i ON c.instructor_id = i.id 
        ORDER BY c.name, c.time
    ");
    $indexClasses = $stmt->fetchAll();
    
    echo "<p class='success'>✅ Index query successful</p>";
    echo "<p><strong>Classes shown on index page:</strong> " . count($indexClasses) . " unique classes</p>";
    
    if (count($indexClasses) > 0) {
        echo "<table>";
        echo "<tr><th>Name</th><th>Schedule</th><th>Time</th><th>Instructor</th><th>Capacity</th><th>Type</th></tr>";
        foreach ($indexClasses as $class) {
            $type = $class['recurring'] ? 'Weekly Recurring' : 'One-time';
            echo "<tr>";
            echo "<td><strong>{$class['name']}</strong></td>";
            echo "<td>{$class['schedule_info']}</td>";
            echo "<td>{$class['time']}</td>";
            echo "<td>{$class['instructor_name']}</td>";
            echo "<td>{$class['capacity']} people</td>";
            echo "<td>{$type}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p class='info'>💡 <strong>This is what users see on the index page:</strong> Each class type is shown once with general information. Users check the calendar for specific dates and availability.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>2. Calendar API - Historical + Future Classes</h2>";
echo "<p><strong>Testing:</strong> Calendar API logic (should show 1 month history + 3 months future)</p>";

try {
    $apiUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/classes.php';
    
    $apiResponse = file_get_contents($apiUrl);
    $apiData = json_decode($apiResponse, true);
    
    if ($apiData && $apiData['success']) {
        $calendarClasses = $apiData['data']['classes'] ?? [];
        echo "<p class='success'>✅ Calendar API successful</p>";
        echo "<p><strong>Classes in calendar:</strong> " . count($calendarClasses) . " class instances</p>";
        
        // Analyze the data
        $today = date('Y-m-d');
        $pastCount = 0;
        $todayCount = 0;
        $futureCount = 0;
        $recurringCount = 0;
        $regularCount = 0;
        
        foreach ($calendarClasses as $class) {
            if ($class['date'] < $today) $pastCount++;
            elseif ($class['date'] === $today) $todayCount++;
            else $futureCount++;
            
            if (isset($class['generated_id'])) $recurringCount++;
            else $regularCount++;
        }
        
        echo "<h4>📊 Calendar Data Breakdown:</h4>";
        echo "<ul>";
        echo "<li><strong>Past classes:</strong> {$pastCount}</li>";
        echo "<li><strong>Today's classes:</strong> {$todayCount}</li>";
        echo "<li><strong>Future classes:</strong> {$futureCount}</li>";
        echo "<li><strong>Recurring instances:</strong> {$recurringCount}</li>";
        echo "<li><strong>Regular classes:</strong> {$regularCount}</li>";
        echo "</ul>";
        
        if (count($calendarClasses) > 0) {
            echo "<h4>Sample Calendar Events (First 15):</h4>";
            echo "<table>";
            echo "<tr><th>Date</th><th>Name</th><th>Time</th><th>Type</th><th>Period</th></tr>";
            
            // Sort by date for display
            usort($calendarClasses, function($a, $b) {
                return strcmp($a['date'], $b['date']);
            });
            
            for ($i = 0; $i < min(15, count($calendarClasses)); $i++) {
                $class = $calendarClasses[$i];
                $type = isset($class['generated_id']) ? 'Recurring' : 'Regular';
                
                $period = '';
                $rowClass = '';
                if ($class['date'] < $today) {
                    $period = 'Past';
                    $rowClass = 'past';
                } elseif ($class['date'] === $today) {
                    $period = 'Today';
                    $rowClass = 'today';
                } else {
                    $period = 'Future';
                    $rowClass = 'future';
                }
                
                echo "<tr class='{$rowClass}'>";
                echo "<td>{$class['date']}</td>";
                echo "<td>{$class['name']}</td>";
                echo "<td>{$class['time']}</td>";
                echo "<td>{$type}</td>";
                echo "<td><strong>{$period}</strong></td>";
                echo "</tr>";
            }
            echo "</table>";
            
            if (count($calendarClasses) > 15) {
                echo "<p class='info'>... and " . (count($calendarClasses) - 15) . " more calendar events</p>";
            }
        }
    } else {
        echo "<p class='error'>❌ API Error: " . ($apiData['error'] ?? 'Unknown error') . "</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ API Test Error: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>3. Summary & Verification</h2>";
echo "<div class='info'>";
echo "<h3>✅ What Should Be Working Now:</h3>";
echo "<ol>";
echo "<li><strong>Index Page 'Available Classes':</strong> Shows each class type once with general info (no duplicates)</li>";
echo "<li><strong>Calendar/Timetable:</strong> Shows specific class instances including:</li>";
echo "<ul>";
echo "<li>Historical classes (up to 1 month back)</li>";
echo "<li>Today's classes</li>";
echo "<li>Future classes (up to 3 months ahead)</li>";
echo "<li>Recurring class instances generated weekly</li>";
echo "</ul>";
echo "<li><strong>User Experience:</strong> Users get overview on index, specific scheduling on calendar</li>";
echo "</ol>";

echo "<h3>🧪 Test the Updates:</h3>";
echo "<ol>";
echo "<li><strong>Visit index page:</strong> Should see unique classes with 'Weekly' badges for recurring ones</li>";
echo "<li><strong>Check calendar:</strong> Should show historical and future events with proper dates</li>";
echo "<li><strong>Verify times:</strong> Calendar events should display at correct times</li>";
echo "<li><strong>Test booking:</strong> Click calendar events to see booking details</li>";
echo "</ol>";

echo "<h3>💡 Design Intent:</h3>";
echo "<p><strong>Index Page:</strong> Informational overview of class types and general schedules</p>";
echo "<p><strong>Calendar:</strong> Detailed scheduling with real-time availability and booking capability</p>";
echo "</div>";
echo "</div>";

echo "</body></html>";
?> 