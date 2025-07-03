<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Elite Martial Arts Academy'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo isset($cssPath) ? $cssPath : '../assets/css/custom.css'; ?>">
    <!-- Real-time Availability Styles -->
    <link rel="stylesheet" href="<?php echo isset($cssPath) ? str_replace('custom.css', 'realtime-availability.css', $cssPath) : '../assets/css/realtime-availability.css'; ?>">
    
    <!-- Additional CSS can be added by individual pages -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Additional head content can be added by individual pages -->
    <?php if (isset($additionalHead)): ?>
        <?php echo $additionalHead; ?>
    <?php endif; ?>
    
    <style>
        .logo-img {
            max-height: 60px;
            width: auto;
        }
        .navbar-brand .logo-img {
            max-height: 40px;
        }
    </style>
</head>
<body class="<?php echo isset($bodyClass) ? $bodyClass : ''; ?>">
    <header class="bg-primary text-white p-3 mb-4">
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-dark">
                <a class="navbar-brand d-flex align-items-center" href="<?php echo isset($homeUrl) ? $homeUrl : 'index.php'; ?>">
                    <img src="<?php echo isset($logoPath) ? $logoPath : '../assets/images/logo.png'; ?>" alt="Home" class="logo-img me-2">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <?php if (isset($navItems) && is_array($navItems)): ?>
                            <?php foreach ($navItems as $item): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo (isset($item['active']) && $item['active']) ? 'active' : ''; ?> <?php echo isset($item['class']) ? $item['class'] : ''; ?>" 
                                       href="<?php echo $item['url']; ?>">
                                        <?php echo $item['title']; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Default navigation -->
                            <li class="nav-item"><a class="nav-link" href="index.php">Classes</a></li>
                            <li class="nav-item"><a class="nav-link" href="../admin/login.php">Admin</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>
        </div>
    </header>
    <main class="container mb-5"> 