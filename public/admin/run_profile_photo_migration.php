<?php
require_once '../api/db.php';

echo "<h2>Profile Photo Migration</h2>";

try {
    // Read the migration file
    $migrationFile = __DIR__ . '/../../config/add_profile_photos.sql';
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Execute each SQL statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty lines and comments
        }
        
        echo "<p>Executing: " . htmlspecialchars(substr($statement, 0, 100)) . "...</p>";
        $pdo->exec($statement);
        echo "<p style='color: green;'>✅ Success</p>";
    }
    
    // Create upload directories
    $userUploadsDir = __DIR__ . '/../uploads/profiles/users';
    $instructorUploadsDir = __DIR__ . '/../uploads/profiles/instructors';
    
    if (!is_dir($userUploadsDir)) {
        mkdir($userUploadsDir, 0755, true);
        echo "<p style='color: green;'>✅ Created directory: $userUploadsDir</p>";
    }
    
    if (!is_dir($instructorUploadsDir)) {
        mkdir($instructorUploadsDir, 0755, true);
        echo "<p style='color: green;'>✅ Created directory: $instructorUploadsDir</p>";
    }
    
    echo "<h3 style='color: green;'>Migration Completed Successfully!</h3>";
    echo "<p><a href='../../admin/instructors.php'>Return to Instructors</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Migration Failed!</h3>";
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 