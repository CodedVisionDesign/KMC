<?php
if (file_exists(__DIR__ . '/../config/user_auth.php')) {
    include __DIR__ . '/../config/user_auth.php';
} else {
    error_log('user_auth.php not found');
    die('Authentication system not available');
}

if (file_exists(__DIR__ . '/../templates/config.php')) {
    include __DIR__ . '/../templates/config.php';
} else {
    error_log('Template config.php not found');
    die('Template configuration not found');
}

// Set up page configuration
setupPageConfig([
    'pageTitle' => 'Authentication Test - Class Booking System',
    'navItems' => getPublicNavigation('test'),
    'footerLinks' => getPublicFooterLinks(),
    'bodyClass' => 'test-auth-page',
    'additionalCSS' => ['https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css']
]);

$userInfo = getUserInfo();
$isLoggedIn = isUserLoggedIn();

$content = <<<HTML
<div class="row">
    <div class="col-12 mb-4">
        <h2><i class="fas fa-shield-alt me-2"></i>Authentication System Test</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Authentication Status</h5>
            </div>
            <div class="card-body">
HTML;

if ($isLoggedIn) {
    $content .= <<<HTML
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Logged In Successfully!</strong>
                </div>
                <p><strong>User ID:</strong> {$userInfo['id']}</p>
                <p><strong>Name:</strong> {$userInfo['name']}</p>
                <p><strong>Email:</strong> {$userInfo['email']}</p>
                <a href="logout.php" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
HTML;
} else {
    $content .= <<<HTML
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Not Logged In</strong>
                </div>
                <p>You need to log in to test the booking system.</p>
                <div class="btn-group" role="group">
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </a>
                    <a href="register.php" class="btn btn-success">
                        <i class="fas fa-user-plus me-2"></i>Register
                    </a>
                </div>
HTML;
}

$content .= <<<HTML
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Test Credentials</h5>
            </div>
            <div class="card-body">
                <p>Use these credentials to test the system:</p>
                <div class="bg-light p-3 rounded">
                    <p class="mb-1"><strong>Email:</strong> john@example.com</p>
                    <p class="mb-0"><strong>Password:</strong> password123</p>
                </div>
                <hr>
                <h6>Features to Test:</h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success me-2"></i>Login/Registration</li>
                    <li><i class="fas fa-check text-success me-2"></i>Navigation updates when logged in</li>
                    <li><i class="fas fa-check text-success me-2"></i>Calendar booking (login required)</li>
                    <li><i class="fas fa-check text-success me-2"></i>Automatic redirect to login</li>
                    <li><i class="fas fa-check text-success me-2"></i>Prevent duplicate bookings</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="alert alert-info">
            <h6><i class="fas fa-info-circle me-2"></i>How to Test</h6>
            <ol class="mb-0">
                <li>Try booking a class without logging in (should redirect to login)</li>
                <li>Register a new account or login with test credentials</li>
                <li>Notice how navigation changes to show "Welcome" and "Logout"</li>
                <li>Book a class (should work without asking for name/email)</li>
                <li>Try booking the same class again (should prevent duplicate)</li>
            </ol>
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