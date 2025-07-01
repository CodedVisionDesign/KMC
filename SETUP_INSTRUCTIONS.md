# Class Booking System - Setup Instructions

## 📦 Package Contents

Your `ClassBookingSystem_Complete.zip` contains:

- Complete PHP application files
- Database export with sample data
- Comprehensive documentation
- Setup utilities

**Package Size**: 0.29 MB  
**Database Export**: 22,523 bytes with 10 tables

---

## 🚀 Quick Setup (5 Minutes)

### Step 1: Extract Files

```bash
# Extract the zip file to your web server directory
# Example paths:
# XAMPP: C:\xampp\htdocs\ClassBookingSystem\
# WAMP: C:\wamp64\www\ClassBookingSystem\
# Linux: /var/www/html/ClassBookingSystem/
```

### Step 2: Import Database

```sql
-- Create database
CREATE DATABASE class_booking;

-- Import the exported data
-- Method 1: phpMyAdmin
-- Upload and import: database_export.sql

-- Method 2: Command line
mysql -u root -p class_booking < database_export.sql
```

### Step 3: Configure Database Connection

Edit `public/api/db.php`:

```php
$host = 'localhost';        // Your database host
$dbname = 'class_booking';  // Your database name
$username = 'root';         // Your database username
$password = '';             // Your database password
```

### Step 4: Set File Permissions

```bash
# Make upload directories writable
chmod 755 public/uploads/videos
chmod 755 public/uploads/thumbnails

# Or on Windows, ensure IIS_IUSRS has write access
```

### Step 5: Access Your System

- **Public Site**: `http://localhost/ClassBookingSystem/public/`
- **Admin Panel**: `http://localhost/ClassBookingSystem/admin/`
- **Admin Login**: Username: `admin`, Password: `admin123`

---

## 🔧 Detailed Setup Guide

### Prerequisites

- **Web Server**: Apache/Nginx with PHP 7.4+
- **Database**: MySQL 5.7+ or MariaDB 10.2+
- **PHP Extensions**: PDO, PDO_MySQL, GD (for image processing)

### Database Setup Details

The `database_export.sql` includes:

- **10 Tables**: Complete schema with relationships
- **Sample Data**:
  - 5 membership plans (£0.00 - £129.99)
  - 8 video series with content
  - 3 active memberships for test user
  - Multiple instructors and classes
  - Admin account

### File Structure After Setup

```
ClassBookingSystem/
├── admin/              # Admin panel
├── public/             # Public interface
├── config/             # Configuration files
├── templates/          # Shared templates
├── assets/             # CSS/JS files
├── database_export.sql # Database backup
└── *.md               # Documentation
```

### Configuration Files to Update

1. **Database Connection** (`public/api/db.php`)
2. **Upload Paths** (if needed)
3. **Admin Credentials** (optional - change from admin/admin123)

---

## 🎯 Testing Your Installation

### 1. Database Connection Test

Visit: `http://localhost/ClassBookingSystem/public/index.php`

- Should display classes, instructors, and membership plans
- Calendar should load with class data

### 2. Admin Panel Test

Visit: `http://localhost/ClassBookingSystem/admin/`

- Login with: admin/admin123
- Dashboard should show statistics
- All management sections should be accessible

### 3. User Registration Test

- Register a new user account
- Login and access student dashboard
- Try booking a class

### 4. Membership System Test

- Admin: Go to Memberships tab
- Check pending requests, payments, and plans
- Test approval workflow

### 5. Video System Test

- Admin: Go to Videos tab
- Upload a test video
- Check if it appears in student video library

---

## 🔍 Troubleshooting

### Common Issues

**1. Database Connection Error**

```
Error: Connection failed: Access denied
```

**Solution**: Check database credentials in `public/api/db.php`

**2. Calendar Not Loading**

```
Error: Classes not appearing on calendar
```

**Solution**: Check browser console for JavaScript errors, verify API endpoints

**3. File Upload Errors**

```
Error: Failed to upload video
```

**Solution**: Check upload directory permissions and PHP file size limits

**4. Admin Login Issues**

```
Error: Invalid credentials
```

**Solution**: Verify admin table exists and contains admin/admin123 (hashed)

### Debug Tools Available

- **`public/debug_classes.php`** - Test class generation
- **`public/bug_scan.php`** - System health check
- **`export_database.php`** - Re-export database if needed

---

## 🗂️ File Management

### Core Files (Keep These)

- All files marked ✅ CORE in `FILE_LIST.md`
- Database schema files
- Authentication systems
- Main application logic

### Optional Files (Can Remove After Setup)

- Files marked 🔧 DEBUG in `FILE_LIST.md`
- Test files (`test_*.php`)
- Setup utilities (after initial setup)
- Documentation files (if not needed)

### Backup Files (Keep for Safety)

- `database_export.sql` - Database backup
- Health system variants (choose one, keep others as backup)
- Setup scripts (in case you need to reset)

---

## 🔐 Security Recommendations

### Production Deployment

1. **Change Admin Password**

```sql
-- Update admin password (use proper hashing)
UPDATE admin SET password = PASSWORD('your_new_password') WHERE username = 'admin';
```

2. **Remove Debug Files**

- Delete all test\_\*.php files
- Remove debug\_\*.php files
- Remove setup\_\*.php files after initial setup

3. **Set Proper Permissions**

```bash
# Application files (read-only)
chmod 644 *.php
chmod 644 config/*.php

# Upload directories (writable)
chmod 755 public/uploads/
chmod 755 public/uploads/videos/
chmod 755 public/uploads/thumbnails/
```

4. **Configure PHP Settings**

```ini
# php.ini recommendations
upload_max_filesize = 500M
post_max_size = 500M
max_execution_time = 300
memory_limit = 256M
```

---

## 📞 Support & Maintenance

### Regular Maintenance Tasks

1. **Database Backup**

   - Run `export_database.php` regularly
   - Store backups securely

2. **File Cleanup**

   - Monitor upload directory sizes
   - Clean up old video files if needed

3. **Log Monitoring**
   - Check PHP error logs
   - Monitor failed login attempts

### System Monitoring

- **Admin Dashboard**: Shows system health
- **Database Stats**: Monitor table sizes
- **File Storage**: Check upload directory usage
- **User Activity**: Monitor booking patterns

---

## 📋 Quick Reference

### Default Credentials

- **Admin**: admin/admin123
- **Test User**: DeVante Johnson-Rose (check database for password)

### Key URLs

- **Homepage**: `/public/index.php`
- **Admin Panel**: `/admin/dashboard.php`
- **User Dashboard**: `/public/user/dashboard.php`
- **API Endpoints**: `/public/api/`

### Database Tables

- `users` - Student accounts
- `instructors` - Fitness instructors
- `classes` - Class schedules
- `bookings` - Class reservations
- `membership_plans` - Available plans
- `user_memberships` - Member records
- `membership_payments` - Payment tracking
- `video_series` - Video categories
- `videos` - Video content
- `admin` - Admin accounts

---

## 🎉 You're Ready!

Your Class Booking System is now ready for use. The system includes:

✅ **Complete Admin Panel** - Manage everything from one place  
✅ **Student Portal** - User-friendly booking and profile management  
✅ **Membership System** - Full lifecycle management with payments  
✅ **Video Library** - Organized content delivery  
✅ **Real-time Features** - Live availability and booking  
✅ **Mobile Responsive** - Works on all devices  
✅ **Sample Data** - Ready to test immediately

**Need Help?** Check the `SYSTEM_GUIDE.md` for detailed technical documentation.
