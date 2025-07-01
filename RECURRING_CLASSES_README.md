# Recurring Classes Feature

## Overview

The recurring classes feature allows administrators to mark classes as "recurring" to indicate they happen weekly at the same time. This helps with class management and gives users a visual indicator of which classes are regularly scheduled.

## Features Added

### Admin Interface

- **Checkbox**: New "Recurring Class" checkbox in the add/edit class form
- **Visual Indicator**: Recurring classes display a blue "Recurring Weekly" badge in the class list
- **Database Support**: New `recurring` column in the classes table

### Database Changes

- Added `recurring TINYINT(1) DEFAULT 0` column to classes table
- Added index for better performance when filtering recurring classes

## How to Use

### For New Installations

The `config/database.sql` file already includes the recurring column.

### For Existing Installations

Run the migration script:

```sql
-- Run this in your database
source config/add_recurring_to_classes.sql;
```

### Admin Usage

1. Go to Admin → Manage Classes
2. When creating or editing a class, check the "Recurring Class" box if the class runs weekly
3. Recurring classes will show a blue "Recurring Weekly" badge in the class list

## Technical Details

### Database Schema

```sql
ALTER TABLE classes
ADD COLUMN recurring TINYINT(1) DEFAULT 0 AFTER instructor_id;
```

### Form Processing

- Checkbox value is captured as 1 (checked) or 0 (unchecked)
- Stored in database as TINYINT for efficient storage
- Handled in both create and edit operations

## Future Enhancements

This foundation allows for future features like:

- Automatic class generation for recurring classes
- Advanced recurring patterns (bi-weekly, monthly)
- Recurring class management dashboard
- Student notifications for recurring class schedules
