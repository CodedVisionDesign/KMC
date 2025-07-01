<?php
require_once 'includes/admin_common.php';

echo "<h2>Duplicate Student Cleanup</h2>";

// Function to find and remove duplicate students
function removeDuplicateStudents($pdo) {
    echo "<h3>Finding Duplicate Students...</h3>";
    
    // Find students with duplicate emails
    $stmt = $pdo->query("
        SELECT email, COUNT(*) as count, 
               GROUP_CONCAT(id ORDER BY created_at ASC) as student_ids,
               GROUP_CONCAT(CONCAT(first_name, ' ', last_name) ORDER BY created_at ASC SEPARATOR ', ') as names
        FROM users 
        WHERE email IS NOT NULL AND email != ''
        GROUP BY email 
        HAVING COUNT(*) > 1
    ");
    $duplicates = $stmt->fetchAll();
    
    if (empty($duplicates)) {
        echo "✅ No duplicate students found.<br>";
        return;
    }
    
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>Email</th><th>Count</th><th>Student Names</th><th>Action</th></tr></thead>";
    echo "<tbody>";
    
    foreach ($duplicates as $duplicate) {
        $studentIds = explode(',', $duplicate['student_ids']);
        $names = explode(', ', $duplicate['names']);
        
        echo "<tr>";
        echo "<td>{$duplicate['email']}</td>";
        echo "<td>{$duplicate['count']}</td>";
        echo "<td>";
        
        for ($i = 0; $i < count($studentIds); $i++) {
            $status = $i == 0 ? "<strong>(Keep)</strong>" : "<em>(Remove)</em>";
            echo "ID {$studentIds[$i]}: {$names[$i]} $status<br>";
        }
        
        echo "</td>";
        echo "<td>";
        
        if (isset($_POST['fix_duplicate']) && $_POST['duplicate_email'] === $duplicate['email']) {
            try {
                $pdo->beginTransaction();
                
                // Keep the first student (oldest), remove the rest
                $keepId = $studentIds[0];
                $removeIds = array_slice($studentIds, 1);
                
                foreach ($removeIds as $removeId) {
                    // Get student info for logging
                    $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
                    $stmt->execute([$removeId]);
                    $student = $stmt->fetch();
                    
                    // Delete future bookings (bookings table has no status column)
                    $stmt = $pdo->prepare("
                        DELETE b FROM bookings b 
                        JOIN classes c ON b.class_id = c.id 
                        WHERE b.user_id = ? AND c.date >= CURDATE()
                    ");
                    $stmt->execute([$removeId]);
                    
                    // Delete membership records
                    $stmt = $pdo->prepare("
                        DELETE mp FROM membership_payments mp 
                        JOIN user_memberships um ON mp.user_membership_id = um.id 
                        WHERE um.user_id = ?
                    ");
                    $stmt->execute([$removeId]);
                    
                    $stmt = $pdo->prepare("DELETE FROM user_memberships WHERE user_id = ?");
                    $stmt->execute([$removeId]);
                    
                    // Delete the student
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$removeId]);
                    
                    echo "✅ Removed: {$student['first_name']} {$student['last_name']} (ID: $removeId)<br>";
                }
                
                $pdo->commit();
                echo "<strong>✅ Duplicate cleanup completed!</strong>";
                
            } catch (Exception $e) {
                $pdo->rollback();
                echo "❌ Error: " . $e->getMessage();
            }
        } else {
            echo "<form method='POST' style='display: inline;'>";
            echo "<input type='hidden' name='duplicate_email' value='{$duplicate['email']}'>";
            echo "<button type='submit' name='fix_duplicate' class='btn btn-danger btn-sm' onclick='return confirm(\"Remove duplicate students for {$duplicate['email']}? This will keep the oldest account and remove the rest.\")'>Fix Duplicates</button>";
            echo "</form>";
        }
        
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
}

// Function to add unique constraint
function addEmailUniqueConstraint($pdo) {
    echo "<h3>Adding Email Unique Constraint...</h3>";
    
    try {
        // Check if constraint already exists
        $stmt = $pdo->query("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'users' 
            AND COLUMN_NAME = 'email' 
            AND CONSTRAINT_NAME LIKE '%unique%'
        ");
        
        if ($stmt->rowCount() > 0) {
            echo "✅ Email unique constraint already exists.<br>";
            return;
        }
        
        // Add unique constraint
        $pdo->exec("ALTER TABLE users ADD CONSTRAINT unique_email UNIQUE (email)");
        echo "✅ Email unique constraint added successfully.<br>";
        
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo "❌ Cannot add unique constraint - there are still duplicate emails. Please fix duplicates first.<br>";
        } else {
            echo "❌ Error adding constraint: " . $e->getMessage() . "<br>";
        }
    }
}

// Run the cleanup
try {
    removeDuplicateStudents($pdo);
    echo "<hr>";
    addEmailUniqueConstraint($pdo);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

echo "<hr>";
echo "<p><a href='students.php' class='btn btn-primary'>Return to Students</a></p>";
?>

<style>
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
.btn-danger {
    color: #fff;
    background-color: #dc3545;
    border-color: #dc3545;
}
.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
    line-height: 1.5;
    border-radius: 3px;
}
</style> 