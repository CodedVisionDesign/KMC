<?php
require_once 'includes/admin_common.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        
        if ($first_name && $last_name && $email && $password) {
            try {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password_hash, phone) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$first_name, $last_name, $email, $password_hash, $phone]);
                $success = "Student added successfully!";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $error = "A student with this email already exists.";
                } else {
                    $error = "Error adding student: " . $e->getMessage();
                }
            }
        } else {
            $error = "Please fill in all required fields.";
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $status = $_POST['status'];
        
        if ($id && $first_name && $last_name && $email) {
            try {
                $stmt = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, status = ? WHERE id = ?');
                $stmt->execute([$first_name, $last_name, $email, $phone, $status, $id]);
                $success = "Student updated successfully!";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $error = "A student with this email already exists.";
                } else {
                    $error = "Error updating student: " . $e->getMessage();
                }
            }
        } else {
            $error = "Please fill in all required fields.";
        }
    } elseif ($action === 'reset_password') {
        $id = intval($_POST['id']);
        $new_password = $_POST['new_password'];
        
        if ($id && $new_password) {
            try {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $stmt->execute([$password_hash, $id]);
                $success = "Password reset successfully!";
            } catch (Exception $e) {
                $error = "Error resetting password: " . $e->getMessage();
            }
        } else {
            $error = "Please provide a valid password.";
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        if ($id) {
            try {
                $pdo->beginTransaction();
                
                // Get student info for logging
                $stmt = $pdo->prepare('SELECT first_name, last_name, email FROM users WHERE id = ?');
                $stmt->execute([$id]);
                $student = $stmt->fetch();
                
                if (!$student) {
                    throw new Exception("Student not found.");
                }
                
                // Delete all future bookings for this student (since bookings table has no status column)
                $stmt = $pdo->prepare('
                    DELETE b FROM bookings b 
                    JOIN classes c ON b.class_id = c.id 
                    WHERE b.user_id = ? AND c.date >= CURDATE()
                ');
                $stmt->execute([$id]);
                $cancelledBookings = $stmt->rowCount();
                
                // Delete all user memberships and related payments
                $stmt = $pdo->prepare('
                    DELETE mp FROM membership_payments mp 
                    JOIN user_memberships um ON mp.user_membership_id = um.id 
                    WHERE um.user_id = ?
                ');
                $stmt->execute([$id]);
                
                $stmt = $pdo->prepare('DELETE FROM user_memberships WHERE user_id = ?');
                $stmt->execute([$id]);
                
                // Finally delete the user
                $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
                $stmt->execute([$id]);
                
                $pdo->commit();
                
                $studentName = $student['first_name'] . ' ' . $student['last_name'];
                if ($cancelledBookings > 0) {
                    $success = "Student '$studentName' deleted successfully! $cancelledBookings future bookings were removed.";
                } else {
                    $success = "Student '$studentName' deleted successfully!";
                }
                
            } catch (Exception $e) {
                $pdo->rollback();
                $error = "Error deleting student: " . $e->getMessage();
            }
        }
    } elseif ($action === 'remove_photo') {
        $id = intval($_POST['id']);
        if ($id) {
            try {
                // Get current photo filename
                $stmt = $pdo->prepare('SELECT profile_photo, first_name, last_name FROM users WHERE id = ?');
                $stmt->execute([$id]);
                $student = $stmt->fetch();
                
                if (!$student) {
                    throw new Exception("Student not found.");
                }
                
                // Delete the photo file if it exists
                if (!empty($student['profile_photo'])) {
                    require_once __DIR__ . '/../config/file_upload_helper.php';
                    deleteProfilePhoto($student['profile_photo'], 'user');
                }
                
                // Update database to remove photo reference
                $stmt = $pdo->prepare('UPDATE users SET profile_photo = NULL WHERE id = ?');
                $stmt->execute([$id]);
                
                $studentName = $student['first_name'] . ' ' . $student['last_name'];
                $success = "Profile photo removed for student '$studentName'.";
                
            } catch (Exception $e) {
                $error = "Error removing photo: " . $e->getMessage();
            }
        }
    }
}

// Get student for editing if ID is provided
$editingStudent = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$editId]);
    $editingStudent = $stmt->fetch();
}

