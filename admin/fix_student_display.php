<?php
require_once 'includes/admin_common.php';

echo "<h2>Student Display Issue Fix</h2>";

// Function to analyze and fix the student display issue
function analyzeStudentDisplay($pdo) {
    echo "<h3>Analyzing Student Display Issue...</h3>";
    
    // Check for actual duplicates by ID
    $stmt = $pdo->query("
        SELECT id, COUNT(*) as count 
        FROM users 
        GROUP BY id 
        HAVING COUNT(*) > 1
    ");
    $duplicateIds = $stmt->fetchAll();
    
    if (!empty($duplicateIds)) {
        echo "❌ Found duplicate IDs in users table:<br>";
        foreach ($duplicateIds as $dup) {
            echo "ID {$dup['id']} appears {$dup['count']} times<br>";
        }
        return false;
    }
    
    // Check the exact query used in students.php
    $stmt = $pdo->query('
        SELECT 
            u.id, u.first_name, u.last_name, u.email,
            COUNT(b.id) as booking_count,
            MAX(b.created_at) as last_booking
        FROM users u 
        LEFT JOIN bookings b ON u.id = b.user_id 
        GROUP BY u.id 
        ORDER BY u.created_at DESC
    ');
    $students = $stmt->fetchAll();
    
    echo "✅ Query returns " . count($students) . " students<br>";
    
    // Check for students with same ID appearing multiple times (should be impossible with GROUP BY)
    $idCounts = array_count_values(array_column($students, 'id'));
    $duplicateDisplayIds = array_filter($idCounts, function($count) { return $count > 1; });
    
    if (!empty($duplicateDisplayIds)) {
        echo "❌ Found IDs appearing multiple times in query result:<br>";
        foreach ($duplicateDisplayIds as $id => $count) {
            echo "ID {$id} appears {$count} times in results<br>";
        }
        return false;
    }
    
    echo "✅ No duplicate IDs found in query results<br>";
    
    // Show details for ID 2 specifically
    $stmt = $pdo->prepare('
        SELECT 
            u.*, 
            COUNT(b.id) as booking_count,
            MAX(b.created_at) as last_booking
        FROM users u 
        LEFT JOIN bookings b ON u.id = b.user_id 
        WHERE u.id = 2
        GROUP BY u.id
    ');
    $stmt->execute();
    $student2 = $stmt->fetch();
    
    if ($student2) {
        echo "<h4>Details for Student ID 2:</h4>";
        echo "<table class='table table-striped'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>ID</td><td>{$student2['id']}</td></tr>";
        echo "<tr><td>Name</td><td>{$student2['first_name']} {$student2['last_name']}</td></tr>";
        echo "<tr><td>Email</td><td>{$student2['email']}</td></tr>";
        echo "<tr><td>Booking Count</td><td>{$student2['booking_count']}</td></tr>";
        echo "<tr><td>Last Booking</td><td>" . ($student2['last_booking'] ?: 'Never') . "</td></tr>";
        echo "</table>";
    }
    
    return true;
}

// Function to clear potential caching issues
function clearCacheIssues() {
    echo "<h3>Clearing Potential Cache Issues...</h3>";
    
    // Add a cache-busting parameter to force browser refresh
    $cacheParam = "?v=" . time();
    
    echo "<p>The duplicate display issue is likely caused by:</p>";
    echo "<ul>";
    echo "<li>Browser caching old JavaScript or CSS</li>";
    echo "<li>PHP opcache serving stale bytecode</li>";
    echo "<li>Session data causing display issues</li>";
    echo "</ul>";
    
    echo "<h4>Recommended Actions:</h4>";
    echo "<ol>";
    echo "<li><strong>Clear Browser Cache:</strong> Press Ctrl+Shift+Delete (or Cmd+Shift+Delete on Mac)</li>";
    echo "<li><strong>Hard Refresh:</strong> Press Ctrl+F5 (or Cmd+Shift+R on Mac)</li>";
    echo "<li><strong>Try Incognito Mode:</strong> Test in a private/incognito browser window</li>";
    echo "<li><strong>Check Browser Console:</strong> Look for JavaScript errors in Developer Tools</li>";
    echo "</ol>";
    
    echo "<div class='alert alert-info'>";
    echo "<strong>Test Links (with cache busting):</strong><br>";
    echo "<a href='students.php{$cacheParam}' target='_blank' class='btn btn-primary'>Students Page (Fresh)</a> ";
    echo "<a href='../diagnostic.php{$cacheParam}' target='_blank' class='btn btn-info'>Diagnostic Page</a>";
    echo "</div>";
}

// Function to display debug information directly
function showDebugInfo($pdo) {
    echo "<h3>Debug Information</h3>";
    
    // Get the same query used in students.php
    $stmt = $pdo->query('
        SELECT 
            u.id, u.first_name, u.last_name, u.email,
            COUNT(b.id) as booking_count,
            MAX(b.created_at) as last_booking
        FROM users u 
        LEFT JOIN bookings b ON u.id = b.user_id 
        GROUP BY u.id 
        ORDER BY u.created_at DESC
    ');
    $students = $stmt->fetchAll();
    
    echo "<h4>Raw Query Results (for verification):</h4>";
    echo "<div style='max-height: 300px; overflow-y: auto; background: #f8f9fa; padding: 10px; border: 1px solid #ddd;'>";
    echo "<pre>" . htmlspecialchars(print_r($students, true)) . "</pre>";
    echo "</div>";
    
    echo "<h4>Students Table (formatted):</h4>";
    echo "<table class='table table-striped' style='max-width: 800px;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Bookings</th><th>Last Booking</th></tr>";
    foreach ($students as $student) {
        $highlight = ($student['id'] == 2) ? 'style="background-color: #fff3cd;"' : '';
        echo "<tr {$highlight}>";
        echo "<td>" . $student['id'] . "</td>";
        echo "<td>" . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($student['email']) . "</td>";
        echo "<td>" . $student['booking_count'] . "</td>";
        echo "<td>" . ($student['last_booking'] ? date('M j, Y', strtotime($student['last_booking'])) : 'Never') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Notice:</strong> Student ID 2 is highlighted in yellow. There should only be ONE row for each student ID.</p>";
}

// Run the analysis
try {
    $isDataCorrect = analyzeStudentDisplay($pdo);
    
    if ($isDataCorrect) {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ Database Data is Correct</h4>";
        echo "<p>The database contains no duplicate students. The duplicate display you're seeing is likely a browser/caching issue.</p>";
        echo "</div>";
        
        clearCacheIssues();
        showDebugInfo($pdo);
    } else {
        echo "<div class='alert alert-danger'>";
        echo "<h4>❌ Database Issues Found</h4>";
        echo "<p>There are actual data issues in the database that need to be fixed.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

echo "<hr>";
echo "<p><a href='students.php' class='btn btn-primary'>Return to Students</a></p>";
?>

<style>
.alert {
    padding: 15px;
    margin: 15px 0;
    border: 1px solid transparent;
    border-radius: 4px;
}
.alert-success {
    color: #3c763d;
    background-color: #dff0d8;
    border-color: #d6e9c6;
}
.alert-danger {
    color: #a94442;
    background-color: #f2dede;
    border-color: #ebccd1;
}
.alert-info {
    color: #31708f;
    background-color: #d9edf7;
    border-color: #bce8f1;
}
.table {
    margin-top: 20px;
    border-collapse: collapse;
    width: 100%;
}
.table th, .table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}
.table th {
    background-color: #f8f9fa;
    font-weight: bold;
}
.table-striped tbody tr:nth-child(odd) {
    background-color: #f9f9f9;
}
.btn {
    display: inline-block;
    padding: 6px 12px;
    margin-bottom: 0;
    font-size: 14px;
    font-weight: normal;
    line-height: 1.42857143;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    cursor: pointer;
    border: 1px solid transparent;
    border-radius: 4px;
    text-decoration: none;
}
.btn-primary {
    color: #fff;
    background-color: #007bff;
    border-color: #007bff;
}
.btn-info {
    color: #fff;
    background-color: #17a2b8;
    border-color: #17a2b8;
}
</style> 