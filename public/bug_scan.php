<?php
/**
 * Bug Scanning Script for Membership System
 * Identifies common issues and potential bugs
 */

require_once __DIR__ . '/../config/user_auth.php';
require_once __DIR__ . '/../config/membership_functions.php';
require_once __DIR__ . '/api/db.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bug Scan Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .bug-critical { color: #dc3545; font-weight: bold; }
        .bug-warning { color: #ffc107; font-weight: bold; }
        .bug-success { color: #28a745; font-weight: bold; }
        .bug-info { color: #17a2b8; font-weight: bold; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">🐛 Bug Scan Report</h1>
        
        <?php
        $bugs = [];
        $warnings = [];
        $info = [];
        
        try {
            $pdo = connectUserDB();
            
            // Check 1: Database integrity
            echo "<div class='card mb-3'><div class='card-header'><h5>Database Integrity Check</h5></div><div class='card-body'>";
            
            // Check for orphaned records
            $stmt = $pdo->query("
                SELECT COUNT(*) as count 
                FROM user_memberships um 
                LEFT JOIN users u ON um.user_id = u.id 
                WHERE u.id IS NULL
            ");
            $orphanedMemberships = $stmt->fetchColumn();
            if ($orphanedMemberships > 0) {
                $bugs[] = "Found $orphanedMemberships orphaned membership records (memberships without users)";
            }
            
            $stmt = $pdo->query("
                SELECT COUNT(*) as count 
                FROM user_memberships um 
                LEFT JOIN membership_plans mp ON um.plan_id = mp.id 
                WHERE mp.id IS NULL
            ");
            $orphanedPlans = $stmt->fetchColumn();
            if ($orphanedPlans > 0) {
                $bugs[] = "Found $orphanedPlans memberships with invalid plan references";
            }
            
            $stmt = $pdo->query("
                SELECT COUNT(*) as count 
                FROM bookings b 
                LEFT JOIN users u ON b.user_id = u.id 
                WHERE u.id IS NULL
            ");
            $orphanedBookings = $stmt->fetchColumn();
            if ($orphanedBookings > 0) {
                $bugs[] = "Found $orphanedBookings orphaned booking records";
            }
            
            if (count($bugs) == 0) {
                echo "<p class='bug-success'>✓ No orphaned records found</p>";
            } else {
                foreach ($bugs as $bug) {
                    echo "<p class='bug-critical'>✗ $bug</p>";
                }
            }
            
            echo "</div></div>";
            
            // Check 2: Membership Logic Issues
            echo "<div class='card mb-3'><div class='card-header'><h5>Membership Logic Check</h5></div><div class='card-body'>";
            $membershipWarnings = [];
            
            // Check for users with multiple active memberships
            $stmt = $pdo->query("
                SELECT user_id, COUNT(*) as count 
                FROM user_memberships 
                WHERE status = 'active' 
                AND end_date >= CURDATE() 
                GROUP BY user_id 
                HAVING count > 1
            ");
            $multiMemberships = $stmt->fetchAll();
            if (count($multiMemberships) > 0) {
                $membershipWarnings[] = "Found " . count($multiMemberships) . " users with multiple active memberships";
                foreach ($multiMemberships as $user) {
                    $membershipWarnings[] = "User ID {$user['user_id']} has {$user['count']} active memberships";
                }
            }
            
            // Check for expired memberships still marked as active
            $stmt = $pdo->query("
                SELECT COUNT(*) as count 
                FROM user_memberships 
                WHERE status = 'active' 
                AND end_date < CURDATE()
            ");
            $expiredActive = $stmt->fetchColumn();
            if ($expiredActive > 0) {
                $membershipWarnings[] = "Found $expiredActive expired memberships still marked as active";
            }
            
            // Check for bookings without proper membership cycle
            $stmt = $pdo->query("
                SELECT COUNT(*) as count 
                FROM bookings 
                WHERE membership_cycle IS NULL 
                OR membership_cycle = ''
            ");
            $invalidCycles = $stmt->fetchColumn();
            if ($invalidCycles > 0) {
                $membershipWarnings[] = "Found $invalidCycles bookings without proper membership cycle tracking";
            }
            
            if (empty($membershipWarnings)) {
                echo "<p class='bug-success'>✓ No membership logic issues found</p>";
            } else {
                foreach ($membershipWarnings as $warning) {
                    echo "<p class='bug-warning'>⚠ $warning</p>";
                }
                $warnings = array_merge($warnings, $membershipWarnings);
            }
            
            echo "</div></div>";
            
            // Check 3: Free Trial Issues
            echo "<div class='card mb-3'><div class='card-header'><h5>Free Trial Check</h5></div><div class='card-body'>";
            $trialWarnings = [];
            $trialBugs = [];
            
            // Check for users marked as used trial but no trial bookings
            $stmt = $pdo->query("
                SELECT u.id, u.email 
                FROM users u 
                WHERE u.free_trial_used = 1 
                AND NOT EXISTS (
                    SELECT 1 FROM bookings b 
                    WHERE b.user_id = u.id AND b.is_free_trial = 1
                )
            ");
            $trialMismatch = $stmt->fetchAll();
            if (count($trialMismatch) > 0) {
                $trialWarnings[] = "Found " . count($trialMismatch) . " users marked as trial used but no trial bookings found";
            }
            
            // Check for trial bookings but user not marked as used
            $stmt = $pdo->query("
                SELECT DISTINCT b.user_id, u.email 
                FROM bookings b 
                JOIN users u ON b.user_id = u.id 
                WHERE b.is_free_trial = 1 
                AND u.free_trial_used = 0
            ");
            $trialNotMarked = $stmt->fetchAll();
            if (count($trialNotMarked) > 0) {
                $trialWarnings[] = "Found " . count($trialNotMarked) . " users with trial bookings but not marked as trial used";
            }
            
            // Check for multiple trial bookings per user
            $stmt = $pdo->query("
                SELECT user_id, COUNT(*) as count 
                FROM bookings 
                WHERE is_free_trial = 1 
                GROUP BY user_id 
                HAVING count > 1
            ");
            $multiTrials = $stmt->fetchAll();
            if (count($multiTrials) > 0) {
                $trialBugs[] = "Found " . count($multiTrials) . " users with multiple free trial bookings";
            }
            
            if (empty($trialWarnings) && empty($trialBugs)) {
                echo "<p class='bug-success'>✓ No free trial issues found</p>";
            } else {
                foreach ($trialBugs as $bug) {
                    echo "<p class='bug-critical'>✗ $bug</p>";
                }
                foreach ($trialWarnings as $warning) {
                    echo "<p class='bug-warning'>⚠ $warning</p>";
                }
                $bugs = array_merge($bugs, $trialBugs);
                $warnings = array_merge($warnings, $trialWarnings);
            }
            
            echo "</div></div>";
            
            // Check 4: Monthly Limit Enforcement
            echo "<div class='card mb-3'><div class='card-header'><h5>Monthly Limit Check</h5></div><div class='card-body'>";
            
            // Check for users exceeding their monthly limits
            $stmt = $pdo->query("
                SELECT 
                    u.id, u.email, mp.name as plan_name, 
                    mp.monthly_class_limit, 
                    COUNT(b.id) as bookings_count,
                    b.membership_cycle
                FROM users u
                JOIN user_memberships um ON u.id = um.user_id
                JOIN membership_plans mp ON um.plan_id = mp.id
                JOIN bookings b ON u.id = b.user_id
                WHERE um.status = 'active' 
                AND um.end_date >= CURDATE()
                AND mp.monthly_class_limit IS NOT NULL
                AND b.membership_cycle = DATE_FORMAT(NOW(), '%Y-%m')
                AND b.is_free_trial = 0
                GROUP BY u.id, b.membership_cycle
                HAVING bookings_count > mp.monthly_class_limit
            ");
            $limitExceeded = $stmt->fetchAll();
            if (count($limitExceeded) > 0) {
                $bugs[] = "Found " . count($limitExceeded) . " users who have exceeded their monthly class limits";
                foreach ($limitExceeded as $user) {
                    $bugs[] = "User {$user['email']} ({$user['plan_name']}) has {$user['bookings_count']} bookings but limit is {$user['monthly_class_limit']}";
                }
            }
            
            if (empty($bugs)) {
                echo "<p class='bug-success'>✓ No monthly limit violations found</p>";
            } else {
                foreach ($bugs as $bug) {
                    echo "<p class='bug-critical'>✗ $bug</p>";
                }
            }
            
            echo "</div></div>";
            
            // Check 5: File System Issues
            echo "<div class='card mb-3'><div class='card-header'><h5>File System Check</h5></div><div class='card-body'>";
            
            // Check upload directories
            $uploadDirs = [
                __DIR__ . '/../uploads/videos/',
                __DIR__ . '/../uploads/thumbnails/'
            ];
            
            foreach ($uploadDirs as $dir) {
                if (!is_dir($dir)) {
                    $warnings[] = "Upload directory missing: $dir";
                } elseif (!is_writable($dir)) {
                    $bugs[] = "Upload directory not writable: $dir";
                }
            }
            
            // Check for video records without files
            try {
                $stmt = $pdo->query("DESCRIBE videos");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (in_array('video_path', $columns)) {
                    $stmt = $pdo->query("SELECT id, video_path FROM videos WHERE video_path IS NOT NULL");
                    $videos = $stmt->fetchAll();
                    $missingVideos = 0;
                    foreach ($videos as $video) {
                        $fullPath = __DIR__ . '/../' . $video['video_path'];
                        if (!file_exists($fullPath)) {
                            $missingVideos++;
                        }
                    }
                    if ($missingVideos > 0) {
                        $warnings[] = "Found $missingVideos video records with missing files";
                    }
                } else {
                    echo "<p class='bug-info'>→ Video path column not found in videos table (this is normal if videos haven't been uploaded yet)</p>";
                }
            } catch (Exception $e) {
                echo "<p class='bug-warning'>⚠ Could not check video files: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            
            if (empty($warnings) && empty($bugs)) {
                echo "<p class='bug-success'>✓ No file system issues found</p>";
            } else {
                foreach ($bugs as $bug) {
                    echo "<p class='bug-critical'>✗ $bug</p>";
                }
                foreach ($warnings as $warning) {
                    echo "<p class='bug-warning'>⚠ $warning</p>";
                }
            }
            
            echo "</div></div>";
            
            // Check 6: API Response Issues
            echo "<div class='card mb-3'><div class='card-header'><h5>API Response Check</h5></div><div class='card-body'>";
            
            // Test booking API with invalid data
            $testResults = [];
            
            // Test 1: Booking without login
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/book.php');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['class_id' => 1]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 401) {
                $warnings[] = "Booking API should return 401 for unauthenticated requests, got $httpCode";
            }
            
            // Test 2: Classes API
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/classes.php');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                $bugs[] = "Classes API should return 200, got $httpCode";
            } else {
                $data = json_decode($response, true);
                if (!$data || !isset($data['success'])) {
                    $bugs[] = "Classes API returned invalid JSON structure";
                }
            }
            
            if (empty($warnings) && empty($bugs)) {
                echo "<p class='bug-success'>✓ No API issues found</p>";
            } else {
                foreach ($bugs as $bug) {
                    echo "<p class='bug-critical'>✗ $bug</p>";
                }
                foreach ($warnings as $warning) {
                    echo "<p class='bug-warning'>⚠ $warning</p>";
                }
            }
            
            echo "</div></div>";
            
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>Error running bug scan: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        
        // Summary
        $totalBugs = count($bugs);
        $totalWarnings = count($warnings);
        
        echo "<div class='card mb-3'><div class='card-header'><h5>Summary</h5></div><div class='card-body'>";
        
        if ($totalBugs === 0 && $totalWarnings === 0) {
            echo "<div class='alert alert-success'>";
            echo "<h6>🎉 No Critical Issues Found!</h6>";
            echo "<p>The membership system appears to be functioning correctly.</p>";
            echo "</div>";
        } else {
            echo "<div class='alert alert-warning'>";
            echo "<h6>⚠️ Issues Found</h6>";
            echo "<p><strong>Critical Bugs:</strong> $totalBugs</p>";
            echo "<p><strong>Warnings:</strong> $totalWarnings</p>";
            echo "<p>Please review and fix the issues listed above.</p>";
            echo "</div>";
        }
        
        echo "<h6>Recommended Testing Steps:</h6>";
        echo "<ul>";
        echo "<li><a href='test_membership_web.php' target='_blank'>Run the membership test suite</a></li>";
        echo "<li><a href='index.php' target='_blank'>Test booking as a new user (free trial)</a></li>";
        echo "<li><a href='user/membership.php' target='_blank'>Test membership purchase flow</a></li>";
        echo "<li><a href='user/videos.php' target='_blank'>Test video access restrictions</a></li>";
        echo "<li>Test monthly limit enforcement by booking multiple classes</li>";
        echo "</ul>";
        
        echo "</div></div>";
        ?>
        
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 