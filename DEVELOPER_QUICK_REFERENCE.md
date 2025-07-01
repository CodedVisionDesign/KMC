# Developer Quick Reference Guide

## Common Development Tasks

### 1. Adding a New API Endpoint

```php
<?php
// /public/api/new_endpoint.php
header('Content-Type: application/json');

// Include error handling
require_once __DIR__ . '/../../config/error_handling.php';

// Database connection
require_once __DIR__ . '/db.php';

// Authentication check (if required)
require_once __DIR__ . '/../../config/user_auth.php';
if (!isUserLoggedIn()) {
    http_response_code(401);
    echo ErrorMessages::apiError(ErrorMessages::LOGIN_REQUIRED, 401);
    exit();
}

try {
    // Handle different HTTP methods
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetRequest();
            break;
        case 'POST':
            handlePostRequest();
            break;
        default:
            http_response_code(405);
            echo ErrorMessages::apiError('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    http_response_code(500);
    echo ErrorMessages::apiError(ErrorMessages::GENERIC_ERROR, 500);
}

function handleGetRequest() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM table_name");
    $data = $stmt->fetchAll();
    
    echo ErrorMessages::apiSuccess($data);
}

function handlePostRequest() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (empty($input['required_field'])) {
        http_response_code(400);
        echo ErrorMessages::apiError(ErrorMessages::REQUIRED_FIELDS, 400);
        return;
    }
    
    // Process data
    $stmt = $pdo->prepare("INSERT INTO table_name (field) VALUES (?)");
    $stmt->execute([$input['required_field']]);
    
    echo ErrorMessages::apiSuccess(['id' => $pdo->lastInsertId()]);
}
?>
```

### 2. Creating a New Admin Page

```php
<?php
// /admin/new_page.php
require_once __DIR__ . '/includes/admin_common.php';

$pageTitle = 'New Admin Page';
$success = '';
$error = '';

// Handle form submission
if ($_POST) {
    try {
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'])) {
            throw new Exception('Security validation failed');
        }
        
        // Process form data
        $name = sanitizeInputWithLength($_POST['name'], 100);
        
        // Database operation
        $stmt = $pdo->prepare("INSERT INTO table_name (name) VALUES (?)");
        $stmt->execute([$name]);
        
        $success = 'Record created successfully';
        
    } catch (Exception $e) {
        $error = getErrorMessage($e);
    }
}

// Page content
$content = '
<div class="row">
    <div class="col-12">
        <h1>' . $pageTitle . '</h1>
        
        <form method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">
            
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
                <div class="invalid-feedback">Please provide a name.</div>
            </div>
            
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>';

// Header actions
$headerActions = '<a href="list.php" class="btn btn-secondary">Back to List</a>';

// Render page
renderAdminPage($content, [
    'pageDescription' => 'Create a new record',
    'headerActions' => $headerActions,
    'success' => $success,
    'error' => $error,
    'additionalJS' => ['../assets/js/form-validation.js']
]);
?>
```

### 3. Adding Client-Side Form Validation

```javascript
// Form validation helper
function validateForm(formId, rules) {
    const form = document.getElementById(formId);
    const errors = [];
    
    Object.keys(rules).forEach(fieldName => {
        const field = form.elements[fieldName];
        const rule = rules[fieldName];
        
        if (rule.required && !field.value.trim()) {
            errors.push(`${rule.label} is required`);
            field.classList.add('is-invalid');
        } else if (field.value && rule.pattern && !rule.pattern.test(field.value)) {
            errors.push(`${rule.label} format is invalid`);
            field.classList.add('is-invalid');
        } else {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        }
    });
    
    return errors;
}

// Usage example
const validationRules = {
    email: {
        required: true,
        label: 'Email',
        pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    },
    password: {
        required: true,
        label: 'Password',
        pattern: /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/
    }
};

document.getElementById('myForm').addEventListener('submit', function(e) {
    const errors = validateForm('myForm', validationRules);
    
    if (errors.length > 0) {
        e.preventDefault();
        showAlert('danger', errors.join('<br>'));
    }
});
```

### 4. Database Migration Template

```sql
-- Migration: Add new table
-- File: config/migrations/001_add_new_table.sql

CREATE TABLE IF NOT EXISTS new_table (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add indexes
CREATE INDEX idx_new_table_status ON new_table(status);
CREATE INDEX idx_new_table_created_at ON new_table(created_at);

-- Insert default data
INSERT INTO new_table (name, description) VALUES
('Default Item', 'Default description');
```

### 5. Adding a New Membership Feature

```php
<?php
// config/membership_functions.php - Add new function

/**
 * Check if user has specific membership feature access
 */
function userHasFeatureAccess($userId, $featureName) {
    try {
        $membership = getUserActiveMembership($userId);
        
        if (!$membership) {
            return false;
        }
        
        // Check feature access based on plan
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as has_access
            FROM membership_plan_features mpf
            JOIN membership_plans mp ON mpf.plan_id = mp.id
            JOIN user_memberships um ON um.plan_id = mp.id
            WHERE um.user_id = ? 
            AND um.status = 'active'
            AND mpf.feature_name = ?
            AND um.start_date <= CURDATE()
            AND um.end_date >= CURDATE()
        ");
        $stmt->execute([$userId, $featureName]);
        $result = $stmt->fetch();
        
        return $result['has_access'] > 0;
        
    } catch (Exception $e) {
        error_log('Error checking feature access: ' . $e->getMessage());
        return false;
    }
}

// Usage in API or page
if (!userHasFeatureAccess($userId, 'premium_classes')) {
    echo ErrorMessages::apiError('Premium membership required', 403);
    exit();
}
?>
```

