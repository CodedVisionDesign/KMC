# Instructor Functionality Setup Guide

This guide explains how to set up and use the new instructor functionality in the Class Booking System.

## Issues Fixed

### 1. Booking Redirect Issue

**Problem**: When users tried to book a class without being logged in, they would see "Booking failed. Please try again." instead of being redirected to the registration page.

**Solution**: Fixed the JavaScript in `assets/js/main.js` to properly handle HTTP 401 responses and redirect users to the login page with a return URL.

### 2. Missing Instructor Functionality

**Problem**: Classes had no instructor information, and there was no way to assign instructors to classes.

**Solution**: Added complete instructor management system including database tables, admin interface, and frontend display.

## Database Changes

### New Tables Added

1. **`instructors` Table**:

   - `id` - Primary key
   - `first_name` - Instructor's first name
   - `last_name` - Instructor's last name
   - `email` - Unique email address
   - `phone` - Phone number (optional)
   - `bio` - Instructor biography
   - `specialties` - Comma-separated list of specialties
   - `status` - Active/Inactive status
   - `created_at` / `updated_at` - Timestamps

2. **Updated `classes` Table**:
   - Added `instructor_id` field with foreign key to instructors table

## Setup Instructions

### For New Installations

1. Use the updated `config/database.sql` file which includes the instructors table
2. The sample data will automatically be inserted during setup

### For Existing Installations

1. Run the migration script: `config/add_instructors.sql`
2. This script will:
   - Create the instructors table
   - Add instructor_id column to classes table
   - Insert sample instructor data
   - Assign existing classes to appropriate instructors based on class names

## Admin Interface Features

### Instructor Management (`admin/instructors.php`)

- **Add new instructors** with full profile information
- **Edit existing instructors** including status management
- **View instructor specialties** as badges
- **See class count** for each instructor
- **Delete instructors** (only if they have no assigned classes)
- **Search and filter** by status

### Updated Class Management (`admin/classes.php`)

- **Assign instructors** when creating or editing classes
- **View instructor names** in the class list
- **Optional instructor assignment** - classes can exist without instructors

### Dashboard Updates (`admin/dashboard.php`)

- **Instructor count** statistics
- **Quick link** to instructor management
- **Updated layout** with three-column stats display

## Frontend Features

### Class Details Modal

- **Instructor information** displayed prominently
- **Instructor bio** and specialties shown as badges
- **Professional styling** with instructor icon

### Calendar View

- **Instructor names** included in calendar event titles
- Format: "Class Name - Instructor Name (X spots)"

### API Updates

- **Enhanced class data** includes instructor information
- **Backward compatibility** maintained for existing API consumers

## Sample Data Included

The system comes with 5 sample instructors:

1. **Sarah Johnson** - Yoga specialist (Morning Yoga, Hatha, Vinyasa)
2. **Mike Chen** - Pilates and HIIT trainer
3. **Emma Davis** - Meditation and mindfulness coach
4. **Alex Rodriguez** - High-intensity training specialist
5. **Lisa Thompson** - Beginner-friendly yoga instructor

## Usage Examples

### Assigning Instructors to Classes

```php
// When creating a class
INSERT INTO classes (name, description, date, time, capacity, instructor_id)
VALUES ('Morning Yoga', 'Relaxing yoga session', '2025-01-15', '09:00:00', 15, 1);
```

### Querying Classes with Instructor Info

```php
// Get classes with instructor names
SELECT
    c.*,
    CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
    i.bio as instructor_bio,
    i.specialties as instructor_specialties
FROM classes c
LEFT JOIN instructors i ON c.instructor_id = i.id
ORDER BY c.date, c.time;
```

## File Changes Made

### Database Files

- `config/database.sql` - Updated with instructors table
- `config/add_instructors.sql` - Migration script for existing databases

### Admin Files

- `admin/instructors.php` - New instructor management interface
- `admin/classes.php` - Updated to include instructor assignment
- `admin/dashboard.php` - Added instructor statistics and links

### API Files

- `public/api/classes.php` - Updated to include instructor data
- `public/api/class.php` - Updated individual class endpoint

### Frontend Files

- `assets/js/main.js` - Fixed booking redirect and added instructor display

## Security Considerations

- **CSRF protection** on all admin forms
- **Input validation** for all instructor data
- **SQL injection prevention** using prepared statements
- **XSS protection** with proper HTML escaping
- **Email uniqueness** enforced at database level

## Troubleshooting

### Instructor Dropdown Not Showing

- Verify the instructors table exists
- Check that there are active instructors in the database
- Ensure the admin/classes.php file has been updated

### Booking Redirect Still Not Working

- Clear browser cache
- Check that assets/js/main.js has been updated
- Verify the API is returning proper 401 status codes

### Database Migration Issues

- Check MySQL error logs
- Ensure proper foreign key constraints
- Verify column types match existing structure

## Future Enhancements

Potential improvements that could be added:

1. **Instructor Availability** - Track when instructors are available
2. **Instructor Ratings** - Allow students to rate instructors
3. **Instructor Profiles** - Public pages showing instructor details
4. **Multiple Instructors** - Allow multiple instructors per class
5. **Instructor Login** - Separate portal for instructors to manage their classes
6. **Photo Upload** - Add instructor photos to profiles
7. **Qualifications** - Track instructor certifications and qualifications

## Support

If you encounter any issues with the instructor functionality:

1. Check the PHP error logs
2. Verify database connections
3. Ensure all files have been updated
4. Test with sample data first
5. Check browser console for JavaScript errors

The instructor system is designed to be robust and user-friendly while maintaining the simplicity of the existing booking system.
