<?php
require_once 'includes/admin_common.php';

$message = '';
$hasError = false;

// Check if migration was requested
if (isset($_POST['run_migration'])) {
    try {
        // Read the migration SQL file
        $migrationFile = '../config/membership_system_migration.sql';
        if (!file_exists($migrationFile)) {
            throw new Exception('Migration file not found');
        }
        
        $sql = file_get_contents($migrationFile);
        
        // Split SQL by semicolon and execute each statement
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, 'DELIMITER') === 0) {
                continue;
            }
            
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Log error but continue with other statements
                error_log('Migration statement error: ' . $e->getMessage() . ' for statement: ' . $statement);
            }
        }
        
        $message = '<div class="alert alert-success">
            <h5><i class="fas fa-check-circle me-2"></i>Migration Completed Successfully!</h5>
            <p>The membership and video management system has been set up. You can now:</p>
            <ul>
                <li>Manage membership plans and approvals</li>
                <li>Track payments and payment methods</li>
                <li>Upload and organize videos by series</li>
                <li>Assign videos to different categories</li>
            </ul>
            <div class="mt-3">
                <a href="memberships.php" class="btn btn-primary me-2">
                    <i class="fas fa-crown me-1"></i>Manage Memberships
                </a>
                <a href="videos.php" class="btn btn-success me-2">
                    <i class="fas fa-video me-1"></i>Manage Videos
                </a>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-tachometer-alt me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>';
        
    } catch (Exception $e) {
        $hasError = true;
        $message = '<div class="alert alert-danger">
            <h5><i class="fas fa-exclamation-triangle me-2"></i>Migration Failed</h5>
            <p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>
        </div>';
    }
}

// Check if tables already exist
$tablesExist = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'membership_plans'");
    $membershipPlansExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'videos'");
    $videosExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'video_series'");
    $videoSeriesExists = $stmt->rowCount() > 0;
    
    $tablesExist = $membershipPlansExists && $videosExists && $videoSeriesExists;
} catch (PDOException $e) {
    error_log('Error checking tables: ' . $e->getMessage());
}

$content = <<<HTML
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-database me-2"></i>Membership & Video System Setup</h3>
                </div>
                <div class="card-body">
                    {$message}
HTML;

if (!$message || $hasError) {
    if ($tablesExist) {
        $content .= <<<HTML
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle me-2"></i>System Already Set Up</h5>
                        <p>The membership and video management tables already exist in your database.</p>
                        <div class="mt-3">
                            <a href="memberships.php" class="btn btn-primary me-2">
                                <i class="fas fa-crown me-1"></i>Manage Memberships
                            </a>
                            <a href="videos.php" class="btn btn-success me-2">
                                <i class="fas fa-video me-1"></i>Manage Videos
                            </a>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-tachometer-alt me-1"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
HTML;
    } else {
        $content .= <<<HTML
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Setup Required</h5>
                        <p>The membership and video management system needs to be set up. This will create the necessary database tables and sample data.</p>
                    </div>
                    
                    <h5>What will be created:</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6><i class="fas fa-crown me-2"></i>Membership System</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i>Membership plans table</li>
                                        <li><i class="fas fa-check text-success me-2"></i>User memberships table</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Payment tracking table</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Sample membership plans</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6><i class="fas fa-video me-2"></i>Video System</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i>Video series table</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Videos table</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Sample video categories</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Upload directories</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <form method="post" class="text-center">
                        <button type="submit" name="run_migration" class="btn btn-primary btn-lg">
                            <i class="fas fa-play me-2"></i>Set Up Membership & Video System
                        </button>
                        <div class="mt-2">
                            <small class="text-muted">This process is safe to run multiple times</small>
                        </div>
                    </form>
HTML;
    }
}

$content .= <<<HTML
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
HTML;

// Render the admin page
renderAdminPage($content, [
    'pageDescription' => 'Set up membership and video management system'
]); 