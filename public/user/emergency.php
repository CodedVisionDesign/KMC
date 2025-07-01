<?php
require_once __DIR__ . '/../../config/user_auth.php';
require_once __DIR__ . '/../../config/security.php';

$pageTitle = 'Emergency Contacts';
$message = '';
$messageType = '';

// Include header (this sets $userInfo)
include 'header.php';

// Get user health data (which includes emergency contacts)
try {
    $pdo = connectUserDB();
    $stmt = $pdo->prepare("SELECT health_questionnaire FROM users WHERE id = ?");
    $stmt->execute([$userInfo['id']]);
    $result = $stmt->fetch();
    $healthData = [];
    if ($result && !empty($result['health_questionnaire'])) {
        $healthData = json_decode($result['health_questionnaire'], true) ?? [];
    }
} catch (Exception $e) {
    error_log('Emergency contact data error: ' . $e->getMessage());
    $healthData = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $message = 'Security token validation failed. Please try again.';
        $messageType = 'danger';
    } else {
        // Update emergency contact information in existing health data
        $healthData['emergency_contact_name'] = trim($_POST['emergency_contact_name'] ?? '');
        $healthData['emergency_contact_phone'] = trim($_POST['emergency_contact_phone'] ?? '');
        $healthData['emergency_contact_relationship'] = trim($_POST['emergency_contact_relationship'] ?? '');
        $healthData['updated_at'] = date('Y-m-d H:i:s');
        
        // Validation
        if (empty($healthData['emergency_contact_name']) && empty($healthData['emergency_contact_phone'])) {
            $message = 'Please provide at least an emergency contact name and phone number.';
            $messageType = 'danger';
        } elseif (strlen($healthData['emergency_contact_name']) > 100) {
            $message = 'Emergency contact name must be 100 characters or less.';
            $messageType = 'danger';
        } elseif (strlen($healthData['emergency_contact_phone']) > 20) {
            $message = 'Emergency contact phone must be 20 characters or less.';
            $messageType = 'danger';
        } elseif (strlen($healthData['emergency_contact_relationship']) > 50) {
            $message = 'Emergency contact relationship must be 50 characters or less.';
            $messageType = 'danger';
        } else {
            try {
                // Update user health data with new emergency contact info
                $healthJson = json_encode($healthData);
                $stmt = $pdo->prepare("UPDATE users SET health_questionnaire = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$healthJson, $userInfo['id']]);
                
                $message = 'Emergency contact information updated successfully!';
                $messageType = 'success';
            } catch (Exception $e) {
                error_log('Emergency contact update error: ' . $e->getMessage());
                $message = 'Failed to update emergency contact information. Please try again.';
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
                <h4><i class="fas fa-phone me-2"></i>Emergency Contacts</h4>
                <p class="mb-0 text-muted">Add, edit, or remove your emergency contact information</p>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Important:</strong> This information is crucial for your safety during fitness classes. 
                    Staff will contact this person in case of a medical emergency.
                </div>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2">Primary Emergency Contact</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="emergency_contact_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="emergency_contact_name" 
                                       name="emergency_contact_name" 
                                       value="<?php echo htmlspecialchars($healthData['emergency_contact_name'] ?? ''); ?>" 
                                       maxlength="100" required>
                                <div class="form-text">Maximum 100 characters</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="emergency_contact_phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="emergency_contact_phone" 
                                       name="emergency_contact_phone" 
                                       value="<?php echo htmlspecialchars($healthData['emergency_contact_phone'] ?? ''); ?>" 
                                       maxlength="20" required>
                                <div class="form-text">Maximum 20 characters</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="emergency_contact_relationship" class="form-label">Relationship</label>
                            <input type="text" class="form-control" id="emergency_contact_relationship" 
                                   name="emergency_contact_relationship" 
                                   value="<?php echo htmlspecialchars($healthData['emergency_contact_relationship'] ?? ''); ?>" 
                                   placeholder="e.g., Parent, Spouse, Friend, Sibling" maxlength="50">
                            <div class="form-text">Maximum 50 characters</div>
                        </div>
                    </div>
                    
                    <?php if (!empty($healthData['emergency_contact_name']) || !empty($healthData['emergency_contact_phone'])): ?>
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2">Current Emergency Contact</h5>
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Name:</strong><br>
                                        <?php echo htmlspecialchars($healthData['emergency_contact_name'] ?? 'Not provided'); ?>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Phone:</strong><br>
                                        <?php echo htmlspecialchars($healthData['emergency_contact_phone'] ?? 'Not provided'); ?>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Relationship:</strong><br>
                                        <?php echo htmlspecialchars($healthData['emergency_contact_relationship'] ?? 'Not specified'); ?>
                                    </div>
                                </div>
                                <?php if (!empty($healthData['updated_at'])): ?>
                                <div class="mt-2 text-muted small">
                                    <i class="fas fa-clock me-1"></i>
                                    Last updated: <?php echo date('M j, Y g:i A', strtotime($healthData['updated_at'])); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Update Emergency Contact
                        </button>
                    </div>
                </form>
                
                <div class="mt-4 pt-3 border-top">
                    <h6>Need to update your health information too?</h6>
                    <p class="text-muted small">
                        Emergency contact information is part of your health questionnaire. 
                        You can also update medical conditions, medications, and other health details.
                    </p>
                    <a href="health.php" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-heartbeat me-1"></i> Update Health Details
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?> 