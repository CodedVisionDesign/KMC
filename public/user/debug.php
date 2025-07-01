<?php
// Debug page to check authentication and PHP processing
require_once __DIR__ . '/../../config/user_auth.php';
require_once __DIR__ . '/../../config/security.php';

echo "<h1>Debug Information</h1>";

echo "<h2>PHP Information</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current Time: " . date('Y-m-d H:i:s') . "<br>";

echo "<h2>Session Information</h2>";
echo "Session Status: " . session_status() . " (1=disabled, 2=active, 3=none)<br>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Variables:<br>";
echo "<pre>";
print_r($_SESSION ?? []);
echo "</pre>";

echo "<h2>Authentication Status</h2>";
echo "Is User Logged In: " . (isUserLoggedIn() ? 'YES' : 'NO') . "<br>";

if (isUserLoggedIn()) {
    echo "User Info:<br>";
    echo "<pre>";
    print_r(getUserInfo());
    echo "</pre>";
} else {
    echo "User is not logged in.<br>";
}

echo "<h2>Database Connection Test</h2>";
try {
    $pdo = connectUserDB();
    echo "Database connection: SUCCESS<br>";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Users in database: " . $result['count'] . "<br>";
} catch (Exception $e) {
    echo "Database connection: FAILED<br>";
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<h2>File Paths</h2>";
echo "Current file: " . __FILE__ . "<br>";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";

echo "<h2>Server Information</h2>";
echo "Server software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "HTTP Host: " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "<br>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2 { color: #333; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style> 