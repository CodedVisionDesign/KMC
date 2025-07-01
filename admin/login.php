<?php
// Include error handling configuration first
require_once __DIR__ . '/../config/error_handling.php';

session_start();
if (file_exists(__DIR__ . '/../public/api/db.php')) {
    require_once __DIR__ . '/../public/api/db.php';
} else {
    error_log('Database connection file not found');
    die('Database system not available');
}

if (file_exists(__DIR__ . '/../config/security.php')) {
    require_once __DIR__ . '/../config/security.php';
} else {
    error_log('Security config not found');
    die('Security system not available');
}

$error = '';
if ($_POST) {
    // Verify CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = ErrorMessages::CSRF_INVALID;
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Input length validation
        if (strlen($username) > 50) {
            $error = ErrorMessages::NAME_TOO_LONG;
        } elseif (strlen($password) > 255) {
            $error = ErrorMessages::PASSWORD_TOO_LONG;
        } elseif ($username && $password) {
            try {
                $stmt = $pdo->prepare('SELECT id, password_hash FROM admin WHERE username = ?');
                $stmt->execute([$username]);
                $admin = $stmt->fetch();
                
                if ($admin && password_verify($password, $admin['password_hash'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $username;
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = ErrorMessages::LOGIN_FAILED;
                }
            } catch (Exception $e) {
                error_log('Admin login error: ' . $e->getMessage());
                $error = ErrorMessages::GENERIC_ERROR;
            }
        } else {
            $error = ErrorMessages::REQUIRED_FIELDS;
        }
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Include template config and set up page without navigation
if (file_exists(__DIR__ . '/../templates/config.php')) {
    include __DIR__ . '/../templates/config.php';
} else {
    error_log('Template config.php not found');
    die('Template configuration not found');
}
setupPageConfig([
    'pageTitle' => 'Admin Login - Class Booking System',
    'siteTitle' => 'Class Booking Admin',
    'cssPath' => '../assets/css/custom.css',
    'homeUrl' => '../public/index.php',
    'navItems' => [], // No navigation for login page
    'footerLinks' => getAdminFooterLinks(),
    'bodyClass' => 'admin-login-page'
]);

// Prepare error HTML before using it
$error_html = $error ? '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>' : '';

$content = <<<HTML
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3>Admin Login</h3>
            </div>
            <div class="card-body">
                {$error_html}
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="{$csrfToken}">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" maxlength="50" required>
                        <div class="form-text">Maximum 50 characters</div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" maxlength="255" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Login</button>
                    <a href="../public/index.php" class="btn btn-secondary">Back to Classes</a>
                </form>
            </div>
        </div>
    </div>
</div>
HTML;

$content = str_replace(['{$error_html}', '{$csrfToken}'], [$error_html, $csrfToken], $content);

if (file_exists(__DIR__ . '/../templates/base.php')) {
    include __DIR__ . '/../templates/base.php';
} else {
    error_log('Template base.php not found');
    die('Template base not found');
} 