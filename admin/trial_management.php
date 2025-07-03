<?php
/**
 * Admin Trial Management Interface
 * Configure trial settings and manage user trials
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/configurable_trial_functions.php';
require_once __DIR__ . '/../config/error_handling.php';

// Error handling is automatically initialized when the file is included

$currentAdminId = getCurrentAdminId();
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_settings':
                    $trialClasses = (int) $_POST['trial_classes_per_user'];
                    $systemEnabled = isset($_POST['trial_system_enabled']) ? 1 : 0;
                    $autoResetEnabled = isset($_POST['trial_auto_reset_enabled']) ? 1 : 0;
                    $existingUsersEligible = isset($_POST['trial_eligible_for_existing_users']) ? 1 : 0;
                    
                    if ($trialClasses < 0 || $trialClasses > 10) {
                        throw new Exception('Trial classes must be between 0 and 10');
                    }
                    
                    updateTrialSetting('trial_classes_per_user', $trialClasses, $currentAdminId);
                    updateTrialSetting('trial_system_enabled', $systemEnabled, $currentAdminId);
                    updateTrialSetting('trial_auto_reset_enabled', $autoResetEnabled, $currentAdminId);
                    updateTrialSetting('trial_eligible_for_existing_users', $existingUsersEligible, $currentAdminId);
                    
                    $success = 'Trial settings updated successfully!';
                    break;
                    
                case 'reset_user_trial':
                    $userId = (int) $_POST['user_id'];
                    $notes = trim($_POST['notes'] ?? '');
                    
                    if ($userId <= 0) {
                        throw new Exception('Invalid user ID');
                    }
                    
                    if (resetUserTrial($userId, $currentAdminId, $notes)) {
                        $success = 'User trial reset successfully!';
                    } else {
                        throw new Exception('Failed to reset user trial');
                    }
                    break;
                    
                case 'bulk_reset_trials':
                    $notes = trim($_POST['bulk_notes'] ?? '');
                    $affectedRows = bulkResetAllTrials($currentAdminId, $notes);
                    
                    if ($affectedRows !== false) {
                        $success = "Successfully reset trials for $affectedRows users!";
                    } else {
                        throw new Exception('Failed to perform bulk trial reset');
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get current settings
$trialSettings = getAllTrialSettings();
$settingsMap = [];
foreach ($trialSettings as $setting) {
    $settingsMap[$setting['setting_name']] = $setting['setting_value'];
}

// Get users with trial information
try {
    $pdo = connectUserDB();
    $stmt = $pdo->query("
        SELECT u.id, u.first_name, u.last_name, u.email, u.created_at,
               u.trial_classes_used, u.trial_reset_count, u.trial_last_reset_date,
               u.free_trial_used
        FROM users u
        ORDER BY u.created_at DESC
        LIMIT 100
    ");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $users = [];
    $error = 'Failed to load users: ' . $e->getMessage();
}

// Get recent trial management log
$recentLog = getTrialManagementLog(null, null, 20);

include __DIR__ . '/templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-gift me-2"></i>Trial Management</h1>
                <div>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Trial Settings Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-cog me-2"></i>Trial System Settings</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="trial_classes_per_user" class="form-label">
                                        <strong>Trial Classes Per User</strong>
                                    </label>
                                    <input type="number" class="form-control" id="trial_classes_per_user" 
                                           name="trial_classes_per_user" min="0" max="10" 
                                           value="<?= htmlspecialchars($settingsMap['trial_classes_per_user'] ?? 1) ?>" required>
                                    <div class="form-text">Number of free trial classes each user can book (0-10)</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><strong>System Options</strong></label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="trial_system_enabled" 
                                               name="trial_system_enabled" value="1"
                                               <?= ($settingsMap['trial_system_enabled'] ?? 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="trial_system_enabled">
                                            Enable Trial System
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="trial_eligible_for_existing_users" 
                                               name="trial_eligible_for_existing_users" value="1"
                                               <?= ($settingsMap['trial_eligible_for_existing_users'] ?? 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="trial_eligible_for_existing_users">
                                            Existing Users Eligible for Trials
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="trial_auto_reset_enabled" 
                                               name="trial_auto_reset_enabled" value="1"
                                               <?= ($settingsMap['trial_auto_reset_enabled'] ?? 0) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="trial_auto_reset_enabled">
                                            Enable Bulk Trial Reset
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Current Status Overview -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-users text-primary"></i><br>
                                Total Users
                            </h5>
                            <h3 class="text-primary"><?= count($users) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-gift text-success"></i><br>
                                Trial Available
                            </h5>
                            <?php
                            $trialAvailable = 0;
                            $maxTrials = (int) ($settingsMap['trial_classes_per_user'] ?? 1);
                            foreach ($users as $user) {
                                if ((int) $user['trial_classes_used'] < $maxTrials) {
                                    $trialAvailable++;
                                }
                            }
                            ?>
                            <h3 class="text-success"><?= $trialAvailable ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-check text-info"></i><br>
                                Trial Used
                            </h5>
                            <h3 class="text-info"><?= count($users) - $trialAvailable ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-redo text-warning"></i><br>
                                Reset Count
                            </h5>
                            <?php
                            $totalResets = 0;
                            foreach ($users as $user) {
                                $totalResets += (int) $user['trial_reset_count'];
                            }
                            ?>
                            <h3 class="text-warning"><?= $totalResets ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk Actions -->
            <?php if ($settingsMap['trial_auto_reset_enabled'] ?? 0): ?>
            <div class="card mb-4">
                <div class="card-header bg-warning">
                    <h3><i class="fas fa-exclamation-triangle me-2"></i>Bulk Actions</h3>
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return confirm('Are you sure you want to reset ALL user trials? This cannot be undone!')">
                        <input type="hidden" name="action" value="bulk_reset_trials">
                        <div class="row">
                            <div class="col-md-9">
                                <label for="bulk_notes" class="form-label">Reset Reason/Notes</label>
                                <input type="text" class="form-control" id="bulk_notes" name="bulk_notes" 
                                       placeholder="e.g., New promotional period, System reset">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="fas fa-redo"></i> Reset All Trials
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- User Management -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-users me-2"></i>User Trial Management</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                    <th>Trial Status</th>
                                    <th>Resets</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                                        <br><small class="text-muted">ID: <?= $user['id'] ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <?php 
                                        $maxTrials = (int) ($settingsMap['trial_classes_per_user'] ?? 1);
                                        $used = (int) $user['trial_classes_used'];
                                        $remaining = max(0, $maxTrials - $used);
                                        ?>
                                        <span class="badge <?= $remaining > 0 ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $used ?>/<?= $maxTrials ?> used
                                        </span>
                                        <?php if ($remaining > 0): ?>
                                            <br><small class="text-success"><?= $remaining ?> remaining</small>
                                        <?php else: ?>
                                            <br><small class="text-muted">All used</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['trial_reset_count'] > 0): ?>
                                            <span class="badge bg-warning"><?= $user['trial_reset_count'] ?></span>
                                            <?php if ($user['trial_last_reset_date']): ?>
                                                <br><small class="text-muted">
                                                    Last: <?= date('M d, Y', strtotime($user['trial_last_reset_date'])) ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="showResetModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>')">
                                            <i class="fas fa-redo"></i> Reset Trial
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Log -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history me-2"></i>Recent Trial Activity</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($recentLog)): ?>
                        <p class="text-muted">No trial management activity recorded yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Action</th>
                                        <th>User</th>
                                        <th>Admin</th>
                                        <th>Details</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentLog as $log): ?>
                                    <tr>
                                        <td><?= date('M d, Y H:i', strtotime($log['created_at'])) ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?= ucwords(str_replace('_', ' ', $log['action_type'])) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($log['user_id'] == 0): ?>
                                                <em>Bulk Action</em>
                                            <?php else: ?>
                                                <?= htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($log['user_email']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($log['admin_username']) ?></td>
                                        <td><?= htmlspecialchars($log['old_value']) ?> → <?= htmlspecialchars($log['new_value']) ?></td>
                                        <td><?= htmlspecialchars($log['notes']) ?></td>
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

<!-- Reset Trial Modal -->
<div class="modal fade" id="resetTrialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset User Trial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reset_user_trial">
                <input type="hidden" name="user_id" id="resetUserId">
                <div class="modal-body">
                    <p>Reset trial eligibility for <strong id="resetUserName"></strong>?</p>
                    <p class="text-muted">This will allow the user to book trial classes again.</p>
                    
                    <div class="mb-3">
                        <label for="resetNotes" class="form-label">Reason/Notes</label>
                        <input type="text" class="form-control" id="resetNotes" name="notes" 
                               placeholder="e.g., Customer service request, Special promotion">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Reset Trial</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showResetModal(userId, userName) {
    document.getElementById('resetUserId').value = userId;
    document.getElementById('resetUserName').textContent = userName;
    document.getElementById('resetNotes').value = '';
    new bootstrap.Modal(document.getElementById('resetTrialModal')).show();
}
</script>

<?php include __DIR__ . '/templates/footer.php'; ?> 