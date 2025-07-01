<?php
require_once 'auth.php';
require_once '../public/api/db.php';

$message = '';
$error = '';
$steps = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_now'])) {
    $transactionStarted = false;
    try {
        $pdo->beginTransaction();
        $transactionStarted = true;
        
        // Step 1: Create instructors table
        try {
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
            $steps[] = "✅ Created instructors table";
        } catch (Exception $e) {
            $steps[] = "ℹ️ Instructors table already exists";
        }
        
        // Step 2: Add instructor_id column if it doesn't exist
        try {
            $pdo->exec("ALTER TABLE classes ADD COLUMN instructor_id INT NULL");
            $steps[] = "✅ Added instructor_id column to classes table";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                $steps[] = "ℹ️ instructor_id column already exists in classes table";
            } else {
                throw $e;
            }
        }
        
        // Step 3: Add foreign key constraint
        try {
            $pdo->exec("
                ALTER TABLE classes 
                ADD CONSTRAINT fk_classes_instructor 
                FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE SET NULL
            ");
            $steps[] = "✅ Added foreign key constraint";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate key') !== false || strpos($e->getMessage(), 'already exists') !== false) {
                $steps[] = "ℹ️ Foreign key constraint already exists";
            } else {
                $steps[] = "⚠️ Foreign key constraint issue: " . $e->getMessage();
            }
        }
        
        // Step 4: Insert sample instructors
        try {
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO instructors (first_name, last_name, email, phone, bio, specialties) VALUES 
                (?, ?, ?, ?, ?, ?)
            ");
            
            $instructors = [
                ['Sarah', 'Johnson', 'sarah.johnson@studio.com', '555-0101', 'Certified yoga instructor with 10+ years experience. Specializes in Hatha and Vinyasa yoga.', 'Hatha Yoga, Vinyasa, Meditation'],
                ['Mike', 'Chen', 'mike.chen@studio.com', '555-0102', 'Personal trainer and Pilates instructor. Former athlete with expertise in strength training.', 'Pilates, HIIT, Strength Training'],
                ['Emma', 'Davis', 'emma.davis@studio.com', '555-0103', 'Mindfulness coach and meditation teacher. Creates calming environments for healing and growth.', 'Meditation, Mindfulness, Breathwork'],
                ['Alex', 'Rodriguez', 'alex.rodriguez@studio.com', '555-0104', 'High-intensity training specialist. Motivational coach focused on fitness transformations.', 'HIIT, CrossFit, Weight Training'],
                ['Lisa', 'Thompson', 'lisa.thompson@studio.com', '555-0105', 'Beginner-friendly yoga instructor. Patient and encouraging approach to wellness.', 'Beginner Yoga, Gentle Yoga, Seniors Fitness']
            ];
            
            $inserted = 0;
            foreach ($instructors as $instructor) {
                $result = $stmt->execute($instructor);
                if ($stmt->rowCount() > 0) {
                    $inserted++;
                }
            }
            
            if ($inserted > 0) {
                $steps[] = "✅ Inserted $inserted sample instructors";
            } else {
                $steps[] = "ℹ️ Sample instructors already exist";
            }
            
        } catch (Exception $e) {
            $steps[] = "⚠️ Error inserting instructors: " . $e->getMessage();
        }
        
        // Step 5: Assign instructors to classes
        try {
            $assignments = [
                "UPDATE classes SET instructor_id = 1 WHERE (name LIKE '%Yoga%' OR name LIKE '%yoga%') AND instructor_id IS NULL",
                "UPDATE classes SET instructor_id = 2 WHERE (name LIKE '%Pilates%' OR name LIKE '%pilates%') AND instructor_id IS NULL",
                "UPDATE classes SET instructor_id = 3 WHERE (name LIKE '%Meditation%' OR name LIKE '%meditation%') AND instructor_id IS NULL",
                "UPDATE classes SET instructor_id = 4 WHERE (name LIKE '%HIIT%' OR name LIKE '%hiit%') AND instructor_id IS NULL",
                "UPDATE classes SET instructor_id = 1 WHERE instructor_id IS NULL"
            ];
            
            $totalUpdated = 0;
            foreach ($assignments as $sql) {
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $totalUpdated += $stmt->rowCount();
            }
            
            if ($totalUpdated > 0) {
                $steps[] = "✅ Assigned instructors to $totalUpdated classes";
            } else {
                $steps[] = "ℹ️ All classes already have instructors assigned";
            }
            
        } catch (Exception $e) {
            $steps[] = "⚠️ Error assigning instructors to classes: " . $e->getMessage();
        }
        
        $pdo->commit();
        $transactionStarted = false;
        $message = 'Instructor system setup completed successfully!';
        
    } catch (Exception $e) {
        if ($transactionStarted) {
            try {
                $pdo->rollback();
            } catch (Exception $rollbackException) {
                // Ignore rollback errors
            }
        }
        $error = 'Setup failed: ' . $e->getMessage();
    }
}

