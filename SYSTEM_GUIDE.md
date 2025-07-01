# Class Booking System - Complete System Guide

## System Overview

This is a PHP-based fitness studio management system with:

- **Admin Panel**: Manage classes, instructors, students, memberships, videos
- **Student Portal**: Book classes, manage profile, view videos
- **Public Interface**: Browse classes, instructors, membership plans
- **Real-time Features**: Live availability, booking system
- **Membership Management**: Plans, payments, approvals
- **Video Library**: Organized content by series

**Technology Stack**: PHP 7.4+, MySQL, Bootstrap 5, FullCalendar.js

---

## Database Structure & Key Queries

### Core Tables

1. **users** - Student accounts
2. **instructors** - Fitness instructors
3. **classes** - Fitness classes (with recurring support)
4. **bookings** - Class reservations
5. **membership_plans** - Available plans
6. **user_memberships** - User membership records
7. **membership_payments** - Payment tracking
8. **video_series** - Video categories
9. **videos** - Video content

### Key SQL Patterns

**Fetch Classes for Calendar (with recurring logic)**:

```php
// In public/api/classes.php
$stmt = $pdo->query("
    SELECT c.id, c.name, c.description, c.date, c.time, c.capacity, c.recurring,
           CONCAT(i.first_name, ' ', i.last_name) as instructor_name
    FROM classes c
    LEFT JOIN instructors i ON c.instructor_id = i.id
    WHERE c.date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
    AND c.date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH)
    ORDER BY c.date, c.time
");

// Generate recurring instances
if ($class['recurring']) {
    $startDate = new DateTime($class['date']);
    $endDate = new DateTime('+3 months');
    while ($startDate <= $endDate) {
        // Create weekly instances
        $classInstance = [
            'id' => $class['id'] . '_' . $startDate->format('Y-m-d'),
            'title' => $class['name'],
            'start' => $startDate->format('Y-m-d') . 'T' . $class['time']
        ];
        $startDate->add(new DateInterval('P7D'));
    }
}
```

**Membership Plans Query**:

```php
// In public/index.php
$stmt = $pdo->query("
    SELECT id, name, description, price, monthly_class_limit
    FROM membership_plans
    WHERE status = 'active'
    ORDER BY price ASC
");
```

---

## Admin Pages Deep Dive

### 1. Admin Dashboard (`admin/dashboard.php`)

**Purpose**: Central control panel

**Key Code Sections**:

```php
// Statistics fetching
$totalClasses = $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();
$pendingMemberships = $pdo->query("SELECT COUNT(*) FROM user_memberships WHERE status = 'pending'")->fetchColumn();
$pendingPayments = $pdo->query("SELECT COUNT(*) FROM membership_payments WHERE status = 'pending'")->fetchColumn();

// Health checks
$tablesExist = [
    'membership_plans' => tableExists($pdo, 'membership_plans'),
    'video_series' => tableExists($pdo, 'video_series')
];
```

**Features**:

- Real-time statistics cards
- System health indicators
- Quick action buttons
- Pending alerts

### 2. Membership Management (`admin/memberships.php`)

**Purpose**: Complete membership lifecycle

**4 Main Tabs**:

1. **Pending Requests** - Approve/reject new memberships
2. **Active Memberships** - Manage current members
3. **Pending Payments** - Confirm payments
4. **Membership Plans** - CRUD operations on plans

**Key AJAX Handlers**:

```php
switch ($_POST['action']) {
    case 'approve_membership':
        $stmt = $pdo->prepare("UPDATE user_memberships SET status = 'active', start_date = CURRENT_DATE, end_date = DATE_ADD(CURRENT_DATE, INTERVAL 1 MONTH) WHERE id = ?");
        break;
    case 'confirm_payment':
        $stmt = $pdo->prepare("UPDATE membership_payments SET status = 'completed', confirmed_at = NOW() WHERE id = ?");
        break;
    case 'add_plan':
        $stmt = $pdo->prepare("INSERT INTO membership_plans (name, description, price, monthly_class_limit, status) VALUES (?, ?, ?, ?, ?)");
        break;
}
```

### 3. Video Management (`admin/videos.php`)

**Purpose**: Upload and organize videos

**Upload Process**:

```php
// File validation
$allowedTypes = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv'];
$maxSize = 500 * 1024 * 1024; // 500MB

// File handling
$fileName = uniqid() . '_' . basename($_FILES['video_file']['name']);
$uploadPath = '../public/uploads/videos/' . $fileName;
move_uploaded_file($_FILES['video_file']['tmp_name'], $uploadPath);

// Database record
$stmt = $pdo->prepare("INSERT INTO videos (title, description, file_path, series_id, file_size) VALUES (?, ?, ?, ?, ?)");
```

