<?php
/**
 * Layout Configuration Helper
 * Use this to set up page-specific variables for modular templates
 */

// Default configuration values
$pageDefaults = [
    'pageTitle' => 'Class Booking System',
    'siteTitle' => 'Class Booking System',
    'cssPath' => '../assets/css/custom.css',
    'homeUrl' => 'index.php',
    'adminUrl' => '../admin/login.php',
    'footerText' => 'Class Booking System',
    'bodyClass' => '',
    'additionalCSS' => [],
    'additionalJS' => [],
    'additionalHead' => '',
    'additionalFooter' => '',
    'navItems' => null, // Will use default nav if null
    'footerLinks' => null, // Will use default footer links if null
];

/**
 * Set up page configuration
 * @param array $config Array of configuration values to override defaults
 * @return array Merged configuration
 */
function setupPageConfig($config = []) {
    global $pageDefaults;
    $pageConfig = array_merge($pageDefaults, $config);
    
    // Extract all config variables to global scope for templates
    foreach ($pageConfig as $key => $value) {
        $GLOBALS[$key] = $value;
    }
    
    return $pageConfig;
}

/**
 * Common navigation configurations
 */
function getPublicNavigation($currentPage = '') {
    // Check if user is logged in (include auth helper if available)
    $isLoggedIn = false;
    $userName = '';
    
    if (file_exists(__DIR__ . '/../config/user_auth.php')) {
        include_once __DIR__ . '/../config/user_auth.php';
        $isLoggedIn = isUserLoggedIn();
        if ($isLoggedIn) {
            $userInfo = getUserInfo();
            $userName = isset($userInfo['first_name']) ? $userInfo['first_name'] : 'User';
        }
    }
    
    $nav = [
        ['title' => 'Classes', 'url' => 'index.php', 'active' => $currentPage === 'classes'],
    ];
    
    if ($isLoggedIn) {
        $nav[] = ['title' => 'My Dashboard', 'url' => 'user/dashboard.php', 'active' => $currentPage === 'dashboard'];
        $nav[] = ['title' => 'My Bookings', 'url' => 'user/bookings.php', 'active' => $currentPage === 'bookings'];
        $nav[] = ['title' => 'Membership', 'url' => 'user/membership.php', 'active' => $currentPage === 'membership'];
        $nav[] = ['title' => 'Videos', 'url' => 'user/videos.php', 'active' => $currentPage === 'videos'];
        $nav[] = ['title' => "Welcome, $userName", 'url' => '#', 'active' => false, 'class' => 'disabled'];
        $nav[] = ['title' => 'Logout', 'url' => 'logout.php', 'active' => false];
    } else {
        $nav[] = ['title' => 'Login', 'url' => 'login.php', 'active' => $currentPage === 'login'];
        $nav[] = ['title' => 'Register', 'url' => 'register.php', 'active' => $currentPage === 'register'];
    }
    
    $nav[] = ['title' => 'Admin', 'url' => '../admin/login.php', 'active' => false];
    
    return $nav;
}

function getUserNavigation($currentPage = '') {
    // Navigation for user pages (like membership.php, videos.php)
    // Check if user is logged in (include auth helper if available)
    $isLoggedIn = false;
    $userName = '';
    
    if (file_exists(__DIR__ . '/../config/user_auth.php')) {
        include_once __DIR__ . '/../config/user_auth.php';
        $isLoggedIn = isUserLoggedIn();
        if ($isLoggedIn) {
            $userInfo = getUserInfo();
            $userName = isset($userInfo['first_name']) ? $userInfo['first_name'] : 'User';
        }
    }
    
    $nav = [
        ['title' => 'Classes', 'url' => '../index.php', 'active' => $currentPage === 'classes'],
    ];
    
    if ($isLoggedIn) {
        $nav[] = ['title' => 'My Dashboard', 'url' => 'dashboard.php', 'active' => $currentPage === 'dashboard'];
        $nav[] = ['title' => 'My Bookings', 'url' => 'bookings.php', 'active' => $currentPage === 'bookings'];
        $nav[] = ['title' => 'Membership', 'url' => 'membership.php', 'active' => $currentPage === 'membership'];
        $nav[] = ['title' => 'Videos', 'url' => 'videos.php', 'active' => $currentPage === 'videos'];
        $nav[] = ['title' => "Welcome, $userName", 'url' => '#', 'active' => false, 'class' => 'disabled'];
        $nav[] = ['title' => 'Logout', 'url' => '../logout.php', 'active' => false];
    } else {
        $nav[] = ['title' => 'Login', 'url' => '../login.php', 'active' => $currentPage === 'login'];
        $nav[] = ['title' => 'Register', 'url' => '../register.php', 'active' => $currentPage === 'register'];
    }
    
    $nav[] = ['title' => 'Admin', 'url' => '../../admin/login.php', 'active' => false];
    
    return $nav;
}

function getAdminNavigation($currentPage = '') {
    return [
        ['title' => 'Dashboard', 'url' => 'dashboard.php', 'active' => $currentPage === 'dashboard'],
        ['title' => 'Classes', 'url' => 'classes.php', 'active' => $currentPage === 'classes'],
        ['title' => 'Instructors', 'url' => 'instructors.php', 'active' => $currentPage === 'instructors'],
        ['title' => 'Students', 'url' => 'students.php', 'active' => $currentPage === 'students'],
        ['title' => 'Bookings', 'url' => 'bookings.php', 'active' => $currentPage === 'bookings'],
        ['title' => 'Logout', 'url' => 'logout.php', 'active' => false],
    ];
}

/**
 * Common footer link configurations
 */
function getPublicFooterLinks() {
    return [
        ['title' => 'Privacy Policy', 'url' => '#'],
        ['title' => 'Terms of Service', 'url' => '#'],
        ['title' => 'Contact', 'url' => '#'],
    ];
}

function getAdminFooterLinks() {
    return [
        ['title' => 'Help', 'url' => '#'],
        ['title' => 'Settings', 'url' => '#'],
    ];
}
?> 