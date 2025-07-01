<?php
require_once __DIR__ . '/../../config/user_auth.php';
require_once __DIR__ . '/../../config/security.php';

// Check if user is logged in
if (!isUserLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$userInfo = getUserInfo();
$pageTitle = 'Health Details';

// Simple test output
echo "<!DOCTYPE html>";
echo "<html><head><title>Health Test</title></head><body>";
echo "<h1>Health Details - Simple Test</h1>";
echo "<p>User: " . htmlspecialchars($userInfo['first_name']) . "</p>";
echo "<p>This is a working PHP page!</p>";
echo "</body></html>";
?> 