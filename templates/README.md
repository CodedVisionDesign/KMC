# Modular Template System

This directory contains the modular template components for the Class Booking System.

## Files Overview

- **`header.php`** - Modular header component with navigation
- **`footer.php`** - Modular footer component with scripts
- **`base.php`** - Base template that combines header + content + footer
- **`config.php`** - Configuration helper for customizing templates

## Usage Methods

### Method 1: Using base.php (Recommended for simple pages)

```php
<?php
include __DIR__ . '/../templates/config.php';

setupPageConfig([
    'pageTitle' => 'My Page Title',
    'navItems' => getPublicNavigation('current-page'),
    'additionalCSS' => ['path/to/custom.css']
]);

$content = '<h1>My page content</h1>';
include __DIR__ . '/../templates/base.php';
?>
```

### Method 2: Direct inclusion (Recommended for complex pages)

```php
<?php
include __DIR__ . '/../templates/config.php';
setupPageConfig([/* your config */]);

include __DIR__ . '/../templates/header.php';
?>

<!-- Your page content here -->
<div class="container">
    <h1>Custom page content</h1>
</div>

<?php
include __DIR__ . '/../templates/footer.php';
?>
```

## Configuration Options

### Page Configuration

- **`pageTitle`** - Browser title bar text
- **`siteTitle`** - Header site title
- **`cssPath`** - Path to main CSS file
- **`homeUrl`** - URL for home/logo link
- **`bodyClass`** - CSS class for body element

### Navigation

- **`navItems`** - Array of navigation items
- **`footerLinks`** - Array of footer links

### Assets

- **`additionalCSS`** - Array of additional CSS files
- **`additionalJS`** - Array of additional JavaScript files
- **`additionalHead`** - Custom HTML for `<head>` section
- **`additionalFooter`** - Custom HTML before `</body>`

## Pre-configured Navigation

### Public Navigation

```php
$navItems = getPublicNavigation('current-page');
```

### Admin Navigation

```php
$navItems = getAdminNavigation('current-page');
```

## Example Configurations

### Simple Public Page

```php
setupPageConfig([
    'pageTitle' => 'Classes - Class Booking System',
    'navItems' => getPublicNavigation('classes'),
    'footerLinks' => getPublicFooterLinks()
]);
```

### Admin Page with Custom Assets

```php
setupPageConfig([
    'pageTitle' => 'Admin Dashboard',
    'siteTitle' => 'Class Booking Admin',
    'navItems' => getAdminNavigation('dashboard'),
    'bodyClass' => 'admin-page',
    'additionalCSS' => ['../assets/css/admin.css'],
    'additionalJS' => ['../assets/js/admin.js']
]);
```

### Page with Custom JavaScript

```php
setupPageConfig([
    'pageTitle' => 'Calendar View',
    'additionalCSS' => [
        'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css'
    ],
    'additionalJS' => [
        'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js',
        '../assets/js/calendar.js'
    ]
]);
```

## Benefits

1. **Consistency** - All pages use the same header/footer structure
2. **Maintainability** - Update header/footer once, changes apply everywhere
3. **Flexibility** - Each page can customize its appearance as needed
4. **DRY Principle** - Don't repeat yourself, reuse components
5. **Easy Theming** - Consistent structure makes styling easier

## File Structure

```
templates/
├── README.md          # This documentation
├── config.php         # Configuration helper functions
├── header.php         # Modular header component
├── footer.php         # Modular footer component
└── base.php           # Base template (header + content + footer)
```

## Migration from Old System

Old way:

```php
// Hardcoded HTML in each file
echo '<html><head><title>My Page</title></head>';
echo '<body><h1>Content</h1></body></html>';
```

New way:

```php
// Use modular system
include 'templates/config.php';
setupPageConfig(['pageTitle' => 'My Page']);
$content = '<h1>Content</h1>';
include 'templates/base.php';
```
