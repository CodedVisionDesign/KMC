# Class Booking System - File List & Descriptions

## 📁 Root Directory Files

### Core Documentation

- **SYSTEM_GUIDE.md** - Complete system documentation (NEW)
- **README.md** - Project overview and setup instructions
- **COMPLETION_SUMMARY.md** - Development completion summary
- **database_export.sql** - Complete database export (NEW)
- **export_database.php** - Database export utility (NEW)

### Configuration Files

- **membership_system_prd.md** - Product requirements document
- **bugs.txt** - Known issues log
- **test_calendar.html** - Calendar testing page
- **test.php** - General testing script

### Setup & Documentation Files

- **ADMIN_SETUP_GUIDE.md** - Admin system setup guide
- **ADMIN_STUDENT_HEALTH_VIEWING.md** - Health data viewing guide
- **INSTRUCTOR_SETUP.md** - Instructor setup instructions
- **MODULAR_ADMIN_IMPLEMENTATION.md** - Admin modular design
- **MODULAR_ADMIN_README.md** - Admin system documentation
- **REALTIME_AVAILABILITY_README.md** - Real-time features guide
- **RECURRING_CLASSES_README.md** - Recurring classes documentation
- **TESTING_CHECKLIST.md** - Testing procedures
- **START_HERE.html** - Getting started guide

---

## 📁 admin/ - Admin Panel

### Core Admin Files

- **dashboard.php** - Main admin dashboard ✅ CORE
- **classes.php** - Class management ✅ CORE
- **instructors.php** - Instructor management ✅ CORE
- **students.php** - Student management ✅ CORE
- **memberships.php** - Membership management ✅ CORE
- **videos.php** - Video management ✅ CORE
- **login.php** - Admin authentication ✅ CORE
- **logout.php** - Admin logout ✅ CORE
- **auth.php** - Authentication handler ✅ CORE
- **bookings.php** - Booking management ✅ CORE

### Setup & Utility Files

- **setup_instructors.php** - Instructor setup utility 🔧 SETUP
- **setup_instructors_fixed.php** - Fixed instructor setup 🔧 SETUP
- **setup_membership_video.php** - Membership/video system setup 🔧 SETUP
- **fix_instructors.php** - Instructor fix utility 🔧 SETUP

### Admin Templates

- **templates/header.php** - Admin header template ✅ CORE
- **templates/footer.php** - Admin footer template ✅ CORE
- **includes/admin_common.php** - Admin authentication & utilities ✅ CORE

---

## 📁 public/ - Public Interface

### Main Public Files

- **index.php** - Homepage with class info & calendar ✅ CORE
- **login.php** - User login ✅ CORE
- **register.php** - User registration ✅ CORE
- **logout.php** - User logout ✅ CORE

### API Endpoints

- **api/db.php** - Database connection ✅ CORE
- **api/classes.php** - Class data API for calendar ✅ CORE
- **api/book.php** - Booking API ✅ CORE
- **api/cancel_booking.php** - Cancel booking API ✅ CORE
- **api/class.php** - Individual class API ✅ CORE

### Student Portal (public/user/)

- **user/dashboard.php** - Student dashboard ✅ CORE
- **user/bookings.php** - Booking management ✅ CORE
- **user/profile.php** - Profile management ✅ CORE
- **user/membership.php** - Membership portal ✅ CORE
- **user/videos.php** - Video library ✅ CORE
- **user/health.php** - Health information ✅ CORE
- **user/header.php** - Student header template ✅ CORE
- **user/footer.php** - Student footer template ✅ CORE

### Health System Variants (Choose One)

- **user/health_working.php** - Working health system ✅ CORE
- **user/health_fixed_encoding.php** - Fixed encoding version 🔧 ALTERNATIVE
- **user/health_simple.php** - Simplified version 🔧 ALTERNATIVE
- **user/health_clean.php** - Clean version 🔧 ALTERNATIVE
- **user/health_backup.php** - Backup version 🔧 BACKUP
- **user/health_test.php** - Test version 🔧 DEBUG
- **user/health_debug.php** - Debug version 🔧 DEBUG
- **user/emergency.php** - Emergency contact system 🔧 ALTERNATIVE
- **user/debug.php** - Debug utilities 🔧 DEBUG

### File Storage

