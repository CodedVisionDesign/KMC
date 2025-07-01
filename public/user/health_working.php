<?php
require_once __DIR__ . '/../../config/user_auth.php';
require_once __DIR__ . '/../../config/security.php';

// Ensure user is logged in
if (!isUserLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$userInfo = getUserInfo();
$pageTitle = 'Health Details';
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Class Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1><i class="fas fa-heartbeat me-2"></i>Health Details</h1>
        <p>Welcome, <?php echo htmlspecialchars($userInfo['first_name']); ?>!</p>
        
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Do you have any medical conditions?</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="has_medical_conditions" id="medical_yes" value="yes">
                                <label class="form-check-label" for="medical_yes">Yes</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="has_medical_conditions" id="medical_no" value="no">
                                <label class="form-check-label" for="medical_no">No</label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Update Health Information
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 