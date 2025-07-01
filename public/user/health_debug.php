<?php
echo "Starting health debug...<br>";

echo "Testing require_once user_auth.php...<br>";
require_once __DIR__ . '/../../config/user_auth.php';
echo "user_auth.php loaded successfully!<br>";

echo "Testing require_once security.php...<br>";
require_once __DIR__ . '/../../config/security.php';
echo "security.php loaded successfully!<br>";

echo "Testing isUserLoggedIn()...<br>";
if (function_exists('isUserLoggedIn')) {
    if (isUserLoggedIn()) {
        echo "User is logged in!<br>";
        $userInfo = getUserInfo();
        echo "User info: " . print_r($userInfo, true) . "<br>";
    } else {
        echo "User is NOT logged in!<br>";
    }
} else {
    echo "isUserLoggedIn function not found!<br>";
}

echo "Including header.php...<br>";
$pageTitle = 'Health Details Debug';
include 'header.php';

echo "Header included successfully!<br>";
echo "Script completed successfully!<br>";
?> 