---

## Public/Student Pages

### 1. Main Index (`public/index.php`)

**Purpose**: Public homepage

**Key Features**:

- Shows unique class types (not individual instances)
- Membership plan showcase
- Instructor profiles
- Interactive calendar

**Class Display Logic**:

```php
// Fetch unique classes for info display (no duplicates)
$stmt = $pdo->query("
    SELECT c.id, c.name, c.description, c.capacity,
           CONCAT(i.first_name, ' ', i.last_name) as instructor_name
    FROM classes c
    LEFT JOIN instructors i ON c.instructor_id = i.id
    ORDER BY c.name, c.time
");

// Display simplified cards
foreach ($classes as $class) {
    echo "<h5>{$class['name']}</h5>";
    echo "<p>{$class['description']}</p>";
    echo "<p><strong>Instructor:</strong> {$class['instructor_name']}</p>";
    echo "<p><strong>Capacity:</strong> {$class['capacity']}</p>";
}
```

### 2. User Dashboard (`public/user/dashboard.php`)

**Purpose**: Student control panel

**Key Queries**:

```php
// Upcoming bookings
$stmt = $pdo->prepare("
    SELECT b.id, c.name, c.date, c.time, b.booking_date
    FROM bookings b
    JOIN classes c ON b.class_id = c.id
    WHERE b.user_id = ? AND b.status = 'confirmed' AND c.date >= CURDATE()
    ORDER BY c.date, c.time LIMIT 5
");

// Membership status
$stmt = $pdo->prepare("
    SELECT um.*, mp.name as plan_name, mp.price
    FROM user_memberships um
    JOIN membership_plans mp ON um.plan_id = mp.id
    WHERE um.user_id = ? AND um.status = 'active'
    ORDER BY um.created_at DESC LIMIT 1
");
```

### 3. Booking System (`public/user/bookings.php`)

**Purpose**: Manage class bookings

**Booking Process**:

1. Check user authentication
2. Validate class capacity
3. Prevent double booking
4. Create booking record

```php
// Capacity check
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE class_id = ? AND status = 'confirmed'");
$currentBookings = $stmt->fetchColumn();

if ($currentBookings >= $class['capacity']) {
    throw new Exception('Class is fully booked');
}

// Create booking
$stmt = $pdo->prepare("INSERT INTO bookings (user_id, class_id, booking_date) VALUES (?, ?, NOW())");
```

---

## API Endpoints

### 1. Classes API (`public/api/classes.php`)

**Returns**: JSON for FullCalendar

**Key Logic**:

```php
// Fetch base classes
$classes = $pdo->query("SELECT * FROM classes WHERE date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)")->fetchAll();

$events = [];
foreach ($classes as $class) {
    if ($class['recurring']) {
        // Generate weekly instances for 3 months
        $startDate = new DateTime($class['date']);
        $endDate = new DateTime('+3 months');

        while ($startDate <= $endDate) {
            $events[] = [
                'id' => $class['id'] . '_' . $startDate->format('Y-m-d'),
                'title' => $class['name'],
                'start' => $startDate->format('Y-m-d') . 'T' . $class['time'],
                'extendedProps' => [
                    'instructor' => $class['instructor_name'],
                    'capacity' => $class['capacity'],
                    'booked' => getBookingCount($class['id'], $startDate->format('Y-m-d'))
                ]
            ];
            $startDate->add(new DateInterval('P7D'));
        }
    } else {
        // Single instance
        $events[] = [
            'id' => $class['id'],
            'title' => $class['name'],
            'start' => $class['date'] . 'T' . $class['time']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($events);
```

### 2. Booking API (`public/api/book.php`)

**Process**: Handle class reservations

### 3. Database Connection (`public/api/db.php`)

**Purpose**: Centralized PDO connection

```php
$host = 'localhost';
$dbname = 'class_booking';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
```

---

## Authentication System

### Admin Authentication

- **Login**: `admin/login.php` (hardcoded: admin/admin123)
- **Session Check**: `admin/includes/admin_common.php`
- **Protection**: All admin pages require authentication

### User Authentication

- **Registration**: `public/register.php`
- **Login**: `public/login.php`
- **Helpers**: `config/user_auth.php`

```php
// User auth functions
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isUserLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function getUserInfo() {
    if (!isUserLoggedIn()) return null;

    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
```