// Check current status
$instructorsExists = false;
$instructorIdExists = false;
$instructorCount = 0;

try {
    $stmt = $pdo->query('SELECT COUNT(*) FROM instructors');
    $instructorCount = $stmt->fetchColumn();
    $instructorsExists = true;
} catch (Exception $e) {
    $instructorsExists = false;
}

try {
    $stmt = $pdo->query('SELECT instructor_id FROM classes LIMIT 1');
    $instructorIdExists = true;
} catch (Exception $e) {
    $instructorIdExists = false;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Instructor Setup - Admin</title>
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
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-tools me-2"></i>Fix Instructor Setup</h4>
                    </div>
                    <div class="card-body">
                        
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card <?= $instructorsExists ? 'border-success' : 'border-danger' ?>">
                                    <div class="card-body text-center">
                                        <i class="fas fa-table fa-2x <?= $instructorsExists ? 'text-success' : 'text-danger' ?> mb-2"></i>
                                        <h6>Instructors Table</h6>
                                        <p class="mb-0"><?= $instructorsExists ? "✅ Exists ($instructorCount records)" : "❌ Missing" ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card <?= $instructorIdExists ? 'border-success' : 'border-danger' ?>">
                                    <div class="card-body text-center">
                                        <i class="fas fa-link fa-2x <?= $instructorIdExists ? 'text-success' : 'text-danger' ?> mb-2"></i>
                                        <h6>Column Link</h6>
                                        <p class="mb-0"><?= $instructorIdExists ? "✅ instructor_id exists" : "❌ instructor_id missing" ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card <?= ($instructorsExists && $instructorIdExists) ? 'border-success' : 'border-warning' ?>">
                                    <div class="card-body text-center">
                                        <i class="fas fa-cogs fa-2x <?= ($instructorsExists && $instructorIdExists) ? 'text-success' : 'text-warning' ?> mb-2"></i>
                                        <h6>System Status</h6>
                                        <p class="mb-0"><?= ($instructorsExists && $instructorIdExists) ? "✅ Ready" : "⚠️ Needs Fix" ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

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

                        <?php if (!empty($steps)): ?>
                            <div class="alert alert-info">
                                <h6><i class="fas fa-list me-2"></i>Setup Steps Completed:</h6>
                                <ul class="mb-0">
                                    <?php foreach ($steps as $step): ?>
                                        <li><?= htmlspecialchars($step) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($instructorsExists && $instructorIdExists): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Perfect!</strong> The instructor system is fully set up and working.
                            </div>
                            <div class="d-grid gap-2">
                                <a href="instructors.php" class="btn btn-success">
                                    <i class="fas fa-user-tie me-2"></i>Manage Instructors
                                </a>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-wrench me-2"></i>
                                <strong>Ready to Fix:</strong> This will safely create missing tables and columns.
                            </div>

                            <form method="POST">
                                <div class="d-grid">
                                    <button type="submit" name="fix_now" class="btn btn-primary btn-lg">
                                        <i class="fas fa-magic me-2"></i>Fix Everything Now
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