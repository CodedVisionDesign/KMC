<?php
/**
 * Data Cleanup Script
 * Fixes orphaned records, duplicate memberships, and other data integrity issues
 */

require_once '../api/db.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Cleanup Script</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .log-output {
            background-color: #1a1a1a;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            padding: 15px;
            border-radius: 5px;
            max-height: 500px;
            overflow-y: auto;
        }
        .success { color: #00ff00; }
        .warning { color: #ffcc00; }
        .error { color: #ff4444; }
        .info { color: #00ccff; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">🧹 Data Cleanup Script</h1>
                
                <?php if (isset($_POST['run_cleanup'])): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>Cleanup Log</h3>
                        </div>
                        <div class="card-body">
                            <div class="log-output">
                                <?php
                                echo "<span class='success'>Starting data cleanup...</span><br>";
                                
                                try {
                                    $pdo->beginTransaction();
                                    
                                    $totalFixed = 0;
                                    
                                    // 1. Fix orphaned booking records
                                    echo "<br><span class='info'>1. Cleaning up orphaned booking records...</span><br>";
                                    
                                    $stmt = $pdo->query("
                                        SELECT b.id, b.name, b.email 
                                        FROM bookings b 
                                        LEFT JOIN users u ON b.user_id = u.id 
                                        WHERE u.id IS NULL
                                    ");
                                    $orphanedBookings = $stmt->fetchAll();
                                    
                                    if (count($orphanedBookings) > 0) {
                                        echo "<span class='warning'>Found " . count($orphanedBookings) . " orphaned booking records</span><br>";
                                        
                                        foreach ($orphanedBookings as $booking) {
                                            echo "Removing booking ID {$booking['id']} for {$booking['name']} ({$booking['email']})<br>";
                                        }
                                        
                                        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id IN (" . implode(',', array_column($orphanedBookings, 'id')) . ")");
                                        $stmt->execute();
                                        
                                        echo "<span class='success'>✓ Removed " . count($orphanedBookings) . " orphaned booking records</span><br>";
                                        $totalFixed += count($orphanedBookings);
                                    } else {
                                        echo "<span class='success'>✓ No orphaned booking records found</span><br>";
                                    }
                                    
                                    // 2. Fix duplicate active memberships
                                    echo "<br><span class='info'>2. Fixing duplicate active memberships...</span><br>";
                                    
                                    $stmt = $pdo->query("
                                        SELECT user_id, COUNT(*) as count 
                                        FROM user_memberships 
                                        WHERE status = 'active' 
                                        AND end_date >= CURDATE() 
                                        GROUP BY user_id 
                                        HAVING count > 1
                                    ");
                                    $duplicateUsers = $stmt->fetchAll();
                                    
                                    if (count($duplicateUsers) > 0) {
                                        echo "<span class='warning'>Found " . count($duplicateUsers) . " users with multiple active memberships</span><br>";
                                        
                                        foreach ($duplicateUsers as $user) {
                                            echo "Processing user ID {$user['user_id']} with {$user['count']} active memberships<br>";
                                            
                                            // Get all active memberships for this user, ordered by start date (keep the latest)
                                            $stmt = $pdo->prepare("
                                                SELECT id, plan_id, start_date, end_date 
                                                FROM user_memberships 
                                                WHERE user_id = ? 
                                                AND status = 'active' 
                                                AND end_date >= CURDATE() 
                                                ORDER BY start_date DESC
                                            ");
                                            $stmt->execute([$user['user_id']]);
                                            $memberships = $stmt->fetchAll();
                                            
                                            // Keep the first (latest) membership, deactivate the rest
                                            $kept = array_shift($memberships);
                                            echo "Keeping membership ID {$kept['id']} (started {$kept['start_date']})<br>";
                                            
                                            foreach ($memberships as $membership) {
                                                echo "Deactivating membership ID {$membership['id']} (started {$membership['start_date']})<br>";
                                                $stmt = $pdo->prepare("UPDATE user_memberships SET status = 'superseded' WHERE id = ?");
                                                $stmt->execute([$membership['id']]);
                                                $totalFixed++;
                                            }
                                        }
                                        
                                        echo "<span class='success'>✓ Fixed duplicate memberships for " . count($duplicateUsers) . " users</span><br>";
                                    } else {
                                        echo "<span class='success'>✓ No duplicate active memberships found</span><br>";
                                    }
                                    
                                    // 3. Fix bookings without membership cycle tracking
                                    echo "<br><span class='info'>3. Fixing bookings without membership cycle tracking...</span><br>";
                                    
                                    $stmt = $pdo->query("
                                        SELECT b.id, b.user_id, c.date 
                                        FROM bookings b 
                                        JOIN classes c ON b.class_id = c.id 
                                        WHERE b.membership_cycle IS NULL 
                                        OR b.membership_cycle = ''
                                    ");
                                    $invalidCycles = $stmt->fetchAll();
                                    
                                    if (count($invalidCycles) > 0) {
                                        echo "<span class='warning'>Found " . count($invalidCycles) . " bookings without proper membership cycle</span><br>";
                                        
                                        foreach ($invalidCycles as $booking) {
                                            $cycle = date('Y-m', strtotime($booking['date']));
                                            echo "Setting membership cycle for booking ID {$booking['id']} to {$cycle}<br>";
                                            
                                            $stmt = $pdo->prepare("UPDATE bookings SET membership_cycle = ? WHERE id = ?");
                                            $stmt->execute([$cycle, $booking['id']]);
                                            $totalFixed++;
                                        }
                                        
                                        echo "<span class='success'>✓ Fixed membership cycle for " . count($invalidCycles) . " bookings</span><br>";
                                    } else {
                                        echo "<span class='success'>✓ All bookings have proper membership cycle tracking</span><br>";
                                    }
                                    
                                    $pdo->commit();
                                    
                                    echo "<br><span class='success'>==== CLEANUP COMPLETED ====</span><br>";
                                    echo "<span class='success'>Total issues fixed: $totalFixed</span><br>";
                                    
                                } catch (Exception $e) {
                                    $pdo->rollback();
                                    echo "<br><span class='error'>ERROR: " . htmlspecialchars($e->getMessage()) . "</span><br>";
                                    echo "<span class='error'>All changes have been rolled back</span><br>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="../bug_scan.php" class="btn btn-primary">Run Bug Scan Again</a>
                        <a href="../test_membership_web.php" class="btn btn-success">Run Test Suite</a>
                    </div>
                    
                <?php else: ?>
                    
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> Warning</h5>
                        <p>This script will fix data integrity issues in your database. It will:</p>
                        <ul>
                            <li>Remove orphaned booking records</li>
                            <li>Fix duplicate active memberships (keep latest, mark others as superseded)</li>
                            <li>Add missing membership cycle tracking to bookings</li>
                        </ul>
                        <p><strong>This operation uses database transactions for safety.</strong></p>
                    </div>
                    
                    <form method="POST">
                        <button type="submit" name="run_cleanup" class="btn btn-danger btn-lg">
                            <i class="fas fa-broom"></i> Run Data Cleanup
                        </button>
                        <a href="../bug_scan.php" class="btn btn-secondary btn-lg ms-3">
                            <i class="fas fa-search"></i> Run Bug Scan First
                        </a>
                    </form>
                    
                <?php endif; ?>
                
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 