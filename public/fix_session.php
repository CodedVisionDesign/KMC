<?php
// Session fix tool
session_start();

require_once __DIR__ . '/../config/user_auth.php';

if (!isUserLoggedIn()) {
    die("Not logged in - cannot fix session");
}

$userInfo = getUserInfo();
$sessionEmail = $userInfo['email'];

echo "<h2>Fixing Session for: " . htmlspecialchars($sessionEmail) . "</h2>";

// Find correct user in database
$pdo = connectUserDB();
$stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE email = ?");
$stmt->execute([$sessionEmail]);
$correctUser = $stmt->fetch();

if (!$correctUser) {
    die("User not found in database!");
}

echo "<p>Current session user ID: " . $userInfo['id'] . "</p>";
echo "<p>Correct user ID: " . $correctUser['id'] . "</p>";

if ($userInfo['id'] != $correctUser['id']) {
    // Fix the session
    $_SESSION['user_id'] = $correctUser['id'];
    $_SESSION['user_name'] = $correctUser['first_name'] . ' ' . $correctUser['last_name'];
    $_SESSION['user_first_name'] = $correctUser['first_name'];
    
    echo "<p style='color: green;'><strong>✅ Session fixed!</strong></p>";
    echo "<p>New session user ID: " . $correctUser['id'] . "</p>";
    
    // Clear any incorrect user bookings cache
    echo "<script>
        if (window.userBookedClasses) {
            window.userBookedClasses = [];
        }
        if (window.userId) {
            window.userId = " . $correctUser['id'] . ";
        }
    </script>";
    
} else {
    echo "<p style='color: green;'>Session is already correct!</p>";
}

echo '<p><a href="index.php" style="background: green; color: white; padding: 10px; text-decoration: none;">Back to Home</a></p>';
echo '<p><a href="debug_session.php">Debug Session Again</a></p>';
?> 