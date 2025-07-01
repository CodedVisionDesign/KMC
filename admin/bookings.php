<?php
require_once 'includes/admin_common.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'cancel_booking') {
        $booking_id = intval($_POST['booking_id']);
        if ($booking_id) {
            try {
                // Get booking details before deletion for confirmation message
                $stmt = $pdo->prepare('
                    SELECT b.*, c.name as class_name, c.date, c.time 
                    FROM bookings b 
                    JOIN classes c ON b.class_id = c.id 
                    WHERE b.id = ?
                ');
                $stmt->execute([$booking_id]);
                $booking = $stmt->fetch();
                
                if ($booking) {
                    // Delete the booking
                    $stmt = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
                    $stmt->execute([$booking_id]);
                    $success = "Booking cancelled successfully! " . htmlspecialchars($booking['name']) . " has been removed from " . htmlspecialchars($booking['class_name']) . ".";
                } else {
                    $error = "Booking not found.";
                }
            } catch (Exception $e) {
                $error = "Error cancelling booking: " . $e->getMessage();
            }
        }
    } elseif ($action === 'cancel_class') {
        $class_id = intval($_POST['class_id']);
        if ($class_id) {
            try {
                // Get class details and booking count before deletion
                $stmt = $pdo->prepare('
                    SELECT c.name as class_name, c.date, c.time, COUNT(b.id) as booking_count
                    FROM classes c 
                    LEFT JOIN bookings b ON c.id = b.class_id 
                    WHERE c.id = ?
                    GROUP BY c.id
                ');
                $stmt->execute([$class_id]);
                $classInfo = $stmt->fetch();
                
                if ($classInfo) {
                    // Delete all bookings for this class
                    $stmt = $pdo->prepare('DELETE FROM bookings WHERE class_id = ?');
                    $stmt->execute([$class_id]);
                    
                    $bookingCount = $classInfo['booking_count'];
                    $className = $classInfo['class_name'];
                    $success = "All bookings cancelled successfully! {$bookingCount} student(s) have been removed from " . htmlspecialchars($className) . ".";
                } else {
                    $error = "Class not found.";
                }
            } catch (Exception $e) {
                $error = "Error cancelling class bookings: " . $e->getMessage();
            }
        }
    }
}

$selectedClass = isset($_GET['class']) ? intval($_GET['class']) : 0;

// Get all classes for filter dropdown
try {
    $stmt = $pdo->query('SELECT id, name, date, time FROM classes ORDER BY date, time');
    $classes = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Failed to load classes for bookings filter: ' . $e->getMessage());
    $classes = [];
}

