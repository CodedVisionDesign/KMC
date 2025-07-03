<?php
require_once 'includes/admin_common.php';

// Get statistics for dashboard
$classCount = 0;
$bookingCount = 0;
$instructorCount = 0;
$membershipCount = 0;
$pendingMemberships = 0;
$videoCount = 0;
$pendingPayments = 0;

try {
    // Count classes
    $stmt = $pdo->query("SELECT COUNT(*) FROM classes");
    $classCount = $stmt->fetchColumn();
    
    // Count bookings
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
    $bookingCount = $stmt->fetchColumn();
    
    // Debug: Log the total bookings count
    error_log("Dashboard - Total bookings from database: " . $bookingCount);
    
    // Count instructors (if table exists)
    $instructorsTableExists = false;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM instructors WHERE status = 'active'");
        $instructorCount = $stmt->fetchColumn();
        $instructorsTableExists = true;
    } catch (PDOException $e) {
        // Instructors table might not exist yet
        $instructorCount = 0;
        $instructorsTableExists = false;
    }
    
    // Count users/students
    $studentCount = 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $studentCount = $stmt->fetchColumn();
    } catch (PDOException $e) {
        $studentCount = 0;
    }
    
    // Count memberships
    $membershipTableExists = false;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM user_memberships WHERE status = 'active'");
        $membershipCount = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM user_memberships WHERE status = 'pending'");
        $pendingMemberships = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM membership_payments WHERE status = 'pending'");
        $pendingPayments = $stmt->fetchColumn();
        
        $membershipTableExists = true;
    } catch (PDOException $e) {
        $membershipCount = 0;
        $pendingMemberships = 0;
        $pendingPayments = 0;
        $membershipTableExists = false;
    }
    
    // Count videos
    $videoTableExists = false;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM videos WHERE status = 'active'");
        $videoCount = $stmt->fetchColumn();
        $videoTableExists = true;
    } catch (PDOException $e) {
        $videoCount = 0;
        $videoTableExists = false;
    }
    
} catch (PDOException $e) {
    error_log('Admin dashboard database error: ' . $e->getMessage());
    $error_html = '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

$instructorButton = $instructorsTableExists 
    ? '<a href="instructors.php" class="btn btn-warning">Manage Instructors</a>'
    : '<a href="setup_instructors.php" class="btn btn-outline-warning"><i class="fas fa-cog me-1"></i>Setup Instructors</a>';

$instructorQuickAction = $instructorsTableExists
    ? '<a href="instructors.php" class="btn btn-outline-warning">Add New Instructor</a>'
    : '<a href="setup_instructors.php" class="btn btn-outline-warning">Setup Instructors</a>';

$membershipButton = $membershipTableExists
    ? '<a href="memberships.php" class="btn btn-info">Manage Memberships</a>'
    : '<a href="setup_membership_video.php" class="btn btn-outline-info"><i class="fas fa-database me-1"></i>Setup System</a>';

$videoButton = $videoTableExists
    ? '<a href="videos.php" class="btn btn-secondary">Manage Videos</a>'
    : '<a href="setup_membership_video.php" class="btn btn-outline-secondary"><i class="fas fa-database me-1"></i>Setup System</a>';

// Alert for pending items
$pendingAlerts = '';
if ($pendingMemberships > 0) {
    $pendingAlerts .= '<div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>' . $pendingMemberships . '</strong> membership request(s) need approval.
        <a href="memberships.php" class="alert-link">Review now</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

if ($pendingPayments > 0) {
    $pendingAlerts .= '<div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-credit-card me-2"></i>
        <strong>' . $pendingPayments . '</strong> payment(s) pending confirmation.
        <a href="memberships.php?tab=payments" class="alert-link">Review now</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

$content = <<<HTML
{$pendingAlerts}

<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Classes</h5>
                        <h2>{$classCount}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-calendar-alt fa-2x"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="classes.php" class="btn btn-light btn-sm">
                        <i class="fas fa-cog me-1"></i>Manage Classes
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Instructors</h5>
                        <h2>{$instructorCount}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-tie fa-2x"></i>
                    </div>
                </div>
                <div class="mt-3">
                    {$instructorButton}
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-info text-white position-relative">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Active Memberships</h5>
                        <h2>{$membershipCount}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-crown fa-2x"></i>
                    </div>
                </div>
HTML;

if ($pendingMemberships > 0) {
    $content .= <<<HTML
                <div class="position-absolute top-0 end-0 mt-2 me-2">
                    <span class="badge bg-danger">{$pendingMemberships}</span>
                </div>
HTML;
}

$content .= <<<HTML
                <div class="mt-3">
                    {$membershipButton}
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Bookings</h5>
                        <h2>{$bookingCount}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-calendar-check fa-2x"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="bookings.php" class="btn btn-light btn-sm">
                        <i class="fas fa-cog me-1"></i>Manage Bookings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-secondary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Videos</h5>
                        <h2>{$videoCount}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-video fa-2x"></i>
                    </div>
                </div>
                <div class="mt-3">
                    {$videoButton}
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-dark text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Students</h5>
                        <h2>{$studentCount}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="students.php" class="btn btn-light btn-sm">
                        <i class="fas fa-cog me-1"></i>Manage Students
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-gradient" style="background: linear-gradient(45deg, #6f42c1, #e83e8c);">
            <div class="card-body text-white">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Pending Payments</h5>
                        <h2>{$pendingPayments}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-credit-card fa-2x"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="memberships.php?tab=payments" class="btn btn-light btn-sm">
                        <i class="fas fa-check me-1"></i>Review Payments
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card" style="background: linear-gradient(45deg, #fd7e14, #ffc107);">
            <div class="card-body text-white">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Trial System</h5>
                        <h6>Manage User Trials</h6>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-gift fa-2x"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="trial_management.php" class="btn btn-light btn-sm">
                        <i class="fas fa-cog me-1"></i>Manage Trials
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <a href="classes.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-plus me-2"></i>Add New Class
                        </a>
                    </div>
                    <div class="col-md-6 mb-2">
                        {$instructorQuickAction}
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="memberships.php" class="btn btn-outline-info w-100">
                            <i class="fas fa-crown me-2"></i>Review Memberships
                        </a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="videos.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-video me-2"></i>Manage Videos
                        </a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="students.php" class="btn btn-outline-dark w-100">
                            <i class="fas fa-user-plus me-2"></i>Manage Students
                        </a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="bookings.php" class="btn btn-outline-success w-100">
                            <i class="fas fa-calendar-check me-2"></i>View Recent Bookings
                        </a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <a href="trial_management.php" class="btn btn-outline-warning w-100">
                            <i class="fas fa-gift me-2"></i>Trial Management
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-link me-2"></i>System Links</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="../public/index.php" class="btn btn-outline-secondary" target="_blank">
                        <i class="fas fa-external-link-alt me-2"></i>View Public Site
                    </a>
                    <a href="../START_HERE.html" class="btn btn-outline-info" target="_blank">
                        <i class="fas fa-info-circle me-2"></i>Setup Guide
                    </a>
                    <a href="logout.php" class="btn btn-outline-danger">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;

// Render the admin page
renderAdminPage($content, [
    'pageDescription' => 'Overview of classes, instructors, students, bookings, memberships, and videos'
]);