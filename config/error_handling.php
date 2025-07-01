<?php
/**
 * Centralized Error Handling Configuration
 * Handles error reporting and display based on environment
 */

// Load environment configuration if available
if (file_exists(__DIR__ . '/environment.php')) {
    require_once __DIR__ . '/environment.php';
}

// Determine environment (production vs development)
// Priority: environment.php > environment variable > default
$environment = (defined('APP_ENV') ? APP_ENV : ($_ENV['APP_ENV'] ?? 'development'));
$isProduction = ($environment === 'production');

if ($isProduction) {
    // Production settings - hide errors from users
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/error.log');
} else {
    // Development settings - show errors for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}

/**
 * Standardized Error Messages
 * Consistent error messaging across the application
 */
class ErrorMessages {
    // Generic messages
    const GENERIC_ERROR = 'An error occurred. Please try again.';
    const SYSTEM_UNAVAILABLE = 'System temporarily unavailable. Please try again later.';
    const INVALID_REQUEST = 'Invalid request. Please check your input and try again.';
    
    // Authentication and authorization
    const LOGIN_FAILED = 'Invalid credentials. Please check your email and password.';
    const LOGIN_REQUIRED = 'Please log in to access this feature.';
    const ACCESS_DENIED = 'Access denied. You do not have permission to perform this action.';
    const SESSION_EXPIRED = 'Your session has expired. Please log in again.';
    const CSRF_INVALID = 'Security validation failed. Please try again.';
    
    // Form validation
    const REQUIRED_FIELDS = 'Please fill in all required fields.';
    const INVALID_EMAIL = 'Please enter a valid email address.';
    const INVALID_PASSWORD = 'Password must be at least 8 characters and contain both letters and numbers.';
    const PASSWORDS_MISMATCH = 'Passwords do not match.';
    const INVALID_DATE = 'Please enter a valid date.';
    const INVALID_TIME = 'Please enter a valid time.';
    
    // Input length validation
    const NAME_TOO_LONG = 'Name must be 100 characters or less.';
    const EMAIL_TOO_LONG = 'Email address must be 100 characters or less.';
    const PASSWORD_TOO_LONG = 'Password is too long.';
    const DESCRIPTION_TOO_LONG = 'Description must be 500 characters or less.';
    const PHONE_TOO_LONG = 'Phone number must be 20 characters or less.';
    
    // Business logic
    const CLASS_NOT_FOUND = 'The requested class could not be found.';
    const CLASS_FULLY_BOOKED = 'This class is fully booked.';
    const ALREADY_BOOKED = 'You have already booked this class.';
    const INVALID_CAPACITY = 'Class capacity must be greater than 0.';
    const BOOKING_FAILED = 'Failed to process your booking. Please try again.';
    
    // Database and system errors
    const DATABASE_ERROR = 'Database connection failed. Please try again later.';
    const FILE_NOT_FOUND = 'Required system file not found.';
    const OPERATION_FAILED = 'Operation failed. Please try again.';
    
    /**
     * Get user-friendly error message based on error type
     */
    public static function getStandardMessage($messageType) {
        return constant("self::$messageType") ?? self::GENERIC_ERROR;
    }
    
    /**
     * Format error message for display
     */
    public static function formatError($message, $isHtml = true) {
        if ($isHtml) {
            return '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
        }
        return $message;
    }
    
    /**
     * Format success message for display
     */
    public static function formatSuccess($message, $isHtml = true) {
        if ($isHtml) {
            return '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
        }
        return $message;
    }
    
    /**
     * Handle API error responses
     */
    public static function apiError($message, $httpCode = 400) {
        http_response_code($httpCode);
        return json_encode(['success' => false, 'error' => $message]);
    }
    
    /**
     * Handle API success responses
     */
    public static function apiSuccess($data = null, $message = null) {
        $response = ['success' => true];
        if ($message) $response['message'] = $message;
        if ($data) $response['data'] = $data;
        return json_encode($response);
    }
}

/**
 * Custom error handler for production
 */
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    global $isProduction;
    
    // Log the error
    error_log("Error [$errno]: $errstr in $errfile on line $errline");
    
    // In production, don't display technical error details
    if ($isProduction && $errno !== E_USER_ERROR) {
        return true; // Suppress the error from being displayed
    }
    
    return false; // Let PHP handle the error normally in development
}

/**
 * Custom exception handler for production
 */
function customExceptionHandler($exception) {
    global $isProduction;
    
    // Log the exception
    error_log("Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    
    if ($isProduction) {
        // Show generic error page in production
        if (!headers_sent()) {
            http_response_code(500);
            if (php_sapi_name() !== 'cli') {
                echo ErrorMessages::formatError(ErrorMessages::SYSTEM_UNAVAILABLE);
            }
        }
    } else {
        // Show detailed error in development
        echo "<h1>Uncaught Exception</h1>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
    }
}

// Set custom error and exception handlers
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');

// Create logs directory if it doesn't exist
$logsDir = __DIR__ . '/../logs';
if (!is_dir($logsDir)) {
    @mkdir($logsDir, 0755, true);
}
?> 