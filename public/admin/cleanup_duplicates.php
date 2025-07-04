<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleanup Duplicates</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Cleanup Duplicate Records</h1>
        <p>This script will remove duplicate membership plans and video series from the database.</p>
        
        <?php
        // Include database connection
        require_once __DIR__ . '/../api/db.php';

        if (isset($_POST['cleanup'])) {
            echo "<h2>Cleanup Log</h2>";
            echo "<pre>";
            
            try {
                echo "Connecting to database...\n";
                $pdo = connectUserDB();
                echo "<span class='success'>✓ Database connected successfully</span>\n\n";

                // Clean up duplicate membership plans
                echo "Cleaning up duplicate membership plans...\n";
                
                // Find duplicates by name
                $stmt = $pdo->query("
                    SELECT name, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids
                    FROM membership_plans 
                    GROUP BY name 
                    HAVING COUNT(*) > 1
                ");
                $duplicatePlans = $stmt->fetchAll();
                
                if (empty($duplicatePlans)) {
                    echo "<span class='info'>ℹ No duplicate membership plans found</span>\n";
                } else {
                    foreach ($duplicatePlans as $duplicate) {
                        $ids = explode(',', $duplicate['ids']);
                        // Keep the first one, delete the rest
                        $keepId = array_shift($ids);
                        $deleteIds = implode(',', $ids);
                        
                        echo "Plan '{$duplicate['name']}': Keeping ID {$keepId}, deleting IDs: {$deleteIds}\n";
                        
                        // Delete the duplicates
                        $stmt = $pdo->prepare("DELETE FROM membership_plans WHERE id IN ({$deleteIds})");
                        $stmt->execute();
                        
                        echo "<span class='success'>✓ Deleted " . count($ids) . " duplicate(s)</span>\n";
                    }
                }
                
                echo "\n";
                
                // Clean up duplicate video series
                echo "Cleaning up duplicate video series...\n";
                
                $stmt = $pdo->query("
                    SELECT title, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids
                    FROM video_series 
                    GROUP BY title 
                    HAVING COUNT(*) > 1
                ");
                $duplicateSeries = $stmt->fetchAll();
                
                if (empty($duplicateSeries)) {
                    echo "<span class='info'>ℹ No duplicate video series found</span>\n";
                } else {
                    foreach ($duplicateSeries as $duplicate) {
                        $ids = explode(',', $duplicate['ids']);
                        // Keep the first one, delete the rest
                        $keepId = array_shift($ids);
                        $deleteIds = implode(',', $ids);
                        
                        echo "Series '{$duplicate['title']}': Keeping ID {$keepId}, deleting IDs: {$deleteIds}\n";
                        
                        // Delete the duplicates
                        $stmt = $pdo->prepare("DELETE FROM video_series WHERE id IN ({$deleteIds})");
                        $stmt->execute();
                        
                        echo "<span class='success'>✓ Deleted " . count($ids) . " duplicate(s)</span>\n";
                    }
                }
                
                echo "\n<span class='success'>✓ Cleanup completed successfully!</span>\n";
                
                // Show current counts
                echo "\nCurrent record counts:\n";
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM membership_plans");
                $planCount = $stmt->fetch()['count'];
                echo "- Membership Plans: {$planCount}\n";
                
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM video_series");
                $seriesCount = $stmt->fetch()['count'];
                echo "- Video Series: {$seriesCount}\n";
                
            } catch (Exception $e) {
                echo "<span class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
                echo "File: " . $e->getFile() . "\n";
                echo "Line: " . $e->getLine() . "\n";
            }
            
            echo "</pre>";
            
        } else {
            // Show current status
            try {
                $pdo = connectUserDB();
                
                echo "<h2>Current Status</h2>";
                
                // Check for duplicate membership plans
                $stmt = $pdo->query("
                    SELECT name, COUNT(*) as count
                    FROM membership_plans 
                    GROUP BY name 
                    HAVING COUNT(*) > 1
                ");
                $duplicatePlans = $stmt->fetchAll();
                
                if (!empty($duplicatePlans)) {
                    echo "<div class='warning'>";
                    echo "<h3>⚠️ Duplicate Membership Plans Found:</h3>";
                    echo "<ul>";
                    foreach ($duplicatePlans as $plan) {
                        echo "<li>{$plan['name']} ({$plan['count']} copies)</li>";
                    }
                    echo "</ul>";
                    echo "</div>";
                }
                
                // Check for duplicate video series
                $stmt = $pdo->query("
                    SELECT title, COUNT(*) as count
                    FROM video_series 
                    GROUP BY title 
                    HAVING COUNT(*) > 1
                ");
                $duplicateSeries = $stmt->fetchAll();
                
                if (!empty($duplicateSeries)) {
                    echo "<div class='warning'>";
                    echo "<h3>⚠️ Duplicate Video Series Found:</h3>";
                    echo "<ul>";
                    foreach ($duplicateSeries as $series) {
                        echo "<li>{$series['title']} ({$series['count']} copies)</li>";
                    }
                    echo "</ul>";
                    echo "</div>";
                }
                
                if (empty($duplicatePlans) && empty($duplicateSeries)) {
                    echo "<div class='success'>";
                    echo "<h3>✅ No Duplicates Found</h3>";
                    echo "<p>Your database is clean!</p>";
                    echo "</div>";
                }
                
                // Show total counts
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM membership_plans");
                $planCount = $stmt->fetch()['count'];
                
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM video_series");
                $seriesCount = $stmt->fetch()['count'];
                
                echo "<h3>📊 Current Record Counts:</h3>";
                echo "<ul>";
                echo "<li>Membership Plans: {$planCount}</li>";
                echo "<li>Video Series: {$seriesCount}</li>";
                echo "</ul>";
                
            } catch (Exception $e) {
                echo "<div class='error'>";
                echo "<h3>❌ Database Error</h3>";
                echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
            }
            
            if (!empty($duplicatePlans) || !empty($duplicateSeries)) {
                echo "<form method='post'>";
                echo "<button type='submit' name='cleanup' class='btn btn-danger'>🧹 Clean Up Duplicates</button>";
                echo "</form>";
            }
        }
        ?>
        
        <hr>
        <p><a href="run_migration.php" class="btn">🔄 Run Migration</a></p>
        <p><a href="../user/membership.php" class="btn">👀 View Membership Page</a></p>
    </div>
</body>
</html>
