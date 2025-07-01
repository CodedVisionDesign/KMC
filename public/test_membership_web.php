<?php
/**
 * Web-accessible test script for membership system
 * Access via: http://localhost/testbook/public/test_membership_web.php
 */

// Include required files
require_once __DIR__ . '/../config/user_auth.php';
require_once __DIR__ . '/../config/membership_functions.php';
require_once __DIR__ . '/api/db.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership System Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-success { color: #28a745; }
        .test-error { color: #dc3545; }
        .test-info { color: #007bff; }
        .test-warning { color: #ffc107; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">🧪 Membership System Test Suite</h1>
        
        <?php
        $testResults = [];
        
        // Test 1: Database Connection
        echo "<div class='card mb-3'><div class='card-header'><h5>1. Database Connection Test</h5></div><div class='card-body'>";
        try {
            $pdo = connectUserDB();
            echo "<p class='test-success'>✓ Database connection successful</p>";
            $testResults['db_connection'] = true;
        } catch (Exception $e) {
            echo "<p class='test-error'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
            $testResults['db_connection'] = false;
        }
        echo "</div></div>";
        
        if (!$testResults['db_connection']) {
            echo "<div class='alert alert-danger'>Cannot continue tests without database connection.</div>";
            echo "</body></html>";
            exit;
        }
        
        // Test 2: Table Existence
        echo "<div class='card mb-3'><div class='card-header'><h5>2. Database Schema Test</h5></div><div class='card-body'>";
        $requiredTables = ['membership_plans', 'user_memberships', 'membership_payments', 'video_series', 'videos'];
        $allTablesExist = true;
        
        foreach ($requiredTables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                echo "<p class='test-success'>✓ Table '$table' exists with $count records</p>";
            } catch (Exception $e) {
                echo "<p class='test-error'>✗ Table '$table' missing: " . htmlspecialchars($e->getMessage()) . "</p>";
                $allTablesExist = false;
            }
        }
        $testResults['tables_exist'] = $allTablesExist;
        echo "</div></div>";
        
        // Test 3: Users Table Schema
        echo "<div class='card mb-3'><div class='card-header'><h5>3. Users Table Schema Test</h5></div><div class='card-body'>";
        try {
            $stmt = $pdo->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $hasFreeTrial = false;
            foreach ($columns as $column) {
                if ($column['Field'] === 'free_trial_used') {
                    $hasFreeTrial = true;
                    break;
                }
            }
            if ($hasFreeTrial) {
                echo "<p class='test-success'>✓ Users table has free_trial_used column</p>";
                $testResults['users_schema'] = true;
            } else {
                echo "<p class='test-error'>✗ Users table missing free_trial_used column</p>";
                $testResults['users_schema'] = false;
            }
        } catch (Exception $e) {
            echo "<p class='test-error'>✗ Error checking users table: " . htmlspecialchars($e->getMessage()) . "</p>";
            $testResults['users_schema'] = false;
        }
        echo "</div></div>";
        
        // Test 4: Bookings Table Schema
        echo "<div class='card mb-3'><div class='card-header'><h5>4. Bookings Table Schema Test</h5></div><div class='card-body'>";
        try {
            $stmt = $pdo->query("DESCRIBE bookings");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $hasMembershipCycle = false;
            $hasFreeTrial = false;
            foreach ($columns as $column) {
                if ($column['Field'] === 'membership_cycle') $hasMembershipCycle = true;
                if ($column['Field'] === 'is_free_trial') $hasFreeTrial = true;
            }
            if ($hasMembershipCycle && $hasFreeTrial) {
                echo "<p class='test-success'>✓ Bookings table has membership tracking columns</p>";
                $testResults['bookings_schema'] = true;
            } else {
                echo "<p class='test-error'>✗ Bookings table missing membership columns</p>";
                $testResults['bookings_schema'] = false;
            }
        } catch (Exception $e) {
            echo "<p class='test-error'>✗ Error checking bookings table: " . htmlspecialchars($e->getMessage()) . "</p>";
            $testResults['bookings_schema'] = false;
        }
        echo "</div></div>";
        
        // Test 5: Membership Plans
        echo "<div class='card mb-3'><div class='card-header'><h5>5. Membership Plans Test</h5></div><div class='card-body'>";
        try {
            $plans = getAvailableMembershipPlans();
            if (count($plans) > 0) {
                echo "<p class='test-success'>✓ Found " . count($plans) . " membership plans</p>";
                foreach ($plans as $plan) {
                    $limit = $plan['monthly_class_limit'] ? $plan['monthly_class_limit'] . ' classes' : 'Unlimited';
                    echo "<p class='test-info'>- {$plan['name']}: £{$plan['price']} ({$limit})</p>";
                }
                $testResults['membership_plans'] = true;
            } else {
                echo "<p class='test-error'>✗ No membership plans found</p>";
                $testResults['membership_plans'] = false;
            }
        } catch (Exception $e) {
            echo "<p class='test-error'>✗ Error getting membership plans: " . htmlspecialchars($e->getMessage()) . "</p>";
            $testResults['membership_plans'] = false;
        }
        echo "</div></div>";
        
        // Test 6: Test User Functions
        echo "<div class='card mb-3'><div class='card-header'><h5>6. User Functions Test</h5></div><div class='card-body'>";
        try {
            // Check if test user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute(['test@membership.com']);
            $testUser = $stmt->fetch();
            
            $testUserId = null;
            if ($testUser) {
                $testUserId = $testUser['id'];
                echo "<p class='test-info'>→ Using existing test user (ID: $testUserId)</p>";
            } else {
                // Create test user
                $testUserId = registerUser('Test', 'User', 'test@membership.com', 'password123', '1234567890');
                echo "<p class='test-success'>✓ Created test user (ID: $testUserId)</p>";
            }
            
            // Test free trial check
            $hasUsedTrial = hasUserUsedFreeTrial($testUserId);
            echo "<p class='test-info'>→ Free trial used: " . ($hasUsedTrial ? 'Yes' : 'No') . "</p>";
            
            // Test membership status
            $membershipStatus = getUserMembershipStatus($testUserId);
            echo "<p class='test-info'>→ Membership status:</p>";
            echo "<pre>" . htmlspecialchars(print_r($membershipStatus, true)) . "</pre>";
            
            // Test booking eligibility
            $canBook = canUserBookClass($testUserId);
            echo "<p class='test-info'>→ Can book class:</p>";
            echo "<pre>" . htmlspecialchars(print_r($canBook, true)) . "</pre>";
            
            $testResults['user_functions'] = true;
            
        } catch (Exception $e) {
            echo "<p class='test-error'>✗ Error testing user functions: " . htmlspecialchars($e->getMessage()) . "</p>";
            $testResults['user_functions'] = false;
        }
        echo "</div></div>";
        
        // Test Summary
        echo "<div class='card mb-3'><div class='card-header'><h5>7. Test Summary</h5></div><div class='card-body'>";
        
        $passedTests = array_filter($testResults);
        $totalTests = count($testResults);
        $passedCount = count($passedTests);
        
        if ($passedCount === $totalTests) {
            echo "<div class='alert alert-success'>";
            echo "<h6>🎉 All Tests Passed! ($passedCount/$totalTests)</h6>";
            echo "<p>The membership system appears to be working correctly.</p>";
            echo "</div>";
        } else {
            echo "<div class='alert alert-warning'>";
            echo "<h6>⚠️ Some Tests Failed ($passedCount/$totalTests passed)</h6>";
            echo "<p>Please review the failed tests above and fix any issues.</p>";
            echo "</div>";
        }
        
        echo "<h6>Next Steps for Manual Testing:</h6>";
        echo "<ul>";
        echo "<li><a href='index.php' target='_blank'>Test the main booking page</a></li>";
        echo "<li><a href='register.php' target='_blank'>Test user registration</a></li>";
        echo "<li><a href='login.php' target='_blank'>Test user login</a></li>";
        echo "<li><a href='user/membership.php' target='_blank'>Test membership page (requires login)</a></li>";
        echo "<li><a href='user/videos.php' target='_blank'>Test video access (requires login)</a></li>";
        echo "</ul>";
        
        echo "</div></div>";
        ?>
        
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 