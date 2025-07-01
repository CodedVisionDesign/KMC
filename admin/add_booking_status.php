<?php
require_once 'includes/admin_common.php';

echo "<h2>Add Booking Status Column</h2>";

if (isset($_POST['add_status_column'])) {
    try {
        // Check if column already exists
        $stmt = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'status'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='alert alert-info'>✅ Status column already exists in bookings table.</div>";
        } else {
            // Add status column
            $pdo->exec("ALTER TABLE bookings ADD COLUMN status VARCHAR(20) DEFAULT 'confirmed' AFTER email");
            echo "<div class='alert alert-success'>✅ Status column added successfully!</div>";
            
            // Update existing bookings to have 'confirmed' status
            $stmt = $pdo->exec("UPDATE bookings SET status = 'confirmed' WHERE status IS NULL");
            echo "<div class='alert alert-info'>✅ Updated {$stmt} existing bookings with 'confirmed' status.</div>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
    }
}

// Check current table structure
echo "<h3>Current Bookings Table Structure:</h3>";
try {
    $stmt = $pdo->query("DESCRIBE bookings");
    $columns = $stmt->fetchAll();
    
    echo "<table class='table table-striped'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    $hasStatus = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'status') {
            $hasStatus = true;
        }
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if (!$hasStatus) {
        echo "<div class='alert alert-warning'>";
        echo "<h4>⚠️ No Status Column Found</h4>";
        echo "<p>The bookings table doesn't have a status column. This means:</p>";
        echo "<ul>";
        echo "<li>Bookings can only be deleted, not cancelled</li>";
        echo "<li>You cannot track booking status (confirmed, cancelled, etc.)</li>";
        echo "</ul>";
        echo "<p><strong>Recommendation:</strong> Add a status column to enable booking cancellation tracking.</p>";
        echo "</div>";
        
        echo "<form method='POST'>";
        echo "<button type='submit' name='add_status_column' class='btn btn-primary' onclick='return confirm(\"Add status column to bookings table? This will allow tracking of booking status.\")'>Add Status Column</button>";
        echo "</form>";
    } else {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ Status Column Found</h4>";
        echo "<p>The bookings table has a status column. You can now track booking status.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Error checking table structure: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<h3>What's the Difference?</h3>";
echo "<div class='alert alert-info'>";
echo "<h5>Without Status Column:</h5>";
echo "<ul>";
echo "<li><strong>Delete Student:</strong> Removes future bookings completely from database</li>";
echo "<li><strong>Pros:</strong> Clean database, no orphaned records</li>";
echo "<li><strong>Cons:</strong> Lose booking history, cannot track cancellations</li>";
echo "</ul>";

echo "<h5>With Status Column:</h5>";
echo "<ul>";
echo "<li><strong>Delete Student:</strong> Marks future bookings as 'cancelled'</li>";
echo "<li><strong>Pros:</strong> Preserves booking history, can track cancellations</li>";
echo "<li><strong>Cons:</strong> Keeps more records in database</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='students.php' class='btn btn-secondary'>Return to Students</a></p>";
?>

<style>
.alert {
    padding: 15px;
    margin: 15px 0;
    border: 1px solid transparent;
    border-radius: 4px;
}
.alert-success {
    color: #3c763d;
    background-color: #dff0d8;
    border-color: #d6e9c6;
}
.alert-danger {
    color: #a94442;
    background-color: #f2dede;
    border-color: #ebccd1;
}
.alert-info {
    color: #31708f;
    background-color: #d9edf7;
    border-color: #bce8f1;
}
.alert-warning {
    color: #8a6d3b;
    background-color: #fcf8e3;
    border-color: #faebcc;
}
.table {
    margin-top: 20px;
    border-collapse: collapse;
    width: 100%;
}
.table th, .table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}
.table th {
    background-color: #f8f9fa;
    font-weight: bold;
}
.table-striped tbody tr:nth-child(odd) {
    background-color: #f9f9f9;
}
.btn {
    display: inline-block;
    padding: 6px 12px;
    margin-bottom: 0;
    font-size: 14px;
    font-weight: normal;
    line-height: 1.42857143;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    cursor: pointer;
    border: 1px solid transparent;
    border-radius: 4px;
    text-decoration: none;
}
.btn-primary {
    color: #fff;
    background-color: #007bff;
    border-color: #007bff;
}
.btn-secondary {
    color: #fff;
    background-color: #6c757d;
    border-color: #6c757d;
}
</style> 