---

## File Organization

### ✅ CORE FILES (Essential)

**Database & Config**:

- `config/database.sql` - Database schema
- `public/api/db.php` - Database connection
- `config/user_auth.php` - Authentication helpers
- `templates/config.php` - Template system

**Admin System**:

- `admin/includes/admin_common.php` - Admin auth
- `admin/dashboard.php` - Main admin page
- `admin/classes.php` - Class management
- `admin/instructors.php` - Instructor management
- `admin/students.php` - Student management
- `admin/memberships.php` - Membership system
- `admin/videos.php` - Video management
- `admin/login.php` - Admin login

**Public System**:

- `public/index.php` - Homepage
- `public/login.php` - User login
- `public/register.php` - User registration
- `public/api/classes.php` - Calendar data
- `public/api/book.php` - Booking system
- `public/api/cancel_booking.php` - Cancel bookings

**Student Portal**:

- `public/user/dashboard.php` - Student dashboard
- `public/user/bookings.php` - Booking management
- `public/user/profile.php` - Profile management
- `public/user/membership.php` - Membership portal
- `public/user/videos.php` - Video library
- `public/user/health.php` - Health information

**Assets**:

- `assets/css/custom.css` - Main styles
- `assets/js/main.js` - Core JavaScript
- `templates/base.php` - Template system

### 🔧 DEBUG/TEST FILES (Can be deleted)

**Debug Files**:

- `public/debug_classes.php` - Class generation testing
- `public/bug_scan.php` - System scanner
- `public/test_*.php` - Various test files
- `test_calendar.html` - Calendar testing
- `test.php` - General testing

**Setup Files** (after initial setup):

- `admin/setup_*.php` - Setup utilities
- `public/admin/run_migration.php` - Migration runner
- `public/add_sample_data.php` - Sample data

**Documentation** (optional in production):

- All `.md` files except README.md
- `bugs.txt` - Issue log
- `TESTING_CHECKLIST.md` - Test procedures

---

## Common Code Patterns

### 1. Admin Page Structure

```php
<?php
require_once 'includes/admin_common.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form data
    echo json_encode(['success' => true]);
    exit;
}

// Fetch data for display
$data = $pdo->query("SELECT * FROM table")->fetchAll();

// Build HTML content
$content = '<div class="container">...</div>';

// Render with template
renderAdminPage($content, 'Page Title');
?>
```

### 2. User Page Structure

```php
<?php
require_once '../config/user_auth.php';
requireLogin();

$userInfo = getUserInfo();

// Page content
include 'header.php';
?>
<div class="container">
    <!-- Page content -->
</div>
<?php include 'footer.php'; ?>
```

### 3. API Response Pattern

```php
<?php
header('Content-Type: application/json');

try {
    // Process request
    $result = processRequest();
    echo json_encode(['success' => true, 'data' => $result]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
```

---

## Setup Instructions

### 1. Database Setup

```sql
-- Import main schema
SOURCE config/database.sql;

-- Import membership system
SOURCE config/membership_system_migration.sql;

-- Add sample instructors (optional)
SOURCE config/add_instructors.sql;
```

### 2. Configuration

1. Update database credentials in `public/api/db.php`
2. Ensure upload directories exist and are writable:
   ```bash
   mkdir -p public/uploads/videos
   mkdir -p public/uploads/thumbnails
   chmod 755 public/uploads/videos
   chmod 755 public/uploads/thumbnails
   ```

### 3. Initial Setup

1. Run `admin/setup_membership_video.php` to initialize system
2. Login to admin panel (admin/admin123)
3. Add instructors and classes
4. Create membership plans

### 4. Testing

1. Create test user account
2. Book a class to verify system
3. Test admin functions

---

## Troubleshooting Guide

### Common Issues

**1. Calendar Not Loading**

- Check `public/api/classes.php` for database errors
- Verify JavaScript console for errors
- Test with `public/debug_classes.php`

**2. Booking Failures**

- Verify user authentication
- Check class capacity limits
- Review `public/api/book.php` error logs

**3. File Upload Issues**

- Check upload directory permissions
- Verify file size limits in PHP config
- Ensure allowed file types are correct

**4. Database Errors**

- Verify connection credentials
- Check if all tables exist
- Run migration scripts if needed

**5. Authentication Issues**

- Clear browser sessions/cookies
- Check session configuration
- Verify user exists in database

---

This guide covers all major components of the Class Booking System. Each section provides the essential information needed to understand and maintain the system.
