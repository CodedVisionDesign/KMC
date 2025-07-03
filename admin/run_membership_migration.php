<?php
/**
 * Martial Arts Membership Migration Script
 * Run this to upgrade your system to the new age-based membership structure
 */

require_once 'includes/admin_common.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin authentication
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

$messages = [];
$errors = [];
$migrationCompleted = false;

if ($_POST && isset($_POST['run_migration'])) {
    try {
        // Read the migration SQL file
        $migrationFile = '../config/update_membership_structure.sql';
        
        if (!file_exists($migrationFile)) {
            throw new Exception('Migration file not found: ' . $migrationFile);
        }
        
        $sql = file_get_contents($migrationFile);
        
        if (empty($sql)) {
            throw new Exception('Migration file is empty');
        }
        
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)), 
            function($stmt) { 
                return !empty($stmt) && !preg_match('/^--/', $stmt); 
            }
        );
        
        $pdo = connectUserDB();
        $pdo->beginTransaction();
        
        $executedCount = 0;
        foreach ($statements as $statement) {
            if (trim($statement)) {
                try {
                    $pdo->exec($statement);
                    $executedCount++;
                } catch (PDOException $e) {
                    // Log the error but continue with other statements
                    error_log("Migration statement failed: " . $e->getMessage());
                    error_log("Statement: " . $statement);
                    
                    // Some errors are expected (like table/column already exists)
                    if (strpos($e->getMessage(), 'already exists') === false && 
                        strpos($e->getMessage(), 'Duplicate column') === false) {
                        throw $e;
                    }
                }
            }
        }
        
        $pdo->commit();
        
        $messages[] = "Migration completed successfully!";
        $messages[] = "Executed {$executedCount} SQL statements";
        $messages[] = "Your system now supports age-based martial arts memberships";
        $migrationCompleted = true;
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollback();
        }
        $errors[] = "Migration failed: " . $e->getMessage();
        error_log("Membership migration failed: " . $e->getMessage());
    }
}

// Check current system status
$systemStatus = [];
try {
    $pdo = connectUserDB();
    
    // Check if new columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM membership_plans LIKE 'age_min'");
    $systemStatus['age_columns'] = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM classes LIKE 'class_type'");
    $systemStatus['class_type_column'] = $stmt->rowCount() > 0;
    
    // Check if martial arts plans exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM membership_plans WHERE name LIKE '%Adult%' OR name LIKE '%Junior%' OR name LIKE '%Senior%' OR name LIKE '%Infant%'");
    $result = $stmt->fetch();
    $systemStatus['martial_arts_plans'] = $result['count'] > 0;
    
    // Check if functions exist
    $stmt = $pdo->query("SHOW FUNCTION STATUS WHERE Name = 'GetUserAge'");
    $systemStatus['helper_functions'] = $stmt->rowCount() > 0;
    
} catch (Exception $e) {
    $errors[] = "Error checking system status: " . $e->getMessage();
}

$needsMigration = !($systemStatus['age_columns'] ?? false) || 
                  !($systemStatus['class_type_column'] ?? false) || 
                  !($systemStatus['martial_arts_plans'] ?? false);

