<?php
// Include authentication and database functions
require_once __DIR__ . '/../../config/user_auth.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/membership_functions.php';

// Ensure user is logged in
if (!isUserLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$userInfo = getUserInfo();
$pageTitle = 'Dashboard';

// Get user statistics
try {
    $pdo = connectUserDB();
    
    // Get upcoming bookings count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as upcoming_count 
        FROM bookings b 
        JOIN classes c ON b.class_id = c.id 
        WHERE b.user_id = ? AND c.date >= CURDATE()
    ");
    $stmt->execute([$userInfo['id']]);
    $upcomingCount = $stmt->fetchColumn();
    
    // Get total bookings count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
    $stmt->execute([$userInfo['id']]);
    $totalBookings = $stmt->fetchColumn();
    
    // Get recent bookings
    $stmt = $pdo->prepare("
        SELECT c.name, c.date, c.time, b.created_at,
               CONCAT(i.first_name, ' ', i.last_name) as instructor_name
        FROM bookings b 
        JOIN classes c ON b.class_id = c.id 
        LEFT JOIN instructors i ON c.instructor_id = i.id
        WHERE b.user_id = ? 
        ORDER BY b.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$userInfo['id']]);
    $recentBookings = $stmt->fetchAll();
    
    // Get next upcoming class
    $stmt = $pdo->prepare("
        SELECT c.name, c.date, c.time, c.description,
               CONCAT(i.first_name, ' ', i.last_name) as instructor_name
        FROM bookings b 
        JOIN classes c ON b.class_id = c.id 
        LEFT JOIN instructors i ON c.instructor_id = i.id
        WHERE b.user_id = ? AND c.date >= CURDATE()
        ORDER BY c.date, c.time 
        LIMIT 1
    ");
    $stmt->execute([$userInfo['id']]);
    $nextClass = $stmt->fetch();
    
    // Check if user has completed health questionnaire
    $stmt = $pdo->prepare("SELECT health_questionnaire FROM users WHERE id = ?");
    $stmt->execute([$userInfo['id']]);
    $user = $stmt->fetch();
    $hasHealthInfo = !empty($user['health_questionnaire']);
    
    // Get membership status
    $membershipStatus = getUserMembershipStatusSimple($userInfo['id']);
    $hasActiveMembership = $membershipStatus && $membershipStatus['status'] === 'active';
    $hasFreeTrial = !hasUserUsedFreeTrial($userInfo['id']);
    $hasVideoAccess = userHasVideoAccess($userInfo['id']);
    
} catch (Exception $e) {
    error_log('Dashboard error: ' . $e->getMessage());
    $upcomingCount = 0;
    $totalBookings = 0;
    $recentBookings = [];
    $nextClass = null;
    $hasHealthInfo = false;
    $membershipStatus = null;
    $hasActiveMembership = false;
    $hasFreeTrial = false;
    $hasVideoAccess = false;
}

// Include header
include 'header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-body">
                <h2>Welcome back!</h2>
                <p>Manage your fitness journey and track your progress.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Statistics Cards -->
    <div class="col-md-3 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="display-4 text-primary mb-2">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h3 class="card-title"><?php echo $upcomingCount; ?></h3>
                <p class="text-muted mb-0">Upcoming Classes</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="display-4 text-success mb-2">
                    <i class="fas fa-dumbbell"></i>
                </div>
                <h3 class="card-title"><?php echo $totalBookings; ?></h3>
                <p class="text-muted mb-0">Total Classes</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="display-4 <?php echo $hasHealthInfo ? 'text-success' : 'text-warning'; ?> mb-2">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <h6 class="card-title"><?php echo $hasHealthInfo ? 'Complete' : 'Incomplete'; ?></h6>
                <p class="text-muted mb-0">Health Profile</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <?php if ($hasActiveMembership): ?>
                    <div class="display-4 text-success mb-2">
                        <i class="fas fa-crown"></i>
                    </div>
                    <h6 class="card-title">Active Member</h6>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($membershipStatus['plan_name']); ?></p>
                <?php elseif ($hasFreeTrial): ?>
                    <div class="display-4 text-info mb-2">
                        <i class="fas fa-gift"></i>
                    </div>
                    <h6 class="card-title">Free Trial</h6>
                    <p class="text-muted mb-0">Available</p>
                <?php else: ?>
                    <div class="display-4 text-warning mb-2">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h6 class="card-title">No Membership</h6>
                    <p class="text-muted mb-0"><a href="membership.php" class="text-decoration-none">Get Plan</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Next Class -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock me-2"></i>Next Class
                </h5>
            </div>
            <div class="card-body">
                <?php if ($nextClass): ?>
                    <h6 class="card-title"><?php echo htmlspecialchars($nextClass['name']); ?></h6>
                    <p class="text-muted mb-2"><?php echo htmlspecialchars($nextClass['description']); ?></p>
                    <div class="row text-sm">
                        <div class="col-6">
                            <strong><i class="fas fa-calendar me-1"></i> Date:</strong><br>
                            <?php echo date('M j, Y', strtotime($nextClass['date'])); ?>
                        </div>
                        <div class="col-6">
                            <strong><i class="fas fa-clock me-1"></i> Time:</strong><br>
                            <?php echo date('g:i A', strtotime($nextClass['time'])); ?>
                        </div>
                    </div>
                    <?php if ($nextClass['instructor_name']): ?>
                        <div class="mt-2">
                            <strong><i class="fas fa-user-tie me-1"></i> Instructor:</strong>
                            <?php echo htmlspecialchars($nextClass['instructor_name']); ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-calendar-plus fa-3x mb-3"></i>
                        <p>No upcoming classes booked.</p>
                        <a href="../index.php" class="btn btn-outline-primary">Book a Class</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="profile.php" class="btn btn-outline-primary">
                        <i class="fas fa-user-edit me-2"></i>Update Profile
                    </a>
                    <a href="health.php" class="btn btn-outline-success">
                        <i class="fas fa-heartbeat me-2"></i>Health Details
                    </a>
                    <a href="emergency.php" class="btn btn-outline-warning">
                        <i class="fas fa-phone me-2"></i>Emergency Contacts
                    </a>
                    <a href="membership.php" class="btn btn-outline-info">
                        <i class="fas fa-crown me-2"></i>My Membership
                    </a>
                    <?php if ($hasVideoAccess): ?>
                    <a href="videos.php" class="btn btn-outline-dark">
                        <i class="fas fa-play-circle me-2"></i>Member Videos
                    </a>
                    <?php endif; ?>
                    <a href="bookings.php" class="btn btn-outline-info">
                        <i class="fas fa-calendar-check me-2"></i>View All Bookings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>Recent Bookings
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recentBookings)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Instructor</th>
                                    <th>Booked</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBookings as $booking): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($booking['name']); ?></strong>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($booking['date'])); ?></td>
                                        <td><?php echo date('g:i A', strtotime($booking['time'])); ?></td>
                                        <td><?php echo htmlspecialchars($booking['instructor_name'] ?? 'TBA'); ?></td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y', strtotime($booking['created_at'])); ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="bookings.php" class="btn btn-outline-primary">
                            View All Bookings <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-calendar-times fa-3x mb-3"></i>
                        <p>No bookings yet. Start your fitness journey today!</p>
                        <a href="../index.php" class="btn btn-primary">Book Your First Class</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?> 