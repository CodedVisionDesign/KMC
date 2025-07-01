<?php
// Simple test script for Class Booking System

echo "<h1>Class Booking System - Test Script</h1>";

// Test 1: PHP Version
echo "<h2>1. PHP Version Check</h2>";
$phpVersion = phpversion();
echo "PHP Version: " . $phpVersion;
if (version_compare($phpVersion, '7.4.0', '>=')) {
    echo " ✅ OK";
} else {
    echo " ❌ Requires PHP 7.4+";
}
echo "<br><br>";

// Test 2: Database Connection
echo "<h2>2. Database Connection Test</h2>";
try {
    require_once __DIR__ . '/public/api/db.php';
    echo "Database connection: ✅ SUCCESS<br>";
    
    // Test tables exist
    $tables = ['classes', 'bookings', 'admin'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "Table '$table': ✅ EXISTS<br>";
        } else {
            echo "Table '$table': ❌ MISSING<br>";
        }
    }
} catch (Exception $e) {
    echo "Database connection: ❌ FAILED<br>";
    echo "Error: " . $e->getMessage() . "<br>";
}
echo "<br>";

// Test 3: API Endpoints
echo "<h2>3. API Endpoints Test</h2>";
$endpoints = [
    '/api/classes.php' => 'Classes API',
    '/api/class.php?id=1' => 'Single Class API',
];

foreach ($endpoints as $endpoint => $name) {
    $url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/public' . $endpoint;
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);
    
    $result = @file_get_contents($url, false, $context);
    if ($result !== false) {
        $data = json_decode($result, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "$name: ✅ OK<br>";
        } else {
            echo "$name: ❌ Invalid JSON response<br>";
        }
    } else {
        echo "$name: ❌ Connection failed<br>";
    }
}
echo "<br>";

// Test 4: File Permissions
echo "<h2>4. File Structure Test</h2>";
$requiredDirs = [
    'public',
    'admin', 
    'assets/css',
    'assets/js',
    'config',
    'templates'
];

foreach ($requiredDirs as $dir) {
    if (is_dir($dir)) {
        echo "Directory '$dir': ✅ EXISTS<br>";
    } else {
        echo "Directory '$dir': ❌ MISSING<br>";
    }
}

$requiredFiles = [
    'public/index.php',
    'admin/login.php',
    'templates/base.php',
    'assets/css/custom.css',
    'assets/js/main.js'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "File '$file': ✅ EXISTS<br>";
    } else {
        echo "File '$file': ❌ MISSING<br>";
    }
}
echo "<br>";

// Test 5: Sample Data
echo "<h2>5. Sample Data Test</h2>";
try {
    $stmt = $pdo->query('SELECT COUNT(*) FROM classes');
    $classCount = $stmt->fetchColumn();
    echo "Classes in database: $classCount ";
    echo ($classCount > 0) ? "✅ OK<br>" : "⚠️ No sample data<br>";
    
    $stmt = $pdo->query('SELECT COUNT(*) FROM admin');
    $adminCount = $stmt->fetchColumn();
    echo "Admin users: $adminCount ";
    echo ($adminCount > 0) ? "✅ OK<br>" : "❌ No admin user<br>";
    
} catch (Exception $e) {
    echo "Sample data check failed: " . $e->getMessage() . "<br>";
}

echo "<br><h2>Test Complete!</h2>";
echo "<p><a href='public/index.php'>Go to Main Site</a> | <a href='admin/login.php'>Go to Admin</a></p>";
echo "<p><strong>Note:</strong> Delete this test.php file before deploying to production.</p>";
?> 