<?php
// Session debug and fix tool
session_start();

echo "<h2>Current Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Include user auth functions
require_once __DIR__ . '/../config/user_auth.php';

echo "<h2>User Auth Functions Test</h2>";

if (isUserLoggedIn()) {
    $userInfo = getUserInfo();
    echo "<p><strong>User is logged in:</strong></p>";
    echo "<pre>";
    print_r($userInfo);
    echo "</pre>";
    
    // Check if the user ID matches the database
    if ($userInfo) {
        $pdo = connectUserDB();
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = ?");
        $stmt->execute([$userInfo['id']]);
        $dbUser = $stmt->fetch();
        
        echo "<h3>Database User Data</h3>";
        echo "<pre>";
        print_r($dbUser);
        echo "</pre>";
        
        if (!$dbUser) {
            echo "<p style='color: red;'><strong>ERROR: Session user ID does not exist in database!</strong></p>";
            
            // Try to find user by email
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE email = ?");
            $stmt->execute([$userInfo['email']]);
            $correctUser = $stmt->fetch();
            
            if ($correctUser) {
                echo "<h3>Correct User Found by Email</h3>";
                echo "<pre>";
                print_r($correctUser);
                echo "</pre>";
                
                echo '<p><a href="fix_session.php" style="background: red; color: white; padding: 10px; text-decoration: none;">FIX SESSION</a></p>';
            }
        }
    }
} else {
    echo "<p><strong>User is NOT logged in</strong></p>";
}

echo "<h2>All Users in Database</h2>";
$pdo = connectUserDB();
$stmt = $pdo->query("SELECT id, first_name, last_name, email, status FROM users ORDER BY id");
$users = $stmt->fetchAll();
echo "<pre>";
print_r($users);
echo "</pre>";
?> 