// Get all students with booking counts
try {
    $stmt = $pdo->query('
        SELECT 
            u.*,
            COUNT(b.id) as booking_count,
            MAX(b.created_at) as last_booking
        FROM users u 
        LEFT JOIN bookings b ON u.id = b.user_id 
        GROUP BY u.id 
        ORDER BY u.created_at DESC
    ');
    $students = $stmt->fetchAll();
    
    // Parse health questionnaire JSON for each student and add photo URL
    require_once __DIR__ . '/../config/file_upload_helper.php';
    foreach ($students as &$student) {
        $student['health_data'] = [];
        if (!empty($student['health_questionnaire'])) {
            $decoded = json_decode($student['health_questionnaire'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $student['health_data'] = $decoded;
            }
        }
        // Add the properly constructed photo URL for use in JavaScript
        $student['photo_url'] = getProfilePhotoUrl($student['profile_photo'], 'user');
    }
} catch (Exception $e) {
    $students = [];
    $error = "Error loading students: " . $e->getMessage();
}

// Get total statistics
$totalStudents = count($students);
$activeStudents = count(array_filter($students, function($s) { return $s['status'] === 'active'; }));
$totalBookings = array_sum(array_column($students, 'booking_count'));
?>

<?php
// Calculate averages
$avgBookings = $totalStudents > 0 ? round($totalBookings / $totalStudents, 1) : 0;

// Start output buffering to capture the content
ob_start();
?>
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Total Students</h5>
                                <h3><?= $totalStudents ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Active Students</h5>
                                <h3><?= $activeStudents ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-user-check fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Total Bookings</h5>
                                <h3><?= $totalBookings ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-calendar-check fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Avg Bookings</h5>
                                <h3><?= $avgBookings ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-chart-line fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Age/Gender</th>
                                <th>Health Status</th>
                                <th>Bookings</th>
                                <th>Last Booking</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                                        <td>
                        <?php
                        require_once __DIR__ . '/../config/file_upload_helper.php';
                        $photoUrl = getProfilePhotoUrl($student['profile_photo'], 'user');
                        ?>
                        <div class="position-relative d-inline-block">
                            <img src="<?= htmlspecialchars($photoUrl) ?>" 
                                 alt="<?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>" 
                                 class="rounded-circle border btn-view-student-trigger" 
                                 style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;"
                                 data-student="<?= htmlspecialchars(json_encode($student), ENT_QUOTES) ?>"
                                 title="View Student Details">
                            <?php if (!empty($student['profile_photo'])): ?>
                                <button type="button" 
                                        class="btn btn-danger btn-sm position-absolute top-0 start-100 translate-middle rounded-circle p-1" 
                                        style="width: 20px; height: 20px; font-size: 10px; line-height: 1; z-index: 10;"
                                        onclick="event.stopPropagation(); removeStudentPhoto(<?= $student['id'] ?>, '<?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>')"
                                        title="Remove photo">
                                    <i class="fas fa-times"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                                                        <td>
                        <strong class="btn-view-student-trigger" 
                                style="cursor: pointer; text-decoration: underline; color: #007bff;"
                                data-student="<?= htmlspecialchars(json_encode($student), ENT_QUOTES) ?>"
                                title="View Student Details"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></strong>
                        <br><small class="text-muted">ID: <?= $student['id'] ?></small>
                    </td>
                                    <td><?= htmlspecialchars($student['email']) ?></td>
                                    <td><?= htmlspecialchars($student['phone'] ?: 'Not provided') ?></td>
                                    <td>
                                        <?php 
                                        $age = '';
                                        if ($student['date_of_birth']) {
                                            $dob = new DateTime($student['date_of_birth']);
                                            $now = new DateTime();
                                            $age = $now->diff($dob)->y;
                                        }
                                        ?>
                                        <small>
                                            <?= $age ? $age . ' years' : 'N/A' ?><br>
                                            <?= $student['gender'] ? ucfirst(str_replace('_', ' ', $student['gender'])) : 'N/A' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php 
                                        $healthData = $student['health_data'];
                                        $hasHealthIssues = false;
                                        if ($healthData && is_array($healthData)) {
                                            $hasHealthIssues = ($healthData['has_medical_conditions'] ?? false) ||
                                                             ($healthData['takes_medication'] ?? false) ||
                                                             ($healthData['has_injuries'] ?? false) ||
                                                             ($healthData['has_allergies'] ?? false);
                                        }
                                        ?>
                                        <?php if ($healthData && is_array($healthData)): ?>
                                            <span class="badge bg-<?= $hasHealthIssues ? 'warning' : 'success' ?>">
                                                <?= $hasHealthIssues ? 'Has conditions' : 'Clear' ?>
                                            </span>
                                            <br><small><?= ucfirst($healthData['fitness_level'] ?? 'N/A') ?></small>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">No data</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $student['booking_count'] ?> bookings</span>
                                    </td>
                                    <td>
                                        <?php if ($student['last_booking']): ?>
                                            <small><?= date('M j, Y', strtotime($student['last_booking'])) ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">Never</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $student['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($student['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?= date('M j, Y', strtotime($student['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-info btn-view-student" data-student="<?= htmlspecialchars(json_encode($student), ENT_QUOTES) ?>" title="View Health & Emergency Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-primary btn-edit-student" data-student="<?= htmlspecialchars(json_encode($student), ENT_QUOTES) ?>" title="Edit Student">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-warning" onclick="resetPassword(<?= $student['id'] ?>, '<?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>')" title="Reset Password">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="deleteStudent(<?= $student['id'] ?>, '<?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>')" title="Delete Student">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Modal -->
    <div class="modal fade" id="studentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add Student</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="modalAction" value="add">
                        <input type="hidden" name="id" id="modalId">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" name="first_name" id="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" name="last_name" id="last_name" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" id="email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="phone" id="phone">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="passwordGroup">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" name="password" id="password">
                            <div class="form-text">Minimum 6 characters</div>
                        </div>
                        
                        <div class="mb-3" id="statusGroup" style="display: none;">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" name="status" id="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="modalSubmit">Add Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Password Reset Modal -->
    <div class="modal fade" id="passwordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Reset Password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="id" id="passwordId">
                        <p>Reset password for <strong id="passwordName"></strong>?</p>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" id="new_password" required minlength="6">
                            <div class="form-text">Minimum 6 characters</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteId">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning!</strong> You are about to delete student <strong id="deleteName"></strong>.
                        </div>
                        <p><strong>This action will:</strong></p>
                        <ul>
                            <li>Permanently delete the student account</li>
                            <li>Cancel all future bookings for this student</li>
                            <li>Remove all membership records and payment history</li>
                            <li>Delete all associated data</li>
                        </ul>
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle me-2"></i>
                            <strong>This action cannot be undone!</strong>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Remove Photo Confirmation Modal -->
    <div class="modal fade" id="removePhotoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Remove Profile Photo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="remove_photo">
                        <input type="hidden" name="id" id="removePhotoId">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Are you sure you want to remove the profile photo for <strong id="removePhotoName"></strong>?
                        </div>
                        <p>This will permanently delete the photo file and cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Remove Photo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Student Details Modal -->
    <div class="modal fade" id="studentDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-md me-2"></i>Student Health & Emergency Information
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                                        <!-- Personal Information -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h6>
                        </div>
                        <div class="card-body">
                            <!-- Profile Photo -->
                            <div class="text-center mb-3">
                                <img id="detailProfilePhoto" 
                                     src="" 
                                     alt="Profile Photo" 
                                     class="rounded-circle border"
                                     style="width: 80px; height: 80px; object-fit: cover;">
                            </div>
                            <div class="row">
                                <div class="col-sm-4"><strong>Name:</strong></div>
                                <div class="col-sm-8" id="detailName">-</div>
                            </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4"><strong>Email:</strong></div>
                                        <div class="col-sm-8" id="detailEmail">-</div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4"><strong>Phone:</strong></div>
                                        <div class="col-sm-8" id="detailPhone">-</div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4"><strong>Date of Birth:</strong></div>
                                        <div class="col-sm-8" id="detailDOB">-</div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4"><strong>Gender:</strong></div>
                                        <div class="col-sm-8" id="detailGender">-</div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4"><strong>Fitness Level:</strong></div>
                                        <div class="col-sm-8" id="detailFitnessLevel">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Emergency Contact -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-danger text-white">
                                    <h6 class="mb-0"><i class="fas fa-phone me-2"></i>Emergency Contact</h6>
                                </div>
                                <div class="card-body" id="emergencyContactInfo">
                                    <!-- Emergency contact details will be populated here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <!-- Health Conditions -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="fas fa-heartbeat me-2"></i>Medical Conditions</h6>
                                </div>
                                <div class="card-body" id="medicalConditionsInfo">
                                    <!-- Medical conditions will be populated here -->
                                </div>
                            </div>
                        </div>

                        <!-- Medications & Allergies -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-pills me-2"></i>Medications & Allergies</h6>
                                </div>
                                <div class="card-body" id="medicationsAllergiesInfo">
                                    <!-- Medications and allergies will be populated here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <!-- Exercise Information -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-dumbbell me-2"></i>Exercise & Limitations</h6>
                                </div>
                                <div class="card-body" id="exerciseInfo">
                                    <!-- Exercise information will be populated here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<?php
$content = ob_get_clean();

// Prepare page options
$headerActions = createHeaderActions([
    [
        'text' => 'Add Student',
        'icon' => 'fas fa-plus',
        'class' => 'btn btn-primary',
        'onclick' => "var modal = new bootstrap.Modal(document.getElementById('studentModal')); modal.show();"
    ]
]);

$inlineJS = <<<JS
        function editStudent(student) {
            document.getElementById('modalTitle').textContent = 'Edit Student';
            document.getElementById('modalAction').value = 'edit';
            document.getElementById('modalSubmit').textContent = 'Update Student';
            document.getElementById('modalId').value = student.id;
            document.getElementById('first_name').value = student.first_name;
            document.getElementById('last_name').value = student.last_name;
            document.getElementById('email').value = student.email;
            document.getElementById('phone').value = student.phone || '';
            document.getElementById('status').value = student.status;
            document.getElementById('statusGroup').style.display = 'block';
            document.getElementById('passwordGroup').style.display = 'none';
            
            var modal = new bootstrap.Modal(document.getElementById('studentModal'));
            modal.show();
        }

        function viewStudentDetails(student) {
            // Populate profile photo using the properly constructed URL from PHP
            const profilePhotoElement = document.getElementById('detailProfilePhoto');
            profilePhotoElement.src = student.photo_url;
            profilePhotoElement.alt = student.first_name + ' ' + student.last_name;
            
            // Populate personal information
            document.getElementById('detailName').textContent = student.first_name + ' ' + student.last_name;
            document.getElementById('detailEmail').textContent = student.email;
            document.getElementById('detailPhone').textContent = student.phone || 'Not provided';
            
            // Calculate and display age
            let dobDisplay = 'Not provided';
            if (student.date_of_birth) {
                const dob = new Date(student.date_of_birth);
                const now = new Date();
                const age = now.getFullYear() - dob.getFullYear();
                dobDisplay = student.date_of_birth + ' (Age: ' + age + ')';
            }
            document.getElementById('detailDOB').textContent = dobDisplay;
            
            document.getElementById('detailGender').textContent = student.gender ? 
                student.gender.charAt(0).toUpperCase() + student.gender.slice(1).replace('_', ' ') : 'Not specified';
            
            // Parse health data
            const healthData = student.health_data || {};
            
            document.getElementById('detailFitnessLevel').textContent = healthData.fitness_level ? 
                healthData.fitness_level.charAt(0).toUpperCase() + healthData.fitness_level.slice(1) : 'Not specified';
            
            // Emergency Contact Information
            let emergencyHtml = '';
            if (healthData.emergency_contact_name || healthData.emergency_contact_phone) {
                emergencyHtml = `
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>Name:</strong></div>
                        <div class="col-sm-8">\${healthData.emergency_contact_name || 'Not provided'}</div>
                    </div>
                    <hr>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>Phone:</strong></div>
                        <div class="col-sm-8">\${healthData.emergency_contact_phone || 'Not provided'}</div>
                    </div>
                    <hr>
                    <div class="row mb-2">
                        <div class="col-sm-4"><strong>Relationship:</strong></div>
                        <div class="col-sm-8">\${healthData.emergency_contact_relationship || 'Not provided'}</div>
                    </div>
                `;
            } else {
                emergencyHtml = '<p class="text-muted">No emergency contact information provided</p>';
            }
            document.getElementById('emergencyContactInfo').innerHTML = emergencyHtml;
            
            // Medical Conditions
            let medicalHtml = '';
            if (healthData.has_medical_conditions) {
                medicalHtml = `
                    <div class="alert alert-warning">
                        <strong><i class="fas fa-exclamation-triangle me-2"></i>Has Medical Conditions</strong>
                    </div>
                    <p><strong>Details:</strong></p>
                    <p class="border p-2 bg-light">\${healthData.medical_conditions || 'No details provided'}</p>
                `;
            } else {
                medicalHtml = '<div class="alert alert-success"><i class="fas fa-check me-2"></i>No medical conditions reported</div>';
            }
            
            if (healthData.has_injuries) {
                medicalHtml += `
                    <div class="alert alert-warning mt-2">
                        <strong><i class="fas fa-band-aid me-2"></i>Has Injuries</strong>
                    </div>
                    <p><strong>Injury Details:</strong></p>
                    <p class="border p-2 bg-light">\${healthData.injury_details || 'No details provided'}</p>
                `;
            } else if (healthData.has_injuries === false) {
                medicalHtml += '<div class="alert alert-success mt-2"><i class="fas fa-check me-2"></i>No injuries reported</div>';
            }
            document.getElementById('medicalConditionsInfo').innerHTML = medicalHtml;
            
            // Medications & Allergies
            let medicationsHtml = '';
            
            // Medications
            if (healthData.takes_medication) {
                medicationsHtml = `
                    <div class="alert alert-info">
                        <strong><i class="fas fa-pills me-2"></i>Takes Medication</strong>
                    </div>
                    <p><strong>Medication Details:</strong></p>
                    <p class="border p-2 bg-light">\${healthData.medication_details || 'No details provided'}</p>
                `;
            } else {
                medicationsHtml = '<div class="alert alert-success"><i class="fas fa-check me-2"></i>No medications reported</div>';
            }
            
            // Allergies
            if (healthData.has_allergies) {
                medicationsHtml += `
                    <div class="alert alert-danger mt-3">
                        <strong><i class="fas fa-exclamation-circle me-2"></i>Has Allergies</strong>
                    </div>
                    <p><strong>Allergy Details:</strong></p>
                    <p class="border p-2 bg-light">\${healthData.allergy_details || 'No details provided'}</p>
                `;
            } else if (healthData.has_allergies === false) {
                medicationsHtml += '<div class="alert alert-success mt-3"><i class="fas fa-check me-2"></i>No allergies reported</div>';
            }
            document.getElementById('medicationsAllergiesInfo').innerHTML = medicationsHtml;
            
            // Exercise Information
            let exerciseHtml = '';
            if (healthData.exercise_limitations) {
                exerciseHtml = `
                    <div class="alert alert-warning">
                        <strong><i class="fas fa-exclamation-triangle me-2"></i>Has Exercise Limitations</strong>
                    </div>
                    <p><strong>Limitations:</strong></p>
                    <p class="border p-2 bg-light">\${healthData.exercise_limitations}</p>
                `;
            } else {
                exerciseHtml = '<div class="alert alert-success"><i class="fas fa-check me-2"></i>No exercise limitations reported</div>';
            }
            
            // Medical Emergency Consent
            if (healthData.consent_medical_emergency) {
                exerciseHtml += '<div class="alert alert-info mt-3"><i class="fas fa-check me-2"></i>Consented to medical emergency treatment</div>';
            } else {
                exerciseHtml += '<div class="alert alert-warning mt-3"><i class="fas fa-exclamation-triangle me-2"></i>No medical emergency consent on file</div>';
            }
            
            document.getElementById('exerciseInfo').innerHTML = exerciseHtml;
            
            // Show the modal
            var modal = new bootstrap.Modal(document.getElementById('studentDetailsModal'));
            modal.show();
        }

        function resetPassword(id, name) {
            document.getElementById('passwordId').value = id;
            document.getElementById('passwordName').textContent = name;
            document.getElementById('new_password').value = '';
            
            var modal = new bootstrap.Modal(document.getElementById('passwordModal'));
            modal.show();
        }

        function deleteStudent(id, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteName').textContent = name;
            
            var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        function removeStudentPhoto(id, name) {
            document.getElementById('removePhotoId').value = id;
            document.getElementById('removePhotoName').textContent = name;
            
            var modal = new bootstrap.Modal(document.getElementById('removePhotoModal'));
            modal.show();
        }

        // Reset form when modal is closed
        document.getElementById('studentModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalTitle').textContent = 'Add Student';
            document.getElementById('modalAction').value = 'add';
            document.getElementById('modalSubmit').textContent = 'Add Student';
            document.getElementById('statusGroup').style.display = 'none';
            document.getElementById('passwordGroup').style.display = 'block';
            this.querySelector('form').reset();
        });

        // Add event listeners for student buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-view-student')) {
                const button = e.target.closest('.btn-view-student');
                const student = JSON.parse(button.dataset.student);
                viewStudentDetails(student);
            } else if (e.target.closest('.btn-edit-student')) {
                const button = e.target.closest('.btn-edit-student');
                const student = JSON.parse(button.dataset.student);
                editStudent(student);
            } else if (e.target.closest('.btn-view-student-trigger')) {
                const element = e.target.closest('.btn-view-student-trigger');
                const student = JSON.parse(element.dataset.student);
                viewStudentDetails(student);
            }
        });

JS;

// Add the editing student logic separately to avoid PHP inside JS
if (isset($editingStudent) && $editingStudent) {
    $editingStudentJson = json_encode($editingStudent, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $inlineJS .= "\n        // Auto-open edit modal if editing\n";
    $inlineJS .= "        document.addEventListener('DOMContentLoaded', function() {\n";
    $inlineJS .= "            editStudent($editingStudentJson);\n";
    $inlineJS .= "        });";
}

// Render the admin page
renderAdminPage($content, [
    'pageDescription' => 'Manage student accounts, view health information, and handle student data',
    'headerActions' => $headerActions,
    'success' => $success ?? null,
    'error' => $error ?? null,
    'inlineJS' => $inlineJS
]); 