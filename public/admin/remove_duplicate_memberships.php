<?php
/**
 * Remove Duplicate Membership Plans Script
 * Identifies and removes duplicate membership plans, keeping only the first occurrence
 */

require_once '../api/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Remove Duplicate Membership Plans</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>🧹 Remove Duplicate Membership Plans</h1>
";

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_duplicates'])) {
    try {
        $pdo->beginTransaction();
        
        // Find duplicates
        $stmt = $pdo->query("
            SELECT name, 
                   GROUP_CONCAT(id ORDER BY id) as ids,
                   COUNT(*) as count
            FROM membership_plans 
            GROUP BY name 
            HAVING COUNT(*) > 1
            ORDER BY name
        ");
        $duplicates = $stmt->fetchAll();
        
        $totalRemoved = 0;
        $removedPlans = [];
        
        foreach ($duplicates as $duplicate) {
            $ids = explode(',', $duplicate['ids']);
            $keepId = array_shift($ids); // Keep the first (lowest ID)
            $removeIds = $ids; // Remove the rest
            
            if (!empty($removeIds)) {
                $removeIdsStr = implode(',', $removeIds);
                
                // First, remove any user_memberships that reference these plan IDs
                $stmt = $pdo->prepare("DELETE FROM user_memberships WHERE plan_id IN ($removeIdsStr)");
                $stmt->execute();
                
                // Then remove the duplicate membership plans
                $stmt = $pdo->prepare("DELETE FROM membership_plans WHERE id IN ($removeIdsStr)");
                $stmt->execute();
                
                $removedCount = count($removeIds);
                $totalRemoved += $removedCount;
                $removedPlans[] = [
                    'name' => $duplicate['name'],
                    'kept_id' => $keepId,
                    'removed_ids' => $removeIds,
                    'removed_count' => $removedCount
                ];
            }
        }
        
        $pdo->commit();
        
        if ($totalRemoved > 0) {
            $message = "Successfully removed $totalRemoved duplicate membership plans!";
            $messageType = 'success';
        } else {
            $message = "No duplicate membership plans found to remove.";
            $messageType = 'info';
        }
        
    } catch (Exception $e) {
        $pdo->rollback();
        $message = "Error removing duplicates: " . $e->getMessage();
        $messageType = 'error';
        error_log('Error removing duplicate memberships: ' . $e->getMessage());
    }
}

// Display message if any
if ($message) {
    $class = $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'error' : 'info');
    echo "<div class='section'>";
    echo "<p class='$class'><strong>" . htmlspecialchars($message) . "</strong></p>";
    
    if (isset($removedPlans) && !empty($removedPlans)) {
        echo "<h3>Removal Details:</h3>";
        echo "<table>";
        echo "<tr><th>Plan Name</th><th>Kept ID</th><th>Removed IDs</th><th>Count Removed</th></tr>";
        foreach ($removedPlans as $plan) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($plan['name']) . "</td>";
            echo "<td>" . $plan['kept_id'] . "</td>";
            echo "<td>" . implode(', ', $plan['removed_ids']) . "</td>";
            echo "<td>" . $plan['removed_count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
}

// Check for current duplicates
echo "<div class='section'>";
echo "<h2>Current Duplicate Analysis</h2>";

try {
    // Find current duplicates
    $stmt = $pdo->query("
        SELECT name, 
               GROUP_CONCAT(id ORDER BY id) as ids,
               COUNT(*) as count,
               GROUP_CONCAT(CONCAT('ID:', id, ' (£', price, ')') ORDER BY id SEPARATOR ', ') as details
        FROM membership_plans 
        GROUP BY name 
        HAVING COUNT(*) > 1
        ORDER BY count DESC, name
    ");
    $duplicates = $stmt->fetchAll();
    
    if (empty($duplicates)) {
        echo "<p class='success'>✅ No duplicate membership plans found!</p>";
    } else {
        echo "<p class='warning'>⚠️ Found " . count($duplicates) . " sets of duplicate membership plans:</p>";
        
        echo "<table>";
        echo "<tr><th>Plan Name</th><th>Duplicate Count</th><th>Details</th></tr>";
        
        foreach ($duplicates as $duplicate) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($duplicate['name']) . "</strong></td>";
            echo "<td>" . $duplicate['count'] . "</td>";
            echo "<td>" . htmlspecialchars($duplicate['details']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<form method='POST' style='margin-top: 20px;'>";
        echo "<p class='warning'><strong>Warning:</strong> This will remove duplicate plans and any associated user memberships. The plan with the lowest ID will be kept for each duplicate group.</p>";
        echo "<button type='submit' name='remove_duplicates' class='btn btn-danger' onclick='return confirm(\"Are you sure you want to remove all duplicate membership plans? This cannot be undone!\");'>🗑️ Remove All Duplicates</button>";
        echo "</form>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error checking for duplicates: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Show all current plans
echo "<div class='section'>";
echo "<h2>All Current Membership Plans</h2>";

try {
    $stmt = $pdo->query("
        SELECT id, name, description, price, monthly_class_limit, status, created_at
        FROM membership_plans 
        ORDER BY name, id
    ");
    $allPlans = $stmt->fetchAll();
    
    if (empty($allPlans)) {
        echo "<p class='warning'>⚠️ No membership plans found.</p>";
    } else {
        echo "<p class='info'>📋 Total membership plans: " . count($allPlans) . "</p>";
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Price (GBP)</th><th>Monthly Limit</th><th>Status</th><th>Created</th></tr>";
        
        foreach ($allPlans as $plan) {
            $limit = $plan['monthly_class_limit'] ? $plan['monthly_class_limit'] . ' classes' : 'Unlimited';
            $price = number_format($plan['price'], 2);
            $created = date('M d, Y', strtotime($plan['created_at']));
            
            echo "<tr>";
            echo "<td>" . $plan['id'] . "</td>";
            echo "<td><strong>" . htmlspecialchars($plan['name']) . "</strong></td>";
            echo "<td>£" . $price . "</td>";
            echo "<td>" . $limit . "</td>";
            echo "<td>" . ucfirst($plan['status']) . "</td>";
            echo "<td>" . $created . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error fetching membership plans: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>🔧 Quick Actions</h2>";
echo "<p><a href='../index.php' class='btn'>← Back to Index Page</a></p>";
echo "<p><a href='../test_index_queries.php' class='btn'>🧪 Test Database Queries</a></p>";
echo "<p><a href='run_migration.php' class='btn'>🚀 Run Migration</a></p>";
echo "</div>";

echo "</body></html>";
?> 