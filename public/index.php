<?php
// Include the layout configuration
if (file_exists(__DIR__ . '/../templates/config.php')) {
    include __DIR__ . '/../templates/config.php';
} else {
    error_log('Template config.php not found');
    die('Template configuration not found');
}

// Database connection
require_once __DIR__ . '/api/db.php';

// Include user authentication functions
if (file_exists(__DIR__ . '/../config/user_auth.php')) {
    require_once __DIR__ . '/../config/user_auth.php';
}

// Check if user is logged in first
$isLoggedIn = false;
$userName = '';
$userId = 0;

if (function_exists('isUserLoggedIn') && isUserLoggedIn()) {
    $isLoggedIn = true;
    $userInfo = getUserInfo();
    $userName = $userInfo['first_name'] ?? 'User';
    $userId = $userInfo['id'] ?? 0;
}

// Include file upload helper for profile photos
if (file_exists(__DIR__ . '/../config/file_upload_helper.php')) {
    include __DIR__ . '/../config/file_upload_helper.php';
}

// Fetch membership plans from database
$membershipPlans = [];
try {
    $stmt = $pdo->query("
        SELECT id, name, description, price, monthly_class_limit 
        FROM membership_plans 
        WHERE status = 'active' 
        ORDER BY price ASC
    ");
    $membershipPlans = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching membership plans: ' . $e->getMessage());
    $membershipPlans = [];
}

// Fetch instructors from database with enhanced details
$instructors = [];
try {
    $stmt = $pdo->query("
        SELECT id, first_name, last_name, email, phone, bio, specialties, status,
               profile_photo, created_at
        FROM instructors 
        WHERE status = 'active' 
        ORDER BY first_name, last_name
    ");
    $instructors = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching instructors: ' . $e->getMessage());
    $instructors = [];
}

// Fetch unique classes from database for informational display (no duplicates)
// This shows the types of classes available, not individual instances
$classes = [];

// Include martial arts functions for age-based filtering
if (file_exists(__DIR__ . '/../config/martial_arts_membership_functions.php')) {
    require_once __DIR__ . '/../config/martial_arts_membership_functions.php';
}

try {
    // Show all specified classes regardless of user login status or age on the main page
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN c.name LIKE 'Adult Advanced%' THEN 'Adult Advanced'
                WHEN c.name LIKE 'Adult Fundamentals%' THEN 'Adult Fundamentals'
                WHEN c.name LIKE 'Adults Bag Work%' THEN 'Adults Open Mat'
                WHEN c.name LIKE 'Infants%' THEN 'Infants'
                WHEN c.name LIKE 'Juniors%' THEN 'Juniors'
                WHEN c.name LIKE 'Seniors%' THEN 'Seniors'
                ELSE NULL
            END as unique_name,
            MAX(c.description) as description, 
            MAX(c.capacity) as capacity, 
            MIN(c.age_min) as age_min, 
            MAX(c.age_max) as age_max, 
            MAX(c.trial_eligible) as trial_eligible,
            MAX(c.difficulty_level) as difficulty_level,
            MAX(CONCAT(i.first_name, ' ', i.last_name)) as instructor_name,
            MAX(i.email) as instructor_email
        FROM classes c 
        LEFT JOIN instructors i ON c.instructor_id = i.id 
        WHERE c.recurring = 1
        AND (
            c.name LIKE 'Adult Advanced%' OR
            c.name LIKE 'Adult Fundamentals%' OR
            c.name LIKE 'Adults Bag Work%' OR
            c.name LIKE 'Infants%' OR
            c.name LIKE 'Juniors%' OR
            c.name LIKE 'Seniors%'
        )
        GROUP BY unique_name
        HAVING unique_name IS NOT NULL
        ORDER BY 
            CASE unique_name
                WHEN 'Infants' THEN 1
                WHEN 'Juniors' THEN 2
                WHEN 'Seniors' THEN 3
                WHEN 'Adult Fundamentals' THEN 4
                WHEN 'Adult Advanced' THEN 5
                WHEN 'Adults Open Mat' THEN 6
                ELSE 10
            END
    ");
    $classes = $stmt->fetchAll();
    
    // Function to get class image filename
    function getClassImageName($className) {
        $imageMap = [
            'Adult Advanced' => 'adult-advanced.jpg',
            'Adult Fundamentals' => 'adult-fundamentals.jpg',
            'Adults Open Mat' => 'adult-fundamentals.jpg', // Using as placeholder until specific image is added
            'Infants' => 'infants.webp',
            'Juniors' => 'juniors.webp',
            'Seniors' => 'seniors.webp'
        ];
        return $imageMap[$className] ?? 'default-class.jpg';
    }
    
    // Add image information and age group display
    foreach ($classes as &$class) {
        $class['image_filename'] = getClassImageName($class['unique_name']);
        $class['name'] = $class['unique_name']; // Use the cleaned unique name
        
        // Format age range display
        if ($class['age_min'] && $class['age_max']) {
            if ($class['age_max'] == 99) {
                $class['age_display'] = $class['age_min'] . '+ years';
            } else {
                $class['age_display'] = $class['age_min'] . '-' . $class['age_max'] . ' years';
            }
        } else {
            $class['age_display'] = 'All ages';
        }
        
        // Add trial eligibility text
        $class['trial_text'] = $class['trial_eligible'] ? 'Trial Available' : 'No Trial Available';
        $class['trial_class'] = $class['trial_eligible'] ? 'text-success' : 'text-warning';
    }
    // Clear the reference to avoid issues
    unset($class);
    
} catch (PDOException $e) {
    error_log('Error fetching classes: ' . $e->getMessage());
    $classes = [];
}

// Set up page-specific configuration
setupPageConfig([
    'pageTitle' => 'Class Booking - Class Booking System',
    'cssPath' => '../assets/css/custom.css',
    'navItems' => getPublicNavigation('classes'),
    'footerLinks' => getPublicFooterLinks(),
    'additionalCSS' => [
        'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
        'https://cdn.jsdelivr.net/npm/@glidejs/glide@3.6.0/dist/css/glide.core.min.css',
        'https://cdn.jsdelivr.net/npm/@glidejs/glide@3.6.0/dist/css/glide.theme.min.css'
    ],
    'additionalJS' => [
        'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js',
        'https://cdn.jsdelivr.net/npm/@glidejs/glide@3.6.0/dist/glide.min.js',
        '../assets/js/main.js',
        '../assets/js/reviews.js'
    ]
]);

// Authentication check already done above

$content = '';

// Hero Section
$content .= <<<HTML
<!-- Hero Section -->
<div class="hero-section mb-5" style="background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('assets/images/hero.jpg') center/cover; min-height: 70vh; display: flex; align-items: center; color: white;">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-4">Premier Krav Maga Training in Colchester</h1>
                <p class="lead mb-5">Learn effective self-defence techniques in a safe, professional environment with our certified instructors.</p>
                <a href="#classes" class="btn btn-primary btn-lg px-4 py-3">Start Your Journey</a>
            </div>
        </div>
    </div>
</div>

<!-- About Us Section -->
<div class="row mb-5">
    <div class="col-12">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <img src="assets/image.png"" alt="Krav Maga Training" class="img-fluid rounded shadow">
            </div>
            <div class="col-lg-6">
                <h2 class="mb-3">About Us</h2>
                <h3 class="h4 text-primary mb-3">Krav Maga Training</h3>
                <h4 class="h5 mb-4">Colchester's Leading Krav Maga Centre</h4>
                <p>Established in 2010, Krav Maga Colchester has become the premier destination for practical self-defence training in Essex. Our facility is equipped with state-of-the-art training equipment and provides a supportive environment for practitioners of all levels.</p>
                <p>We specialise in authentic Krav Maga techniques as developed by Imi Lichtenfeld and continuously updated by the Krav Maga Global organisation. Our training methodology focuses on real-world scenarios, stress inoculation, and developing both physical and mental resilience.</p>
                <p class="mb-4">Whether you're looking to improve your fitness, build confidence, or learn effective self-defence, our expert instructors will guide you through your Krav Maga journey.</p>
                <a href="#classes" class="btn btn-primary">View Our Classes</a>
            </div>
        </div>
    </div>
</div>

HTML;

// Add user dashboard shortcut for logged-in users
if ($isLoggedIn) {
    $content .= <<<HTML
<!-- User Dashboard Shortcut -->

HTML;
}

$content .= <<<HTML
<!-- Instructors Section -->
<div class="row mb-5">
    <div class="col-12">
        <h2><i class="fas fa-chalkboard-teacher me-2"></i>Our Instructors</h2>
        <div class="row">
HTML;

if (empty($instructors)) {
    $content .= <<<HTML
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No instructors found.
                </div>
            </div>
HTML;
} else {
    foreach ($instructors as $instructor) {
        $bio = !empty($instructor['bio']) ? htmlspecialchars($instructor['bio']) : 'Professional fitness instructor dedicated to helping you achieve your wellness goals.';
        $photoUrl = getProfilePhotoUrl($instructor['profile_photo'], 'instructor');
        $content .= <<<HTML
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100 instructor-card">
                    <div class="card-body text-center">
                        <div class="instructor-photo mb-3">
                            <img src="{$photoUrl}" alt="{$instructor['first_name']} {$instructor['last_name']}" 
                                 class="rounded-circle img-fluid instructor-profile-photo"
                                 style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #f8f9fa;">
                        </div>
                        <h5 class="card-title text-primary">
                            {$instructor['first_name']} {$instructor['last_name']}
                        </h5>
HTML;
        if (!empty($instructor['specialties'])) {
            $title = htmlspecialchars($instructor['specialties']);
            $content .= <<<HTML
                        <p class="instructor-title">
                            {$title}
                        </p>
HTML;
        }
        $content .= <<<HTML
                        <p class="card-text text-muted mb-3" style="font-size: 0.9em;">
                            {$bio}
                        </p>
                        <div class="instructor-contact">
                            <div class="d-flex align-items-center justify-content-center mb-1">
                                <i class="fas fa-envelope text-primary me-2"></i>
                                <small class="text-muted">{$instructor['email']}</small>
                            </div>
HTML;
        if (!empty($instructor['phone'])) {
            $content .= <<<HTML
                            <div class="d-flex align-items-center justify-content-center">
                                <i class="fas fa-phone text-primary me-2"></i>
                                <small class="text-muted">{$instructor['phone']}</small>
                            </div>
HTML;
        }
        $content .= <<<HTML
                        </div>
                    </div>
                </div>
            </div>
HTML;
    }
}

$content .= <<<HTML
        </div>
    </div>
</div>

<!-- Membership Options Section -->
<div class="row mb-5">
    <div class="col-12">
        <h2><i class="fas fa-crown me-2"></i>Membership Options</h2>
        <p class="text-muted mb-4">Choose the perfect membership plan that fits your fitness goals and schedule.</p>
        <div class="row">
HTML;

if (empty($membershipPlans)) {
    $content .= <<<HTML
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Membership plans are being updated. Please check back soon!
                </div>
            </div>
HTML;
} else {
    // Group membership plans
    $seniorPlans = [];
    $juniorPlans = [];
    $otherPlans = [];
    
    foreach ($membershipPlans as $plan) {
        if (stripos($plan['name'], 'Senior') !== false && stripos($plan['name'], 'Sparring') === false) {
            $seniorPlans[] = $plan;
        } elseif (stripos($plan['name'], 'Junior') !== false) {
            $juniorPlans[] = $plan;
        } else {
            $otherPlans[] = $plan;
        }
    }
    
    // Function to create combined card
    function createCombinedCard($plans, $title, $badgeText, $badgeClass, $cardClass, $ageRange) {
        if (empty($plans)) return '';
        
        $html = <<<HTML
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 {$cardClass} position-relative">
                    <div class="position-absolute top-0 start-50 translate-middle">
                        <span class="badge {$badgeClass} px-3 py-2">{$badgeText}</span>
                    </div>
                    <div class="card-body text-center pt-4">
                        <h5 class="card-title text-primary mb-3">{$title}</h5>
                        <p class="text-muted mb-3"><strong>Age Range:</strong> {$ageRange}</p>
                        
                        <div class="row">
HTML;
        
        foreach ($plans as $index => $plan) {
            $price = number_format($plan['price'], 2);
            $classLimit = $plan['monthly_class_limit'] ? $plan['monthly_class_limit'] . ' classes/week' : 'Unlimited classes';
            $planType = stripos($plan['name'], 'Basic') !== false ? 'Basic' : 'Unlimited';
            $colClass = count($plans) == 2 ? 'col-6' : 'col-12';
            
            $html .= <<<HTML
                            <div class="{$colClass} mb-3">
                                <div class="border rounded p-3 h-100" style="background: rgba(0,123,255,0.05);">
                                    <h6 class="text-primary fw-bold">{$planType}</h6>
                                    <div class="mb-2">
                                        <span class="h4 text-dark">£{$price}</span>
                                        <span class="text-muted">/month</span>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted d-block">{$classLimit}</small>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-center mb-1">
                                        <i class="fas fa-check text-success me-1"></i>
                                        <small>All class types</small>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-center mb-1">
                                        <i class="fas fa-check text-success me-1"></i>
                                        <small>Online booking</small>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-check text-success me-1"></i>
                                        <small>Flexible scheduling</small>
                                    </div>
                                </div>
                            </div>
HTML;
        }
        
        $html .= <<<HTML
                        </div>
                    </div>
                </div>
            </div>
HTML;
        
        return $html;
    }
    
    // Function to create single card
    function createSingleCard($plan) {
        $price = number_format($plan['price'], 2);
        $classLimit = $plan['monthly_class_limit'] ? $plan['monthly_class_limit'] . ' classes' : 'Unlimited classes';
        
        // Determine card styling based on plan type
        $cardClass = '';
        $badgeClass = '';
        $badgeText = '';
        
        if (stripos($plan['name'], 'free') !== false || stripos($plan['name'], 'trial') !== false) {
            $cardClass = 'border-success';
            $badgeClass = 'bg-success';
            $badgeText = 'Free Trial';
        } elseif (stripos($plan['name'], 'unlimited') !== false) {
            $cardClass = 'border-warning';
            $badgeClass = 'bg-warning text-dark';
            $badgeText = 'Most Popular';
        } elseif (stripos($plan['name'], 'basic') !== false) {
            $cardClass = 'border-primary';
            $badgeClass = 'bg-primary';
            $badgeText = 'Great Start';
        } elseif (stripos($plan['name'], 'infant') !== false) {
            $cardClass = 'border-info';
            $badgeClass = 'bg-info';
            $badgeText = 'Ages 4-6';
        } else {
            $cardClass = 'border-secondary';
            $badgeClass = 'bg-secondary';
            $badgeText = 'Good Value';
        }
        
        $html = <<<HTML
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 {$cardClass} position-relative">
HTML;
        
        if ($badgeText) {
            $html .= <<<HTML
                    <div class="position-absolute top-0 start-50 translate-middle">
                        <span class="badge {$badgeClass} px-3 py-2">{$badgeText}</span>
                    </div>
HTML;
        }
        
        $html .= <<<HTML
                    <div class="card-body text-center pt-4">
                        <h5 class="card-title text-primary">{$plan['name']}</h5>
                        <div class="mb-3">
                            <span class="h2 text-dark">£{$price}</span>
                            <span class="text-muted">/month</span>
                        </div>
                        <p class="card-text text-muted">{$plan['description']}</p>
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <i class="fas fa-dumbbell text-primary me-2"></i>
                                <strong>{$classLimit}</strong>
                            </div>
                            <div class="d-flex align-items-center justify-content-center mb-1">
                                <i class="fas fa-check text-success me-2"></i>
                                <small>Access to all class types</small>
                            </div>
                            <div class="d-flex align-items-center justify-content-center mb-1">
                                <i class="fas fa-check text-success me-2"></i>
                                <small>Online booking system</small>
                            </div>
                            <div class="d-flex align-items-center justify-content-center mb-1">
                                <i class="fas fa-check text-success me-2"></i>
                                <small>Flexible scheduling</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
HTML;
        
        return $html;
    }
    
    // Create combined Senior card
    if (!empty($seniorPlans)) {
        $content .= createCombinedCard($seniorPlans, 'Senior School Membership', 'Ages 11-15', 'bg-info', 'border-info', '11-15 years');
    }
    
    // Create combined Junior card
    if (!empty($juniorPlans)) {
        $content .= createCombinedCard($juniorPlans, 'Junior Membership', 'Ages 7-11', 'bg-success', 'border-success', '7-11 years');
    }
    
    // Create individual cards for other plans
    foreach ($otherPlans as $plan) {
        $content .= createSingleCard($plan);
    }
}

$content .= <<<HTML
        </div>
        <div class="row">
            <div class="col-12 text-center">
                <div class="alert alert-light border">
                    <p class="mb-2"><strong>💡 Getting Started:</strong></p>
                    <p class="mb-0">
                        New members get <strong>1 free trial class</strong> to experience our fitness community. 
                        After your trial, choose the membership that best fits your lifestyle!
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Available Classes Section -->
<div class="row mb-5" id="classes">
    <div class="col-12">
        <div class="text-center mb-4">
            <h2><i class="fas fa-fist-raised me-2"></i>Available Classes</h2>
            <p class="text-muted">Discover our range of Krav Maga classes designed for all ages and skill levels. Check the timetable below for schedules and frequencies.</p>
        </div>
        <div class="row">
HTML;

if (empty($classes)) {
    $content .= <<<HTML
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No classes found.
                </div>
            </div>
HTML;
} else {
    foreach ($classes as $class) {
        $instructorText = !empty($class['instructor_name']) ? $class['instructor_name'] : 'TBA';
        
        // Check if class image exists, otherwise use placeholder
        $imagePath = "assets/images/classes/{$class['image_filename']}";
        $imageExists = file_exists(__DIR__ . '/' . $imagePath);
        
        if (!$imageExists) {
            // Create a CSS-based placeholder with class name
            $imageHtml = <<<HTML
<div class="class-image-container">
    <div class="class-image-placeholder d-flex align-items-center justify-content-center text-white fw-bold">
        <div>
            <i class="fas fa-fist-raised fa-2x mb-2"></i><br>
            {$class['name']}
        </div>
    </div>
</div>
HTML;
        } else {
            $imageHtml = <<<HTML
<div class="class-image-container">
    <img src="{$imagePath}" class="card-img-top" alt="{$class['name']}" loading="lazy">
</div>
HTML;
        }
        
        $content .= <<<HTML
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm border-0">
                    {$imageHtml}
                    <div class="card-body">
                        <h5 class="card-title text-primary">{$class['name']}</h5>
                        <p class="card-text text-muted small">{$class['description']}</p>
                        
                        <div class="class-details mt-3">
                            <div class="row text-center mb-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">Age Range</small>
                                    <span class="badge bg-info">{$class['age_display']}</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Capacity</small>
                                    <span class="badge bg-secondary">{$class['capacity']} max</span>
                                </div>
                            </div>
                            
                            <div class="text-center mb-2">
                                <small class="text-muted d-block">Instructor</small>
                                <strong class="text-dark">{$instructorText}</strong>
                            </div>
                            
                            <div class="text-center">
                                <span class="badge {$class['trial_class']} px-3 py-2">
                                    <i class="fas fa-graduation-cap me-1"></i>
                                    {$class['trial_text']}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
HTML;
    }
}

$content .= <<<HTML
        </div>
    </div>
</div>

<!-- Calendar/Timetable Section -->
<div class="row" id="classesContainer">
    <div class="col-12 mb-4">
        <h2><i class="fas fa-calendar-week me-2"></i>Class Timetable</h2>
        <div id="calendar" class="border rounded p-4 bg-light calendar-container"></div>
    </div>
    <div class="col-12">
        <div class="alert alert-info">
            <strong>📅 How to Book:</strong> Click on any class in the calendar above to view details and make a booking!
            <br><small class="text-muted mt-2 d-block">
                <i class="fas fa-circle text-success"></i> Available 
                <i class="fas fa-circle text-warning ms-3"></i> Limited spots 
                <i class="fas fa-circle text-danger ms-3"></i> Fully booked
            </small>
        </div>
    </div>
</div>

<!-- Testimonials Section -->
<div class="row mb-5 mt-5">
    <div class="col-12">
        <div class="text-center mb-5">
            <h2><i class="fas fa-star me-2"></i>What Our Members Say</h2>
            <p class="text-muted">Don't just take our word for it - hear from our satisfied members</p>
        </div>
        <div id="testimonials-glide" class="glide">
            <div class="glide__track" data-glide-el="track">
                <ul class="glide__slides">
                    <!-- Reviews will be populated by reviews.js -->
                </ul>
            </div>
            <div class="glide__arrows" data-glide-el="controls">
                <button class="glide__arrow glide__arrow--left btn btn-outline-primary" data-glide-dir="<">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="glide__arrow glide__arrow--right btn btn-outline-primary" data-glide-dir=">">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
        <div class="text-center mt-4">
            <a href="https://search.google.com/local/writereview?placeid=ChIJx0XryPwF2UcRnQYv0rLcmRs"
                target="_blank" class="btn btn-success">
                <i class="fab fa-google me-2"></i>Leave a Google Review
            </a>
        </div>
    </div>
</div>

<!-- Additional Information & FAQ Section -->
<div class="row mb-5 mt-5">
    <div class="col-12">
        <div class="text-center mb-5">
            <h2><i class="fas fa-info-circle me-2"></i>Additional Information & FAQ</h2>
            <p class="text-muted">Everything you need to know about joining and training with us</p>
        </div>
        
        <div class="accordion" id="infoFaqAccordion">
            <!-- Additional Information Section -->
            <div class="accordion-item">
                <h3 class="accordion-header" id="additionalInfoHeading">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#additionalInfoCollapse" aria-expanded="true" 
                            aria-controls="additionalInfoCollapse">
                        <i class="fas fa-pound-sign me-2"></i>
                        <strong>Membership Information & Pricing</strong>
                    </button>
                </h3>
                <div id="additionalInfoCollapse" class="accordion-collapse collapse show" 
                     aria-labelledby="additionalInfoHeading" data-bs-parent="#infoFaqAccordion">
                    <div class="accordion-body">
                        <div class="additional-info">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h5><i class="fas fa-calendar-alt me-2 text-primary"></i>Monthly Membership Fees</h5>
                                    <p>Payments cover 48 weeks of training per year, split into 12 monthly payments. No discounts for shutdown weeks. 1 month's notice required to cancel. All payments are non-refundable.</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h5><i class="fas fa-user-graduate me-2 text-primary"></i>Private Tuition</h5>
                                    <p>Starting at £50 per hour. Prices vary based on number of students, with discounts on block bookings. Please <a href="#contact">contact us</a> to discuss.</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h5><i class="fas fa-id-card me-2 text-primary"></i>Licences</h5>
                                    <p>Kids/Teens - Junior Training Licence £20 per year; Adult Training Licence from £30 per year. Visit <a href="https://www.kravmaga.co.uk/pages/licences" target="_blank">Krav Maga UK</a> for details.</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h5><i class="fas fa-boxing-glove me-2 text-primary"></i>Equipment</h5>
                                    <p>Required equipment must be suitable and approved. Source your own or purchase discounted packs:</p>
                                    <ul>
                                        <li><strong>Adults</strong> - KMC Essentials Pack £116 (+p&amp;p)</li>
                                        <li><strong>Senior School</strong> - KMC Teens Training Pack £54.99 (+p&amp;p)</li>
                                        <li><strong>Juniors</strong> - KMG Junior/Teens Training Pack £54.99 (+p&amp;p)</li>
                                    </ul>
                                    <p><small>Uniform is optional; ask about KMC branded clothing.</small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="accordion-item">
                <h3 class="accordion-header" id="faqHeading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#faqCollapse" aria-expanded="false" 
                            aria-controls="faqCollapse">
                        <i class="fas fa-question-circle me-2"></i>
                        <strong>Frequently Asked Questions</strong>
                    </button>
                </h3>
                <div id="faqCollapse" class="accordion-collapse collapse" 
                     aria-labelledby="faqHeading" data-bs-parent="#infoFaqAccordion">
                    <div class="accordion-body">
                        <div id="faq" class="faq-container">
                            <div class="accordion" id="faqInnerAccordion">
                                <!-- FAQ Item 1 -->
                                <div class="accordion-item faq-item">
                                    <h4 class="accordion-header" id="faq-heading-0">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#faq-answer-0" aria-expanded="false" aria-controls="faq-answer-0">
                                            How do I join?
                                        </button>
                                    </h4>
                                    <div id="faq-answer-0" class="accordion-collapse collapse" aria-labelledby="faq-heading-0" data-bs-parent="#faqInnerAccordion">
                                        <div class="accordion-body">
                                            In order to be offered a membership, you must first attend a free trial. This is to ensure the club is right for you and you are right for the club. You will be advised of the next steps after your trial if you are offered a place within Krav Maga Colchester.
                                        </div>
                                    </div>
                                </div>

                                <!-- FAQ Item 2 -->
                                <div class="accordion-item faq-item">
                                    <h4 class="accordion-header" id="faq-heading-1">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#faq-answer-1" aria-expanded="false" aria-controls="faq-answer-1">
                                            Do I need to get fit before I start?
                                        </button>
                                    </h4>
                                    <div id="faq-answer-1" class="accordion-collapse collapse" aria-labelledby="faq-heading-1" data-bs-parent="#faqInnerAccordion">
                                        <div class="accordion-body">
                                            No. Krav Maga is a different kind of fitness... If you commit to attend at least 2 classes a week, you will definitely become fitter and stronger!
                                        </div>
                                    </div>
                                </div>

                                <!-- FAQ Item 3 -->
                                <div class="accordion-item faq-item">
                                    <h4 class="accordion-header" id="faq-heading-2">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#faq-answer-2" aria-expanded="false" aria-controls="faq-answer-2">
                                            Is it a PAYG, monthly, or termly membership?
                                        </button>
                                    </h4>
                                    <div id="faq-answer-2" class="accordion-collapse collapse" aria-labelledby="faq-heading-2" data-bs-parent="#faqInnerAccordion">
                                        <div class="accordion-body">
                                            Memberships run on a pay monthly basis. You are paying for 48 weeks of the year split into monthly instalments.
                                        </div>
                                    </div>
                                </div>

                                <!-- FAQ Item 4 -->
                                <div class="accordion-item faq-item">
                                    <h4 class="accordion-header" id="faq-heading-3">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#faq-answer-3" aria-expanded="false" aria-controls="faq-answer-3">
                                            Is there an annual contract?
                                        </button>
                                    </h4>
                                    <div id="faq-answer-3" class="accordion-collapse collapse" aria-labelledby="faq-heading-3" data-bs-parent="#faqInnerAccordion">
                                        <div class="accordion-body">
                                            No. We require that you give us one month's notice in writing via email. Contract will be sent upon sign up.
                                        </div>
                                    </div>
                                </div>

                                <!-- FAQ Item 5 -->
                                <div class="accordion-item faq-item">
                                    <h4 class="accordion-header" id="faq-heading-4">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#faq-answer-4" aria-expanded="false" aria-controls="faq-answer-4">
                                            Do I need insurance?
                                        </button>
                                    </h4>
                                    <div id="faq-answer-4" class="accordion-collapse collapse" aria-labelledby="faq-heading-4" data-bs-parent="#faqInnerAccordion">
                                        <div class="accordion-body">
                                            Yes. We are part of Krav Maga Global (KMG) a worldwide Krav Maga governing body. KMG UK operates a safer training scheme. All members of Krav Maga Colchester are required to enter the scheme by obtaining a training licence when joining.
                                        </div>
                                    </div>
                                </div>

                                <!-- FAQ Item 6 -->
                                <div class="accordion-item faq-item">
                                    <h4 class="accordion-header" id="faq-heading-5">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#faq-answer-5" aria-expanded="false" aria-controls="faq-answer-5">
                                            What equipment do I need?
                                        </button>
                                    </h4>
                                    <div id="faq-answer-5" class="accordion-collapse collapse" aria-labelledby="faq-heading-5" data-bs-parent="#faqInnerAccordion">
                                        <div class="accordion-body">
                                            You must bring the required equipment to each class to train. Adults need Boxing Gloves, Grappling Gloves (for sparring), Shin Guards, Groin Guard, Focus Pads, and a Gum Shield. KMG UK offers a full pack including a 1-year licence for £147 + P&amp;P. Seniors need Boxing Gloves, Shin Guards, Groin Guard, and a Gum Shield. Grappling Gloves are required for sparring. A starter pack is available from KMG UK for £54.99. Juniors need Boxing Gloves and Shin Guards.
                                        </div>
                                    </div>
                                </div>

                                <!-- FAQ Item 7 -->
                                <div class="accordion-item faq-item">
                                    <h4 class="accordion-header" id="faq-heading-6">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#faq-answer-6" aria-expanded="false" aria-controls="faq-answer-6">
                                            Is private tuition available?
                                        </button>
                                    </h4>
                                    <div id="faq-answer-6" class="accordion-collapse collapse" aria-labelledby="faq-heading-6" data-bs-parent="#faqInnerAccordion">
                                        <div class="accordion-body">
                                            Yes. Session prices vary depending on your needs and frequency of training. If you would like private lessons with your family or bigger groups, this can also be arranged. Please <a href="#contact">contact us</a> for more information.
                                        </div>
                                    </div>
                                </div>

                                <!-- FAQ Item 8 -->
                                <div class="accordion-item faq-item">
                                    <h4 class="accordion-header" id="faq-heading-7">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#faq-answer-7" aria-expanded="false" aria-controls="faq-answer-7">
                                            Where can I train?
                                        </button>
                                    </h4>
                                    <div id="faq-answer-7" class="accordion-collapse collapse" aria-labelledby="faq-heading-7" data-bs-parent="#faqInnerAccordion">
                                        <div class="accordion-body">
                                            Krav Maga Colchester, Unit 7b Orchards Business Units, Cockaynes Lane, Alresford, CO7 8BZ.
                                        </div>
                                    </div>
                                </div>

                                <!-- FAQ Item 9 -->
                                <div class="accordion-item faq-item">
                                    <h4 class="accordion-header" id="faq-heading-8">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#faq-answer-8" aria-expanded="false" aria-controls="faq-answer-8">
                                            Do you do women's only classes?
                                        </button>
                                    </h4>
                                    <div id="faq-answer-8" class="accordion-collapse collapse" aria-labelledby="faq-heading-8" data-bs-parent="#faqInnerAccordion">
                                        <div class="accordion-body">
                                            Coming Soon! Please add your name to our waitlist and we will contact you when we are ready to go...
                                        </div>
                                    </div>
                                </div>

                                <!-- FAQ Item 10 -->
                                <div class="accordion-item faq-item">
                                    <h4 class="accordion-header" id="faq-heading-9">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#faq-answer-9" aria-expanded="false" aria-controls="faq-answer-9">
                                            Are the classes open all year?
                                        </button>
                                    </h4>
                                    <div id="faq-answer-9" class="accordion-collapse collapse" aria-labelledby="faq-heading-9" data-bs-parent="#faqInnerAccordion">
                                        <div class="accordion-body">
                                            We are open 48 weeks of the year.
                                        </div>
                                    </div>
                                </div>

                                <!-- FAQ Item 11 -->
                                <div class="accordion-item faq-item">
                                    <h4 class="accordion-header" id="faq-heading-10">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#faq-answer-10" aria-expanded="false" aria-controls="faq-answer-10">
                                            Is there a grading system?
                                        </button>
                                    </h4>
                                    <div id="faq-answer-10" class="accordion-collapse collapse" aria-labelledby="faq-heading-10" data-bs-parent="#faqInnerAccordion">
                                        <div class="accordion-body">
                                            Yes. We are committed to helping you become as proficient as possible in practical and effective self-defence, whilst building your confidence, focus and awareness, should you need to use these skills. Your instructor will make sure you are learning the necessary techniques and skills to grade. Grading is not essential.
                                        </div>
                                    </div>
                                </div>

                                <!-- FAQ Item 12 -->
                                <div class="accordion-item faq-item">
                                    <h4 class="accordion-header" id="faq-heading-11">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#faq-answer-11" aria-expanded="false" aria-controls="faq-answer-11">
                                            Do you spar?
                                        </button>
                                    </h4>
                                    <div id="faq-answer-11" class="accordion-collapse collapse" aria-labelledby="faq-heading-11" data-bs-parent="#faqInnerAccordion">
                                        <div class="accordion-body">
                                            Krav Maga is split into 3 sections... Self-defence, Combatives (fighting) and Third party protection. To become proficient in Krav Maga sparring is essential although you are in charge of how light or hard you go. If you really don't want to, we won't force you. We do have a dedicated sparring class on a Saturday!
                                        </div>
                                    </div>
                                </div>

                                <!-- FAQ Item 13 -->
                                <div class="accordion-item faq-item">
                                    <h4 class="accordion-header" id="faq-heading-12">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#faq-answer-12" aria-expanded="false" aria-controls="faq-answer-12">
                                            Your question not here?
                                        </button>
                                    </h4>
                                    <div id="faq-answer-12" class="accordion-collapse collapse" aria-labelledby="faq-heading-12" data-bs-parent="#faqInnerAccordion">
                                        <div class="accordion-body">
                                            Drop us an email or call and we will be happy to answer!
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for class details and booking -->
<div class="modal fade" id="classModal" tabindex="-1" aria-labelledby="classModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="classModalLabel">Class Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="classModalBody">
        <!-- Class details and booking form will be loaded here -->
      </div>
    </div>
  </div>
</div>

<!-- Modal for time slot selection -->
<div class="modal fade" id="timeSlotModal" tabindex="-1" aria-labelledby="timeSlotModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="timeSlotModalLabel">Select Time Slots</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="timeSlotModalBody">
        <!-- Time slot selection will be loaded here -->
      </div>
    </div>
  </div>
</div>
HTML;

// Add JavaScript variables for login state and user bookings
$content .= '<script>';
$content .= 'window.userLoggedIn = ' . ($isLoggedIn ? 'true' : 'false') . ';';
$content .= 'window.userId = ' . $userId . ';';
$content .= 'window.userName = "' . htmlspecialchars($userName) . '";';
$content .= '</script>';

if (file_exists(__DIR__ . '/../templates/base.php')) {
    include __DIR__ . '/../templates/base.php';
} else {
    error_log('Template base.php not found');
    die('Template base not found');
} 