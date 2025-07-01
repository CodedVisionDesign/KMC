<?php
session_start();
require_once __DIR__ . '/../config/user_auth.php';

echo "<h2>User Bookings Debug Test</h2>";

// Check login status
if (!isUserLoggedIn()) {
    echo "<p style='color: red;'>❌ Not logged in</p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    exit();
}

$userInfo = getUserInfo();
echo "<p style='color: green;'>✅ Logged in as: " . $userInfo['email'] . " (ID: " . $userInfo['id'] . ")</p>";

// Test the API directly
echo "<h3>Testing User Bookings API:</h3>";

// Simulate the API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/Testbook/public/api/user_bookings.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Status:</strong> $httpCode</p>";

if ($error) {
    echo "<p style='color: red;'><strong>cURL Error:</strong> $error</p>";
}

echo "<p><strong>Response:</strong></p>";
echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
echo htmlspecialchars($response);
echo "</pre>";

// Try to decode the JSON
$data = json_decode($response, true);
if ($data) {
    echo "<p><strong>Parsed JSON:</strong></p>";
    echo "<pre style='background: #e8f5e8; padding: 10px; border-radius: 5px;'>";
    print_r($data);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ Failed to parse JSON response</p>";
}

// Test direct database query
echo "<h3>Direct Database Query:</h3>";

try {
    $host = 'localhost';
    $db   = 'testbook';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    $stmt = $pdo->prepare('
        SELECT b.id, b.class_id, b.status,
               c.name as class_name, c.date as class_date, c.time as class_time
        FROM bookings b
        JOIN classes c ON b.class_id = c.id
        WHERE b.user_id = ? AND b.status != "cancelled"
        ORDER BY c.date, c.time
    ');
    $stmt->execute([$userInfo['id']]);
    $bookings = $stmt->fetchAll();
    
    echo "<p style='color: green;'>✅ Database query successful</p>";
    echo "<p><strong>Found " . count($bookings) . " bookings:</strong></p>";
    echo "<pre style='background: #e8f5e8; padding: 10px; border-radius: 5px;'>";
    print_r($bookings);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.php'>← Back to Home</a></p>";
?> 