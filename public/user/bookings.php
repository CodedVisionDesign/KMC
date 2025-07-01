<?php
require_once __DIR__ . '/../../config/user_auth.php';
require_once __DIR__ . '/../../config/security.php';

$pageTitle = 'My Bookings';

// Ensure user is logged in
if (!isUserLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$userInfo = getUserInfo();

// Get user's bookings from database
$upcomingBookings = [];
$pastBookings = [];
$allBookings = [];

try {
    // Use the same database connection as other parts of the system
    require_once __DIR__ . '/../api/db.php';
    
    // Get all bookings with class and instructor details
    // Handle both user_id bookings (for logged-in users) and email-based bookings (for legacy/guest bookings)
    $stmt = $pdo->prepare("
        SELECT b.id as booking_id, b.created_at,
               c.id as class_id, c.name as class_name, c.description, c.date, c.time, c.capacity,
               CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
               i.email as instructor_email,
               (SELECT COUNT(*) FROM bookings WHERE class_id = c.id) as total_bookings
        FROM bookings b
        JOIN classes c ON b.class_id = c.id
        LEFT JOIN instructors i ON c.instructor_id = i.id
        WHERE (b.user_id = ? OR (b.user_id IS NULL AND b.email = ?))
        ORDER BY c.date DESC, c.time DESC
    ");
    $stmt->execute([$userInfo['id'], $userInfo['email']]);
    $allBookings = $stmt->fetchAll();
    
    // Separate into upcoming and past bookings
    $currentDate = date('Y-m-d');
    foreach ($allBookings as $booking) {
        if ($booking['date'] >= $currentDate) {
            $upcomingBookings[] = $booking;
        } else {
            $pastBookings[] = $booking;
        }
    }
    
    // Sort upcoming bookings by date ascending (next first)
    usort($upcomingBookings, function($a, $b) {
        $dateCompare = strtotime($a['date'] . ' ' . $a['time']) - strtotime($b['date'] . ' ' . $b['time']);
        return $dateCompare;
    });
    
} catch (Exception $e) {
    error_log('Error fetching user bookings for user ID ' . $userInfo['id'] . ' (email: ' . $userInfo['email'] . '): ' . $e->getMessage());
    $error_message = 'Unable to load your bookings at this time. Please try again later.';
    
    // For debugging - show more detailed error in development
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $error_message .= ' Debug: ' . $e->getMessage();
    }
}

include 'header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-calendar-check me-2"></i>My Bookings</h2>
            <a href="../index.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Book New Class
            </a>
        </div>
    </div>
</div>

<?php if (isset($error_message)): ?>
<div class="row">
    <div class="col-12">
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Booking Statistics -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <i class="fas fa-calendar-plus fa-2x mb-2"></i>
                <h4><?php echo count($upcomingBookings); ?></h4>
                <p class="mb-0">Upcoming Classes</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <h4><?php echo count($pastBookings); ?></h4>
                <p class="mb-0">Completed Classes</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                <h4><?php echo count($allBookings); ?></h4>
                <p class="mb-0">Total Bookings</p>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming Bookings -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-calendar-plus me-2"></i>Upcoming Classes</h5>
            </div>
            <div class="card-body">
                <?php if (empty($upcomingBookings)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No upcoming bookings</h5>
                        <p class="text-muted">Ready to book your next class?</p>
                        <a href="../index.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Browse Classes
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th>Date & Time</th>
                                    <th>Instructor</th>
                                    <th>Capacity</th>
                                    <th>Booked</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcomingBookings as $booking): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($booking['class_name']); ?></strong>
                                            <?php if (!empty($booking['description'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($booking['description']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('M d, Y', strtotime($booking['date'])); ?>
                                            <br>
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('g:i A', strtotime($booking['time'])); ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($booking['instructor_name'])): ?>
                                                <i class="fas fa-user-tie me-1"></i>
                                                <?php echo htmlspecialchars($booking['instructor_name']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">TBA</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-users me-1"></i>
                                            <?php echo $booking['capacity']; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo $booking['total_bookings']; ?> booked
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="cancelBooking(<?php echo $booking['booking_id']; ?>, '<?php echo htmlspecialchars($booking['class_name']); ?>')">
                                                <i class="fas fa-times me-1"></i>Cancel
                                            </button>
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

<!-- Past Bookings -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history me-2"></i>Past Classes</h5>
            </div>
            <div class="card-body">
                <?php if (empty($pastBookings)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No past bookings</h5>
                        <p class="text-muted">Your completed classes will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th>Date & Time</th>
                                    <th>Instructor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pastBookings as $booking): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($booking['class_name']); ?></strong>
                                        </td>
                                        <td>
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('M d, Y', strtotime($booking['date'])); ?>
                                            <br>
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('g:i A', strtotime($booking['time'])); ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($booking['instructor_name'])): ?>
                                                <i class="fas fa-user-tie me-1"></i>
                                                <?php echo htmlspecialchars($booking['instructor_name']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">TBA</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Completed
                                            </span>
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

<!-- Cancel Booking Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelModalLabel">Cancel Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel your booking for <strong id="cancelClassName"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    This action cannot be undone.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Booking</button>
                <button type="button" class="btn btn-danger" id="confirmCancel">
                    <i class="fas fa-times me-2"></i>Cancel Booking
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentBookingId = null;

function cancelBooking(bookingId, className) {
    currentBookingId = bookingId;
    document.getElementById('cancelClassName').textContent = className;
    
    const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}

document.getElementById('confirmCancel').addEventListener('click', function() {
    if (currentBookingId) {
        // Show loading state
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cancelling...';
        this.disabled = true;
        
        // Send cancel request
        fetch('../api/cancel_booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                booking_id: currentBookingId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to show updated bookings
                location.reload();
            } else {
                alert('Error cancelling booking: ' + (data.message || 'Unknown error'));
                // Reset button
                this.innerHTML = '<i class="fas fa-times me-2"></i>Cancel Booking';
                this.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error cancelling booking. Please try again.');
            // Reset button
            this.innerHTML = '<i class="fas fa-times me-2"></i>Cancel Booking';
            this.disabled = false;
        });
    }
});
</script>

<?php include 'footer.php'; ?> 