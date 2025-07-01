<?php
require_once 'includes/admin_common.php';

// Start session for CSRF token (if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    // Verify CSRF token for all form submissions
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = ErrorMessages::CSRF_INVALID;
    } else {
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $date = $_POST['date'] ?? '';
            $time = $_POST['time'] ?? '';
            $capacity = intval($_POST['capacity'] ?? 0);
            $instructor_id = !empty($_POST['instructor_id']) ? intval($_POST['instructor_id']) : null;
            $recurring = isset($_POST['recurring']) ? 1 : 0;
            
            // New fields
            $days_of_week = isset($_POST['days_of_week']) ? json_encode($_POST['days_of_week']) : null;
            $multiple_times = isset($_POST['multiple_times']) && !empty($_POST['multiple_times'][0]) ? 
                json_encode(array_filter($_POST['multiple_times'])) : null;
            $age_min = !empty($_POST['age_min']) ? intval($_POST['age_min']) : null;
            $age_max = !empty($_POST['age_max']) ? intval($_POST['age_max']) : null;
            $gender_restriction = $_POST['gender_restriction'] ?? 'mixed';
            $prerequisites = trim($_POST['prerequisites'] ?? '');
            $difficulty_level = $_POST['difficulty_level'] ?? 'all_levels';
            $duration_minutes = intval($_POST['duration_minutes'] ?? 60);
            $trial_eligible = isset($_POST['trial_eligible']) ? 1 : 0;
            
            // Input validation
            if (strlen($name) > 100) {
                $error = ErrorMessages::NAME_TOO_LONG;
            } elseif (strlen($description) > 500) {
                $error = ErrorMessages::DESCRIPTION_TOO_LONG;
            } elseif (!$name || !$time || $capacity <= 0) {
                $error = ErrorMessages::REQUIRED_FIELDS;
            } elseif ($date && !validateDate($date)) {
                $error = ErrorMessages::INVALID_DATE;
            } elseif (!validateTime($time)) {
                $error = ErrorMessages::INVALID_TIME;
            } elseif ($capacity <= 0) {
                $error = ErrorMessages::INVALID_CAPACITY;
            } elseif ($age_min && $age_max && $age_min > $age_max) {
                $error = 'Minimum age cannot be greater than maximum age';
            } else {
                try {
                    // Day-specific times for recurring classes
                    $day_specific_times = null;
                    if ($recurring && isset($_POST['day_times'])) {
                        $dayTimes = [];
                        foreach ($_POST['day_times'] as $day => $dayTime) {
                            if (!empty($dayTime) && isset($_POST['days_of_week']) && in_array($day, $_POST['days_of_week'])) {
                                $dayTimes[$day] = $dayTime;
                            }
                        }
                        if (!empty($dayTimes)) {
                            $day_specific_times = json_encode($dayTimes);
                        }
                    }
                    
                    $stmt = $pdo->prepare('INSERT INTO classes (name, description, date, time, capacity, instructor_id, recurring, days_of_week, multiple_times, day_specific_times, age_min, age_max, gender_restriction, prerequisites, difficulty_level, duration_minutes, trial_eligible) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$name, $description, $date, $time, $capacity, $instructor_id, $recurring, $days_of_week, $multiple_times, $day_specific_times, $age_min, $age_max, $gender_restriction, $prerequisites, $difficulty_level, $duration_minutes, $trial_eligible]);
                    $message = 'Class created successfully';
                    
                    // Clear any frontend caches by adding cache-busting parameter
                    header('Location: classes.php?success=' . urlencode($message) . '&v=' . time());
                    exit();
                } catch (Exception $e) {
                    error_log('Failed to create class: ' . $e->getMessage());
                    $error = ErrorMessages::OPERATION_FAILED;
                }
            }
        } elseif ($action === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $date = $_POST['date'] ?? '';
            $time = $_POST['time'] ?? '';
            $capacity = intval($_POST['capacity'] ?? 0);
            $instructor_id = !empty($_POST['instructor_id']) ? intval($_POST['instructor_id']) : null;
            $recurring = isset($_POST['recurring']) ? 1 : 0;
            
            // New fields
            $days_of_week = isset($_POST['days_of_week']) ? json_encode($_POST['days_of_week']) : null;
            $multiple_times = isset($_POST['multiple_times']) && !empty($_POST['multiple_times'][0]) ? 
                json_encode(array_filter($_POST['multiple_times'])) : null;
            $age_min = !empty($_POST['age_min']) ? intval($_POST['age_min']) : null;
            $age_max = !empty($_POST['age_max']) ? intval($_POST['age_max']) : null;
            $gender_restriction = $_POST['gender_restriction'] ?? 'mixed';
            $prerequisites = trim($_POST['prerequisites'] ?? '');
            $difficulty_level = $_POST['difficulty_level'] ?? 'all_levels';
            $duration_minutes = intval($_POST['duration_minutes'] ?? 60);
            $trial_eligible = isset($_POST['trial_eligible']) ? 1 : 0;
            
            // Input validation
            if (strlen($name) > 100) {
                $error = ErrorMessages::NAME_TOO_LONG;
            } elseif (strlen($description) > 500) {
                $error = ErrorMessages::DESCRIPTION_TOO_LONG;
            } elseif ($id <= 0 || !$name || !$time || $capacity <= 0) {
                $error = ErrorMessages::INVALID_REQUEST;
            } elseif ($date && !validateDate($date)) {
                $error = ErrorMessages::INVALID_DATE;
            } elseif (!validateTime($time)) {
                $error = ErrorMessages::INVALID_TIME;
            } elseif ($capacity <= 0) {
                $error = ErrorMessages::INVALID_CAPACITY;
            } elseif ($age_min && $age_max && $age_min > $age_max) {
                $error = 'Minimum age cannot be greater than maximum age';
            } else {
                try {
                    // Day-specific times for recurring classes
                    $day_specific_times = null;
                    if ($recurring && isset($_POST['day_times'])) {
                        $dayTimes = [];
                        foreach ($_POST['day_times'] as $day => $dayTime) {
                            if (!empty($dayTime) && isset($_POST['days_of_week']) && in_array($day, $_POST['days_of_week'])) {
                                $dayTimes[$day] = $dayTime;
                            }
                        }
                        if (!empty($dayTimes)) {
                            $day_specific_times = json_encode($dayTimes);
                        }
                    }
                    
                    $stmt = $pdo->prepare('UPDATE classes SET name = ?, description = ?, date = ?, time = ?, capacity = ?, instructor_id = ?, recurring = ?, days_of_week = ?, multiple_times = ?, day_specific_times = ?, age_min = ?, age_max = ?, gender_restriction = ?, prerequisites = ?, difficulty_level = ?, duration_minutes = ?, trial_eligible = ? WHERE id = ?');
                    $stmt->execute([$name, $description, $date, $time, $capacity, $instructor_id, $recurring, $days_of_week, $multiple_times, $day_specific_times, $age_min, $age_max, $gender_restriction, $prerequisites, $difficulty_level, $duration_minutes, $trial_eligible, $id]);
                    $message = 'Class updated successfully';
                    
                    // Clear any frontend caches by adding cache-busting parameter
                    header('Location: classes.php?success=' . urlencode($message) . '&v=' . time());
                    exit();
                } catch (Exception $e) {
                    error_log('Failed to update class: ' . $e->getMessage());
                    $error = ErrorMessages::OPERATION_FAILED;
                }
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    // Delete bookings first
                    $stmt = $pdo->prepare('DELETE FROM bookings WHERE class_id = ?');
                    $stmt->execute([$id]);
                    // Delete class
                    $stmt = $pdo->prepare('DELETE FROM classes WHERE id = ?');
                    $stmt->execute([$id]);
                    $message = 'Class deleted successfully';
                    
                    // Clear any frontend caches by adding cache-busting parameter  
                    header('Location: classes.php?success=' . urlencode($message) . '&v=' . time());
                    exit();
                } catch (Exception $e) {
                    error_log('Failed to delete class: ' . $e->getMessage());
                    $error = ErrorMessages::OPERATION_FAILED;
                }
            }
        }
    }
}

