<?php
if (file_exists(__DIR__ . '/../config/user_auth.php')) {
    include __DIR__ . '/../config/user_auth.php';
} else {
    error_log('user_auth.php not found');
    die('Authentication system not available');
}

if (file_exists(__DIR__ . '/../config/security.php')) {
    include __DIR__ . '/../config/security.php';
} else {
    error_log('security.php not found');
    die('Security system not available');
}

if (file_exists(__DIR__ . '/../templates/config.php')) {
    include __DIR__ . '/../templates/config.php';
} else {
    error_log('Template config.php not found');
    die('Template configuration not found');
}

// Ensure session is started for CSRF token
ensureSessionStarted();

// Handle login form submission
$error_html = '';
$success_html = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error_html = '<div class="alert alert-danger">Security token validation failed. Please try again.</div>';
    } else {
        $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        // Input length validation
        if (strlen($email) > 100) {
            $error_html = '<div class="alert alert-danger">Email address must be 100 characters or less.</div>';
        } elseif (strlen($password) > 255) {
            $error_html = '<div class="alert alert-danger">Password is too long.</div>';
        } elseif (empty($email) || empty($password)) {
            $error_html = '<div class="alert alert-danger">Please fill in all fields.</div>';
        } else {
            try {
                $user = loginUser($email, $password);
                
                // Redirect to user dashboard or intended destination
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'user/dashboard.php';
                header("Location: $redirect");
                exit();
                
            } catch (Exception $e) {
                error_log('Public login error: ' . $e->getMessage());
                $error_html = '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Set up page configuration
setupPageConfig([
    'pageTitle' => 'Login - Class Booking System',
    'navItems' => getPublicNavigation('login'),
    'footerLinks' => getPublicFooterLinks(),
    'bodyClass' => 'login-page'
]);

// Set form values for display
$emailValue = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '';

$content = <<<HTML
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-header text-center bg-primary text-white">
                <h4><i class="fas fa-sign-in-alt me-2"></i>Login</h4>
            </div>
            <div class="card-body">
                {$error_html}
                {$success_html}
                
                <form method="POST" action="login.php">
                    <input type="hidden" name="csrf_token" value="{$csrfToken}">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="{$emailValue}" maxlength="100" required>
                        <div class="form-text">Maximum 100 characters</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" maxlength="255" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </div>
                </form>
                
                <hr>
                
                <div class="text-center">
                    <p class="mb-0">Don't have an account?</p>
                    <a href="register.php" class="btn btn-outline-success">
                        <i class="fas fa-user-plus me-2"></i>Create Account
                    </a>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <a href="index.php" class="text-decoration-none">
                <i class="fas fa-arrow-left me-2"></i>Back to Classes
            </a>
        </div>
    </div>
</div>
HTML;

if (file_exists(__DIR__ . '/../templates/base.php')) {
    include __DIR__ . '/../templates/base.php';
} else {
    error_log('Template base.php not found');
    die('Template base not found');
}
?> 