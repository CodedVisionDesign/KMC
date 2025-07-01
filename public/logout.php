<?php
if (file_exists(__DIR__ . '/../config/user_auth.php')) {
    include __DIR__ . '/../config/user_auth.php';
} else {
    error_log('user_auth.php not found');
    die('Authentication system not available');
}

// Log out the user
logoutUser();

// Redirect to home page
header('Location: index.php');
exit();
?> 