// Handle success message from redirect
if (isset($_GET['success'])) {
    $message = $_GET['success'];
}

// Get all instructors for dropdown
try {
    $stmt = $pdo->query('SELECT id, first_name, last_name FROM instructors WHERE status = "active" ORDER BY last_name, first_name');
    $instructors = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Failed to load instructors: ' . $e->getMessage());
    $instructors = [];
}

// Get all classes with instructor info
try {
    $stmt = $pdo->query('
        SELECT 
            c.*,
            CONCAT(i.first_name, " ", i.last_name) as instructor_name
        FROM classes c 
        LEFT JOIN instructors i ON c.instructor_id = i.id 
        ORDER BY c.date, c.time
    ');
    $classes = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Failed to load classes: ' . $e->getMessage());
    $classes = [];
    $error = ErrorMessages::OPERATION_FAILED;
}

// Get class for editing
$editClass = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    try {
        $stmt = $pdo->prepare('SELECT * FROM classes WHERE id = ?');
        $stmt->execute([$editId]);
        $editClass = $stmt->fetch();
    } catch (Exception $e) {
        error_log('Failed to load class for editing: ' . $e->getMessage());
        $error = ErrorMessages::CLASS_NOT_FOUND;
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Build instructor dropdown options
$instructor_options = '<option value="">No instructor assigned</option>';
foreach ($instructors as $instructor) {
    $selected = ($editClass && $editClass['instructor_id'] == $instructor['id']) ? ' selected' : '';
    $instructor_options .= '<option value="' . $instructor['id'] . '"' . $selected . '>' . 
                          htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']) . 
                          '</option>';
}

// Prepare form variables
if ($editClass) {
    $form_title = 'Edit Class';
    $form_action = 'edit';
    $edit_id_field = '<input type="hidden" name="id" value="' . $editClass['id'] . '">';
    $edit_name = htmlspecialchars($editClass['name']);
    $edit_description = htmlspecialchars($editClass['description']);
    $edit_date = $editClass['date'];
    $edit_time = $editClass['time'];
    $edit_capacity = $editClass['capacity'];
    $edit_instructor_id = $editClass['instructor_id'] ?? '';
    $edit_recurring = ($editClass['recurring'] ?? 0) ? 'checked' : '';
    $edit_days_of_week = json_decode($editClass['days_of_week'] ?? '[]', true);
    $edit_multiple_times = json_decode($editClass['multiple_times'] ?? '[]', true);
    $edit_day_specific_times = json_decode($editClass['day_specific_times'] ?? '[]', true);
    $edit_age_min = $editClass['age_min'] ?? '';
    $edit_age_max = $editClass['age_max'] ?? '';
    $edit_gender_restriction = $editClass['gender_restriction'] ?? 'mixed';
    $edit_prerequisites = htmlspecialchars($editClass['prerequisites'] ?? '');
    $edit_difficulty_level = $editClass['difficulty_level'] ?? 'all_levels';
    $edit_duration_minutes = $editClass['duration_minutes'] ?? 60;
    $edit_trial_eligible = ($editClass['trial_eligible'] ?? 1) ? 'checked' : '';
    $button_text = 'Update Class';
    $cancel_button = '<a href="classes.php" class="btn btn-secondary ms-2">Cancel</a>';
} else {
    $form_title = 'Create New Class';
    $form_action = 'create';
    $edit_id_field = '';
    $edit_name = '';
    $edit_description = '';
    $edit_date = '';
    $edit_time = '';
    $edit_capacity = '';
    $edit_instructor_id = '';
    $edit_recurring = '';
    $edit_days_of_week = [];
    $edit_multiple_times = [];
    $edit_day_specific_times = [];
    $edit_age_min = '';
    $edit_age_max = '';
    $edit_gender_restriction = 'mixed';
    $edit_prerequisites = '';
    $edit_difficulty_level = 'all_levels';
    $edit_duration_minutes = 60;
    $edit_trial_eligible = 'checked'; // Default to trial eligible
    $button_text = 'Create Class';
    $cancel_button = '';
}

// Prepare classes list
$classes_list = '';
if (empty($classes)) {
    $classes_list = '<p class="text-muted">No classes found.</p>';
} else {
    foreach ($classes as $class) {
        $classes_list .= '<div class="border rounded p-3 mb-3">';
        $classes_list .= '<h6>' . htmlspecialchars($class['name']) . '</h6>';
        $classes_list .= '<p class="small text-muted mb-2">' . htmlspecialchars($class['description']) . '</p>';
        $classes_list .= '<p class="small mb-2"><strong>Date:</strong> ' . $class['date'] . ' <strong>Time:</strong> ' . $class['time'] . '</p>';
        $classes_list .= '<p class="small mb-2"><strong>Capacity:</strong> ' . $class['capacity'] . '</p>';
        if ($class['instructor_name']) {
            $classes_list .= '<p class="small mb-2"><strong>Instructor:</strong> ' . htmlspecialchars($class['instructor_name']) . '</p>';
        } else {
            $classes_list .= '<p class="small mb-2 text-muted"><strong>Instructor:</strong> Not assigned</p>';
        }
        if ($class['recurring'] ?? 0) {
            $classes_list .= '<p class="small mb-2"><span class="badge bg-info">Recurring Weekly</span></p>';
        }
        $classes_list .= '<div class="btn-group btn-group-sm">';
        $classes_list .= '<a href="classes.php?edit=' . $class['id'] . '" class="btn btn-outline-primary">Edit</a>';
        $classes_list .= '<button class="btn btn-outline-danger" onclick="deleteClass(' . $class['id'] . ')">Delete</button>';
        $classes_list .= '</div>';
        $classes_list .= '</div>';
    }
}

// Include the header first
require_once 'templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-dumbbell"></i> Class Management</h1>
                <div>
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Enhanced Class Form -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-plus-circle"></i> <?= $form_title ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="classForm">
                        <?= $edit_id_field ?>
                        <input type="hidden" name="action" value="<?= $form_action ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        
                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3"><i class="fas fa-info-circle"></i> Basic Information</h6>
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label">Class Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?= $edit_name ?>" required maxlength="100" 
                                           placeholder="e.g., Morning Yoga">
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" 
                                              rows="3" maxlength="500" 
                                              placeholder="Brief description of the class..."><?= $edit_description ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="capacity" class="form-label">Capacity *</label>
                                            <input type="number" class="form-control" id="capacity" name="capacity" 
                                                   value="<?= $edit_capacity ?>" required min="1" max="100">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="duration_minutes" class="form-label">Duration (minutes)</label>
                                            <select class="form-select" id="duration_minutes" name="duration_minutes">
                                                <option value="30" <?= $edit_duration_minutes == 30 ? 'selected' : '' ?>>30 minutes</option>
                                                <option value="45" <?= $edit_duration_minutes == 45 ? 'selected' : '' ?>>45 minutes</option>
                                                <option value="60" <?= $edit_duration_minutes == 60 ? 'selected' : '' ?>>60 minutes</option>
                                                <option value="90" <?= $edit_duration_minutes == 90 ? 'selected' : '' ?>>90 minutes</option>
                                                <option value="120" <?= $edit_duration_minutes == 120 ? 'selected' : '' ?>>2 hours</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="instructor_id" class="form-label">Instructor</label>
                                    <select class="form-select" id="instructor_id" name="instructor_id">
                                        <?= $instructor_options ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="difficulty_level" class="form-label">Difficulty Level</label>
                                    <select class="form-select" id="difficulty_level" name="difficulty_level">
                                        <option value="all_levels" <?= $edit_difficulty_level == 'all_levels' ? 'selected' : '' ?>>All Levels</option>
                                        <option value="beginner" <?= $edit_difficulty_level == 'beginner' ? 'selected' : '' ?>>Beginner</option>
                                        <option value="intermediate" <?= $edit_difficulty_level == 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                                        <option value="advanced" <?= $edit_difficulty_level == 'advanced' ? 'selected' : '' ?>>Advanced</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Scheduling & Restrictions -->
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3"><i class="fas fa-calendar-alt"></i> Scheduling</h6>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="recurring" name="recurring" 
                                               <?= $edit_recurring ?> onchange="toggleSchedulingOptions()">
                                        <label class="form-check-label" for="recurring">
                                            Recurring Class
                                        </label>
                                    </div>
                                </div>

                                <!-- Single Date/Time (for non-recurring) -->
                                <div id="singleDateTime" class="mb-3">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="date" class="form-label">Date</label>
                                            <input type="date" class="form-control" id="date" name="date" 
                                                   value="<?= $edit_date ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="time" class="form-label">Time *</label>
                                            <input type="time" class="form-control" id="time" name="time" 
                                                   value="<?= $edit_time ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Days of Week (for recurring) -->
                                <div id="recurringOptions" class="mb-3" style="display: none;">
                                    <label class="form-label">Days of Week</label>
                                    <div class="row">
                                        <?php 
                                        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                        $dayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                                        foreach ($days as $i => $day): 
                                            $checked = in_array($day, $edit_days_of_week) ? 'checked' : '';
                                        ?>
                                        <div class="col-md-3 col-sm-4 col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="day_<?= $day ?>" name="days_of_week[]" 
                                                       value="<?= $day ?>" <?= $checked ?>>
                                                <label class="form-check-label" for="day_<?= $day ?>">
                                                    <?= $dayLabels[$i] ?>
                                                </label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Day-Specific Times -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-clock"></i> Day-Specific Times 
                                        <small class="text-muted">(Leave blank to use the time above for all days)</small>
                                    </label>
                                    <div class="row" id="daySpecificTimesContainer">
                                        <?php 
                                        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                        $dayLabels = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                        foreach ($days as $i => $day): 
                                            $dayTime = $edit_day_specific_times[$day] ?? '';
                                        ?>
                                        <div class="col-md-6 col-lg-4 mb-2">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text" style="min-width: 80px;"><?= $dayLabels[$i] ?></span>
                                                <input type="time" class="form-control" name="day_times[<?= $day ?>]" 
                                                       value="<?= $dayTime ?>" placeholder="Use default">
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle"></i> 
                                        <strong>Example:</strong> Senior class Tuesday at 8:00 PM, Thursday at 6:00 PM, Friday at 10:00 AM - just set different times for each day above!
                                    </div>
                                </div>
                                
                                <!-- Legacy Multiple Times (kept for backward compatibility) -->
                                <div class="mb-3" style="display: none;">
                                    <label class="form-label">Additional Times (Legacy - Hidden)</label>
                                    <div id="multipleTimesContainer">
                                        <?php if (!empty($edit_multiple_times)): ?>
                                            <?php foreach ($edit_multiple_times as $time): ?>
                                            <div class="input-group mb-2">
                                                <input type="time" class="form-control" name="multiple_times[]" value="<?= $time ?>">
                                                <button type="button" class="btn btn-outline-danger" onclick="removeTimeSlot(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <h6 class="text-primary mb-3 mt-4"><i class="fas fa-user-shield"></i> Restrictions</h6>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="age_min" class="form-label">Min Age</label>
                                            <input type="number" class="form-control" id="age_min" name="age_min" 
                                                   value="<?= $edit_age_min ?>" min="1" max="100" 
                                                   placeholder="e.g., 18">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="age_max" class="form-label">Max Age</label>
                                            <input type="number" class="form-control" id="age_max" name="age_max" 
                                                   value="<?= $edit_age_max ?>" min="1" max="100" 
                                                   placeholder="e.g., 65">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="gender_restriction" class="form-label">Gender Restriction</label>
                                    <select class="form-select" id="gender_restriction" name="gender_restriction">
                                        <option value="mixed" <?= $edit_gender_restriction == 'mixed' ? 'selected' : '' ?>>Mixed (All Genders)</option>
                                        <option value="male_only" <?= $edit_gender_restriction == 'male_only' ? 'selected' : '' ?>>Males Only</option>
                                        <option value="female_only" <?= $edit_gender_restriction == 'female_only' ? 'selected' : '' ?>>Females Only</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="prerequisites" class="form-label">Prerequisites</label>
                                    <textarea class="form-control" id="prerequisites" name="prerequisites" 
                                              rows="2" maxlength="500" 
                                              placeholder="Any requirements or prerequisites..."><?= $edit_prerequisites ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="trial_eligible" name="trial_eligible" 
                                               value="1" <?= $edit_trial_eligible ?>>
                                        <label class="form-check-label" for="trial_eligible">
                                            <strong>Trial Eligible</strong>
                                        </label>
                                        <div class="form-text">Allow trial bookings for this class. Uncheck for advanced classes or 1-on-1 sessions.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        
                        <div class="d-flex justify-content-end">
                            <?= $cancel_button ?>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?= $button_text ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Classes List -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Current Classes</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($classes)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-dumbbell fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No classes found. Create your first class above!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Class Name</th>
                                        <th>Instructor</th>
                                        <th>Schedule</th>
                                        <th>Capacity</th>
                                        <th>Restrictions</th>
                                        <th>Level</th>
                                        <th>Trial</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $class): 
                                        $restrictions = [];
                                        if ($class['age_min'] || $class['age_max']) {
                                            $restrictions[] = 'Age: ' . ($class['age_min'] ?: '0') . '-' . ($class['age_max'] ?: '∞');
                                        }
                                        if ($class['gender_restriction'] !== 'mixed') {
                                            $restrictions[] = ucfirst(str_replace('_', ' ', $class['gender_restriction']));
                                        }
                                        $restrictionText = implode(', ', $restrictions) ?: 'None';
                                        
                                        $scheduleText = '';
                                        if ($class['recurring']) {
                                            $days = json_decode($class['days_of_week'] ?? '[]', true);
                                            $scheduleText = 'Weekly: ' . implode(', ', array_map('ucfirst', $days ?: []));
                                            
                                            // Check for day-specific times first
                                            if ($class['day_specific_times']) {
                                                $dayTimes = json_decode($class['day_specific_times'], true);
                                                $timeDetails = [];
                                                foreach ($days as $day) {
                                                    if (isset($dayTimes[$day])) {
                                                        $timeDetails[] = ucfirst($day) . ': ' . date('g:i A', strtotime($dayTimes[$day]));
                                                    } else {
                                                        $timeDetails[] = ucfirst($day) . ': ' . date('g:i A', strtotime($class['time']));
                                                    }
                                                }
                                                $scheduleText .= '<br>' . implode('<br>', $timeDetails);
                                            } elseif ($class['multiple_times']) {
                                                $times = json_decode($class['multiple_times'], true);
                                                $scheduleText .= '<br>Times: ' . implode(', ', array_map(function($t) { return date('g:i A', strtotime($t)); }, $times));
                                            } else {
                                                $scheduleText .= '<br>Time: ' . date('g:i A', strtotime($class['time']));
                                            }
                                        } else {
                                            $scheduleText = date('M j, Y', strtotime($class['date'])) . '<br>' . date('g:i A', strtotime($class['time']));
                                        }
                                    ?>
                                    <tr>
                                        <td><span class="badge bg-secondary">#<?= $class['id'] ?></span></td>
                                        <td>
                                            <strong><?= htmlspecialchars($class['name']) ?></strong>
                                            <?php if ($class['description']): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($class['description']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $class['instructor_name'] ? htmlspecialchars($class['instructor_name']) : '<em class="text-muted">Unassigned</em>' ?>
                                        </td>
                                        <td><?= $scheduleText ?></td>
                                        <td>
                                            <span class="badge bg-info"><?= $class['capacity'] ?></span>
                                            <br><small class="text-muted"><?= $class['duration_minutes'] ?>min</small>
                                        </td>
                                        <td><small><?= $restrictionText ?></small></td>
                                        <td><span class="badge bg-<?= $class['difficulty_level'] === 'beginner' ? 'success' : ($class['difficulty_level'] === 'advanced' ? 'danger' : 'warning') ?>"><?= ucfirst(str_replace('_', ' ', $class['difficulty_level'])) ?></span></td>
                                        <td>
                                            <?php if ($class['trial_eligible'] ?? 1): ?>
                                                <span class="badge bg-success">Yes</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?edit=<?= $class['id'] ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="deleteClass(<?= $class['id'] ?>, '<?= htmlspecialchars($class['name'], ENT_QUOTES) ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Form (Hidden) -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
// Toggle scheduling options based on recurring checkbox
function toggleSchedulingOptions() {
    const recurring = document.getElementById('recurring').checked;
    const singleDateTime = document.getElementById('singleDateTime');
    const recurringOptions = document.getElementById('recurringOptions');
    const dateField = document.getElementById('date');
    
    if (recurring) {
        singleDateTime.style.display = 'none';
        recurringOptions.style.display = 'block';
        dateField.required = false;
    } else {
        singleDateTime.style.display = 'block';
        recurringOptions.style.display = 'none';
        dateField.required = false; // Keep it optional for single classes too
    }
}

// Add time slot
function addTimeSlot() {
    const container = document.getElementById('multipleTimesContainer');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="time" class="form-control" name="multiple_times[]">
        <button type="button" class="btn btn-outline-danger" onclick="removeTimeSlot(this)">
            <i class="fas fa-trash"></i>
        </button>
    `;
    container.appendChild(div);
}

// Remove time slot
function removeTimeSlot(button) {
    button.parentElement.remove();
}

// Delete class confirmation
function deleteClass(id, name) {
    if (confirm(`Are you sure you want to delete the class "${name}"? This action cannot be undone and will also delete all related bookings.`)) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

// Initialize form state
document.addEventListener('DOMContentLoaded', function() {
    toggleSchedulingOptions();
});
</script>

<?php 
require_once 'templates/footer.php';
exit; // Exit here to prevent the old content from rendering

$content = '';

// Replace placeholders
$content = str_replace([
    '{$form_title}', '{$form_action}', '{$edit_id_field}',
    '{$edit_name}', '{$edit_description}', '{$edit_date}', '{$edit_time}', '{$edit_capacity}',
    '{$instructor_options}', '{$edit_instructor_id}', '{$edit_recurring}', '{$button_text}', '{$cancel_button}', '{$classes_list}', '{$csrfToken}'
], [
    $form_title, $form_action, $edit_id_field,
    $edit_name, $edit_description, $edit_date, $edit_time, $edit_capacity,
    $instructor_options, $edit_instructor_id, $edit_recurring, $button_text, $cancel_button, $classes_list, $csrfToken
], $content);

$inlineJS = <<<JS
function deleteClass(id) {
    if (confirm('Are you sure you want to delete this class? All bookings for this class will also be deleted.')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '"><input type="hidden" name="csrf_token" value="{$csrfToken}">';
        document.body.appendChild(form);
        form.submit();
    }
}

// Instructor selection is now handled in HTML
JS;

// Prepare page options
$pageDescription = 'Create, edit, and manage fitness classes. Set recurring schedules and assign instructors.';

$headerActions = createHeaderActions([
    [
        'text' => 'Back to Dashboard',
        'icon' => 'fas fa-arrow-left',
        'class' => 'btn btn-secondary',
        'href' => 'dashboard.php'
    ]
]);

// Replace placeholders in JavaScript
$inlineJS = str_replace(['{$csrfToken}'], [$csrfToken], $inlineJS);

// Render the admin page
renderAdminPage($content, [
    'pageDescription' => $pageDescription,
    'headerActions' => $headerActions,
    'success' => $message ?? null,
    'error' => $error ?? null,
    'inlineJS' => $inlineJS
]); 