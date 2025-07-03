<?php
// Ensure user is logged in
if (!isUserLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$userInfo = getUserInfo();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'User Dashboard'; ?> - Elite Martial Arts Academy</title>
    <link href="../assets/css/bootstrap-offline.css" rel="stylesheet">
    <link href="../assets/css/fontawesome-offline.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <style>
        .user-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        .user-nav {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 2rem;
        }
        .user-nav .nav-link {
            color: #495057;
            font-weight: 500;
        }
        .user-nav .nav-link.active {
            color: #667eea;
            border-bottom: 2px solid #667eea;
        }
        .user-nav .nav-link:hover {
            color: #667eea;
        }
        .profile-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .logo-img {
            max-height: 50px;
            width: auto;
        }
        .header-logo-section {
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Header with Logo -->
    <div class="user-header">
        <div class="container">
            <!-- Logo Section -->
            <div class="header-logo-section">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <img src="../assets/images/logo.png" alt="Elite Martial Arts Academy" class="logo-img">
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="../index.php" class="btn btn-outline-light me-2">
                            <i class="fas fa-calendar me-1"></i> Book Classes
                        </a>
                        <a href="../logout.php" class="btn btn-light">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- User Welcome Section -->
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-flex align-items-center">
                        <div class="profile-avatar me-3">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h4 class="mb-0">Welcome, <?php echo htmlspecialchars($userInfo['first_name']); ?>!</h4>
                            <small class="opacity-75"><?php echo htmlspecialchars($userInfo['email']); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Navigation -->
    <div class="user-nav">
        <div class="container">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'profile' ? 'active' : ''; ?>" href="profile.php">
                        <i class="fas fa-user-edit me-1"></i> Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'health' ? 'active' : ''; ?>" href="health.php">
                        <i class="fas fa-heartbeat me-1"></i> Health Details
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'emergency' ? 'active' : ''; ?>" href="emergency.php">
                        <i class="fas fa-phone me-1"></i> Emergency Contacts
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'bookings' ? 'active' : ''; ?>" href="bookings.php">
                        <i class="fas fa-calendar-check me-1"></i> My Bookings
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="container"> 