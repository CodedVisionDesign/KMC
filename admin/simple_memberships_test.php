<?php
// Simple test to check if the memberships functionality works
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/admin_common.php';

echo "<h2>Simple Memberships Test</h2>";

// Test 1: Check if we can get plan data
echo "<h3>Test 1: Get Plan Data</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM membership_plans LIMIT 1");
    $plan = $stmt->fetch();
    
    if ($plan) {
        echo "✅ Plan found: {$plan['name']}<br>";
        
        // Test 2: Simulate the AJAX get_plan request
        echo "<h3>Test 2: AJAX get_plan Simulation</h3>";
        
        $planId = $plan['id'];
        $stmt = $pdo->prepare("SELECT * FROM membership_plans WHERE id = ?");
        $stmt->execute([$planId]);
        $planData = $stmt->fetch();
        
        if ($planData) {
            $planResult = [
                'id' => $planData['id'],
                'name' => $planData['name'] ?? 'Unknown Plan',
                'description' => $planData['description'] ?? '',
                'price' => $planData['price'] ?? 0,
                'monthly_class_limit' => $planData['monthly_class_limit'] ?? null,
                'status' => $planData['status'] ?? 'active',
                'gocardless_url' => $planData['gocardless_url'] ?? '',
                'bank_account_name' => $planData['bank_account_name'] ?? '',
                'bank_sort_code' => $planData['bank_sort_code'] ?? '',
                'bank_account_number' => $planData['bank_account_number'] ?? ''
            ];
            
            echo "✅ Plan data processed successfully<br>";
            echo "<strong>JSON Result:</strong><br>";
            echo "<pre>" . json_encode(['success' => true, 'plan' => $planResult], JSON_PRETTY_PRINT) . "</pre>";
        }
    } else {
        echo "❌ No plans found in database<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 3: Check for any PHP errors in the actual file
echo "<h3>Test 3: Check memberships.php File</h3>";
$membershipFile = __DIR__ . '/memberships.php';
if (file_exists($membershipFile)) {
    echo "✅ memberships.php file exists<br>";
    
    // Read the file and check for any obvious issues
    $content = file_get_contents($membershipFile);
    $lines = explode("\n", $content);
    
    if (count($lines) > 779) {
        echo "Line 779 content: <code>" . htmlspecialchars(trim($lines[778])) . "</code><br>";
        echo "Line 778 content: <code>" . htmlspecialchars(trim($lines[777])) . "</code><br>";
        echo "Line 780 content: <code>" . htmlspecialchars(trim($lines[779])) . "</code><br>";
    }
    
    // Check for potential constant issues
    $constantIssues = preg_grep('/\bplan\s*[;})]/', $lines);
    if (!empty($constantIssues)) {
        echo "⚠️ Potential constant issues found:<br>";
        foreach ($constantIssues as $lineNum => $line) {
            echo "Line " . ($lineNum + 1) . ": <code>" . htmlspecialchars(trim($line)) . "</code><br>";
        }
    } else {
        echo "✅ No obvious constant issues found<br>";
    }
} else {
    echo "❌ memberships.php file not found<br>";
}

echo "<h3>Test Actions</h3>";
echo "<p>";
echo "<strong>To fix the undefined constant issue:</strong><br>";
echo "1. Clear your browser cache completely<br>";
echo "2. Try in incognito/private mode<br>";
echo "3. Check browser console for JavaScript errors<br>";
echo "4. <a href='memberships.php?v=" . time() . "' target='_blank'>Try memberships page with cache buster</a><br>";
echo "</p>";

echo "<p>";
echo "<a href='fix_duplicate_students.php' class='btn btn-warning'>Fix Duplicate Students</a> ";
echo "<a href='students.php' class='btn btn-success'>View Students</a> ";
echo "<a href='memberships.php' class='btn btn-primary'>Memberships</a>";
echo "</p>";
?>

<style>
.btn {
    display: inline-block;
    padding: 8px 16px;
    margin: 4px 2px;
    text-decoration: none;
    border-radius: 4px;
    border: none;
    cursor: pointer;
}
.btn-primary { background-color: #007bff; color: white; }
.btn-success { background-color: #28a745; color: white; }
.btn-warning { background-color: #ffc107; color: black; }
pre {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    overflow-x: auto;
}
code {
    background: #e9ecef;
    padding: 2px 4px;
    border-radius: 3px;
}
</style> 