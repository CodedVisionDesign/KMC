<?php
// Session fix for XAMPP permission issues
// Include this at the top of admin files if session issues persist

// Check if sessions are working
if (session_status() === PHP_SESSION_NONE) {
    // Try to set a custom session save path if default fails
    $customSessionPath = __DIR__ . '/../temp/sessions';
    
    // Create custom session directory if it doesn't exist
    if (!is_dir($customSessionPath)) {
        mkdir($customSessionPath, 0777, true);
    }
    
    // Set custom session save path
    if (is_writable($customSessionPath)) {
        ini_set('session.save_path', $customSessionPath);
    }
    
    // Start session
    session_start();
}
?> 