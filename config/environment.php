<?php
// Class Booking System - Environment Configuration
// Development Environment Settings

// Application Environment
define('APP_ENV', 'development');

// Security Settings
define('SESSION_LIFETIME', 3600);
define('CSRF_TOKEN_LIFETIME', 3600);

// Error Logging Configuration
define('LOG_ERRORS', true);
define('ERROR_LOG_FILE', __DIR__ . '/../logs/error.log');

// Development settings - show errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');

// Create logs directory if it doesn't exist
$logsDir = __DIR__ . '/../logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
}

// Email Configuration (for future features)
// define('SMTP_HOST', '');
// define('SMTP_PORT', 587);
// define('SMTP_USER', '');
// define('SMTP_PASS', '');
// define('FROM_EMAIL', 'noreply@testbook.local');
// define('FROM_NAME', 'Testbook Class Booking System');
?> 