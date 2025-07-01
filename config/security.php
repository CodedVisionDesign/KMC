<?php
// Security utilities

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}



function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function validateTime($time) {
    // Try H:i format first (24-hour format like 14:30)
    $t = DateTime::createFromFormat('H:i', $time);
    if ($t && $t->format('H:i') === $time) {
        return true;
    }
    
    // Try H:i:s format (with seconds like 14:30:00)
    $t = DateTime::createFromFormat('H:i:s', $time);
    if ($t && $t->format('H:i:s') === $time) {
        return true;
    }
    
    // Try g:i A format (12-hour format like 2:30 PM)
    $t = DateTime::createFromFormat('g:i A', $time);
    if ($t && $t->format('g:i A') === $time) {
        return true;
    }
    
    // Try h:i A format (12-hour format with leading zero like 02:30 PM)
    $t = DateTime::createFromFormat('h:i A', $time);
    if ($t && $t->format('h:i A') === $time) {
        return true;
    }
    
    return false;
} 