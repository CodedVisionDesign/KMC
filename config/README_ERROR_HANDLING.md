# Error Handling System

## Overview

The Class Booking System now includes a comprehensive error handling system that provides consistent error messages and proper production error management.

## Environment Configuration

### Setting Up Environment

1. Copy `config/environment.example` to `config/environment.php`
2. Set `APP_ENV` to either `'production'` or `'development'`
3. Customize other settings as needed

### Environment Modes

- **Development**: Shows detailed error messages, displays all PHP errors
- **Production**: Hides technical details from users, logs errors to file

## Using Standardized Error Messages

### In PHP Files

```php
// Include error handling at the top of each file
require_once __DIR__ . '/config/error_handling.php';

// Use standardized error messages
$error = ErrorMessages::INVALID_EMAIL;
$error = ErrorMessages::REQUIRED_FIELDS;
$error = ErrorMessages::LOGIN_FAILED;
```

### Available Error Messages

- `GENERIC_ERROR` - "An error occurred. Please try again."
- `SYSTEM_UNAVAILABLE` - "System temporarily unavailable. Please try again later."
- `LOGIN_FAILED` - "Invalid credentials. Please check your email and password."
- `REQUIRED_FIELDS` - "Please fill in all required fields."
- `INVALID_EMAIL` - "Please enter a valid email address."
- `CSRF_INVALID` - "Security validation failed. Please try again."
- `CLASS_NOT_FOUND` - "The requested class could not be found."
- `OPERATION_FAILED` - "Operation failed. Please try again."
- And many more...

### Formatting Messages

```php
// For HTML display
$html = ErrorMessages::formatError($message);
$html = ErrorMessages::formatSuccess($message);

// For plain text
$text = ErrorMessages::formatError($message, false);
```

### API Responses

```php
// Success response
echo ErrorMessages::apiSuccess($data, "Operation completed");

// Error response
echo ErrorMessages::apiError("Error message", 400);
```

## File Structure

```
config/
├── error_handling.php      # Main error handling configuration
├── environment.example     # Template for environment config
├── environment.php         # Your environment config (create from example)
└── README_ERROR_HANDLING.md # This documentation

logs/
└── error.log              # Error log file (created automatically)
```

## Production Deployment

### Steps for Production

1. Copy `environment.example` to `environment.php`
2. Set `define('APP_ENV', 'production');` in `environment.php`
3. Ensure `logs/` directory is writable
4. Test error handling is working correctly
5. Monitor `logs/error.log` for any issues

### Security Benefits

- Technical error details are hidden from users
- All errors are logged for debugging
- Consistent, professional error messages
- Custom error handlers prevent information disclosure

## Monitoring Errors

Check the error log regularly:

```bash
tail -f logs/error.log
```

## Adding New Error Messages

Edit `config/error_handling.php` and add new constants to the `ErrorMessages` class:

```php
const NEW_ERROR_TYPE = 'Your error message here.';
```

Then use it in your code:

```php
$error = ErrorMessages::NEW_ERROR_TYPE;
```
