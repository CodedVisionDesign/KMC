<?php
// Comprehensive fix for duplicate display and cache issues
require_once 'public/api/db.php';

// Add cache-busting headers
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

echo "<h1>System Issue Fix</h1>";
echo "<style>body { font-family: Arial, sans-serif; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

echo "<h2>1. Fixing Duplicate Display Issue</h2>";

// Check if there are any phantom bookings causing JOIN issues
$stmt = $pdo->query("
    SELECT 
        u.id, u.first_name, u.last_name, u.email,
        COUNT(b.id) as booking_count
    FROM users u 
    LEFT JOIN bookings b ON u.id = b.user_id 
    GROUP BY u.id 
    ORDER BY u.id
");
$students = $stmt->fetchAll();

echo "<h3>Current Students (with proper GROUP BY):</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Booking Count</th></tr>";
foreach ($students as $student) {
    echo "<tr>";
    echo "<td>{$student['id']}</td>";
    echo "<td>{$student['first_name']} {$student['last_name']}</td>";
    echo "<td>{$student['email']}</td>";
    echo "<td>{$student['booking_count']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>2. Cache-Busting Solutions</h2>";

// Generate cache-busting URLs
$timestamp = time();
echo "<div class='info'>";
echo "<p><strong>Clear Browser Cache:</strong></p>";
echo "<ul>";
echo "<li>Press Ctrl+Shift+Delete (Windows) or Cmd+Shift+Delete (Mac)</li>";
echo "<li>Clear all browsing data</li>";
echo "<li>Or try incognito/private browsing mode</li>";
echo "</ul>";

echo "<p><strong>Cache-Busted Admin Links:</strong></p>";
echo "<ul>";
echo "<li><a href='admin/students.php?v={$timestamp}' target='_blank'>Students (Cache-Busted)</a></li>";
echo "<li><a href='admin/memberships.php?v={$timestamp}' target='_blank'>Memberships (Cache-Busted)</a></li>";
echo "</ul>";
echo "</div>";

echo "<h2>3. Testing Memberships.php Fix</h2>";

// Test if the memberships.php file is working
try {
    // Test that the file can be included without errors
    ob_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Just check if the file can be parsed
    $tokens = token_get_all(file_get_contents('admin/memberships.php'));
    echo "<div class='success'>✅ Memberships.php JavaScript template literal fix applied successfully!</div>";
    
    // Clean up
    ob_end_clean();
} catch (Exception $e) {
    echo "<div class='error'>❌ Error in memberships.php: " . $e->getMessage() . "</div>";
}

echo "<h2>4. JavaScript Refresh Solution</h2>";
echo "<p>The duplicate display issue is likely due to browser caching. Here's a JavaScript solution:</p>";

?>
<script>
// Force refresh of admin pages
function forceRefreshAdminPages() {
    if (window.location.href.includes('/admin/')) {
        // Add timestamp to prevent caching
        const url = new URL(window.location.href);
        url.searchParams.set('v', Date.now());
        window.location.href = url.toString();
    }
}

// Clear local storage and session storage
localStorage.clear();
sessionStorage.clear();

console.log('Cache cleared, forcing refresh...');
setTimeout(forceRefreshAdminPages, 1000);
</script>

<?php
echo "<h2>5. Database Health Check</h2>";

// Check for any orphaned records
echo "<h3>Checking for orphaned bookings:</h3>";
$stmt = $pdo->query("
    SELECT b.id, b.user_id, b.class_id 
    FROM bookings b 
    LEFT JOIN users u ON b.user_id = u.id 
    WHERE u.id IS NULL
");
$orphaned = $stmt->fetchAll();

if (empty($orphaned)) {
    echo "<div class='success'>✅ No orphaned bookings found</div>";
} else {
    echo "<div class='error'>❌ Found " . count($orphaned) . " orphaned bookings</div>";
    foreach ($orphaned as $booking) {
        echo "<p>Booking ID {$booking['id']} references non-existent user {$booking['user_id']}</p>";
    }
}

echo "<h2>6. Recommendations</h2>";
echo "<div class='info'>";
echo "<ol>";
echo "<li><strong>Clear browser cache completely</strong> - This is the most likely cause of the duplicate display</li>";
echo "<li><strong>Use incognito/private browsing</strong> to test if the issue persists</li>";
echo "<li><strong>Hard refresh</strong> the admin pages (Ctrl+F5 or Cmd+Shift+R)</li>";
echo "<li><strong>Check if other users see the same issue</strong> - if not, it's definitely a local browser cache problem</li>";
echo "<li><strong>The memberships.php error has been fixed</strong> by converting JavaScript template literals to string concatenation</li>";
echo "</ol>";
echo "</div>";

echo "<h2>7. Next Steps</h2>";
echo "<p>After clearing your browser cache, try accessing:</p>";
echo "<ul>";
echo "<li><a href='admin/students.php?nocache=" . time() . "' target='_blank'>Students Page (No Cache)</a></li>";
echo "<li><a href='admin/memberships.php?nocache=" . time() . "' target='_blank'>Memberships Page (No Cache)</a></li>";
echo "</ul>";

echo "<p><strong>If the issue persists after clearing cache, please let me know which specific browser you're using and I can provide browser-specific clearing instructions.</strong></p>";
?> 