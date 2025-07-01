<?php
if (file_exists(__DIR__ . '/../config/user_auth.php')) {
    include __DIR__ . '/../config/user_auth.php';
} else {
    error_log('user_auth.php not found');
    die('Authentication system not available');
}

// Include file upload helper
if (file_exists(__DIR__ . '/../config/file_upload_helper.php')) {
    include __DIR__ . '/../config/file_upload_helper.php';
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

// Handle registration form submission
$error_html = '';
$success_html = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error_html = '<div class="alert alert-danger">Security token validation failed. Please try again.</div>';
    } else {
        $firstName = trim(isset($_POST['first_name']) ? $_POST['first_name'] : '');
        $lastName = trim(isset($_POST['last_name']) ? $_POST['last_name'] : '');
        $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');
        $dateOfBirth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : '';
        $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
        
        // Build health questionnaire array
        $healthQuestionnaire = [
            'has_medical_conditions' => isset($_POST['has_medical_conditions']) && $_POST['has_medical_conditions'] === 'yes',
            'medical_conditions' => trim(isset($_POST['medical_conditions']) ? $_POST['medical_conditions'] : ''),
            'takes_medication' => isset($_POST['takes_medication']) && $_POST['takes_medication'] === 'yes',
            'medication_details' => trim(isset($_POST['medication_details']) ? $_POST['medication_details'] : ''),
            'has_injuries' => isset($_POST['has_injuries']) && $_POST['has_injuries'] === 'yes',
            'injury_details' => trim(isset($_POST['injury_details']) ? $_POST['injury_details'] : ''),
            'emergency_contact_name' => trim(isset($_POST['emergency_contact_name']) ? $_POST['emergency_contact_name'] : ''),
            'emergency_contact_phone' => trim(isset($_POST['emergency_contact_phone']) ? $_POST['emergency_contact_phone'] : ''),
            'emergency_contact_relationship' => trim(isset($_POST['emergency_contact_relationship']) ? $_POST['emergency_contact_relationship'] : ''),
            'fitness_level' => isset($_POST['fitness_level']) ? $_POST['fitness_level'] : '',
            'exercise_limitations' => trim(isset($_POST['exercise_limitations']) ? $_POST['exercise_limitations'] : ''),
            'has_allergies' => isset($_POST['has_allergies']) && $_POST['has_allergies'] === 'yes',
            'allergy_details' => trim(isset($_POST['allergy_details']) ? $_POST['allergy_details'] : ''),
            'consent_medical_emergency' => isset($_POST['consent_medical_emergency']) && $_POST['consent_medical_emergency'] === 'on'
        ];
        
        // Input length validation
        if (strlen($firstName) > 50) {
            $error_html = '<div class="alert alert-danger">First name must be 50 characters or less.</div>';
        } elseif (strlen($lastName) > 50) {
            $error_html = '<div class="alert alert-danger">Last name must be 50 characters or less.</div>';
        } elseif (strlen($email) > 100) {
            $error_html = '<div class="alert alert-danger">Email address must be 100 characters or less.</div>';
        } elseif (strlen($password) > 255) {
            $error_html = '<div class="alert alert-danger">Password is too long.</div>';
        } elseif (strlen($phone) > 20) {
            $error_html = '<div class="alert alert-danger">Phone number must be 20 characters or less.</div>';
        } elseif (strlen($healthQuestionnaire['medical_conditions']) > 500) {
            $error_html = '<div class="alert alert-danger">Medical conditions description must be 500 characters or less.</div>';
        } elseif (strlen($healthQuestionnaire['medication_details']) > 500) {
            $error_html = '<div class="alert alert-danger">Medication details must be 500 characters or less.</div>';
        } elseif (strlen($healthQuestionnaire['injury_details']) > 500) {
            $error_html = '<div class="alert alert-danger">Injury details must be 500 characters or less.</div>';
        } elseif (strlen($healthQuestionnaire['emergency_contact_name']) > 100) {
            $error_html = '<div class="alert alert-danger">Emergency contact name must be 100 characters or less.</div>';
        } elseif (strlen($healthQuestionnaire['emergency_contact_phone']) > 20) {
            $error_html = '<div class="alert alert-danger">Emergency contact phone must be 20 characters or less.</div>';
        } elseif (strlen($healthQuestionnaire['emergency_contact_relationship']) > 50) {
            $error_html = '<div class="alert alert-danger">Emergency contact relationship must be 50 characters or less.</div>';
        } elseif (strlen($healthQuestionnaire['exercise_limitations']) > 500) {
            $error_html = '<div class="alert alert-danger">Exercise limitations must be 500 characters or less.</div>';
        } elseif (strlen($healthQuestionnaire['allergy_details']) > 500) {
            $error_html = '<div class="alert alert-danger">Allergy details must be 500 characters or less.</div>';
        } elseif (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
            $error_html = '<div class="alert alert-danger">Please fill in all required fields.</div>';
        } elseif (!validateEmail($email)) {
            $error_html = '<div class="alert alert-danger">Please enter a valid email address.</div>';
        } elseif ($password !== $confirmPassword) {
            $error_html = '<div class="alert alert-danger">Passwords do not match.</div>';
        } elseif (!validatePassword($password)) {
            $error_html = '<div class="alert alert-danger">Password must be at least 8 characters and contain both letters and numbers.</div>';
        } elseif (!validateDateOfBirth($dateOfBirth)) {
            $error_html = '<div class="alert alert-danger">Please enter a valid date of birth. You must be at least 13 years old.</div>';
        } elseif (!validateGender($gender)) {
            $error_html = '<div class="alert alert-danger">Please select a valid gender option.</div>';
        } elseif (!validateHealthQuestionnaire($healthQuestionnaire)) {
            $error_html = '<div class="alert alert-danger">Please complete the health questionnaire and provide emergency contact information if you have any medical conditions.</div>';
        } else {
            try {
                $userId = registerUser($firstName, $lastName, $email, $password, $phone, $dateOfBirth, $gender, $healthQuestionnaire);
                
                // Handle profile photo upload if provided
                if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                    try {
                        $photoFilename = uploadProfilePhoto($_FILES['profile_photo'], 'user', $userId);
                        if ($photoFilename) {
                            // Update user with profile photo filename
                            require_once __DIR__ . '/api/db.php';
                            $stmt = $pdo->prepare('UPDATE users SET profile_photo = ? WHERE id = ?');
                            $stmt->execute([$photoFilename, $userId]);
                        }
                    } catch (Exception $photoError) {
                        // Log photo upload error but don't fail registration
                        error_log('Profile photo upload error during registration: ' . $photoError->getMessage());
                    }
                }
                
                // Auto-login after registration
                loginUser($email, $password);
                
                // Redirect to user dashboard or intended destination
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'user/dashboard.php';
                header("Location: $redirect");
                exit();
                
            } catch (Exception $e) {
                error_log('User registration error: ' . $e->getMessage());
                $error_html = '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Set up page configuration
setupPageConfig([
    'pageTitle' => 'Register - Class Booking System',
    'navItems' => getPublicNavigation('register'),
    'footerLinks' => getPublicFooterLinks(),
    'bodyClass' => 'register-page',
    'additionalCSS' => ['https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css']
]);

// Set form values for display
$firstNameValue = isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : '';
$lastNameValue = isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : '';
$emailValue = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '';
$phoneValue = isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '';
$dobValue = isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : '';
$genderValue = isset($_POST['gender']) ? htmlspecialchars($_POST['gender']) : '';
$medicalConditionsValue = isset($_POST['medical_conditions']) ? htmlspecialchars($_POST['medical_conditions']) : '';
$medicationDetailsValue = isset($_POST['medication_details']) ? htmlspecialchars($_POST['medication_details']) : '';
$injuryDetailsValue = isset($_POST['injury_details']) ? htmlspecialchars($_POST['injury_details']) : '';
$emergencyNameValue = isset($_POST['emergency_contact_name']) ? htmlspecialchars($_POST['emergency_contact_name']) : '';
$emergencyPhoneValue = isset($_POST['emergency_contact_phone']) ? htmlspecialchars($_POST['emergency_contact_phone']) : '';
$emergencyRelationshipValue = isset($_POST['emergency_contact_relationship']) ? htmlspecialchars($_POST['emergency_contact_relationship']) : '';
$fitnessLevelValue = isset($_POST['fitness_level']) ? htmlspecialchars($_POST['fitness_level']) : '';
$exerciseLimitationsValue = isset($_POST['exercise_limitations']) ? htmlspecialchars($_POST['exercise_limitations']) : '';
$allergyDetailsValue = isset($_POST['allergy_details']) ? htmlspecialchars($_POST['allergy_details']) : '';

// Set checked states for radio buttons and select options
$maleSelected = ($genderValue === 'male') ? 'selected' : '';
$femaleSelected = ($genderValue === 'female') ? 'selected' : '';
$otherSelected = ($genderValue === 'other') ? 'selected' : '';
$preferNotSelected = ($genderValue === 'prefer_not_to_say') ? 'selected' : '';

$medicalYesChecked = (isset($_POST['has_medical_conditions']) && $_POST['has_medical_conditions'] === 'yes') ? 'checked' : '';
$medicalNoChecked = (isset($_POST['has_medical_conditions']) && $_POST['has_medical_conditions'] === 'no') ? 'checked' : '';
$medicationYesChecked = (isset($_POST['takes_medication']) && $_POST['takes_medication'] === 'yes') ? 'checked' : '';
$medicationNoChecked = (isset($_POST['takes_medication']) && $_POST['takes_medication'] === 'no') ? 'checked' : '';
$injuriesYesChecked = (isset($_POST['has_injuries']) && $_POST['has_injuries'] === 'yes') ? 'checked' : '';
$injuriesNoChecked = (isset($_POST['has_injuries']) && $_POST['has_injuries'] === 'no') ? 'checked' : '';
$allergiesYesChecked = (isset($_POST['has_allergies']) && $_POST['has_allergies'] === 'yes') ? 'checked' : '';
$allergiesNoChecked = (isset($_POST['has_allergies']) && $_POST['has_allergies'] === 'no') ? 'checked' : '';

$beginnerSelected = ($fitnessLevelValue === 'beginner') ? 'selected' : '';
$intermediateSelected = ($fitnessLevelValue === 'intermediate') ? 'selected' : '';
$advancedSelected = ($fitnessLevelValue === 'advanced') ? 'selected' : '';

$consentChecked = (isset($_POST['consent_medical_emergency'])) ? 'checked' : '';

$content = <<<HTML
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header text-center bg-success text-white">
                <h4><i class="fas fa-user-plus me-2"></i>Create Account</h4>
            </div>
            <div class="card-body">
                {$error_html}
                {$success_html}
                
                <form method="POST" action="register.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="{$csrfToken}">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="{$firstNameValue}" maxlength="50" required>
                            <div class="form-text">Maximum 50 characters</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="{$lastNameValue}" maxlength="50" required>
                            <div class="form-text">Maximum 50 characters</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="{$emailValue}" maxlength="100" required>
                        <div class="form-text">Maximum 100 characters</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="{$phoneValue}" maxlength="20" placeholder="Optional">
                        <div class="form-text">Maximum 20 characters</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                   value="{$dobValue}">
                            <div class="form-text">Must be at least 13 years old</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-control" id="gender" name="gender">
                                <option value="">Select Gender (Optional)</option>
                                <option value="male" {$maleSelected}>Male</option>
                                <option value="female" {$femaleSelected}>Female</option>
                                <option value="other" {$otherSelected}>Other</option>
                                <option value="prefer_not_to_say" {$preferNotSelected}>Prefer not to say</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Profile Photo Section -->
                    <div class="mb-4">
                        <label for="profile_photo" class="form-label">
                            <i class="fas fa-camera me-2"></i>Profile Photo (Optional)
                        </label>
                        <input type="file" class="form-control" id="profile_photo" name="profile_photo" 
                               accept="image/jpeg,image/png,image/gif">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Upload a profile photo (JPEG, PNG, or GIF). Maximum size: 5MB. 
                            Photo will be automatically resized to 300x300 pixels.
                        </div>
                        <div id="photo_preview" class="mt-2" style="display: none;">
                            <img id="preview_image" src="" alt="Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                        </div>
                    </div>
                    
                    <!-- Health Questionnaire Section -->
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2">Health Questionnaire</h5>
                        <p class="text-muted small">Please complete this health information to ensure your safety during classes.</p>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Do you have any medical conditions?</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="has_medical_conditions" id="medical_yes" value="yes" {$medicalYesChecked}>
                                        <label class="form-check-label" for="medical_yes">Yes</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="has_medical_conditions" id="medical_no" value="no" {$medicalNoChecked}>
                                        <label class="form-check-label" for="medical_no">No</label>
                                    </div>
                                </div>
                                <textarea class="form-control mt-2" name="medical_conditions" id="medical_conditions" 
                                         placeholder="If yes, please describe..." rows="2" maxlength="500" style="display:none;">{$medicalConditionsValue}</textarea>
                                <div class="form-text" style="display:none;" id="medical_conditions_help">Maximum 500 characters</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Do you take any medications?</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="takes_medication" id="medication_yes" value="yes" {$medicationYesChecked}>
                                        <label class="form-check-label" for="medication_yes">Yes</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="takes_medication" id="medication_no" value="no" {$medicationNoChecked}>
                                        <label class="form-check-label" for="medication_no">No</label>
                                    </div>
                                </div>
                                <textarea class="form-control mt-2" name="medication_details" id="medication_details" 
                                         placeholder="If yes, please list medications..." rows="2" maxlength="500" style="display:none;">{$medicationDetailsValue}</textarea>
                                <div class="form-text" style="display:none;" id="medication_details_help">Maximum 500 characters</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Do you have any injuries?</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="has_injuries" id="injuries_yes" value="yes" {$injuriesYesChecked}>
                                        <label class="form-check-label" for="injuries_yes">Yes</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="has_injuries" id="injuries_no" value="no" {$injuriesNoChecked}>
                                        <label class="form-check-label" for="injuries_no">No</label>
                                    </div>
                                </div>
                                <textarea class="form-control mt-2" name="injury_details" id="injury_details" 
                                         placeholder="If yes, please describe..." rows="2" maxlength="500" style="display:none;">{$injuryDetailsValue}</textarea>
                                <div class="form-text" style="display:none;" id="injury_details_help">Maximum 500 characters</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Do you have any allergies?</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="has_allergies" id="allergies_yes" value="yes" {$allergiesYesChecked}>
                                        <label class="form-check-label" for="allergies_yes">Yes</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="has_allergies" id="allergies_no" value="no" {$allergiesNoChecked}>
                                        <label class="form-check-label" for="allergies_no">No</label>
                                    </div>
                                </div>
                                <textarea class="form-control mt-2" name="allergy_details" id="allergy_details" 
                                         placeholder="If yes, please describe..." rows="2" maxlength="500" style="display:none;">{$allergyDetailsValue}</textarea>
                                <div class="form-text" style="display:none;" id="allergy_details_help">Maximum 500 characters</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="fitness_level" class="form-label">Fitness Level</label>
                            <select class="form-control" id="fitness_level" name="fitness_level">
                                <option value="">Select Fitness Level (Optional)</option>
                                <option value="beginner" {$beginnerSelected}>Beginner</option>
                                <option value="intermediate" {$intermediateSelected}>Intermediate</option>
                                <option value="advanced" {$advancedSelected}>Advanced</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="exercise_limitations" class="form-label">Exercise Limitations</label>
                            <textarea class="form-control" name="exercise_limitations" id="exercise_limitations" 
                                     placeholder="Any physical limitations or restrictions we should know about..." rows="2" maxlength="500">{$exerciseLimitationsValue}</textarea>
                            <div class="form-text">Maximum 500 characters</div>
                        </div>
                        
                        <div class="border rounded p-3 bg-light mb-3">
                            <h6>Emergency Contact Information</h6>
                            <p class="small text-muted mb-3">Required if you have any medical conditions, take medications, have injuries, or allergies.</p>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label for="emergency_contact_name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="emergency_contact_name" 
                                           name="emergency_contact_name" value="{$emergencyNameValue}" maxlength="100">
                                    <div class="form-text">Maximum 100 characters</div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label for="emergency_contact_phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="emergency_contact_phone" 
                                           name="emergency_contact_phone" value="{$emergencyPhoneValue}" maxlength="20">
                                    <div class="form-text">Maximum 20 characters</div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label for="emergency_contact_relationship" class="form-label">Relationship</label>
                                    <input type="text" class="form-control" id="emergency_contact_relationship" 
                                           name="emergency_contact_relationship" value="{$emergencyRelationshipValue}" 
                                           placeholder="e.g., Parent, Spouse, Friend" maxlength="50">
                                    <div class="form-text">Maximum 50 characters</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="consent_medical_emergency" 
                                   name="consent_medical_emergency" {$consentChecked} required>
                            <label class="form-check-label" for="consent_medical_emergency">
                                I consent to the staff contacting emergency services and my emergency contact 
                                in case of a medical emergency during class participation. *
                            </label>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" maxlength="255" required>
                            <div class="form-text">At least 8 characters with letters and numbers</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" maxlength="255" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> and 
                                <a href="#" class="text-decoration-none">Privacy Policy</a>
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                    </div>
                </form>
                
                <hr>
                
                <div class="text-center">
                    <p class="mb-0">Already have an account?</p>
                    <a href="login.php" class="btn btn-outline-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
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
    
    // Profile photo preview
    function setupProfilePhotoPreview() {
        const photoInput = document.getElementById('profile_photo');
        const previewDiv = document.getElementById('photo_preview');
        const previewImage = document.getElementById('preview_image');
        
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPEG, PNG, or GIF).');
                    this.value = '';
                    previewDiv.style.display = 'none';
                    return;
                }
                
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB.');
                    this.value = '';
                    previewDiv.style.display = 'none';
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewDiv.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                previewDiv.style.display = 'none';
            }
        });
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
    setupProfilePhotoPreview();
});
</script>

HTML;

if (file_exists(__DIR__ . '/../templates/base.php')) {
    include __DIR__ . '/../templates/base.php';
} else {
    error_log('Template base.php not found');
    die('Template base not found');
}
?> 