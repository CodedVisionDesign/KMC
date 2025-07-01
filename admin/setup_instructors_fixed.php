<?php
require_once 'auth.php';
require_once '../public/api/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    try {
        $pdo->beginTransaction();
        
        // Step 1: Create instructors table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS instructors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                phone VARCHAR(20),
                bio TEXT,
                specialties TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                status ENUM('active', 'inactive') DEFAULT 'active'
            )
        ");
        
        // Step 2: Check if instructor_id column exists in classes table
        $stmt = $pdo->query("
            SELECT COUNT(*) as col_count
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'classes' 
            AND COLUMN_NAME = 'instructor_id'
        ");
        $colExists = $stmt->fetch()['col_count'] > 0;
        
        // Step 3: Add instructor_id column if it doesn't exist
        if (!$colExists) {
            $pdo->exec("ALTER TABLE classes ADD COLUMN instructor_id INT NULL");
        }
        
        // Step 4: Check if foreign key constraint exists
        $stmt = $pdo->query("
            SELECT COUNT(*) as fk_count
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'classes' 
            AND CONSTRAINT_NAME = 'fk_classes_instructor'
        ");
        $fkExists = $stmt->fetch()['fk_count'] > 0;
        
        // Step 5: Add foreign key constraint if it doesn't exist
        if (!$fkExists) {
            $pdo->exec("
                ALTER TABLE classes 
                ADD CONSTRAINT fk_classes_instructor 
                FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE SET NULL
            ");
        }
        
        // Step 6: Insert sample instructors (using INSERT IGNORE to avoid duplicates)
        $pdo->exec("
            INSERT IGNORE INTO instructors (first_name, last_name, email, phone, bio, specialties) VALUES 
            ('Sarah', 'Johnson', 'sarah.johnson@studio.com', '555-0101', 'Certified yoga instructor with 10+ years experience. Specializes in Hatha and Vinyasa yoga.', 'Hatha Yoga, Vinyasa, Meditation'),
            ('Mike', 'Chen', 'mike.chen@studio.com', '555-0102', 'Personal trainer and Pilates instructor. Former athlete with expertise in strength training.', 'Pilates, HIIT, Strength Training'),
            ('Emma', 'Davis', 'emma.davis@studio.com', '555-0103', 'Mindfulness coach and meditation teacher. Creates calming environments for healing and growth.', 'Meditation, Mindfulness, Breathwork'),
            ('Alex', 'Rodriguez', 'alex.rodriguez@studio.com', '555-0104', 'High-intensity training specialist. Motivational coach focused on fitness transformations.', 'HIIT, CrossFit, Weight Training'),
            ('Lisa', 'Thompson', 'lisa.thompson@studio.com', '555-0105', 'Beginner-friendly yoga instructor. Patient and encouraging approach to wellness.', 'Beginner Yoga, Gentle Yoga, Seniors Fitness')
        ");
        
        // Step 7: Assign instructors to existing classes (only if instructor_id is NULL)
        $pdo->exec("UPDATE classes SET instructor_id = 1 WHERE (name LIKE '%Yoga%' OR name LIKE '%yoga%') AND name LIKE '%Morning%' AND instructor_id IS NULL");
        $pdo->exec("UPDATE classes SET instructor_id = 2 WHERE (name LIKE '%Pilates%' OR name LIKE '%pilates%') AND instructor_id IS NULL");
        $pdo->exec("UPDATE classes SET instructor_id = 3 WHERE (name LIKE '%Meditation%' OR name LIKE '%meditation%') AND instructor_id IS NULL");
        $pdo->exec("UPDATE classes SET instructor_id = 4 WHERE (name LIKE '%HIIT%' OR name LIKE '%hiit%' OR name LIKE '%High%') AND instructor_id IS NULL");
        $pdo->exec("UPDATE classes SET instructor_id = 5 WHERE (name LIKE '%Beginner%' OR name LIKE '%beginner%') AND (name LIKE '%Yoga%' OR name LIKE '%yoga%') AND instructor_id IS NULL");
        
        // Assign remaining yoga classes to Sarah
        $pdo->exec("UPDATE classes SET instructor_id = 1 WHERE (name LIKE '%Yoga%' OR name LIKE '%yoga%') AND instructor_id IS NULL");
        
        // Assign remaining fitness classes to Mike
        $pdo->exec("UPDATE classes SET instructor_id = 2 WHERE (name LIKE '%Fitness%' OR name LIKE '%fitness%' OR name LIKE '%Workout%' OR name LIKE '%workout%') AND instructor_id IS NULL");
        
        // Assign default instructor to any remaining classes
        $pdo->exec("UPDATE classes SET instructor_id = 1 WHERE instructor_id IS NULL");
        
        // Step 8: Create indexes (ignore errors if they already exist)
        try {
            $pdo->exec("CREATE INDEX idx_instructors_email ON instructors(email)");
        } catch (Exception $e) {
            // Index might already exist, ignore
        }
        
        try {
            $pdo->exec("CREATE INDEX idx_instructors_status ON instructors(status)");
        } catch (Exception $e) {
            // Index might already exist, ignore
        }
        
        try {
            $pdo->exec("CREATE INDEX idx_classes_instructor_id ON classes(instructor_id)");
        } catch (Exception $e) {
            // Index might already exist, ignore
        }
        
        $pdo->commit();
        $message = 'Instructors table created successfully! Sample instructors have been added.';
        
    } catch (Exception $e) {
        $pdo->rollback();
        $error = 'Migration failed: ' . $e->getMessage();
    }
}

// Check if instructors table exists
$tableExists = false;
try {
    $pdo->query('SELECT 1 FROM instructors LIMIT 1');
    $tableExists = true;
} catch (Exception $e) {
    $tableExists = false;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Instructors - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-dumbbell me-2"></i>Fitness Studio Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-database me-2"></i>Instructor System Setup (Fixed Version)</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($tableExists): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Great!</strong> The instructors system is already set up and ready to use.
                            </div>
                            <div class="d-grid gap-2">
                                <a href="instructors.php" class="btn btn-primary">
                                    <i class="fas fa-user-tie me-2"></i>Manage Instructors
                                </a>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Fixed Migration:</strong> This version handles column checking properly and should work without errors.
                            </div>
                            
                            <h5>What will this setup do?</h5>
                            <ul>
                                <li><strong>✅ Create</strong> the <code>instructors</code> table</li>
                                <li><strong>✅ Add</strong> <code>instructor_id</code> column to the <code>classes</code> table (if not exists)</li>
                                <li><strong>✅ Create</strong> foreign key relationship safely</li>
                                <li><strong>✅ Insert</strong> 5 sample instructors with specialties</li>
                                <li><strong>✅ Assign</strong> existing classes to appropriate instructors</li>
                                <li><strong>✅ Create</strong> database indexes for better performance</li>
                            </ul>

                            <div class="alert alert-success">
                                <i class="fas fa-shield-alt me-2"></i>
                                <strong>Safe Migration:</strong> This version properly checks if columns exist before creating them, preventing duplicate column errors.
                            </div>

                            <form method="POST">
                                <div class="d-grid">
                                    <button type="submit" name="run_migration" class="btn btn-primary btn-lg">
                                        <i class="fas fa-play me-2"></i>Run Fixed Setup
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 