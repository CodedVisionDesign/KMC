<?php
require_once 'includes/admin_common.php';

// Include file upload helper
if (file_exists(__DIR__ . '/../config/file_upload_helper.php')) {
    include __DIR__ . '/../config/file_upload_helper.php';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $bio = trim($_POST['bio']);
        $specialties = trim($_POST['specialties']);
        
        if ($first_name && $last_name && $email) {
            try {
                $stmt = $pdo->prepare('INSERT INTO instructors (first_name, last_name, email, phone, bio, specialties) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$first_name, $last_name, $email, $phone, $bio, $specialties]);
                $instructorId = $pdo->lastInsertId();
                
                // Handle profile photo upload if provided
                if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                    try {
                        $photoFilename = uploadProfilePhoto($_FILES['profile_photo'], 'instructor', $instructorId);
                        if ($photoFilename) {
                            $stmt = $pdo->prepare('UPDATE instructors SET profile_photo = ? WHERE id = ?');
                            $stmt->execute([$photoFilename, $instructorId]);
                        }
                    } catch (Exception $photoError) {
                        // Log photo error but don't fail the creation
                        error_log('Profile photo upload error: ' . $photoError->getMessage());
                    }
                }
                
                $success = "Instructor added successfully!";
            } catch (Exception $e) {
                $error = "Error adding instructor: " . $e->getMessage();
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
        $bio = trim($_POST['bio']);
        $specialties = trim($_POST['specialties']);
        $status = $_POST['status'];
        
        if ($id && $first_name && $last_name && $email) {
            try {
                // Get current instructor data for photo handling
                $currentStmt = $pdo->prepare('SELECT profile_photo FROM instructors WHERE id = ?');
                $currentStmt->execute([$id]);
                $currentInstructor = $currentStmt->fetch();
                $currentPhoto = $currentInstructor['profile_photo'] ?? null;
                
                $stmt = $pdo->prepare('UPDATE instructors SET first_name = ?, last_name = ?, email = ?, phone = ?, bio = ?, specialties = ?, status = ? WHERE id = ?');
                $stmt->execute([$first_name, $last_name, $email, $phone, $bio, $specialties, $status, $id]);
                
                // Handle profile photo upload if provided
                if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                    try {
                        // Delete old photo if exists
                        if ($currentPhoto) {
                            deleteProfilePhoto($currentPhoto, 'instructor');
                        }
                        
                        $photoFilename = uploadProfilePhoto($_FILES['profile_photo'], 'instructor', $id);
                        if ($photoFilename) {
                            $photoStmt = $pdo->prepare('UPDATE instructors SET profile_photo = ? WHERE id = ?');
                            $photoStmt->execute([$photoFilename, $id]);
                        }
                    } catch (Exception $photoError) {
                        // Log photo error but don't fail the update
                        error_log('Profile photo upload error: ' . $photoError->getMessage());
                    }
                }
                
                $success = "Instructor updated successfully!";
            } catch (Exception $e) {
                $error = "Error updating instructor: " . $e->getMessage();
            }
        } else {
            $error = "Please fill in all required fields.";
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        if ($id) {
            try {
                // Check if instructor has classes assigned
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM classes WHERE instructor_id = ?');
                $stmt->execute([$id]);
                $classCount = $stmt->fetchColumn();
                
                if ($classCount > 0) {
                    $error = "Cannot delete instructor. They have $classCount classes assigned. Please reassign or remove those classes first.";
                } else {
                    $stmt = $pdo->prepare('DELETE FROM instructors WHERE id = ?');
                    $stmt->execute([$id]);
                    $success = "Instructor deleted successfully!";
                }
            } catch (Exception $e) {
                $error = "Error deleting instructor: " . $e->getMessage();
            }
        }
    }
}

// Get instructor for editing if ID is provided
$editingInstructor = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare('SELECT * FROM instructors WHERE id = ?');
    $stmt->execute([$editId]);
    $editingInstructor = $stmt->fetch();
}

