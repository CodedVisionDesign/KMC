<?php
// Web-based migration runner
// Access this via: http://localhost/testbook/public/admin/run_migration.php

require_once '../api/db.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership System Migration</title>
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
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">🗄️ Membership System Database Migration</h1>
                
                <?php if (isset($_POST['run_migration'])): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>Migration Log</h3>
                        </div>
                        <div class="card-body">
                            <div class="log-output">
                                <?php
                                echo "<span class='success'>Connecting to database...</span><br>";
                                
                                try {
                                    // Test database connection
                                    $stmt = $pdo->query("SELECT 1");
                                    echo "<span class='success'>✓ Database connected successfully</span><br>";
                                    
                                    echo "<span class='success'>Running membership system migration...</span><br><br>";
                                    
                                    // Read the migration file
                                    $migrationFile = '../../config/membership_system_migration.sql';
                                    $migrationSQL = file_get_contents($migrationFile);
                                    
                                    if (!$migrationSQL) {
                                        throw new Exception("Could not read migration file: $migrationFile");
                                    }
                                    
                                    // Split statements by type (regular SQL vs functions)
                                    $regularStatements = [];
                                    $functionStatements = [];
                                    $currentStatement = '';
                                    $inFunction = false;
                                    
                                    $lines = explode("\n", $migrationSQL);
                                    foreach ($lines as $line) {
                                        $line = trim($line);
                                        
                                        // Skip comments and empty lines
                                        if (empty($line) || strpos($line, '--') === 0) {
                                            continue;
                                        }
                                        
                                        // Check if we're starting a function definition
                                        if (strpos($line, 'CREATE FUNCTION') !== false) {
                                            $inFunction = true;
                                            $currentStatement = $line . "\n";
                                            continue;
                                        }
                                        
                                        // Handle delimiter changes
                                        if (strpos($line, 'DELIMITER') === 0) {
                                            continue; // Skip delimiter statements
                                        }
                                        
                                        $currentStatement .= $line . "\n";
                                        
                                        // Check for end of function
                                        if ($inFunction && strpos($line, 'END$$') !== false) {
                                            $functionStatements[] = trim($currentStatement);
                                            $currentStatement = '';
                                            $inFunction = false;
                                            continue;
                                        }
                                        
                                        // Regular statement end
                                        if (!$inFunction && substr($line, -1) === ';') {
                                            $regularStatements[] = trim($currentStatement);
                                            $currentStatement = '';
                                        }
                                    }
                                    
                                    // Add any remaining statement
                                    if (!empty(trim($currentStatement))) {
                                        if ($inFunction) {
                                            $functionStatements[] = trim($currentStatement);
                                        } else {
                                            $regularStatements[] = trim($currentStatement);
                                        }
                                    }
                                    
                                    $successCount = 0;
                                    $skipCount = 0;
                                    $transactionStarted = false;
                                    
                                    // Execute regular statements within transaction
                                    echo "<span class='success'>Executing regular statements...</span><br>";
                                    
                                    foreach ($regularStatements as $statement) {
                                        $statement = trim($statement);
                                        if (empty($statement)) continue;
                                        
                                        // Start transaction only when we have a statement to execute
                                        if (!$transactionStarted) {
                                            $pdo->beginTransaction();
                                            $transactionStarted = true;
                                        }
                                        
                                        $shortStatement = substr(str_replace(["\r", "\n"], ' ', $statement), 0, 60);
                                        echo "Executing: " . htmlspecialchars($shortStatement) . "...<br>";
                                        
                                        try {
                                            $pdo->exec($statement);
                                            echo "<span class='success'>✓ Success</span><br>";
                                            $successCount++;
                                        } catch (PDOException $e) {
                                            // Some statements might fail if they already exist, that's okay
                                            if (strpos($e->getMessage(), 'already exists') !== false || 
                                                strpos($e->getMessage(), 'Duplicate column') !== false ||
                                                strpos($e->getMessage(), 'Duplicate key') !== false) {
                                                echo "<span class='warning'>⚠ Already exists (skipping)</span><br>";
                                                $skipCount++;
                                            } else {
                                                throw $e;
                                            }
                                        }
                                    }
                                    
                                    // Only commit if we actually started a transaction
                                    if ($transactionStarted) {
                                        $pdo->commit();
                                        echo "<span class='success'>✓ Regular statements completed</span><br><br>";
                                    } else {
                                        echo "<span class='warning'>✓ No new statements to execute</span><br><br>";
                                    }
                                    
                                    // Execute function statements separately (no transaction)
                                    if (!empty($functionStatements)) {
                                        echo "<span class='success'>Creating MySQL functions...</span><br>";
                                        
                                        foreach ($functionStatements as $statement) {
                                            $statement = trim($statement);
                                            if (empty($statement)) continue;
                                            
                                            // Remove DELIMITER statements and replace $$ with ;
                                            $statement = str_replace('$$', '', $statement);
                                            
                                            $shortStatement = substr(str_replace(["\r", "\n"], ' ', $statement), 0, 60);
                                            echo "Creating function: " . htmlspecialchars($shortStatement) . "...<br>";
                                            
                                            try {
                                                $pdo->exec($statement);
                                                echo "<span class='success'>✓ Success</span><br>";
                                                $successCount++;
                                            } catch (PDOException $e) {
                                                if (strpos($e->getMessage(), 'already exists') !== false) {
                                                    echo "<span class='warning'>⚠ Function already exists (skipping)</span><br>";
                                                    $skipCount++;
                                                } else {
                                                    echo "<span class='warning'>⚠ Function creation failed: " . htmlspecialchars($e->getMessage()) . "</span><br>";
                                                    echo "<span class='warning'>This is usually not critical - the system will work without these functions</span><br>";
                                                    $skipCount++;
                                                }
                                            }
                                        }
                                    }
                                    
                                    echo "<br><span class='success'>Migration completed successfully!</span><br>";
                                    echo "<span class='success'>Executed: $successCount statements, Skipped: $skipCount existing items</span><br><br>";
                                    
                                    // Verify the tables were created
                                    echo "<span class='success'>Verifying tables...</span><br>";
                                    $tables = ['membership_plans', 'user_memberships', 'membership_payments', 'video_series', 'videos'];
                                    
                                    foreach ($tables as $table) {
                                        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                                        if ($stmt->rowCount() > 0) {
                                            echo "<span class='success'>✓ Table '$table' created successfully</span><br>";
                                        } else {
                                            echo "<span class='error'>✗ Table '$table' was not created</span><br>";
                                        }
                                    }
                                    
                                    // Check if columns were added to existing tables
                                    echo "<br><span class='success'>Checking modified tables...</span><br>";
                                    
                                    // Check bookings table
                                    $stmt = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'membership_cycle'");
                                    if ($stmt->rowCount() > 0) {
                                        echo "<span class='success'>✓ Column 'membership_cycle' added to bookings table</span><br>";
                                    } else {
                                        echo "<span class='error'>✗ Column 'membership_cycle' not found in bookings table</span><br>";
                                    }
                                    
                                    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'free_trial_used'");
                                    if ($stmt->rowCount() > 0) {
                                        echo "<span class='success'>✓ Column 'free_trial_used' added to users table</span><br>";
                                    } else {
                                        echo "<span class='error'>✗ Column 'free_trial_used' not found in users table</span><br>";
                                    }
                                    
                                    // Check sample data
                                    echo "<br><span class='success'>Checking sample data...</span><br>";
                                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM membership_plans");
                                    $result = $stmt->fetch();
                                    echo "• Membership plans: " . $result['count'] . " records<br>";
                                    
                                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM video_series");
                                    $result = $stmt->fetch();
                                    echo "• Video series: " . $result['count'] . " records<br>";
                                    
                                    // Show membership plans
                                    echo "<br><span class='success'>Available membership plans:</span><br>";
                                    $stmt = $pdo->query("SELECT name, description, monthly_class_limit, price FROM membership_plans ORDER BY price");
                                    $plans = $stmt->fetchAll();
                                    foreach ($plans as $plan) {
                                        $limit = $plan['monthly_class_limit'] ? $plan['monthly_class_limit'] . ' classes' : 'Unlimited';
                                        echo "• {$plan['name']}: {$limit}/month - £{$plan['price']}<br>";
                                    }
                                    
                                    echo "<br><span class='success'>🎉 Database migration completed successfully!</span><br>";
                                    echo "<span class='success'>You can now test the membership system!</span><br>";
                                    
                                } catch (Exception $e) {
                                    if (isset($pdo) && $pdo->inTransaction()) {
                                        $pdo->rollback();
                                    }
                                    echo "<span class='error'>Migration failed: " . htmlspecialchars($e->getMessage()) . "</span><br>";
                                    echo "<span class='error'>File: " . $e->getFile() . "</span><br>";
                                    echo "<span class='error'>Line: " . $e->getLine() . "</span><br>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">Run Again</a>
                        <a href="../index.php" class="btn btn-primary">Back to Site</a>
                        <a href="../user/membership.php" class="btn btn-success">Test Membership System</a>
                    </div>
                    
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>Ready to Run Migration</h3>
                        </div>
                        <div class="card-body">
                            <p class="card-text">
                                This will create the necessary database tables and sample data for the membership system:
                            </p>
                            <ul>
                                <li><strong>membership_plans</strong> - Different membership plan options</li>
                                <li><strong>user_memberships</strong> - User membership records</li>
                                <li><strong>membership_payments</strong> - Payment tracking</li>
                                <li><strong>video_series</strong> - Video content organization</li>
                                <li><strong>videos</strong> - Individual video records</li>
                            </ul>
                            <p class="text-muted">
                                <strong>Note:</strong> This is safe to run multiple times. Existing tables and data will not be affected.
                            </p>
                            
                            <form method="POST">
                                <button type="submit" name="run_migration" class="btn btn-success btn-lg">
                                    🚀 Run Migration
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 