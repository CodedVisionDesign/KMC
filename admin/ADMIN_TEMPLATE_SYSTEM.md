# Admin Modular Template System

## Overview

The admin panel now uses a modular template system to ensure consistency across all admin pages. This system provides standardized navigation, styling, error handling, and utility functions.

## File Structure

```
admin/
├── includes/
│   └── admin_common.php      # Common authentication, database, and utility functions
├── templates/
│   ├── header.php           # Modular header with navigation
│   └── footer.php           # Modular footer with scripts
├── dashboard.php            # ✅ Updated to use modular system
├── classes.php              # ✅ Updated to use modular system
├── students.php             # ✅ Updated to use modular system
├── instructors.php          # 🔄 Needs update
├── bookings.php             # 🔄 Needs update
└── other admin pages...     # 🔄 Need updates
```

## How to Use the Modular System

### 1. Include Common Functions

Replace individual includes with the common include file:

```php
<?php
// OLD WAY:
// require_once 'auth.php';
// require_once '../public/api/db.php';

// NEW WAY:
require_once 'includes/admin_common.php';
```

### 2. Build Your Page Content

Create your page content as an HTML string:

```php
$content = <<<HTML
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5>Your Page Content</h5>
                <p>Your page content goes here...</p>
            </div>
        </div>
    </div>
</div>
HTML;
```

### 3. Prepare Page Options

Configure page-specific options:

```php
// Optional header actions (buttons in top right)
$headerActions = createHeaderActions([
    [
        'text' => 'Add New Item',
        'icon' => 'fas fa-plus',
        'class' => 'btn btn-primary',
        'href' => 'add.php'
    ],
    [
        'text' => 'Export',
        'icon' => 'fas fa-download',
        'class' => 'btn btn-secondary',
        'onclick' => 'exportData()'
    ]
]);

// Optional JavaScript
$inlineJS = <<<JS
function exportData() {
    alert('Exporting data...');
}
JS;
```

### 4. Render the Page

Use the render function to output the complete page:

```php
renderAdminPage($content, [
    'pageDescription' => 'Describe what this page does',
    'headerActions' => $headerActions,
    'success' => $success ?? null,
    'error' => $error ?? null,
    'message' => $message ?? null,
    'additionalCSS' => ['../assets/css/custom-page.css'],
    'additionalJS' => ['../assets/js/custom-page.js'],
    'inlineJS' => $inlineJS
]);
```

## Features Provided

### 🎨 **Consistent Navigation**

- Auto-highlighting of current page
- Dynamic instructor link based on table existence
- Responsive navigation with mobile menu
- Admin dropdown with quick links

### 📊 **Standardized Page Headers**

- Auto-generated page titles with icons
- Optional page descriptions
- Configurable header action buttons
- Consistent spacing and styling

### ⚠️ **Unified Alert System**

- Success, error, and info message display
- Auto-dismiss after 5 seconds
- Bootstrap styling with icons
- Accessible markup

### 🛠️ **Utility Functions**

- `createHeaderActions()` - Generate action buttons
- `instructorsTableExists()` - Check instructor table
- `getErrorMessage()` - User-friendly error messages
- Input validation helpers
- CSRF protection functions

### 📱 **Responsive Design**

- Mobile-friendly navigation
- Consistent card styling
- Bootstrap-based layout
- Custom admin styling

### 🔧 **JavaScript Utilities**

- Auto-dismiss alerts
- Tooltip/popover initialization
- Delete confirmation dialogs
- AJAX request helper
- Toast notifications
- Loading state management

## Page Configuration Options

| Option            | Type   | Description                                           |
| ----------------- | ------ | ----------------------------------------------------- |
| `pageDescription` | string | Subtitle text below page title                        |
| `headerActions`   | string | HTML for action buttons (use `createHeaderActions()`) |
| `success`         | string | Success message to display                            |
| `error`           | string | Error message to display                              |
| `message`         | string | Info message to display                               |
| `additionalCSS`   | array  | Additional CSS files to include                       |
| `additionalJS`    | array  | Additional JS files to include                        |
| `inlineJS`        | string | Inline JavaScript code                                |

## Helper Functions

### `createHeaderActions(array $actions)`

Generates HTML for header action buttons:

```php
$actions = [
    [
        'text' => 'Button Text',
        'icon' => 'fas fa-icon-name',    // Optional
        'class' => 'btn btn-primary',    // Optional, default: btn btn-primary
        'href' => 'target.php',         // For links
        'onclick' => 'jsFunction()'     // For JavaScript actions
    ]
];
```

### Input Validation Helpers

- `sanitizeInput($input, $maxLength)` - Trim and validate length
- `validateEmailInput($email)` - Email validation
- `validateDateInput($date)` - Date validation
- `validateTimeInput($time)` - Time validation

### Error Handling

- `getErrorMessage($exception)` - Convert database errors to user-friendly messages

## Benefits

✅ **Consistency** - All admin pages have identical navigation and styling  
✅ **Maintainability** - Changes to navigation/styling update all pages  
✅ **Security** - Centralized authentication and CSRF protection  
✅ **Performance** - Shared CSS/JS resources, optimized loading  
✅ **Accessibility** - Standardized markup and keyboard navigation  
✅ **Responsiveness** - Mobile-friendly across all admin pages

## Migration Checklist

To update an existing admin page:

1. ✅ Replace auth/db includes with `require_once 'includes/admin_common.php';`
2. ✅ Remove hardcoded HTML structure (`<!DOCTYPE>`, `<nav>`, etc.)
3. ✅ Move page content into `$content` variable
4. ✅ Configure page options (actions, descriptions, etc.)
5. ✅ Extract JavaScript into `$inlineJS` variable
6. ✅ Call `renderAdminPage($content, $options)` to render
7. ✅ Test navigation, alerts, and functionality

## Pages Status

- ✅ **dashboard.php** - Fully converted
- ✅ **classes.php** - Fully converted
- ✅ **students.php** - Fully converted
- 🔄 **instructors.php** - Needs conversion
- 🔄 **bookings.php** - Needs conversion
- 🔄 **setup_instructors.php** - Needs conversion
- 🔄 **Other pages** - Need conversion

## Next Steps

1. Convert remaining admin pages to use the modular system
2. Test all pages for consistent behavior
3. Add any missing utility functions as needed
4. Document page-specific customizations
5. Consider adding themes or advanced customization options
