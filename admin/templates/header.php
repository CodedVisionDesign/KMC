<?php
// Admin Header Template
// Ensure admin authentication is checked before including this header
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get current page for navigation highlighting
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Define page titles
$pageTitles = [
    'dashboard' => 'Dashboard',
    'classes' => 'Manage Classes',
    'instructors' => 'Manage Instructors',
    'students' => 'Manage Students',
    'bookings' => 'Manage Bookings',
    'memberships' => 'Membership Management',
    'videos' => 'Video Management',
    'setup_instructors' => 'Setup Instructors',
    'setup_instructors_fixed' => 'Setup Instructors',
    'fix_instructors' => 'Fix Instructors'
];

$pageTitle = $pageTitles[$currentPage] ?? 'Admin Panel';
$fullTitle = $pageTitle . ' - Fitness Studio Admin';

// Check if instructors table exists for navigation
$instructorsTableExists = false;
try {
    if (isset($pdo)) {
        $stmt = $pdo->query("SELECT 1 FROM instructors LIMIT 1");
        $instructorsTableExists = true;
    }
} catch (PDOException $e) {
    $instructorsTableExists = false;
}

// Always link to instructors.php - setup_instructors is only for initial setup
$instructorsLink = 'instructors.php';
$instructorsText = 'Instructors';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($fullTitle) ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Admin CSS -->
    <link href="../assets/css/custom.css" rel="stylesheet">
    
    <!-- Additional page-specific CSS can be added by including $additionalCSS variable -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $cssFile): ?>
            <link href="<?= htmlspecialchars($cssFile) ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <style>
        /* Admin-specific styles */
        .navbar-brand {
            font-weight: bold;
        }
        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1) !important;
            border-radius: 0.375rem;
        }
        .admin-page {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .btn-group-sm > .btn, .btn-sm {
            font-size: 0.875rem;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .alert {
            border: none;
            border-radius: 0.5rem;
        }
        .badge {
            font-weight: 500;
        }
    </style>
</head>
<body class="admin-page">
    <!-- Admin Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-dumbbell me-2"></i>Fitness Studio Admin
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'classes' ? 'active' : '' ?>" href="classes.php">
                            <i class="fas fa-calendar-alt me-1"></i>Classes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= in_array($currentPage, ['instructors', 'setup_instructors', 'setup_instructors_fixed', 'fix_instructors']) ? 'active' : '' ?>" href="<?= $instructorsLink ?>">
                            <i class="fas fa-user-tie me-1"></i><?= $instructorsText ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'students' ? 'active' : '' ?>" href="students.php">
                            <i class="fas fa-users me-1"></i>Students
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'bookings' ? 'active' : '' ?>" href="bookings.php">
                            <i class="fas fa-calendar-check me-1"></i>Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'memberships' ? 'active' : '' ?>" href="memberships.php">
                            <i class="fas fa-credit-card me-1"></i>Memberships
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'videos' ? 'active' : '' ?>" href="videos.php">
                            <i class="fas fa-video me-1"></i>Videos
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>Admin
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="../public/index.php" target="_blank">
                                    <i class="fas fa-external-link-alt me-2"></i>View Public Site
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container-fluid mt-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h2 mb-0">
                            <?php
                            $pageIcons = [
                                'dashboard' => 'fas fa-tachometer-alt',
                                'classes' => 'fas fa-calendar-alt',
                                'instructors' => 'fas fa-user-tie',
                                'students' => 'fas fa-users',
                                'bookings' => 'fas fa-calendar-check',
                                'memberships' => 'fas fa-credit-card',
                                'videos' => 'fas fa-video',
                                'setup_instructors' => 'fas fa-cogs',
                                'setup_instructors_fixed' => 'fas fa-cogs',
                                'fix_instructors' => 'fas fa-tools'
                            ];
                            $icon = $pageIcons[$currentPage] ?? 'fas fa-cog';
                            ?>
                            <i class="<?= $icon ?> me-2 text-primary"></i><?= htmlspecialchars($pageTitle) ?>
                        </h1>
                        <?php if (isset($pageDescription)): ?>
                            <p class="text-muted mb-0"><?= htmlspecialchars($pageDescription) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (isset($headerActions)): ?>
                        <div class="d-flex gap-2">
                            <?= $headerActions ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Alert Messages -->
        <?php if (isset($success) && $success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error) && $error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($message) && $message): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <i class="fas fa-info-circle me-2"></i><?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Page Content Starts Here -->
    </div>
</body>
</html> 