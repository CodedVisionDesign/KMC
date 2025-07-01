# Class Booking System - Complete Documentation Guide

## Table of Contents

1. [System Overview](#system-overview)
2. [Database Structure](#database-structure)
3. [Admin Pages](#admin-pages)
4. [Public/Student Pages](#public-student-pages)
5. [API Endpoints](#api-endpoints)
6. [Authentication Flow](#authentication-flow)
7. [File Structure](#file-structure)
8. [Debugging & Troubleshooting Pages](#debugging--troubleshooting-pages)
9. [Core vs Optional Files](#core-vs-optional-files)
10. [Setup Instructions](#setup-instructions)

---

## System Overview

This is a PHP-based fitness studio class booking system with the following main features:

- **Admin Panel**: Manage classes, instructors, students, memberships, and videos
- **Student Portal**: View classes, book sessions, manage profile and membership
- **Public Interface**: Browse classes, view instructors, membership plans
- **Real-time Availability**: Live class booking with capacity management
- **Membership System**: Multiple plans with payment tracking
- **Video Library**: Organized video content by series

### Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: Bootstrap 5, JavaScript
- **Calendar**: FullCalendar.js
- **Authentication**: Custom PHP sessions

---

## Database Structure

### Core Tables

#### 1. `users` - User accounts (students/members)

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    phone VARCHAR(20),
    date_of_birth DATE,
    emergency_contact VARCHAR(100),
    emergency_phone VARCHAR(20),
    medical_conditions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 2. `instructors` - Fitness instructors

```sql
CREATE TABLE instructors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    email VARCHAR(100),
    phone VARCHAR(20),
    bio TEXT,
    specialties VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 3. `classes` - Fitness classes

```sql
CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    description TEXT,
    instructor_id INT,
    date DATE,
    time TIME,
    capacity INT DEFAULT 15,
    recurring BOOLEAN DEFAULT 0,
    status ENUM('active', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES instructors(id)
);
```

#### 4. `bookings` - Class bookings

```sql
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    class_id INT,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (class_id) REFERENCES classes(id)
);
```

#### 5. `membership_plans` - Available membership plans

```sql
CREATE TABLE membership_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    description TEXT,
    price DECIMAL(10,2),
    monthly_class_limit INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 6. `user_memberships` - User membership records

```sql
CREATE TABLE user_memberships (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    plan_id INT,
    status ENUM('pending', 'active', 'expired', 'cancelled', 'rejected'),
    start_date DATE,
    end_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (plan_id) REFERENCES membership_plans(id)
);
```

#### 7. `membership_payments` - Payment tracking

```sql
CREATE TABLE membership_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_membership_id INT,
    amount DECIMAL(10,2),
    payment_method ENUM('cash', 'card', 'bank_transfer', 'paypal', 'other'),
    reference_number VARCHAR(100),
    status ENUM('pending', 'completed', 'failed'),
    confirmed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_membership_id) REFERENCES user_memberships(id)
);
```

#### 8. `video_series` - Video categories

```sql
CREATE TABLE video_series (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100),
    description TEXT,
    cover_image VARCHAR(255),
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 9. `videos` - Video content

```sql
CREATE TABLE videos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    description TEXT,
    file_path VARCHAR(500),
    file_size BIGINT,
    duration_seconds INT,
    thumbnail_path VARCHAR(500),
    series_id INT,
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    uploaded_at TIMESTAMP,
    updated_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (series_id) REFERENCES video_series(id)
);
```

---

## Admin Pages

### 1. Admin Dashboard (`admin/dashboard.php`)

**Purpose**: Central admin control panel with overview statistics

**Key Features**:

- Statistics cards (classes, instructors, memberships, videos)
- Quick action buttons
- System health checks
- Recent activity alerts

**Code Flow**:

```php
// 1. Authentication check
require_once 'includes/admin_common.php';

// 2. Fetch statistics
$totalClasses = $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();
$totalInstructors = $pdo->query("SELECT COUNT(*) FROM instructors WHERE status = 'active'")->fetchColumn();

// 3. Render dashboard with statistics
renderAdminPage($content);
```

**Database Queries**:

- Count active classes, instructors, students
- Check pending memberships and payments
- Video statistics

### 2. Class Management (`admin/classes.php`)

**Purpose**: Manage fitness classes, schedules, and instructors

**Key Features**:

- Add/edit/delete classes
- Assign instructors
- Set recurring schedules
- View booking statistics

**API Actions**:

- `add_class`: Create new class
- `update_class`: Modify existing class
- `delete_class`: Remove class (with booking checks)
- `toggle_status`: Activate/deactivate class

### 3. Instructor Management (`admin/instructors.php`)

**Purpose**: Manage instructor profiles and assignments

**Key Features**:

- Add/edit instructor profiles
- Manage specialties and bio
- View assigned classes
- Contact information management

### 4. Student Management (`admin/students.php`)

**Purpose**: View and manage student accounts

**Key Features**:

- View student profiles
- Check membership status
- View booking history
- Manage health information

### 5. Membership Management (`admin/memberships.php`)

**Purpose**: Complete membership lifecycle management

**Key Features**:

- **Pending Requests Tab**: Approve/reject new memberships
- **Active Memberships Tab**: View current members, handle cancellations
- **Pending Payments Tab**: Track and confirm payments
- **Membership Plans Tab**: Create/edit/delete membership plans

**Code Flow**:

```php
// Handle AJAX requests
if ($_POST['action']) {
    switch ($_POST['action']) {
        case 'approve_membership':
            // Set status to active, set dates
            $stmt = $pdo->prepare("UPDATE user_memberships SET status = 'active', start_date = CURRENT_DATE, end_date = DATE_ADD(CURRENT_DATE, INTERVAL 1 MONTH) WHERE id = ?");
            break;
        case 'add_plan':
            // Create new membership plan
            $stmt = $pdo->prepare("INSERT INTO membership_plans (name, description, price, monthly_class_limit, status) VALUES (?, ?, ?, ?, ?)");
            break;
    }
}
```

### 6. Video Management (`admin/videos.php`)

**Purpose**: Upload and organize video content

**Key Features**:

- Upload videos with thumbnails
- Organize by series/categories
- Edit video details
- File management and cleanup

**File Upload Process**:

```php
// 1. Validate file type and size
$allowedTypes = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv'];
if (!in_array($_FILES['video_file']['type'], $allowedTypes)) {
    throw new Exception('Invalid file type');
}

// 2. Generate unique filename
$fileName = uniqid() . '_' . $originalName;
$uploadPath = '../public/uploads/videos/' . $fileName;

// 3. Move file and create database record
move_uploaded_file($_FILES['video_file']['tmp_name'], $uploadPath);
$stmt = $pdo->prepare("INSERT INTO videos (title, file_path, series_id) VALUES (?, ?, ?)");
```

---

## Public/Student Pages

### 1. Main Index (`public/index.php`)

**Purpose**: Public homepage with class information

**Key Features**:

- Display available classes (unique types, not instances)
- Show instructors and their specialties
- Membership plan overview
- Interactive calendar for class booking

**Code Flow**:

```php
// 1. Fetch unique classes for information display
$stmt = $pdo->query("
    SELECT c.id, c.name, c.description, c.capacity,
           CONCAT(i.first_name, ' ', i.last_name) as instructor_name
    FROM classes c
    LEFT JOIN instructors i ON c.instructor_id = i.id
    ORDER BY c.name, c.time
");

// 2. Fetch membership plans
$stmt = $pdo->query("
    SELECT id, name, description, price, monthly_class_limit
    FROM membership_plans
    WHERE status = 'active'
    ORDER BY price ASC
");
```

### 2. User Dashboard (`public/user/dashboard.php`)

**Purpose**: Student's personal control panel

**Key Features**:

- Upcoming bookings
- Membership status
- Quick booking access
- Profile management links

### 3. Class Booking (`public/user/bookings.php`)

**Purpose**: Manage class bookings

**Key Features**:

- View upcoming bookings
- Cancel bookings
- Booking history
- Availability checking

### 4. User Profile (`public/user/profile.php`)

**Purpose**: Personal information management

**Key Features**:

- Edit personal details
- Emergency contact information
- Password management

### 5. Health Information (`public/user/health.php`)

**Purpose**: Medical information and emergency contacts

**Key Features**:

- Medical conditions tracking
- Emergency contact details
- Health questionnaire

### 6. Membership Portal (`public/user/membership.php`)

**Purpose**: Membership management for students

**Key Features**:

- View current membership
- Upgrade/change plans
- Payment history
- Renewal management

### 7. Video Library (`public/user/videos.php`)

**Purpose**: Access to video content

**Key Features**:

- Browse videos by series
- Stream video content
- Progress tracking
- Organized categories

---

## API Endpoints

### 1. Class API (`public/api/classes.php`)

**Purpose**: Provide class data for calendar and booking

**Endpoints**:

- `GET /api/classes.php` - Fetch all classes for calendar
- Generates recurring class instances dynamically
- Returns JSON format for FullCalendar

**Code Example**:

```php
// Generate recurring classes for next 3 months
if ($class['recurring']) {
    $startDate = new DateTime($class['date']);
    $endDate = new DateTime('+3 months');

    while ($startDate <= $endDate) {
        // Create class instance for each week
        $classInstance = [
            'id' => $class['id'] . '_' . $startDate->format('Y-m-d'),
            'title' => $class['name'],
            'start' => $startDate->format('Y-m-d') . 'T' . $class['time'],
            'extendedProps' => [
                'instructor' => $class['instructor_name'],
                'capacity' => $class['capacity'],
                'booked' => $bookedCount
            ]
        ];
        $startDate->add(new DateInterval('P7D')); // Add 7 days
    }
}
```

### 2. Booking API (`public/api/book.php`)

**Purpose**: Handle class bookings

**Process**:

1. Validate user authentication
2. Check class capacity
3. Prevent double booking
4. Create booking record

### 3. Cancel Booking API (`public/api/cancel_booking.php`)

**Purpose**: Cancel existing bookings

### 4. Database Connection (`public/api/db.php`)

**Purpose**: Centralized database connection

```php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=class_booking", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
```

---

## Authentication Flow

### Admin Authentication

1. **Login** (`admin/login.php`): Hardcoded admin credentials
2. **Session Check** (`admin/includes/admin_common.php`): Validates admin session
3. **Logout** (`admin/logout.php`): Destroys admin session

### User Authentication

1. **Registration** (`public/register.php`): Create user account
2. **Login** (`public/login.php`): Validate credentials, create session
3. **Session Management** (`config/user_auth.php`): Helper functions
4. **Logout** (`public/logout.php`): Destroy user session

```php
// Example session check
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isUserLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
```

---

## File Structure

### Core Application Files

```
Testbook/
├── admin/                          # Admin panel
│   ├── includes/
│   │   └── admin_common.php        # Admin authentication & utilities
│   ├── templates/
│   │   ├── header.php              # Admin header template
│   │   └── footer.php              # Admin footer template
│   ├── dashboard.php               # Admin dashboard
│   ├── classes.php                 # Class management
│   ├── instructors.php             # Instructor management
│   ├── students.php                # Student management
│   ├── memberships.php             # Membership management
│   ├── videos.php                  # Video management
│   ├── login.php                   # Admin login
│   └── logout.php                  # Admin logout
├── public/
│   ├── api/                        # API endpoints
│   │   ├── db.php                  # Database connection
│   │   ├── classes.php             # Class data API
│   │   ├── book.php                # Booking API
│   │   └── cancel_booking.php      # Cancel booking API
│   ├── user/                       # Student portal
│   │   ├── dashboard.php           # Student dashboard
│   │   ├── bookings.php            # Booking management
│   │   ├── profile.php             # Profile management
│   │   ├── health.php              # Health information
│   │   ├── membership.php          # Membership portal
│   │   ├── videos.php              # Video library
│   │   ├── header.php              # Student header
│   │   └── footer.php              # Student footer
│   ├── uploads/                    # File storage
│   │   ├── videos/                 # Video files
│   │   └── thumbnails/             # Video thumbnails
│   ├── assets/
│   │   ├── css/
│   │   │   └── custom.css          # Custom styles
│   │   └── js/
│   │       └── main.js             # JavaScript functionality
│   ├── index.php                   # Public homepage
│   ├── login.php                   # User login
│   ├── register.php                # User registration
│   └── logout.php                  # User logout
├── config/                         # Configuration files
│   ├── database.sql                # Database schema
│   ├── user_auth.php               # User authentication helpers
│   ├── security.php                # Security functions
│   └── error_handling.php          # Error handling
├── templates/                      # Shared templates
│   ├── base.php                    # Base template
│   ├── config.php                  # Template configuration
│   ├── header.php                  # Shared header
│   └── footer.php                  # Shared footer
└── assets/                         # Global assets
    ├── css/
    │   ├── custom.css              # Main stylesheet
    │   └── realtime-availability.css
    └── js/
        ├── main.js                 # Main JavaScript
        └── realtime-availability.js
```

---

## Debugging & Troubleshooting Pages

### 🔧 Debug/Test Files (Can be deleted in production)

1. **`public/debug_classes.php`** - Debug class generation logic
2. **`public/test_*.php`** files:
   - `test_auth.php` - Authentication testing
   - `test_class_display_fix.php` - Class display debugging
   - `test_final.html` - Final testing page
   - `test_index_queries.php` - Database query testing
   - `test_index_queries_fixed.php` - Fixed query testing
   - `test_membership_web.php` - Membership system testing
   - `test_realtime.html` - Real-time features testing
   - `test_recurring_fix.php` - Recurring class testing
3. **`public/bug_scan.php`** - System bug scanner
4. **`public/example_standalone.php`** - Standalone example
5. **`test_calendar.html`** - Calendar testing
6. **`test.php`** - General testing

### 🛠️ Admin Utility Files (Keep for maintenance)

1. **`admin/setup_*.php`** files:
   - `setup_instructors.php` - Instructor setup
   - `setup_instructors_fixed.php` - Fixed instructor setup
   - `setup_membership_video.php` - Membership/video system setup
2. **`public/admin/`** folder:
   - `cleanup_data.php` - Data cleanup utilities
   - `cleanup_duplicates.php` - Remove duplicate data
   - `remove_duplicate_memberships.php` - Membership cleanup
   - `run_migration.php` - Database migration runner
3. **`public/add_sample_data.php`** - Add sample data

### 📋 Documentation Files (Keep for reference)

1. **`*.md`** files - Documentation and guides
2. **`TESTING_CHECKLIST.md`** - Testing procedures
3. **`README.md`** - Project overview
4. **`bugs.txt`** - Known issues log

---

## Core vs Optional Files

### ✅ CORE FILES (Required for functionality)

#### Database & Configuration

- `config/database.sql` - Database schema
- `public/api/db.php` - Database connection
- `config/user_auth.php` - Authentication system
- `templates/config.php` - Template system

#### Admin System

- `admin/includes/admin_common.php` - Admin authentication
- `admin/dashboard.php` - Admin dashboard
- `admin/classes.php` - Class management
- `admin/instructors.php` - Instructor management
- `admin/students.php` - Student management
- `admin/memberships.php` - Membership management
- `admin/videos.php` - Video management
- `admin/login.php` - Admin authentication

#### Public System

- `public/index.php` - Homepage
- `public/login.php` - User login
- `public/register.php` - User registration
- `public/api/classes.php` - Class data API
- `public/api/book.php` - Booking API
- `public/api/cancel_booking.php` - Cancel booking API

#### Student Portal

- `public/user/dashboard.php` - Student dashboard
- `public/user/bookings.php` - Booking management
- `public/user/profile.php` - Profile management
- `public/user/membership.php` - Membership portal
- `public/user/videos.php` - Video library

#### Assets

- `assets/css/custom.css` - Main styles
- `assets/js/main.js` - Core JavaScript
- `templates/base.php` - Template system

### ⚠️ OPTIONAL FILES (Can be removed)

#### Debug/Testing Files

- All `test_*.php` files
- All `debug_*.php` files
- `public/bug_scan.php`
- `test_calendar.html`
- `test.php`

#### Setup/Migration Files (after initial setup)

- `admin/setup_*.php` files
- `public/admin/run_migration.php`
- `public/add_sample_data.php`

#### Documentation (optional in production)

- All `.md` files except README.md
- `bugs.txt`
- `TESTING_CHECKLIST.md`

---

## Setup Instructions

### 1. Database Setup

```sql
-- Import the main database schema
SOURCE config/database.sql;

-- Run membership system migration
SOURCE config/membership_system_migration.sql;

-- Add sample data (optional)
SOURCE config/add_instructors.sql;
```

### 2. Configuration

1. **Database Connection**: Update `public/api/db.php` with your database credentials
2. **File Permissions**: Ensure `public/uploads/` folders are writable
3. **Admin Access**: Default admin login is `admin`/`admin123`

### 3. File Structure Setup

```bash
# Ensure upload directories exist and are writable
mkdir -p public/uploads/videos
mkdir -p public/uploads/thumbnails
chmod 755 public/uploads/videos
chmod 755 public/uploads/thumbnails
```

### 4. Initial Data

1. Run `/admin/setup_membership_video.php` to initialize membership system
2. Add instructors via admin panel
3. Create initial classes and membership plans

### 5. Testing

1. Create a test user account
2. Book a class to verify booking system
3. Test admin functions for managing data

---

## Common Issues & Solutions

### 1. Calendar Not Showing Classes

- **Issue**: Classes not appearing on calendar
- **Solution**: Check `public/api/classes.php` for database connection and query errors
- **Debug**: Use `public/debug_classes.php` to test class generation

### 2. Booking Failures

- **Issue**: Cannot book classes
- **Solution**: Verify user authentication and class capacity
- **Debug**: Check `public/api/book.php` for error messages

### 3. File Upload Issues

- **Issue**: Video uploads failing
- **Solution**: Check file permissions on `public/uploads/` directories
- **Debug**: Verify file size limits and allowed types

### 4. Database Connection Errors

- **Issue**: "Connection failed" errors
- **Solution**: Update database credentials in `public/api/db.php`
- **Debug**: Test connection with a simple PHP script

---

This documentation provides a complete overview of the Class Booking System. Each component is designed to work together to provide a comprehensive fitness studio management solution.
