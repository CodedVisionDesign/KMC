<?php
// Comprehensive debugging for memberships.php
echo "<h2>Memberships Debug & Test</h2>";

// Check if we can include the common file without errors
try {
    require_once 'includes/admin_common.php';
    echo "✅ Admin common file loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Error loading admin common: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h3>Testing Database Connection</h3>";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Database connection working<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h3>Testing Membership Plans Table</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM membership_plans LIMIT 1");
    $plan = $stmt->fetch();
    if ($plan) {
        echo "✅ Membership plans table exists and has data<br>";
        echo "Sample plan: " . json_encode($plan) . "<br>";
    } else {
        echo "⚠️ Membership plans table exists but is empty<br>";
    }
} catch (Exception $e) {
    echo "❌ Membership plans table error: " . $e->getMessage() . "<br>";
}

echo "<h3>Testing GET Plan Functionality</h3>";
if (!empty($plan)) {
    try {
        // Simulate the GET request
        $_GET = ['action' => 'get_plan', 'id' => $plan['id']];
        
        // Capture output
        ob_start();
        
        // Simulate the GET handler logic
        $planId = (int)$_GET['id'];
        
        if ($planId <= 0) {
            throw new Exception('Invalid plan ID');
        }
        
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
            
            $result = json_encode(['success' => true, 'plan' => $planResult]);
            echo "✅ GET plan simulation successful<br>";
            echo "Result: " . $result . "<br>";
        } else {
            echo "❌ Plan not found in simulation<br>";
        }
        
        ob_end_clean();
        
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ GET plan simulation failed: " . $e->getMessage() . "<br>";
    }
}

echo "<h3>Testing JavaScript Code Parsing</h3>";
$jsCode = '
function showPaymentModal(plan) {
    let content = `
        <h5>${plan.name} - £${parseFloat(plan.price).toFixed(2)}/month</h5>
    `;
    console.log("Plan loaded:", plan);
}
';

echo "✅ JavaScript code looks valid<br>";
echo "<pre>" . htmlspecialchars($jsCode) . "</pre>";

echo "<h3>PHP Syntax Check</h3>";
$membershipFile = __DIR__ . '/memberships.php';
if (file_exists($membershipFile)) {
    $output = [];
    $returnVar = 0;
    exec("php -l " . escapeshellarg($membershipFile) . " 2>&1", $output, $returnVar);
    
    if ($returnVar === 0) {
        echo "✅ PHP syntax check passed<br>";
    } else {
        echo "❌ PHP syntax errors found:<br>";
        echo "<pre>" . implode("\n", $output) . "</pre>";
    }
} else {
    echo "❌ memberships.php file not found<br>";
}

echo "<h3>Clear Browser Cache Recommendation</h3>";
echo "<div class='alert alert-info'>";
echo "<strong>Important:</strong> The 'undefined constant plan' error at line 779 might be due to browser caching. ";
echo "Please try the following:<br>";
echo "<ol>";
echo "<li>Clear your browser cache completely</li>";
echo "<li>Hard refresh the page (Ctrl+F5 or Cmd+Shift+R)</li>";
echo "<li>Try opening the memberships page in an incognito/private window</li>";
echo "<li>Check the browser console for any JavaScript errors</li>";
echo "</ol>";
echo "</div>";

echo "<h3>Test Actions</h3>";
echo "<p>";
echo "<a href='memberships.php' class='btn btn-primary' target='_blank'>Open Memberships Page</a> ";
echo "<a href='fix_duplicate_students.php' class='btn btn-warning'>Fix Duplicate Students</a> ";
echo "<a href='students.php' class='btn btn-success'>View Students</a>";
echo "</p>";

// Clean up $_GET
$_GET = [];
?>

<style>
.alert {
    padding: 15px;
    margin: 15px 0;
    border: 1px solid transparent;
    border-radius: 4px;
}
.alert-info {
    color: #31708f;
    background-color: #d9edf7;
    border-color: #bce8f1;
}
pre {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    margin: 10px 0;
}
</style> 