// Get all instructors
$stmt = $pdo->query('
    SELECT 
        i.*,
        COUNT(c.id) as class_count 
    FROM instructors i 
    LEFT JOIN classes c ON i.id = c.instructor_id 
    GROUP BY i.id 
    ORDER BY i.last_name, i.first_name
');
$instructors = $stmt->fetchAll();

// Start output buffering to capture the content
ob_start();
?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Title</th>
                                <th>Classes</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($instructors as $instructor): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']) ?></strong>
                                        <?php if ($instructor['bio']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars(substr($instructor['bio'], 0, 50)) ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($instructor['email']) ?></td>
                                    <td><?= htmlspecialchars($instructor['phone'] ?: 'Not provided') ?></td>
                                    <td>
                                        <?php if ($instructor['specialties']): ?>
                                            <span class="badge bg-primary"><?= htmlspecialchars($instructor['specialties']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">None specified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $instructor['class_count'] ?> classes</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $instructor['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($instructor['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary btn-edit-instructor" data-instructor="<?= htmlspecialchars(json_encode($instructor), ENT_QUOTES) ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($instructor['class_count'] == 0): ?>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteInstructor(<?= $instructor['id'] ?>, <?= htmlspecialchars(json_encode($instructor['first_name'] . ' ' . $instructor['last_name']), ENT_QUOTES) ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Instructor Modal -->
    <div class="modal fade" id="instructorModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add Instructor</h5>
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
                        
                        <div class="mb-3">
                            <label for="specialties" class="form-label">Title</label>
                            <input type="text" class="form-control" name="specialties" id="specialties" 
                                   placeholder="e.g., Senior Yoga Instructor, Personal Training Specialist">
                            <div class="form-text">Enter the instructor's professional title</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" name="bio" id="bio" rows="3" 
                                      placeholder="Brief description of the instructor's background and experience"></textarea>
                        </div>
                        
                        <div class="mb-3">
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
                            <div id="instructor_photo_preview" class="mt-2" style="display: none;">
                                <img id="instructor_preview_image" src="" alt="Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                            </div>
                            <div id="current_photo_display" class="mt-2" style="display: none;">
                                <label class="form-label">Current Photo:</label>
                                <br>
                                <img id="current_photo_image" src="" alt="Current photo" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">
                            </div>
                        </div>
                        
                        <div class="mb-3" id="statusGroup" style="display: none;">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" name="status" id="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="modalSubmit">Add Instructor</button>
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
                        <p>Are you sure you want to delete instructor <strong id="deleteName"></strong>?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Instructor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php
$content = ob_get_clean();

// Prepare header actions
$headerActions = createHeaderActions([
    [
        'text' => 'Add Instructor',
        'icon' => 'fas fa-plus',
        'class' => 'btn btn-primary',
        'onclick' => "var modal = new bootstrap.Modal(document.getElementById('instructorModal')); modal.show();"
    ]
]);

$inlineJS = <<<'JAVASCRIPT'
        function editInstructor(instructor) {
            document.getElementById('modalTitle').textContent = 'Edit Instructor';
            document.getElementById('modalAction').value = 'edit';
            document.getElementById('modalSubmit').textContent = 'Update Instructor';
            document.getElementById('modalId').value = instructor.id;
            document.getElementById('first_name').value = instructor.first_name;
            document.getElementById('last_name').value = instructor.last_name;
            document.getElementById('email').value = instructor.email;
            document.getElementById('phone').value = instructor.phone || '';
            document.getElementById('specialties').value = instructor.specialties || '';
            document.getElementById('bio').value = instructor.bio || '';
            document.getElementById('status').value = instructor.status;
            document.getElementById('statusGroup').style.display = 'block';
            
            // Handle current photo display
            const currentPhotoDisplay = document.getElementById('current_photo_display');
            const currentPhotoImage = document.getElementById('current_photo_image');
            if (instructor.profile_photo) {
                currentPhotoImage.src = '../uploads/profiles/instructors/' + instructor.profile_photo;
                currentPhotoDisplay.style.display = 'block';
            } else {
                currentPhotoDisplay.style.display = 'none';
            }
            
            var modal = new bootstrap.Modal(document.getElementById('instructorModal'));
            modal.show();
        }

        function deleteInstructor(id, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteName').textContent = name;
            
            var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        // Profile photo preview setup
        function setupInstructorPhotoPreview() {
            const photoInput = document.getElementById('profile_photo');
            const previewDiv = document.getElementById('instructor_photo_preview');
            const previewImage = document.getElementById('instructor_preview_image');
            
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

        // Reset form when modal is closed
        document.getElementById('instructorModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalTitle').textContent = 'Add Instructor';
            document.getElementById('modalAction').value = 'add';
            document.getElementById('modalSubmit').textContent = 'Add Instructor';
            document.getElementById('statusGroup').style.display = 'none';
            document.getElementById('current_photo_display').style.display = 'none';
            document.getElementById('instructor_photo_preview').style.display = 'none';
            this.querySelector('form').reset();
        });
        
        // Initialize photo preview
        setupInstructorPhotoPreview();

        // Add event listeners for instructor buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-edit-instructor')) {
                const button = e.target.closest('.btn-edit-instructor');
                const instructor = JSON.parse(button.dataset.instructor);
                editInstructor(instructor);
            }
        });
JAVASCRIPT;

// Add the editing instructor logic separately to avoid PHP inside JS
if (isset($editingInstructor) && $editingInstructor) {
    $editingInstructorJson = json_encode($editingInstructor, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $inlineJS .= "\n        // Auto-open edit modal if editing\n";
    $inlineJS .= "        document.addEventListener('DOMContentLoaded', function() {\n";
    $inlineJS .= "            editInstructor($editingInstructorJson);\n";
    $inlineJS .= "        });";
}

// Render the admin page
renderAdminPage($content, [
    'pageDescription' => 'Manage fitness instructors, their profiles, and class assignments',
    'headerActions' => $headerActions,
    'success' => $success ?? null,
    'error' => $error ?? null,
    'inlineJS' => $inlineJS
]); 