// Include admin template
ob_start();
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-database me-2"></i>Martial Arts Membership Migration</h3>
                    <p class="mb-0 text-muted">Upgrade your system to support age-based martial arts memberships</p>
                </div>
                <div class="card-body">
                    
                    <?php if (!empty($messages)): ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <?php foreach ($errors as $error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <h5><i class="fas fa-info-circle me-2"></i>Current System Status</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="list-group">
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    Age-based Membership Fields
                                    <?php if ($systemStatus['age_columns'] ?? false): ?>
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Ready</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning"><i class="fas fa-times"></i> Needs Update</span>
                                    <?php endif; ?>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    Martial Arts Class Types
                                    <?php if ($systemStatus['class_type_column'] ?? false): ?>
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Ready</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning"><i class="fas fa-times"></i> Needs Update</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="list-group">
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    Age-based Membership Plans
                                    <?php if ($systemStatus['martial_arts_plans'] ?? false): ?>
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Ready</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning"><i class="fas fa-times"></i> Needs Update</span>
                                    <?php endif; ?>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    Helper Functions
                                    <?php if ($systemStatus['helper_functions'] ?? false): ?>
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Ready</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning"><i class="fas fa-times"></i> Needs Update</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($needsMigration && !$migrationCompleted): ?>
                        <div class="alert alert-info">
                            <h5><i class="fas fa-arrow-up me-2"></i>Migration Required</h5>
                            <p>Your system needs to be updated to support the new martial arts membership structure. This migration will:</p>
                            <ul>
                                <li><strong>Add age-based membership tiers:</strong> Infants (4-6), Juniors (7-11), Seniors (11-15), Adults (15+)</li>
                                <li><strong>Create specialized membership types:</strong> Beginner deals, PAYG options, invitation-only classes</li>
                                <li><strong>Update class structure:</strong> Add martial arts class types and age restrictions</li>
                                <li><strong>Add GoCardless payment links:</strong> Your provided payment URLs will be integrated</li>
                                <li><strong>Create helper functions:</strong> Age validation and booking logic</li>
                            </ul>
                            
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Important:</strong> This will replace your current membership plans with the new martial arts structure. 
                                Make sure you have backed up your database before proceeding.
                            </div>
                        </div>
                        
                        <form method="POST" onsubmit="return confirm('Are you sure you want to run the migration? This will update your database structure.');">
                            <div class="d-grid gap-2">
                                <button type="submit" name="run_migration" class="btn btn-primary btn-lg">
                                    <i class="fas fa-play me-2"></i>Run Migration
                                </button>
                            </div>
                        </form>
                        
                    <?php elseif ($migrationCompleted): ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle me-2"></i>Migration Complete!</h5>
                            <p>Your system has been successfully upgraded. You can now:</p>
                            <ul>
                                <li>Manage age-based membership plans</li>
                                <li>Set up martial arts classes with age restrictions</li>
                                <li>Process GoCardless payments</li>
                                <li>Handle special membership rules automatically</li>
                            </ul>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="memberships.php" class="btn btn-success">
                                <i class="fas fa-crown me-2"></i>Manage Memberships
                            </a>
                            <a href="classes.php" class="btn btn-primary">
                                <i class="fas fa-dumbbell me-2"></i>Manage Classes
                            </a>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </div>
                        
                    <?php else: ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle me-2"></i>System Up to Date</h5>
                            <p>Your system already supports the new martial arts membership structure. No migration is needed.</p>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="memberships.php" class="btn btn-success">
                                <i class="fas fa-crown me-2"></i>Manage Memberships
                            </a>
                            <a href="classes.php" class="btn btn-primary">
                                <i class="fas fa-dumbbell me-2"></i>Manage Classes
                            </a>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>
            
            <?php if ($needsMigration): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-list me-2"></i>New Membership Structure Preview</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Adults (15+ years)</h6>
                            <ul>
                                <li>£85 - Unlimited classes</li>
                                <li>£65 - Basic (2 classes/week)</li>
                                <li>£40 - Beginner deal (12 weeks max)</li>
                            </ul>
                            
                            <h6>Senior School (11-15 years)</h6>
                            <ul>
                                <li>£50 - Unlimited classes</li>
                                <li>£30 - Basic (1 class/week)</li>
                                <li>£10 - PAYG Sparring (invitation only)</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Juniors (7-11 years)</h6>
                            <ul>
                                <li>£50 - Unlimited classes</li>
                                <li>£30 - Basic (1 class/week)</li>
                            </ul>
                            
                            <h6>Infants (4-6 years)</h6>
                            <ul>
                                <li>£20 - 1 class per week</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Membership Migration';
include 'templates/header.php';
echo $content;
include 'templates/footer.php';
?> 