// Get bookings with user information
try {
    if ($selectedClass > 0) {
        $stmt = $pdo->prepare('
            SELECT b.*, c.name as class_name, c.date, c.time, c.capacity,
                   COALESCE(u.first_name, SUBSTRING_INDEX(b.name, " ", 1)) as first_name,
                   COALESCE(u.last_name, SUBSTRING_INDEX(b.name, " ", -1)) as last_name,
                   COALESCE(u.email, b.email) as user_email, 
                   u.phone, COALESCE(u.status, "unknown") as user_status,
                   (SELECT COUNT(*) FROM bookings WHERE user_id = b.user_id OR (user_id IS NULL AND email = b.email)) as user_total_bookings
            FROM bookings b 
            JOIN classes c ON b.class_id = c.id 
            LEFT JOIN users u ON b.user_id = u.id
            WHERE c.id = ?
            ORDER BY b.created_at DESC
        ');
        $stmt->execute([$selectedClass]);
    } else {
        $stmt = $pdo->query('
            SELECT b.*, c.name as class_name, c.date, c.time, c.capacity,
                   COALESCE(u.first_name, SUBSTRING_INDEX(b.name, " ", 1)) as first_name,
                   COALESCE(u.last_name, SUBSTRING_INDEX(b.name, " ", -1)) as last_name,
                   COALESCE(u.email, b.email) as user_email, 
                   u.phone, COALESCE(u.status, "unknown") as user_status,
                   (SELECT COUNT(*) FROM bookings WHERE user_id = b.user_id OR (user_id IS NULL AND email = b.email)) as user_total_bookings
            FROM bookings b 
            JOIN classes c ON b.class_id = c.id 
            LEFT JOIN users u ON b.user_id = u.id
            ORDER BY c.date, c.time, b.created_at DESC
        ');
    }
    $bookings = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Failed to load bookings: ' . $e->getMessage());
    $bookings = [];
}

// Calculate statistics from database directly
$totalBookings = 0;
$upcomingBookings = 0;
$todayBookings = 0;
$currentDate = date('Y-m-d');

// Get total bookings directly from database
try {
    $stmt = $pdo->query('SELECT COUNT(*) FROM bookings');
    $totalBookings = $stmt->fetchColumn();
    
    // Debug: Log the total bookings count
    error_log("Bookings page - Total bookings from database: " . $totalBookings);
    
    // Get upcoming bookings
    $stmt = $pdo->prepare('
        SELECT COUNT(*) 
        FROM bookings b 
        JOIN classes c ON b.class_id = c.id 
        WHERE c.date >= ?
    ');
    $stmt->execute([$currentDate]);
    $upcomingBookings = $stmt->fetchColumn();
    
    // Get today's bookings
    $stmt = $pdo->prepare('
        SELECT COUNT(*) 
        FROM bookings b 
        JOIN classes c ON b.class_id = c.id 
        WHERE c.date = ?
    ');
    $stmt->execute([$currentDate]);
    $todayBookings = $stmt->fetchColumn();
} catch (Exception $e) {
    error_log('Failed to load booking statistics: ' . $e->getMessage());
    $totalBookings = count($bookings);
}

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
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Upcoming</h5>
                        <h3><?= $upcomingBookings ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-arrow-right fa-2x"></i>
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
                        <h5 class="card-title">Today's Classes</h5>
                        <h3><?= $todayBookings ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-calendar-day fa-2x"></i>
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
                        <h5 class="card-title">Selected Class</h5>
                        <h3><?= $selectedClass > 0 ? count($bookings) : 'All' ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-filter fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <form method="GET" class="d-flex">
            <select name="class" class="form-select me-2">
                <option value="0">All Classes</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?= $class['id'] ?>" <?= ($class['id'] == $selectedClass) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($class['name']) ?> - <?= $class['date'] ?> <?= $class['time'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter me-2"></i>Filter
            </button>
        </form>
    </div>
    <div class="col-md-6 text-end">
        <?php if ($selectedClass > 0): ?>
            <a href="bookings.php" class="btn btn-outline-secondary">
                <i class="fas fa-times me-2"></i>Clear Filter
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>
            <?= $selectedClass > 0 ? 'Students Booked for Selected Class' : 'All Class Bookings' ?>
        </h5>
        <?php if ($selectedClass > 0 && !empty($bookings)): ?>
            <?php 
            $classInfo = $bookings[0]; // Get class info from first booking
            ?>
            <button class="btn btn-sm btn-danger" 
                    onclick="cancelEntireClass(<?= $selectedClass ?>, '<?= htmlspecialchars($classInfo['class_name']) ?>', <?= count($bookings) ?>)"
                    title="Cancel all bookings for this class">
                <i class="fas fa-exclamation-triangle me-1"></i>Cancel Entire Class
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($bookings)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>No bookings found<?= $selectedClass > 0 ? ' for this class' : '' ?>.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Class Details</th>
                            <th>Student Information</th>
                            <th>Contact</th>
                            <th>Booking Details</th>
                            <th>Student Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $currentClass = '';
                        $classCount = [];
                        
                        // Group bookings by class
                        foreach ($bookings as $booking) {
                            $classKey = $booking['class_id'];
                            if (!isset($classCount[$classKey])) {
                                $classCount[$classKey] = 0;
                            }
                            $classCount[$classKey]++;
                        }
                        
                        foreach ($bookings as $booking): 
                            $classKey = $booking['class_id'];
                            $isNewClass = ($currentClass !== $classKey);
                            if ($isNewClass) {
                                $currentClass = $classKey;
                            }
                        ?>
                            <?php if ($isNewClass && $selectedClass == 0): ?>
                                <tr class="table-light">
                                    <td colspan="5">
                                        <strong><i class="fas fa-calendar me-2"></i><?= htmlspecialchars($booking['class_name']) ?></strong>
                                        <small class="text-muted ms-2"><?= date('M j, Y', strtotime($booking['date'])) ?> at <?= $booking['time'] ?></small>
                                        <span class="badge bg-info ms-2"><?= $classCount[$classKey] ?> bookings</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="cancelEntireClass(<?= $booking['class_id'] ?>, '<?= htmlspecialchars($booking['class_name']) ?>', <?= $classCount[$classKey] ?>)"
                                                title="Cancel all bookings for this class">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Cancel Class
                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td>
                                    <?php if ($selectedClass > 0): ?>
                                        <strong><?= htmlspecialchars($booking['class_name']) ?></strong>
                                        <br><small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i><?= date('M j, Y', strtotime($booking['date'])) ?>
                                            <i class="fas fa-clock ms-2 me-1"></i><?= $booking['time'] ?>
                                        </small>
                                        <br><small class="badge bg-secondary">Capacity: <?= $booking['capacity'] ?></small>
                                    <?php else: ?>
                                        <small class="text-muted ms-3">
                                            <i class="fas fa-user me-1"></i>Student booking
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?></strong>
                                    <br><small class="text-muted"><?= htmlspecialchars($booking['user_email']) ?></small>
                                    <br><small class="badge bg-info"><?= $booking['user_total_bookings'] ?> total bookings</small>
                                </td>
                                <td>
                                    <?php if ($booking['phone']): ?>
                                        <i class="fas fa-phone me-1"></i><?= htmlspecialchars($booking['phone']) ?>
                                    <?php else: ?>
                                        <small class="text-muted">No phone provided</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small>
                                        <strong>Booked:</strong><br>
                                        <?= date('M j, Y', strtotime($booking['created_at'])) ?><br>
                                        <span class="text-muted"><?= date('H:i', strtotime($booking['created_at'])) ?></span>
                                    </small>
                                    <?php if (!empty($booking['name']) && $booking['name'] !== ($booking['first_name'] . ' ' . $booking['last_name'])): ?>
                                        <br><small class="text-muted">Form name: <?= htmlspecialchars($booking['name']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $booking['user_status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($booking['user_status']) ?>
                                    </span>
                                    <?php if (strtotime($booking['date']) < strtotime('today')): ?>
                                        <br><small class="badge bg-secondary mt-1">Past class</small>
                                    <?php elseif ($booking['date'] === date('Y-m-d')): ?>
                                        <br><small class="badge bg-warning mt-1">Today</small>
                                    <?php else: ?>
                                        <br><small class="badge bg-success mt-1">Upcoming</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="cancelBooking(<?= $booking['id'] ?>, '<?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($booking['class_name'], ENT_QUOTES) ?>')"
                                            title="Cancel this booking">
                                        <i class="fas fa-times"></i> Cancel
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

<!-- Cancel Individual Booking Confirmation Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="cancel_booking">
                    <input type="hidden" name="booking_id" id="cancelBookingId">
                    <p>Are you sure you want to cancel the booking for:</p>
                    <div class="alert alert-warning">
                        <strong>Student:</strong> <span id="cancelStudentName"></span><br>
                        <strong>Class:</strong> <span id="cancelClassName"></span>
                    </div>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Booking</button>
                    <button type="submit" class="btn btn-danger">Cancel Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Entire Class Confirmation Modal -->
<div class="modal fade" id="cancelClassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Entire Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="cancel_class">
                    <input type="hidden" name="class_id" id="cancelClassId">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>WARNING: This will cancel ALL bookings for this class!</strong>
                    </div>
                    <p>You are about to cancel:</p>
                    <div class="alert alert-warning">
                        <strong>Class:</strong> <span id="cancelClassFullName"></span><br>
                        <strong>Total Bookings:</strong> <span id="cancelClassBookingCount"></span> students
                    </div>
                    <p class="text-danger">This action cannot be undone. All students will be removed from this class.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Class</button>
                    <button type="submit" class="btn btn-danger">Cancel Entire Class</button>
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
        'text' => 'Dashboard',
        'icon' => 'fas fa-tachometer-alt',
        'class' => 'btn btn-outline-secondary',
        'href' => 'dashboard.php'
    ],
    [
        'text' => 'Export Bookings',
        'icon' => 'fas fa-download',
        'class' => 'btn btn-outline-info',
        'onclick' => 'exportBookings()'
    ]
]);

$inlineJS = <<<JS
        function cancelBooking(bookingId, studentName, className) {
            try {
                // Check if modal elements exist
                const bookingIdElement = document.getElementById('cancelBookingId');
                const studentNameElement = document.getElementById('cancelStudentName');
                const classNameElement = document.getElementById('cancelClassName');
                const modalElement = document.getElementById('cancelModal');
                
                if (!bookingIdElement || !studentNameElement || !classNameElement || !modalElement) {
                    console.error('Modal elements not found');
                    alert('Error: Modal elements not found. Please refresh the page and try again.');
                    return;
                }
                
                bookingIdElement.value = bookingId;
                studentNameElement.textContent = studentName;
                classNameElement.textContent = className;
                
                var modal = new bootstrap.Modal(modalElement);
                modal.show();
            } catch (error) {
                console.error('Error in cancelBooking:', error);
                alert('Error opening cancel dialog. Please refresh the page and try again.');
            }
        }

        function cancelEntireClass(classId, className, bookingCount) {
            try {
                // Check if modal elements exist
                const classIdElement = document.getElementById('cancelClassId');
                const classNameElement = document.getElementById('cancelClassFullName');
                const bookingCountElement = document.getElementById('cancelClassBookingCount');
                const modalElement = document.getElementById('cancelClassModal');
                
                if (!classIdElement || !classNameElement || !bookingCountElement || !modalElement) {
                    console.error('Class modal elements not found');
                    alert('Error: Modal elements not found. Please refresh the page and try again.');
                    return;
                }
                
                classIdElement.value = classId;
                classNameElement.textContent = className;
                bookingCountElement.textContent = bookingCount;
                
                var modal = new bootstrap.Modal(modalElement);
                modal.show();
            } catch (error) {
                console.error('Error in cancelEntireClass:', error);
                alert('Error opening cancel class dialog. Please refresh the page and try again.');
            }
        }

        function exportBookings() {
            // Simple CSV export functionality
            const table = document.querySelector('table');
            if (!table) {
                alert('No data to export');
                return;
            }
            
            let csv = 'Class Name,Date,Time,Student Name,Email,Phone,Booking Date,Status\\n';
            
            const rows = table.querySelectorAll('tbody tr:not(.table-light)'); // Skip class header rows
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 6) {
                    try {
                        // Extract data from each cell (simplified)
                        const className = cells[0].querySelector('strong')?.textContent?.trim() || 'N/A';
                        const dateTime = cells[0].textContent.replace(className, '').trim();
                        const studentName = cells[1].querySelector('strong')?.textContent?.trim() || 'N/A';
                        const email = cells[1].querySelector('small')?.textContent?.trim() || 'N/A';
                        const phone = cells[2].textContent.trim().replace(/📞/g, '');
                        const bookingDate = cells[3].textContent.trim();
                        const status = cells[4].querySelector('.badge')?.textContent?.trim() || 'N/A';
                        
                        csv += `"\${className}","\${dateTime}","\${studentName}","\${email}","\${phone}","\${bookingDate}","\${status}"\\n`;
                    } catch (e) {
                        console.warn('Error processing row:', e);
                    }
                }
            });
            
            // Create and download file
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'bookings_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        }

        // Debug function to check if elements exist
        function checkModalElements() {
            const elements = [
                'cancelBookingId', 'cancelStudentName', 'cancelClassName', 'cancelModal',
                'cancelClassId', 'cancelClassFullName', 'cancelClassBookingCount', 'cancelClassModal'
            ];
            
            elements.forEach(id => {
                const element = document.getElementById(id);
                if (!element) {
                    console.error('Missing element:', id);
                } else {
                    console.log('Found element:', id);
                }
            });
        }

        // Run check when page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, checking modal elements...');
            checkModalElements();
        });
JS;

// Render the admin page
renderAdminPage($content, [
    'pageDescription' => 'View and manage class bookings from students',
    'headerActions' => $headerActions,
    'success' => $success ?? null,
    'error' => $error ?? null,
    'inlineJS' => $inlineJS
]); 