## Common Code Patterns

### 1. Secure Form Processing

```php
// Standard form processing pattern
if ($_POST && isset($_POST['action'])) {
    try {
        // CSRF validation
        if (!validateCSRFToken($_POST['csrf_token'])) {
            throw new Exception('Security validation failed');
        }
        
        // Input validation
        $requiredFields = ['name', 'email'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new InvalidArgumentException("$field is required");
            }
        }
        
        // Sanitize inputs
        $data = [
            'name' => sanitizeInputWithLength($_POST['name'], 100),
            'email' => validateEmailInput($_POST['email'])
        ];
        
        // Database transaction
        $pdo->beginTransaction();
        
        // Your database operations here
        $stmt = $pdo->prepare("INSERT INTO table (name, email) VALUES (?, ?)");
        $stmt->execute([$data['name'], $data['email']]);
        
        $pdo->commit();
        $success = 'Operation completed successfully';
        
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) $pdo->rollback();
        $error = $e->getMessage();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollback();
        error_log('Form processing error: ' . $e->getMessage());
        $error = 'An error occurred. Please try again.';
    }
}
```

### 2. AJAX Request Pattern

```javascript
// Standard AJAX request with error handling
function makeAjaxRequest(url, data, method = 'POST') {
    return fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: method === 'POST' ? JSON.stringify(data) : null
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            return data;
        } else {
            throw new Error(data.error || 'Request failed');
        }
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        showAlert('danger', error.message);
        throw error;
    });
}

// Usage
makeAjaxRequest('/api/endpoint.php', { id: 123 })
    .then(data => {
        console.log('Success:', data);
        showAlert('success', 'Operation completed');
    })
    .catch(error => {
        // Error already handled in makeAjaxRequest
    });
```

### 3. Modal Management

```javascript
// Modal management helper
class ModalManager {
    constructor(modalId) {
        this.modal = document.getElementById(modalId);
        this.bsModal = new bootstrap.Modal(this.modal);
    }
    
    show(data = {}) {
        // Populate modal with data
        Object.keys(data).forEach(key => {
            const element = this.modal.querySelector(`[data-field="${key}"]`);
            if (element) {
                element.textContent = data[key];
            }
        });
        
        this.bsModal.show();
    }
    
    hide() {
        this.bsModal.hide();
    }
    
    onSubmit(callback) {
        const form = this.modal.querySelector('form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                callback(new FormData(form));
            });
        }
    }
}

// Usage
const editModal = new ModalManager('editModal');
editModal.onSubmit(async (formData) => {
    try {
        const data = Object.fromEntries(formData);
        await makeAjaxRequest('/api/update.php', data);
        editModal.hide();
        location.reload();
    } catch (error) {
        // Error handled by makeAjaxRequest
    }
});
```

## Debugging Tips

### 1. Enable Debug Mode

```php
// Add to top of PHP file for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');

// Debug function
function debug($data, $label = 'DEBUG') {
    error_log("[$label] " . print_r($data, true));
}

// Usage
debug($_POST, 'Form Data');
debug($pdo->lastInsertId(), 'Last Insert ID');
```

### 2. JavaScript Debugging

```javascript
// Debug helper
function debugLog(data, label = 'DEBUG') {
    console.group(label);
    console.log(data);
    console.trace();
    console.groupEnd();
}

// Network debugging
function debugFetch(url, options) {
    console.log('Fetch Request:', { url, options });
    return fetch(url, options)
        .then(response => {
            console.log('Fetch Response:', response);
            return response;
        });
}
```

### 3. Database Query Debugging

```php
// Debug database queries
function debugQuery($stmt, $params = []) {
    $query = $stmt->queryString;
    foreach ($params as $param) {
        $query = preg_replace('/\?/', "'$param'", $query, 1);
    }
    error_log("SQL Query: $query");
}

// Usage
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
debugQuery($stmt, [$userId]);
$stmt->execute([$userId]);
```

## Performance Optimization

### 1. Database Query Optimization

```php
// Use prepared statements with placeholders
$stmt = $pdo->prepare("
    SELECT u.*, m.plan_name 
    FROM users u 
    LEFT JOIN user_memberships um ON u.id = um.user_id 
    LEFT JOIN membership_plans m ON um.plan_id = m.id 
    WHERE u.id = ? AND um.status = 'active'
");

// Batch operations
$stmt = $pdo->prepare("INSERT INTO table (col1, col2) VALUES (?, ?)");
foreach ($data as $row) {
    $stmt->execute([$row['col1'], $row['col2']]);
}
```

### 2. Frontend Optimization

```javascript
// Debounce function for search inputs
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Usage
const searchInput = document.getElementById('search');
const debouncedSearch = debounce(performSearch, 300);
searchInput.addEventListener('input', debouncedSearch);
```

This quick reference guide provides common patterns and solutions for typical development tasks in the Class Booking System.