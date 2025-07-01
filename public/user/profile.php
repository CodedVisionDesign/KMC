<?php
// Include authentication and database functions
require_once __DIR__ . '/../../config/user_auth.php';
require_once __DIR__ . '/../../config/security.php';

$pageTitle = 'Profile Settings';
$message = '';
$messageType = '';

// Include header (this sets $userInfo)
include 'header.php';

// Get user data
try {
    $pdo = connectUserDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userInfo['id']]);
    $userData = $stmt->fetch();
} catch (Exception $e) {
    error_log('Profile error: ' . $e->getMessage());
    $userData = null;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $message = 'Security token validation failed. Please try again.';
        $messageType = 'danger';
    } else {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $dateOfBirth = $_POST['date_of_birth'] ?? '';
        $gender = $_POST['gender'] ?? '';
        
        // Validation
        if (empty($firstName) || empty($lastName) || empty($email)) {
            $message = 'Please fill in all required fields.';
            $messageType = 'danger';
        } elseif (!validateEmail($email)) {
            $message = 'Please enter a valid email address.';
            $messageType = 'danger';
        } elseif (!validateDateOfBirth($dateOfBirth)) {
            $message = 'Please enter a valid date of birth.';
            $messageType = 'danger';
        } else {
            try {
                // Check if email is already used by another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $userInfo['id']]);
                if ($stmt->fetch()) {
                    $message = 'Email address is already in use by another account.';
                    $messageType = 'danger';
                } else {
                    // Update user profile
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET first_name = ?, last_name = ?, email = ?, phone = ?, 
                            date_of_birth = ?, gender = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$firstName, $lastName, $email, $phone, $dateOfBirth, $gender, $userInfo['id']]);
                    
                    // Update session data
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_name'] = $firstName . ' ' . $lastName;
                    $_SESSION['user_first_name'] = $firstName;
                    
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$userInfo['id']]);
                    $userData = $stmt->fetch();
                    
                    $message = 'Profile updated successfully!';
                    $messageType = 'success';
                }
            } catch (Exception $e) {
                error_log('Profile update error: ' . $e->getMessage());
                $message = 'Failed to update profile. Please try again.';
                $messageType = 'danger';
            }
        }
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();
?>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-user-edit me-2"></i>Profile Settings</h4>
                <p class="mb-0 text-muted">Update your personal information</p>
            </div>
            <div class="card-body">
                <?php if ($userData): ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($userData['first_name'] ?? ''); ?>" 
                                   maxlength="50" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($userData['last_name'] ?? ''); ?>" 
                                   maxlength="50" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" 
                               maxlength="100" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>" 
                               maxlength="20" placeholder="Optional">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                   value="<?php echo htmlspecialchars($userData['date_of_birth'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-control" id="gender" name="gender">
                                <option value="">Select Gender (Optional)</option>
                                <option value="male" <?php echo ($userData['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($userData['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($userData['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                <option value="prefer_not_to_say" <?php echo ($userData['gender'] ?? '') === 'prefer_not_to_say' ? 'selected' : ''; ?>>Prefer not to say</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Update Profile
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Unable to load your profile data. Please try refreshing the page.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?> 