<?php
/**
 * Common Admin Include File
 * Handles authentication, database connection, security, and template setup
 * Include this at the top of every admin page
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include security configuration
if (file_exists(__DIR__ . '/../../config/security.php')) {
    require_once __DIR__ . '/../../config/security.php';
} else {
    error_log('Security config not found');
    die('Security system not available');
}

// Include error handling
if (file_exists(__DIR__ . '/../../config/error_handling.php')) {
    require_once __DIR__ . '/../../config/error_handling.php';
} else {
    error_log('Error handling config not found');
    die('Error handling system not available');
}

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Include database connection
if (file_exists(__DIR__ . '/../../public/api/db.php')) {
    require_once __DIR__ . '/../../public/api/db.php';
} else {
    error_log('Database connection file not found');
    die('Database system not available');
}

// Helper function to render admin page
function renderAdminPage($content, $options = []) {
    // Set default options
    $defaults = [
        'pageDescription' => null,
        'headerActions' => null,
        'additionalCSS' => [],
        'additionalJS' => [],
        'inlineJS' => null,
        'success' => null,
        'error' => null,
        'message' => null
    ];
    
    // Merge options with defaults
    $options = array_merge($defaults, $options);
    
    // Extract variables for use in templates
    extract($options);
    
    // Include header
    include __DIR__ . '/../templates/header.php';
    
    // Output main content
    echo $content;
    
    // Include footer
    include __DIR__ . '/../templates/footer.php';
}

// Helper function to check if instructors table exists
function instructorsTableExists($pdo) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM instructors LIMIT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Helper function to get user-friendly error messages
function getErrorMessage($exception) {
    if (strpos($exception->getMessage(), 'Duplicate entry') !== false) {
        return 'A record with this information already exists.';
    } elseif (strpos($exception->getMessage(), 'foreign key constraint') !== false) {
        return 'Cannot delete this record because it is being used elsewhere.';
    } else {
        error_log('Database error: ' . $exception->getMessage());
        return 'An error occurred while processing your request. Please try again.';
    }
}

// Helper function to sanitize and validate input with length checking
// This wraps the sanitizeInput from security.php with additional length validation
function sanitizeInputWithLength($input, $maxLength = null) {
    $input = sanitizeInput(trim($input)); // Use the function from security.php
    if ($maxLength && strlen($input) > $maxLength) {
        throw new InvalidArgumentException("Input exceeds maximum length of {$maxLength} characters.");
    }
    return $input;
}

// Helper function to validate email
function validateEmailInput($email) {
    $email = sanitizeInputWithLength($email, 100);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException("Please enter a valid email address.");
    }
    return $email;
}

// Helper function to validate date
function validateDateInput($date) {
    if (!$date || !strtotime($date)) {
        throw new InvalidArgumentException("Please enter a valid date.");
    }
    return $date;
}

// Helper function to validate time
function validateTimeInput($time) {
    if (!$time || !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
        throw new InvalidArgumentException("Please enter a valid time in HH:MM format.");
    }
    return $time;
}

// Helper function to create header actions
function createHeaderActions($actions) {
    $html = '';
    foreach ($actions as $action) {
        $class = $action['class'] ?? 'btn btn-primary';
        $href = isset($action['href']) ? 'href="' . htmlspecialchars($action['href']) . '"' : '';
        $onclick = isset($action['onclick']) ? 'onclick="' . htmlspecialchars($action['onclick']) . '"' : '';
        $icon = isset($action['icon']) ? '<i class="' . htmlspecialchars($action['icon']) . ' me-1"></i>' : '';
        $text = htmlspecialchars($action['text']);
        
        $html .= "<a class=\"{$class}\" {$href} {$onclick}>{$icon}{$text}</a>";
    }
    return $html;
}

// Make PDO connection available globally for the admin templates
global $pdo; 