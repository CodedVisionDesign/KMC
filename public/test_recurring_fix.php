<?php
/**
 * Test Recurring Classes Fix
 * Verify that the index page and API now properly handle recurring classes
 */

require_once 'api/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Recurring Classes Fix</title>
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
        .recurring { background-color: #fff3cd; }
    </style>
</head>
<body>
    <h1>🧪 Test Recurring Classes Fix</h1>";

echo "<div class='section'>";
echo "<h2>1. Test Recurring Class Generation Logic</h2>";
echo "<p><strong>Testing:</strong> The same logic used in index.php and API</p>";

try {
    // Get all classes including recurring ones (same as new logic)
    $stmt = $pdo->query("
        SELECT c.id, c.name, c.description, c.date, c.time, c.capacity, c.recurring,
               c.instructor_id,
               CONCAT(i.first_name, ' ', i.last_name) as instructor_name
        FROM classes c 
        LEFT JOIN instructors i ON c.instructor_id = i.id 
        WHERE c.date >= CURDATE() OR c.recurring = 1
        ORDER BY c.date, c.time
    ");
    $allClasses = $stmt->fetchAll();
    
    // Process classes and generate recurring instances (same logic as index.php)
    $processedClasses = [];
    $today = new DateTime();
    $endDate = new DateTime('+3 months');
    
    echo "<p class='info'>📅 Generating classes from today (" . $today->format('Y-m-d') . ") to " . $endDate->format('Y-m-d') . "</p>";
    
    foreach ($allClasses as $class) {
        if ($class['recurring']) {
            echo "<p class='info'>🔄 Processing recurring class: {$class['name']} (Original date: {$class['date']})</p>";
            
            // Generate recurring instances
            $classDate = new DateTime($class['date']);
            $currentDate = clone $today;
            
            // If the original class date is in the past, start from today
            if ($classDate < $today) {
                echo "<p class='warning'>⚠️ Original date is in the past, calculating next occurrence...</p>";
                
                // Find the next occurrence of this day of the week
                $dayOfWeek = $classDate->format('N'); // 1 (Monday) to 7 (Sunday)
                $currentDayOfWeek = $currentDate->format('N');
                
                if ($currentDayOfWeek <= $dayOfWeek) {
                    $daysToAdd = $dayOfWeek - $currentDayOfWeek;
                } else {
                    $daysToAdd = 7 - $currentDayOfWeek + $dayOfWeek;
                }
                
                $currentDate->add(new DateInterval('P' . $daysToAdd . 'D'));
                echo "<p class='info'>📍 Next occurrence will be: " . $currentDate->format('Y-m-d') . " (" . $currentDate->format('l') . ")</p>";
            } else {
                $currentDate = clone $classDate;
                echo "<p class='info'>📍 Original date is in future, starting from: " . $currentDate->format('Y-m-d') . "</p>";
            }
            
            $instanceCount = 0;
            // Generate instances for the next 3 months
            while ($currentDate <= $endDate && $instanceCount < 20) { // Limit to prevent infinite loop
                $instanceClass = $class;
                $instanceClass['date'] = $currentDate->format('Y-m-d');
                $instanceClass['generated_id'] = $class['id'] . '_' . $currentDate->format('Y-m-d');
                $instanceClass['is_generated'] = true;
                $processedClasses[] = $instanceClass;
                
                $instanceCount++;
                // Move to next week
                $currentDate->add(new DateInterval('P7D'));
            }
            
            echo "<p class='success'>✅ Generated {$instanceCount} instances for this recurring class</p>";
        } else {
            // Non-recurring class, only add if it's in the future
            if ($class['date'] >= $today->format('Y-m-d')) {
                $class['is_generated'] = false;
                $processedClasses[] = $class;
                echo "<p>📅 Added regular class: {$class['name']} on {$class['date']}</p>";
            }
        }
    }
    
    echo "<p class='success'>✅ Total processed classes: " . count($processedClasses) . "</p>";
    
    if (count($processedClasses) > 0) {
        // Sort by date and time
        usort($processedClasses, function($a, $b) {
            if ($a['date'] === $b['date']) {
                return strcmp($a['time'], $b['time']);
            }
            return strcmp($a['date'], $b['date']);
        });
        
        echo "<h3>Generated Classes Schedule</h3>";
        echo "<table>";
        echo "<tr>
                <th>ID</th>
                <th>Name</th>
                <th>Date</th>
                <th>Time</th>
                <th>Day</th>
                <th>Type</th>
                <th>Instructor</th>
              </tr>";
        
        $count = 0;
        foreach ($processedClasses as $class) {
            if ($count >= 20) break; // Limit display
            
            $classType = $class['is_generated'] ? 'Generated (Recurring)' : 'Regular';
            $rowClass = $class['is_generated'] ? 'recurring' : '';
            $dayOfWeek = date('l', strtotime($class['date']));
            $displayId = $class['generated_id'] ?? $class['id'];
            
            echo "<tr class='{$rowClass}'>";
            echo "<td>{$displayId}</td>";
            echo "<td>{$class['name']}</td>";
            echo "<td>{$class['date']}</td>";
            echo "<td>{$class['time']}</td>";
            echo "<td>{$dayOfWeek}</td>";
            echo "<td>{$classType}</td>";
            echo "<td>{$class['instructor_name']}</td>";
            echo "</tr>";
            
            $count++;
        }
        echo "</table>";
        
        if (count($processedClasses) > 20) {
            echo "<p class='info'>... and " . (count($processedClasses) - 20) . " more classes</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>2. Test API Response</h2>";
echo "<p><strong>Testing:</strong> /api/classes.php endpoint</p>";

try {
    $apiUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/classes.php';
    echo "<p><strong>API URL:</strong> <a href='{$apiUrl}' target='_blank'>{$apiUrl}</a></p>";
    
    $apiResponse = file_get_contents($apiUrl);
    $apiData = json_decode($apiResponse, true);
    
    if ($apiData && $apiData['success']) {
        $apiClasses = $apiData['data']['classes'] ?? [];
        echo "<p class='success'>✅ API Response successful</p>";
        echo "<p><strong>API returned:</strong> " . count($apiClasses) . " classes</p>";
        
        $recurringCount = 0;
        $regularCount = 0;
        
        foreach ($apiClasses as $class) {
            if (isset($class['generated_id'])) {
                $recurringCount++;
            } else {
                $regularCount++;
            }
        }
        
        echo "<p><strong>Breakdown:</strong> {$regularCount} regular classes, {$recurringCount} recurring instances</p>";
        
        if (count($apiClasses) > 0) {
            echo "<h4>First 10 API Results:</h4>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Date</th><th>Time</th><th>Type</th></tr>";
            
            for ($i = 0; $i < min(10, count($apiClasses)); $i++) {
                $class = $apiClasses[$i];
                $type = isset($class['generated_id']) ? 'Recurring' : 'Regular';
                $displayId = $class['generated_id'] ?? $class['id'];
                
                echo "<tr>";
                echo "<td>{$displayId}</td>";
                echo "<td>{$class['name']}</td>";
                echo "<td>{$class['date']}</td>";
                echo "<td>{$class['time']}</td>";
                echo "<td>{$type}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p class='error'>❌ API Error: " . ($apiData['error'] ?? 'Unknown error') . "</p>";
        echo "<p><strong>Raw Response:</strong> <pre>" . htmlspecialchars($apiResponse) . "</pre></p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ API Test Error: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h2>3. Summary & Next Steps</h2>";
echo "<div class='info'>";
echo "<h3>✅ What Should Be Fixed Now:</h3>";
echo "<ul>";
echo "<li><strong>Index Page:</strong> Should now show all future classes including recurring instances</li>";
echo "<li><strong>Calendar:</strong> Should display recurring events for the next 3 months</li>";
echo "<li><strong>API:</strong> Returns properly formatted class data with recurring instances</li>";
echo "</ul>";

echo "<h3>🧪 To Test:</h3>";
echo "<ol>";
echo "<li>Visit the main index page and check the 'Available Classes' section</li>";
echo "<li>Check the calendar/timetable at the bottom of the index page</li>";
echo "<li>Verify that recurring classes show multiple future dates</li>";
echo "<li>Ensure the calendar displays events with proper times</li>";
echo "</ol>";

echo "<h3>🔍 If Issues Persist:</h3>";
echo "<ul>";
echo "<li>Check browser console for JavaScript errors</li>";
echo "<li>Verify that classes in database have proper recurring flag set</li>";
echo "<li>Check that instructor assignments are correct</li>";
echo "<li>Ensure bookings table structure is compatible</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "</body></html>";
?> 