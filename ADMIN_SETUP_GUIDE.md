# Admin Setup Guide

## Issues Fixed ✅

### 1. Session Warning Fixed

- Fixed the duplicate `session_start()` warning in `classes.php`
- Now checks if session is already active before starting

### 2. Students Management Added

- Created comprehensive `admin/students.php` interface
- Admin can now view, add, edit, and manage all students
- Shows student statistics, booking counts, and last activity
- Added students navigation link to admin menu

### 3. Instructor System Setup

- Created `admin/setup_instructors.php` for easy database setup
- Dashboard now detects if instructor system needs setup

## Quick Setup Steps

### Step 1: Set Up Instructor System

1. Visit: `http://127.0.0.1/testbook/admin/setup_instructors.php`
2. Click "Run Setup" to create the instructors table
3. This will:
   - Create the `instructors` table
   - Add `instructor_id` column to `classes` table
   - Add 5 sample instructors
   - Assign existing classes to instructors
   - Create necessary database indexes

### Step 2: Access Admin Features

After setup, you can access:

- **Dashboard**: `http://127.0.0.1/testbook/admin/dashboard.php`
- **Manage Students**: `http://127.0.0.1/testbook/admin/students.php`
- **Manage Instructors**: `http://127.0.0.1/testbook/admin/instructors.php`
- **Manage Classes**: `http://127.0.0.1/testbook/admin/classes.php`
- **Manage Bookings**: `http://127.0.0.1/testbook/admin/bookings.php`

## New Admin Features

### Student Management (`admin/students.php`)

- **View all students** with statistics (total bookings, last activity)
- **Add new students** with email, name, phone
- **Edit student details** and status (active/inactive/suspended)
- **Reset student passwords**
- **Delete students** (only if they have no bookings)
- **Search and filter** students by status
- **Statistics dashboard** showing totals and averages

### Enhanced Dashboard

- **Visual statistics cards** with color coding
- **Smart instructor setup** - shows setup button if table doesn't exist
- **Quick action buttons** for common tasks
- **System status** indicators
- **Modern responsive design**

### Instructor Management

- **Full CRUD operations** for instructors
- **Specialties management** (Yoga, Pilates, HIIT, etc.)
- **Bio and contact information**
- **Status management** (active/inactive)
- **Automatic class assignment** based on class names

## Troubleshooting

### If you see "Table doesn't exist" errors:

1. Go to `admin/setup_instructors.php`
2. Run the migration to create all necessary tables

### If you get permission errors:

1. Check that your database user has CREATE and ALTER permissions
2. Verify the database connection in `public/api/db.php`

### If classes don't show instructors:

1. Make sure you ran the instructor setup
2. Check that existing classes have been assigned instructors
3. New classes will require instructor selection

## Security Features

- **CSRF protection** on all admin forms
- **Input validation** and sanitization
- **SQL injection prevention** with prepared statements
- **Password hashing** for student accounts
- **Session security** with proper logout handling

## Next Steps

1. Run the instructor setup first
2. Add/edit your actual instructors in the instructor management
3. Review and assign instructors to your existing classes
4. Use the student management to handle user accounts
5. Monitor bookings and class attendance through the admin interface

All error messages have been resolved and the system is now ready for full admin management!