- **uploads/videos/** - Video files directory ✅ CORE
- **uploads/thumbnails/** - Video thumbnails directory ✅ CORE
- **assets/images/video-placeholder.jpg** - Placeholder image ✅ CORE

### Debug & Test Files

- **debug_classes.php** - Class generation debugging 🔧 DEBUG
- **bug_scan.php** - System bug scanner 🔧 DEBUG
- **test\_\*.php** - Various test files 🔧 DEBUG
  - test_auth.php - Authentication testing
  - test_class_display_fix.php - Class display debugging
  - test_index_queries.php - Database query testing
  - test_index_queries_fixed.php - Fixed query testing
  - test_membership_web.php - Membership system testing
  - test_recurring_fix.php - Recurring class testing
  - test_final.html - Final testing page
  - test_realtime.html - Real-time features testing
- **example_standalone.php** - Standalone example 🔧 DEBUG
- **add_sample_data.php** - Sample data utility 🔧 SETUP

### Admin Utilities (public/admin/)

- **admin/cleanup_data.php** - Data cleanup utilities 🔧 SETUP
- **admin/cleanup_duplicates.php** - Remove duplicate data 🔧 SETUP
- **admin/remove_duplicate_memberships.php** - Membership cleanup 🔧 SETUP
- **admin/run_migration.php** - Database migration runner 🔧 SETUP

---

## 📁 config/ - Configuration

### Database Files

- **database.sql** - Main database schema ✅ CORE
- **membership_system_migration.sql** - Membership system schema ✅ CORE
- **setup.sql** - Basic setup SQL ✅ CORE
- **setup_users.sql** - User setup SQL ✅ CORE
- **enhanced_users.sql** - Enhanced user schema ✅ CORE
- **add_instructors.sql** - Instructor data SQL 🔧 SETUP
- **add_instructors_safe.sql** - Safe instructor insertion 🔧 SETUP
- **add_recurring_to_classes.sql** - Recurring classes migration 🔧 SETUP

### Configuration Files

- **user_auth.php** - User authentication helpers ✅ CORE
- **security.php** - Security functions ✅ CORE
- **error_handling.php** - Error handling utilities ✅ CORE
- **membership_functions.php** - Membership helper functions ✅ CORE
- **environment.example** - Environment configuration example 🔧 SETUP
- **README_ERROR_HANDLING.md** - Error handling documentation 📋 DOC

---

## 📁 templates/ - Shared Templates

- **base.php** - Base template system ✅ CORE
- **config.php** - Template configuration ✅ CORE
- **header.php** - Shared header template ✅ CORE
- **footer.php** - Shared footer template ✅ CORE
- **README.md** - Template system documentation 📋 DOC

---

## 📁 assets/ - Global Assets

### CSS Files

- **css/custom.css** - Main stylesheet ✅ CORE
- **css/realtime-availability.css** - Real-time features styles ✅ CORE

### JavaScript Files

- **js/main.js** - Core JavaScript functionality ✅ CORE
- **js/realtime-availability.js** - Real-time features JavaScript ✅ CORE

---

## Legend

- ✅ **CORE** - Essential files required for system functionality
- 🔧 **DEBUG** - Debug/test files (can be deleted in production)
- 🔧 **SETUP** - Setup/migration files (can be deleted after initial setup)
- 🔧 **ALTERNATIVE** - Alternative versions (choose one)
- 🔧 **BACKUP** - Backup versions (keep for safety)
- 📋 **DOC** - Documentation files (optional in production)

---

## Database Export Information

**File**: `database_export.sql`
**Size**: 22,523 bytes
**Tables Exported**: 10

- admin
- bookings
- classes
- instructors
- membership_payments
- membership_plans
- user_memberships
- users
- video_series
- videos

**Sample Data Included**:

- 5 membership plans (Free Trial £0.00 to Unlimited £129.99)
- 8 video series with titles
- 3 active user memberships for "DeVante Johnson-Rose"
- Multiple instructors and classes
- Admin account (admin/admin123)

---

## Quick Setup Guide

1. **Import Database**: Import `database_export.sql`
2. **Update Config**: Edit `public/api/db.php` with your database credentials
3. **Set Permissions**: Make `public/uploads/` directories writable
4. **Access System**:
   - Public: `http://localhost/Testbook/public/`
   - Admin: `http://localhost/Testbook/admin/` (admin/admin123)
5. **Optional**: Run setup files if needed, then delete debug/test files

---

This file list helps identify which files are essential for production deployment versus development/testing files that can be removed.
