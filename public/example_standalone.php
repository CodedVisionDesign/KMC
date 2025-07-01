<?php
// Include the layout configuration
if (file_exists(__DIR__ . '/../templates/config.php')) {
    include __DIR__ . '/../templates/config.php';
} else {
    error_log('Template config.php not found');
    die('Template configuration not found');
}

// Set up page-specific configuration
setupPageConfig([
    'pageTitle' => 'Standalone Page Example - Class Booking System',
    'cssPath' => '../assets/css/custom.css',
    'navItems' => getPublicNavigation('example'),
    'footerLinks' => getPublicFooterLinks(),
    'bodyClass' => 'example-page',
    'additionalCSS' => [
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'
    ],
    'additionalHead' => '<meta name="description" content="Example of modular template usage">',
    'additionalFooter' => '<script>console.log("Standalone page loaded");</script>'
]);

// Include header
if (file_exists(__DIR__ . '/../templates/header.php')) {
    include __DIR__ . '/../templates/header.php';
} else {
    error_log('Template header.php not found');
    die('Template header not found');
}
?>

<!-- Page content goes here -->
<div class="row">
    <div class="col-12 mb-4">
        <h2><i class="fas fa-cog me-2"></i>Standalone Page Example</h2>
        <p class="lead">This page demonstrates how to use the modular header and footer components directly.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Features Demonstrated</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">✅ Custom page title</li>
                    <li class="list-group-item">✅ Custom navigation items</li>
                    <li class="list-group-item">✅ Additional CSS (Font Awesome)</li>
                    <li class="list-group-item">✅ Custom body class</li>
                    <li class="list-group-item">✅ Additional head meta tags</li>
                    <li class="list-group-item">✅ Additional footer JavaScript</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Usage Benefits</h5>
            </div>
            <div class="card-body">
                <p><strong>Modular Design:</strong> Header and footer can be included in any page.</p>
                <p><strong>Flexible Configuration:</strong> Each page can customize its appearance.</p>
                <p><strong>Maintainable:</strong> Changes to header/footer affect all pages.</p>
                <p><strong>DRY Principle:</strong> Don't repeat yourself - reuse components.</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="alert alert-info">
            <h6><i class="fas fa-info-circle me-2"></i>Template Structure</h6>
            <p class="mb-0">
                <code>templates/header.php</code> + <code>your-content.php</code> + <code>templates/footer.php</code> = Complete page
            </p>
        </div>
    </div>
</div>

<?php
// Include footer
if (file_exists(__DIR__ . '/../templates/footer.php')) {
    include __DIR__ . '/../templates/footer.php';
} else {
    error_log('Template footer.php not found');
    die('Template footer not found');
}
?> 