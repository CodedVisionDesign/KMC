# Class Booking System - API Documentation

A comprehensive guide to all public APIs, functions, and components in the Class Booking System.

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Public APIs](#public-apis)
4. [User Management](#user-management)
5. [Membership System](#membership-system)
6. [Admin Functions](#admin-functions)
7. [Error Handling](#error-handling)
8. [Frontend Components](#frontend-components)
9. [Configuration](#configuration)
10. [Database Schema](#database-schema)
11. [File Upload System](#file-upload-system)
12. [Security Functions](#security-functions)

---

## Overview

The Class Booking System is a comprehensive web application built with PHP, MySQL, and modern frontend technologies. It provides a complete solution for managing fitness class bookings, memberships, and user accounts.

### Technology Stack
- **Backend**: PHP 7.4+ with PDO
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3 (Bootstrap 5), JavaScript (FullCalendar)
- **Authentication**: Session-based with password hashing
- **Security**: CSRF protection, input validation, SQL injection prevention

---

## Authentication

### User Authentication Functions

Located in: `config/user_auth.php`

#### `registerUser($firstName, $lastName, $email, $password, $phone = null, $dateOfBirth = null, $gender = null, $healthQuestionnaire = null)`

Registers a new user in the system.

**Parameters:**
- `$firstName` (string): User's first name
- `$lastName` (string): User's last name  
- `$email` (string): Valid email address
- `$password` (string): Plain text password (will be hashed)
- `$phone` (string, optional): Phone number
- `$dateOfBirth` (string, optional): Date in Y-m-d format
- `$gender` (string, optional): 'male', 'female', 'other', 'prefer_not_to_say'
- `$healthQuestionnaire` (array, optional): Health questionnaire data

**Returns:** User ID of newly created user

**Example:**
```php
try {
    $userId = registerUser(
        'John', 
        'Doe', 
        'john@example.com', 
        'securePassword123',
        '+44123456789',
        '1990-01-01',
        'male',
        [
            'has_medical_conditions' => false,
            'takes_medication' => false,
            'has_injuries' => false,
            'has_allergies' => false,
            'consent_medical_emergency' => true
        ]
    );
    echo "User registered with ID: $userId";
} catch (Exception $e) {
    echo "Registration failed: " . $e->getMessage();
}
```

#### `loginUser($email, $password)`

Authenticates a user and creates a session.

**Parameters:**
- `$email` (string): User's email address
- `$password` (string): Plain text password

**Returns:** User data array

**Example:**
```php
try {
    $user = loginUser('john@example.com', 'securePassword123');
    echo "Welcome " . $user['first_name'];
} catch (Exception $e) {
    echo "Login failed: " . $e->getMessage();
}
```

#### `isUserLoggedIn()`

Checks if a user is currently logged in.

**Returns:** Boolean

**Example:**
```php
if (isUserLoggedIn()) {
    echo "User is logged in";
} else {
    echo "Please log in";
}
```

#### `getUserInfo()`

Gets information about the currently logged-in user.

**Returns:** Array with user information or null

**Example:**
```php
$userInfo = getUserInfo();
if ($userInfo) {
    echo "Hello " . $userInfo['first_name'];
    echo "Email: " . $userInfo['email'];
}
```

---

## Public APIs

### Classes API

#### GET `/public/api/classes.php`

Retrieves all available classes with booking information and recurring class instances.

**Response Format:**
```json
{
    "success": true,
    "data": {
        "classes": [
            {
                "id": 1,
                "name": "Beginner Krav Maga",
                "description": "Introduction to basic Krav Maga techniques",
                "date": "2024-01-15",
                "time": "18:00",
                "capacity": 20,
                "current_bookings": 5,
                "spots_remaining": 15,
                "availability_status": "available",
                "availability_percentage": 75,
                "recurring": 1,
                "days_of_week": ["monday", "wednesday"],
                "instructor_id": 1,
                "instructor_name": "John Smith",
                "instructor_bio": "Certified Krav Maga instructor",
                "instructor_specialties": "Self Defense, Fitness"
            }
        ],
        "last_updated": "2024-01-15 10:30:00",
        "server_time": 1705312200
    }
}
```

**Availability Status Values:**
- `available`: More than 20% of spots remaining
- `low`: 20% or fewer spots remaining
- `full`: No spots remaining

**Example Usage:**
```javascript
fetch('/public/api/classes.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            data.data.classes.forEach(classItem => {
                console.log(`${classItem.name} - ${classItem.spots_remaining} spots left`);
            });
        }
    });
```

#### GET `/public/api/class.php?id={id}`

Retrieves details for a specific class.

**Parameters:**
- `id` (integer): Class ID

**Response Format:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Beginner Krav Maga",
        "description": "Introduction to basic Krav Maga techniques",
        "date": "2024-01-15",
        "time": "18:00",
        "capacity": 20,
        "current_bookings": 5,
        "spots_remaining": 15,
        "instructor_name": "John Smith"
    }
}
```

### Booking API

#### POST `/public/api/book.php`

Books a class for the currently logged-in user.

**Authentication Required:** Yes

**Request Body:**
```json
{
    "class_id": 1
}
```

**Response Format:**
```json
{
    "success": true,
    "message": "Class booked successfully!",
    "remaining_classes": 7
}
```

**Error Responses:**
```json
{
    "success": false,
    "error": "Class is fully booked"
}
```

**Example Usage:**
```javascript
fetch('/public/api/book.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        class_id: 1
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        alert(data.message);
    } else {
        alert('Booking failed: ' + data.error);
    }
});
```

#### POST `/public/api/cancel_booking.php`

Cancels a booking for the currently logged-in user.

**Authentication Required:** Yes

**Request Body:**
```json
{
    "booking_id": 123
}
```

**Response Format:**
```json
{
    "success": true,
    "message": "Booking cancelled successfully"
}
```

### User Bookings API

#### GET `/public/api/user_bookings.php`

Retrieves all bookings for the currently logged-in user.

**Authentication Required:** Yes

**Response Format:**
```json
{
    "success": true,
    "data": {
        "bookings": [
            {
                "id": 123,
                "class_id": 1,
                "class_name": "Beginner Krav Maga",
                "class_date": "2024-01-15",
                "class_time": "18:00",
                "booking_date": "2024-01-10 14:30:00",
                "status": "confirmed",
                "is_free_trial": 0
            }
        ]
    }
}
```

---

## Membership System

Located in: `config/membership_functions.php`

### Core Membership Functions

#### `canUserBookClass($userId)`

Determines if a user can book a class based on their membership status and limits.

**Parameters:**
- `$userId` (integer): User ID

**Returns:** Array with booking eligibility information

**Example:**
```php
$canBook = canUserBookClass(123);
if ($canBook['canBook']) {
    echo $canBook['message']; // "You can book classes! (2/8 used this month)"
} else {
    echo $canBook['message']; // "You have reached your monthly class limit"
}
```

**Return Structure:**
```php
[
    'canBook' => true/false,
    'reason' => 'free_trial|membership_valid|no_membership|limit_reached',
    'message' => 'Human readable message',
    'current_count' => 2, // Optional: current monthly usage
    'limit' => 8 // Optional: monthly limit
]
```

#### `getUserActiveMembership($userId)`

Gets the user's current active membership.

**Parameters:**
- `$userId` (integer): User ID

**Returns:** Membership data array or null

**Example:**
```php
$membership = getUserActiveMembership(123);
if ($membership) {
    echo "Plan: " . $membership['plan_name'];
    echo "Expires: " . $membership['end_date'];
    echo "Monthly limit: " . ($membership['monthly_class_limit'] ?? 'Unlimited');
}
```

#### `getUserMonthlyClassCount($userId, $yearMonth = null)`

Gets the number of classes a user has booked in a specific month.

**Parameters:**
- `$userId` (integer): User ID
- `$yearMonth` (string, optional): Year-month in 'Y-m' format (defaults to current month)

**Returns:** Integer count

**Example:**
```php
$currentMonth = getUserMonthlyClassCount(123);
$lastMonth = getUserMonthlyClassCount(123, '2023-12');
echo "This month: $currentMonth classes";
echo "Last month: $lastMonth classes";
```

#### `hasUserUsedFreeTrial($userId)`

Checks if a user has used their free trial class.

**Parameters:**
- `$userId` (integer): User ID

**Returns:** Boolean

**Example:**
```php
if (!hasUserUsedFreeTrial(123)) {
    echo "Free trial available!";
} else {
    echo "Free trial already used";
}
```

#### `getAvailableMembershipPlans()`

Gets all active membership plans.

**Returns:** Array of membership plans

**Example:**
```php
$plans = getAvailableMembershipPlans();
foreach ($plans as $plan) {
    echo $plan['name'] . " - £" . $plan['price'] . "/month";
    echo "Classes: " . ($plan['monthly_class_limit'] ?? 'Unlimited');
}
```

#### `createUserMembership($userId, $planId, $startDate = null, $duration = 1)`

Creates a new membership for a user.

**Parameters:**
- `$userId` (integer): User ID
- `$planId` (integer): Membership plan ID
- `$startDate` (string, optional): Start date in Y-m-d format
- `$duration` (integer, optional): Duration in months (default: 1)

**Returns:** User membership ID

**Example:**
```php
try {
    $membershipId = createUserMembership(123, 2, '2024-01-01', 12);
    echo "Membership created with ID: $membershipId";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

#### `getUserMembershipStatus($userId)`

Gets comprehensive membership status for display purposes.

**Parameters:**
- `$userId` (integer): User ID

**Returns:** Membership status array or null

**Example:**
```php
$status = getUserMembershipStatus(123);
if ($status) {
    echo "Status: " . $status['status'];
    echo "Plan: " . $status['plan_name'];
    echo "Classes remaining: " . $status['classes_remaining'];
}
```

---

## Admin Functions

Located in: `admin/includes/admin_common.php`

### Admin Helper Functions

#### `renderAdminPage($content, $options = [])`

Renders a complete admin page with header and footer.

**Parameters:**
- `$content` (string): Main page content HTML
- `$options` (array): Page options

**Options Array:**
```php
[
    'pageDescription' => 'Page description for header',
    'headerActions' => 'HTML for header action buttons',
    'additionalCSS' => ['path/to/style.css'],
    'additionalJS' => ['path/to/script.js'],
    'inlineJS' => 'JavaScript code to include',
    'success' => 'Success message to display',
    'error' => 'Error message to display',
    'message' => 'General message to display'
]
```

**Example:**
```php
$content = '<h1>Dashboard</h1><p>Welcome to the admin panel</p>';
$options = [
    'pageDescription' => 'Admin dashboard overview',
    'headerActions' => '<a href="classes.php" class="btn btn-primary">Manage Classes</a>',
    'success' => 'Settings saved successfully'
];
renderAdminPage($content, $options);
```

#### `instructorsTableExists($pdo)`

Checks if the instructors table exists in the database.

**Parameters:**
- `$pdo` (PDO): Database connection

**Returns:** Boolean

**Example:**
```php
if (instructorsTableExists($pdo)) {
    // Load instructor-related features
} else {
    // Show setup instructions
}
```

#### `sanitizeInputWithLength($input, $maxLength = null)`

Sanitizes input with optional length validation.

**Parameters:**
- `$input` (string): Input to sanitize
- `$maxLength` (integer, optional): Maximum allowed length

**Returns:** Sanitized string

**Throws:** InvalidArgumentException if length exceeded

**Example:**
```php
try {
    $name = sanitizeInputWithLength($_POST['name'], 100);
    $email = validateEmailInput($_POST['email']);
} catch (InvalidArgumentException $e) {
    echo "Validation error: " . $e->getMessage();
}
```

#### `validateEmailInput($email)`

Validates and sanitizes email input.

**Parameters:**
- `$email` (string): Email to validate

**Returns:** Sanitized email

**Throws:** InvalidArgumentException if invalid

#### `validateDateInput($date)` / `validateTimeInput($time)`

Validates date and time inputs.

**Parameters:**
- `$date` (string): Date string
- `$time` (string): Time in HH:MM format

**Returns:** Validated input

**Throws:** InvalidArgumentException if invalid

---

## Error Handling

Located in: `config/error_handling.php`

### ErrorMessages Class

Provides standardized error messages and response formatting.

#### Constants

```php
// Generic messages
ErrorMessages::GENERIC_ERROR
ErrorMessages::SYSTEM_UNAVAILABLE
ErrorMessages::INVALID_REQUEST

// Authentication
ErrorMessages::LOGIN_FAILED
ErrorMessages::LOGIN_REQUIRED
ErrorMessages::ACCESS_DENIED

// Form validation
ErrorMessages::REQUIRED_FIELDS
ErrorMessages::INVALID_EMAIL
ErrorMessages::INVALID_PASSWORD

// Business logic
ErrorMessages::CLASS_NOT_FOUND
ErrorMessages::CLASS_FULLY_BOOKED
ErrorMessages::ALREADY_BOOKED

// Database errors
ErrorMessages::DATABASE_ERROR
ErrorMessages::OPERATION_FAILED
```

#### Methods

##### `ErrorMessages::formatError($message, $isHtml = true)`

Formats error messages for display.

**Example:**
```php
echo ErrorMessages::formatError("Invalid input");
// Output: <div class="alert alert-danger">Invalid input</div>
```

##### `ErrorMessages::apiError($message, $httpCode = 400)`

Creates standardized API error responses.

**Example:**
```php
http_response_code(400);
echo ErrorMessages::apiError("Class not found", 404);
// Output: {"success": false, "error": "Class not found"}
```

##### `ErrorMessages::apiSuccess($data = null, $message = null)`

Creates standardized API success responses.

**Example:**
```php
echo ErrorMessages::apiSuccess(['id' => 123], 'Class created successfully');
// Output: {"success": true, "message": "Class created successfully", "data": {"id": 123}}
```

---

## Frontend Components

### Calendar Integration

The system uses FullCalendar for displaying classes in a calendar interface.

#### JavaScript API

Located in: `assets/js/main.js`

##### Calendar Initialization

```javascript
// Initialize FullCalendar
const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek'
    },
    events: '/public/api/classes.php',
    eventClick: function(info) {
        showClassModal(info.event);
    }
});
```

##### Event Handling

```javascript
// Show class booking modal
function showClassModal(event) {
    const modal = new bootstrap.Modal(document.getElementById('classModal'));
    // Populate modal with class details
    document.getElementById('modalClassName').textContent = event.title;
    document.getElementById('modalClassTime').textContent = event.start.toLocaleTimeString();
    modal.show();
}

// Handle booking submission
function bookClass(classId) {
    fetch('/public/api/book.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ class_id: classId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage(data.message);
            calendar.refetchEvents(); // Refresh calendar
        } else {
            showErrorMessage(data.error);
        }
    });
}
```

### Form Validation

#### Client-side Validation

```javascript
// Validate registration form
function validateRegistrationForm(form) {
    const errors = [];
    
    // Email validation
    const email = form.email.value;
    if (!email || !isValidEmail(email)) {
        errors.push('Please enter a valid email address');
    }
    
    // Password validation
    const password = form.password.value;
    if (!password || password.length < 8) {
        errors.push('Password must be at least 8 characters long');
    }
    
    return errors;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}
```

---

## Configuration

### Database Configuration

Located in: `public/api/db.php`

```php
// Database connection settings
$host = 'localhost';
$db   = 'testbook';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
```

### Environment Configuration

Located in: `config/environment.php`

```php
// Application Environment
define('APP_ENV', 'development');

// Security Settings
define('SESSION_LIFETIME', 3600);
define('CSRF_TOKEN_LIFETIME', 3600);

// Error Logging
define('LOG_ERRORS', true);
define('ERROR_LOG_FILE', __DIR__ . '/../logs/error.log');
```

### Security Configuration

Located in: `config/security.php`

```php
// Security headers and input sanitization
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// CSRF token generation and validation
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
```

---

## Database Schema

### Core Tables

#### users
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say'),
    health_questionnaire JSON,
    free_trial_used BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### classes
```sql
CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    time TIME NOT NULL,
    capacity INT NOT NULL,
    recurring BOOLEAN DEFAULT FALSE,
    days_of_week JSON,
    day_specific_times JSON,
    instructor_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES instructors(id)
);
```

#### bookings
```sql
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    user_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    email VARCHAR(100) NOT NULL,
    class_date DATE,
    membership_cycle VARCHAR(7),
    is_free_trial BOOLEAN DEFAULT FALSE,
    status ENUM('confirmed', 'cancelled', 'waitlist') DEFAULT 'confirmed',
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### membership_plans
```sql
CREATE TABLE membership_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    monthly_class_limit INT,
    status ENUM('active', 'inactive') DEFAULT 'active'
);
```

#### user_memberships
```sql
CREATE TABLE user_memberships (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('pending', 'active', 'expired', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (plan_id) REFERENCES membership_plans(id)
);
```

---

## File Upload System

Located in: `config/file_upload_helper.php`

### Profile Photo Functions

#### `getProfilePhotoUrl($filename, $type = 'user')`

Gets the URL for a profile photo.

**Parameters:**
- `$filename` (string): Filename of the photo
- `$type` (string): 'user' or 'instructor'

**Returns:** URL string

**Example:**
```php
$photoUrl = getProfilePhotoUrl($user['profile_photo'], 'user');
echo "<img src='$photoUrl' alt='Profile Photo'>";
```

#### `handleFileUpload($file, $uploadDir, $allowedTypes = [], $maxSize = 5242880)`

Handles file upload with validation.

**Parameters:**
- `$file` (array): $_FILES array element
- `$uploadDir` (string): Upload directory path
- `$allowedTypes` (array): Allowed MIME types
- `$maxSize` (integer): Maximum file size in bytes

**Returns:** Array with success status and filename/error

**Example:**
```php
$result = handleFileUpload(
    $_FILES['profile_photo'],
    'uploads/profiles/',
    ['image/jpeg', 'image/png', 'image/gif'],
    2097152 // 2MB
);

if ($result['success']) {
    echo "File uploaded: " . $result['filename'];
} else {
    echo "Upload failed: " . $result['error'];
}
```

---

## Security Functions

Located in: `config/security.php`

### Input Sanitization

#### `sanitizeInput($input)`

Sanitizes user input to prevent XSS attacks.

**Parameters:**
- `$input` (string): Raw input

**Returns:** Sanitized string

**Example:**
```php
$safeName = sanitizeInput($_POST['name']);
$safeEmail = sanitizeInput($_POST['email']);
```

### CSRF Protection

#### `generateCSRFToken()`

Generates a CSRF token for form protection.

**Returns:** CSRF token string

**Example:**
```php
$token = generateCSRFToken();
echo "<input type='hidden' name='csrf_token' value='$token'>";
```

#### `validateCSRFToken($token)`

Validates a CSRF token.

**Parameters:**
- `$token` (string): Token to validate

**Returns:** Boolean

**Example:**
```php
if (!validateCSRFToken($_POST['csrf_token'])) {
    die('CSRF token validation failed');
}
```

---

## Usage Examples

### Complete Class Booking Flow

```php
<?php
// 1. Check if user is logged in
require_once 'config/user_auth.php';
if (!isUserLoggedIn()) {
    header('Location: login.php');
    exit();
}

// 2. Get user info
$userInfo = getUserInfo();
$userId = $userInfo['id'];

// 3. Check if user can book classes
require_once 'config/membership_functions.php';
$canBook = canUserBookClass($userId);

if (!$canBook['canBook']) {
    echo "Cannot book: " . $canBook['message'];
    if ($canBook['reason'] === 'no_membership') {
        echo '<a href="membership.php">Purchase Membership</a>';
    }
    exit();
}

// 4. Process booking if form submitted
if ($_POST && isset($_POST['class_id'])) {
    try {
        $classId = intval($_POST['class_id']);
        
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'])) {
            throw new Exception('Security validation failed');
        }
        
        // Process booking
        $result = processClassBooking($userId, $classId);
        echo "Booking successful!";
        
    } catch (Exception $e) {
        echo "Booking failed: " . $e->getMessage();
    }
}
?>
```

### Admin Class Management

```php
<?php
// Admin class creation
require_once 'admin/includes/admin_common.php';

if ($_POST && isset($_POST['action']) && $_POST['action'] === 'create_class') {
    try {
        // Validate inputs
        $name = sanitizeInputWithLength($_POST['name'], 200);
        $description = sanitizeInputWithLength($_POST['description'], 500);
        $date = validateDateInput($_POST['date']);
        $time = validateTimeInput($_POST['time']);
        $capacity = intval($_POST['capacity']);
        
        if ($capacity <= 0) {
            throw new InvalidArgumentException('Capacity must be greater than 0');
        }
        
        // Insert class
        $stmt = $pdo->prepare("
            INSERT INTO classes (name, description, date, time, capacity, instructor_id) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $description, $date, $time, $capacity, $_POST['instructor_id']]);
        
        $success = "Class created successfully";
        
    } catch (Exception $e) {
        $error = getErrorMessage($e);
    }
}

// Render admin page
$content = include 'templates/class_form.php';
renderAdminPage($content, [
    'pageDescription' => 'Create a new class',
    'success' => $success ?? null,
    'error' => $error ?? null
]);
?>
```

---

## Error Handling Best Practices

1. **Always use try-catch blocks** for database operations
2. **Validate all inputs** before processing
3. **Use prepared statements** to prevent SQL injection
4. **Log errors** for debugging while showing user-friendly messages
5. **Return consistent API responses** using ErrorMessages class

### Example Error Handling

```php
try {
    // Validate input
    if (empty($_POST['email'])) {
        throw new InvalidArgumentException(ErrorMessages::REQUIRED_FIELDS);
    }
    
    $email = validateEmailInput($_POST['email']);
    
    // Database operation
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if (!$stmt->fetch()) {
        throw new Exception(ErrorMessages::LOGIN_FAILED);
    }
    
    // Success response
    echo ErrorMessages::apiSuccess(['status' => 'found']);
    
} catch (InvalidArgumentException $e) {
    // Validation error - show to user
    http_response_code(400);
    echo ErrorMessages::apiError($e->getMessage());
    
} catch (PDOException $e) {
    // Database error - log and show generic message
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo ErrorMessages::apiError(ErrorMessages::DATABASE_ERROR);
    
} catch (Exception $e) {
    // General error
    error_log('General error: ' . $e->getMessage());
    http_response_code(500);
    echo ErrorMessages::apiError($e->getMessage());
}
```

---

This documentation provides comprehensive coverage of all public APIs, functions, and components in the Class Booking System. For additional implementation details, refer to the individual source files and inline comments.