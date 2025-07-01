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
$message = '';
$messageType = '';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Get user health data  
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
    error_log('Health data error: ' . $e->getMessage());
    $healthData = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $message = 'Security token validation failed. Please try again.';
        $messageType = 'danger';
    } else {
        // Build health questionnaire array
        $healthQuestionnaire = [
            'has_medical_conditions' => isset($_POST['has_medical_conditions']) && $_POST['has_medical_conditions'] === 'yes',
            'medical_conditions' => trim($_POST['medical_conditions'] ?? ''),
            'takes_medication' => isset($_POST['takes_medication']) && $_POST['takes_medication'] === 'yes',
            'medication_details' => trim($_POST['medication_details'] ?? ''),
            'has_injuries' => isset($_POST['has_injuries']) && $_POST['has_injuries'] === 'yes',
            'injury_details' => trim($_POST['injury_details'] ?? ''),
            'emergency_contact_name' => trim($_POST['emergency_contact_name'] ?? ''),
            'emergency_contact_phone' => trim($_POST['emergency_contact_phone'] ?? ''),
            'emergency_contact_relationship' => trim($_POST['emergency_contact_relationship'] ?? ''),
            'fitness_level' => $_POST['fitness_level'] ?? '',
            'exercise_limitations' => trim($_POST['exercise_limitations'] ?? ''),
            'has_allergies' => isset($_POST['has_allergies']) && $_POST['has_allergies'] === 'yes',
            'allergy_details' => trim($_POST['allergy_details'] ?? ''),
            'consent_medical_emergency' => isset($_POST['consent_medical_emergency']),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            // Update user health data
            $healthJson = json_encode($healthQuestionnaire);
            $stmt = $pdo->prepare("UPDATE users SET health_questionnaire = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$healthJson, $userInfo['id']]);
            
            $healthData = $healthQuestionnaire;
            $message = 'Health information updated successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            error_log('Health update error: ' . $e->getMessage());
            $message = 'Failed to update health information. Please try again.';
            $messageType = 'danger';
        }
    }
}

// Generate CSRF token
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
    <link href="/testbook/assets/css/custom.css" rel="stylesheet">
    <style>
        .user-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        .user-nav {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 2rem;
        }
        .user-nav .nav-link {
            color: #495057;
            font-weight: 500;
        }
        .user-nav .nav-link.active {
            color: #667eea;
            border-bottom: 2px solid #667eea;
        }
        .user-nav .nav-link:hover {
            color: #667eea;
        }
        .profile-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <!-- User Header -->
    <div class="user-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <div class="profile-avatar me-3">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h4 class="mb-0">Welcome, <?php echo htmlspecialchars($userInfo['first_name']); ?>!</h4>
                            <small class="opacity-75"><?php echo htmlspecialchars($userInfo['email']); ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="../index.php" class="btn btn-outline-light me-2">
                        <i class="fas fa-calendar me-1"></i> Book Classes
                    </a>
                    <a href="../logout.php" class="btn btn-light">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- User Navigation -->
    <div class="user-nav">
        <div class="container">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'profile' ? 'active' : ''; ?>" href="profile.php">
                        <i class="fas fa-user-edit me-1"></i> Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'health' ? 'active' : ''; ?>" href="health.php">
                        <i class="fas fa-heartbeat me-1"></i> Health Details
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'emergency' ? 'active' : ''; ?>" href="emergency.php">
                        <i class="fas fa-phone me-1"></i> Emergency Contacts
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'bookings' ? 'active' : ''; ?>" href="bookings.php">
                        <i class="fas fa-calendar-check me-1"></i> My Bookings
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="container">
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-heartbeat me-2"></i>Health Details</h4>
                        <p class="mb-0 text-muted">Manage your health questionnaire and medical information</p>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            
                            <!-- Health Questionnaire Section -->
                            <div class="mb-4">
                                <h5 class="border-bottom pb-2">Health Questionnaire</h5>
                                <p class="text-muted small">Please complete this health information to ensure your safety during classes.</p>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Do you have any medical conditions?</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="has_medical_conditions" id="medical_yes" value="yes" 
                                                       <?php echo ($healthData['has_medical_conditions'] ?? false) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="medical_yes">Yes</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="has_medical_conditions" id="medical_no" value="no" 
                                                       <?php echo (!($healthData['has_medical_conditions'] ?? false)) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="medical_no">No</label>
                                            </div>
                                        </div>
                                        <textarea class="form-control mt-2" name="medical_conditions" id="medical_conditions" 
                                                 placeholder="If yes, please describe..." rows="2" maxlength="500" 
                                                 style="display:<?php echo ($healthData['has_medical_conditions'] ?? false) ? 'block' : 'none'; ?>;"><?php echo htmlspecialchars($healthData['medical_conditions'] ?? ''); ?></textarea>
                                        <div class="form-text" style="display:<?php echo ($healthData['has_medical_conditions'] ?? false) ? 'block' : 'none'; ?>;" id="medical_conditions_help">Maximum 500 characters</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Do you take any medications?</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="takes_medication" id="medication_yes" value="yes" 
                                                       <?php echo ($healthData['takes_medication'] ?? false) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="medication_yes">Yes</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="takes_medication" id="medication_no" value="no" 
                                                       <?php echo (!($healthData['takes_medication'] ?? false)) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="medication_no">No</label>
                                            </div>
                                        </div>
                                        <textarea class="form-control mt-2" name="medication_details" id="medication_details" 
                                                 placeholder="If yes, please list medications..." rows="2" maxlength="500" 
                                                 style="display:<?php echo ($healthData['takes_medication'] ?? false) ? 'block' : 'none'; ?>;"><?php echo htmlspecialchars($healthData['medication_details'] ?? ''); ?></textarea>
                                        <div class="form-text" style="display:<?php echo ($healthData['takes_medication'] ?? false) ? 'block' : 'none'; ?>;" id="medication_details_help">Maximum 500 characters</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Do you have any injuries?</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="has_injuries" id="injuries_yes" value="yes" 
                                                       <?php echo ($healthData['has_injuries'] ?? false) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="injuries_yes">Yes</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="has_injuries" id="injuries_no" value="no" 
                                                       <?php echo (!($healthData['has_injuries'] ?? false)) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="injuries_no">No</label>
                                            </div>
                                        </div>
                                        <textarea class="form-control mt-2" name="injury_details" id="injury_details" 
                                                 placeholder="If yes, please describe..." rows="2" maxlength="500" 
                                                 style="display:<?php echo ($healthData['has_injuries'] ?? false) ? 'block' : 'none'; ?>;"><?php echo htmlspecialchars($healthData['injury_details'] ?? ''); ?></textarea>
                                        <div class="form-text" style="display:<?php echo ($healthData['has_injuries'] ?? false) ? 'block' : 'none'; ?>;" id="injury_details_help">Maximum 500 characters</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Do you have any allergies?</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="has_allergies" id="allergies_yes" value="yes" 
                                                       <?php echo ($healthData['has_allergies'] ?? false) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="allergies_yes">Yes</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="has_allergies" id="allergies_no" value="no" 
                                                       <?php echo (!($healthData['has_allergies'] ?? false)) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="allergies_no">No</label>
                                            </div>
                                        </div>
                                        <textarea class="form-control mt-2" name="allergy_details" id="allergy_details" 
                                                 placeholder="If yes, please describe..." rows="2" maxlength="500" 
                                                 style="display:<?php echo ($healthData['has_allergies'] ?? false) ? 'block' : 'none'; ?>;"><?php echo htmlspecialchars($healthData['allergy_details'] ?? ''); ?></textarea>
                                        <div class="form-text" style="display:<?php echo ($healthData['has_allergies'] ?? false) ? 'block' : 'none'; ?>;" id="allergy_details_help">Maximum 500 characters</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="fitness_level" class="form-label">Fitness Level</label>
                                        <select class="form-control" id="fitness_level" name="fitness_level">
                                            <option value="">Select your fitness level</option>
                                            <option value="beginner" <?php echo ($healthData['fitness_level'] ?? '') === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                                            <option value="intermediate" <?php echo ($healthData['fitness_level'] ?? '') === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                            <option value="advanced" <?php echo ($healthData['fitness_level'] ?? '') === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="exercise_limitations" class="form-label">Exercise Limitations</label>
                                        <textarea class="form-control" name="exercise_limitations" id="exercise_limitations" 
                                                 placeholder="Any exercise limitations or restrictions..." rows="2" maxlength="500"><?php echo htmlspecialchars($healthData['exercise_limitations'] ?? ''); ?></textarea>
                                        <div class="form-text">Maximum 500 characters</div>
                                    </div>
                                </div>
                                
                                <!-- Emergency Contact Information -->
                                <div class="border rounded p-3 bg-light mb-3">
                                    <h6>Emergency Contact Information</h6>
                                    <p class="small text-muted mb-3">Required if you have any medical conditions, take medications, have injuries, or allergies.</p>
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <label for="emergency_contact_name" class="form-label">Name</label>
                                            <input type="text" class="form-control" id="emergency_contact_name" 
                                                   name="emergency_contact_name" value="<?php echo htmlspecialchars($healthData['emergency_contact_name'] ?? ''); ?>" maxlength="100">
                                            <div class="form-text">Maximum 100 characters</div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label for="emergency_contact_phone" class="form-label">Phone</label>
                                            <input type="tel" class="form-control" id="emergency_contact_phone" 
                                                   name="emergency_contact_phone" value="<?php echo htmlspecialchars($healthData['emergency_contact_phone'] ?? ''); ?>" maxlength="20">
                                            <div class="form-text">Maximum 20 characters</div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label for="emergency_contact_relationship" class="form-label">Relationship</label>
                                            <input type="text" class="form-control" id="emergency_contact_relationship" 
                                                   name="emergency_contact_relationship" value="<?php echo htmlspecialchars($healthData['emergency_contact_relationship'] ?? ''); ?>" 
                                                   placeholder="e.g., Parent, Spouse, Friend" maxlength="50">
                                            <div class="form-text">Maximum 50 characters</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="consent_medical_emergency" 
                                           name="consent_medical_emergency" <?php echo ($healthData['consent_medical_emergency'] ?? false) ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="consent_medical_emergency">
                                        I consent to the staff contacting emergency services and my emergency contact 
                                        in case of a medical emergency during class participation. *
                                    </label>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Update Health Information
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-5 py-4 bg-light border-top">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">&copy; 2024 Class Booking System. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i> Your data is secure and protected
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Health questionnaire interaction
    document.addEventListener('DOMContentLoaded', function() {
        // Show/hide medical conditions textarea
        function toggleMedicalConditions() {
            const isYes = document.getElementById('medical_yes').checked;
            const textarea = document.getElementById('medical_conditions');
            const helpText = document.getElementById('medical_conditions_help');
            textarea.style.display = isYes ? 'block' : 'none';
            helpText.style.display = isYes ? 'block' : 'none';
            if (!isYes) textarea.value = '';
        }
        
        // Show/hide medication details textarea
        function toggleMedicationDetails() {
            const isYes = document.getElementById('medication_yes').checked;
            const textarea = document.getElementById('medication_details');
            const helpText = document.getElementById('medication_details_help');
            textarea.style.display = isYes ? 'block' : 'none';
            helpText.style.display = isYes ? 'block' : 'none';
            if (!isYes) textarea.value = '';
        }
        
        // Show/hide injury details textarea
        function toggleInjuryDetails() {
            const isYes = document.getElementById('injuries_yes').checked;
            const textarea = document.getElementById('injury_details');
            const helpText = document.getElementById('injury_details_help');
            textarea.style.display = isYes ? 'block' : 'none';
            helpText.style.display = isYes ? 'block' : 'none';
            if (!isYes) textarea.value = '';
        }
        
        // Show/hide allergy details textarea
        function toggleAllergyDetails() {
            const isYes = document.getElementById('allergies_yes').checked;
            const textarea = document.getElementById('allergy_details');
            const helpText = document.getElementById('allergy_details_help');
            textarea.style.display = isYes ? 'block' : 'none';
            helpText.style.display = isYes ? 'block' : 'none';
            if (!isYes) textarea.value = '';
        }
        
        // Add event listeners
        document.querySelectorAll('input[name="has_medical_conditions"]').forEach(radio => {
            radio.addEventListener('change', toggleMedicalConditions);
        });
        
        document.querySelectorAll('input[name="takes_medication"]').forEach(radio => {
            radio.addEventListener('change', toggleMedicationDetails);
        });
        
        document.querySelectorAll('input[name="has_injuries"]').forEach(radio => {
            radio.addEventListener('change', toggleInjuryDetails);
        });
        
        document.querySelectorAll('input[name="has_allergies"]').forEach(radio => {
            radio.addEventListener('change', toggleAllergyDetails);
        });
        
        // Initialize on page load
        toggleMedicalConditions();
        toggleMedicationDetails();
        toggleInjuryDetails();
        toggleAllergyDetails();
    });
    </script>
</body>
</html>
