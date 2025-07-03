<?php
// Include required files
require_once '../api/db.php';
require_once '../../config/user_auth.php';
require_once '../../config/membership_functions.php';

// Only include martial arts functions if we haven't already loaded the base functions to avoid conflicts
if (file_exists('../../config/martial_arts_membership_functions.php') && !function_exists('canUserBookSpecificClass')) {
    require_once '../../config/martial_arts_membership_functions.php';
}

// Check if user is logged in
if (!isUserLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Get user info
$userInfo = getUserInfo();
$userId = $userInfo['id'];

// Get user's current membership status
$membershipStatus = getUserMembershipStatus($userId);
$availablePlans = getAvailablePlansForUser($userId);

// Handle membership purchase
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase_plan'])) {
    $planId = (int)$_POST['plan_id'];
    
    // Validate plan exists and user can access it (age restrictions)
    $planExists = false;
    $canAccess = false;
    foreach ($availablePlans as $plan) {
        if ($plan['id'] == $planId) {
            $planExists = true;
            break;
        }
    }
    
    // Double-check age eligibility
    if ($planExists) {
        $accessCheck = canUserAccessPlan($userId, $planId);
        $canAccess = $accessCheck['canAccess'];
        if (!$canAccess) {
            $message = "Age restriction: " . $accessCheck['reason'];
            $messageType = "error";
        }
    }
    
    if ($planExists && $canAccess) {
        // Create membership (admin will need to confirm payment)
        try {
            $membershipId = createUserMembership($userId, $planId);
            
            if ($membershipId) {
                $message = "Membership plan selected! Please contact admin to complete payment.";
                $messageType = "success";
                
                // Refresh membership status
                $membershipStatus = getUserMembershipStatus($userId);
            } else {
                $message = "Failed to create membership. Please try again.";
                $messageType = "error";
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = "error";
        }
    } else if (!$planExists) {
        $message = "Invalid membership plan selected.";
        $messageType = "error";
    }
    // Age restriction error message already set above
}

// Include the layout configuration
if (file_exists(__DIR__ . '/../../templates/config.php')) {
    include __DIR__ . '/../../templates/config.php';
}

// Set up page-specific configuration
setupPageConfig([
    'pageTitle' => 'My Membership - Class Booking System',
    'cssPath' => '../../assets/css/custom.css',
    'navItems' => getUserNavigation('membership'),
    'footerLinks' => getPublicFooterLinks(),
    'additionalCSS' => [
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'
    ]
]);

$content = '';

// Show message if any
if ($message) {
    $alertClass = $messageType === 'success' ? 'alert-success' : 'alert-danger';
    $content .= <<<HTML
<div class="alert $alertClass alert-dismissible fade show" role="alert">
    $message
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
HTML;
}

$content .= <<<HTML
<div class="row mb-4">
    <div class="col-12">
        <h1><i class="fas fa-id-card me-2"></i>My Membership</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Membership</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Current Membership Status -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-circle me-2"></i>Current Membership Status</h3>
            </div>
            <div class="card-body">
HTML;

// Check if user has an active membership (regardless of trial status)
$activeMembership = getUserActiveMembership($userId);

if ($activeMembership) {
    // User has an active membership - show membership details
    $statusBadge = 'bg-success';
    $statusIcon = 'fas fa-check-circle';
    
    $startDate = date('M d, Y', strtotime($activeMembership['start_date']));
    $endDate = date('M d, Y', strtotime($activeMembership['end_date']));
    $classLimit = $activeMembership['monthly_class_limit'] ? $activeMembership['monthly_class_limit'] . ' classes' : 'Unlimited';
    $planName = $activeMembership['plan_name'];
    $planDescription = $activeMembership['plan_description'] ?: 'No description available';
    $classesUsed = getUserMonthlyClassCount($userId);
    
    $content .= <<<HTML
                <div class="row">
                    <div class="col-md-6">
                        <h5>Plan: {$planName}</h5>
                        <p class="text-muted">{$planDescription}</p>
                        <p><strong>Monthly Limit:</strong> $classLimit</p>
                        <p><strong>Period:</strong> $startDate - $endDate</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="badge $statusBadge fs-6">
                            <i class="$statusIcon me-1"></i>
                            Active
                        </span>
                        <div class="mt-3">
                            <p><strong>Classes Used This Month:</strong> {$classesUsed}</p>
HTML;
    
    if ($activeMembership['monthly_class_limit']) {
        $remaining = max(0, $activeMembership['monthly_class_limit'] - $classesUsed);
        $content .= <<<HTML
                            <p><strong>Classes Remaining:</strong> $remaining</p>
HTML;
    }
    
    $content .= <<<HTML
                        </div>
                    </div>
                </div>
HTML;
    
    // Check payment status for active membership
    $pdo = connectUserDB();
    $stmt = $pdo->prepare("
        SELECT um.*, mp.name as plan_name, mp.price, mp.gocardless_url, 
               mp.bank_account_name, mp.bank_sort_code, mp.bank_account_number,
               mp.gocardless_visible, mp.bank_details_visible
        FROM user_memberships um 
        JOIN membership_plans mp ON um.plan_id = mp.id 
        WHERE um.user_id = ? AND um.status = 'active'
        ORDER BY um.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $currentMembership = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentMembership) {
        // Check payment status
        if ($currentMembership['payment_method'] === 'pending') {
            // If membership status is 'active', it's considered approved regardless of admin_approved_by field
            if ($currentMembership['status'] === 'active' || $currentMembership['admin_approved_by']) {
                // Admin has approved - show payment options
                $content .= <<<HTML
                <div class="alert alert-success mt-3">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Membership Approved!</strong> Your membership has been approved. Please complete payment using one of the options below.
                </div>
HTML;
                
                // Show GoCardless option if enabled
                if ($currentMembership['gocardless_visible']) {
                    $content .= <<<HTML
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-credit-card me-2"></i>Direct Debit Payment (Recommended)</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Amount:</strong> £{$currentMembership['price']}</p>
                        <p>Set up a Direct Debit for automatic monthly payments using GoCardless.</p>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Benefits of Direct Debit:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Automatic monthly payments - never miss a payment</li>
                                <li>Protected by the Direct Debit Guarantee</li>
                                <li>Easy to cancel or modify</li>
                            </ul>
                        </div>
                        <a href="{$currentMembership['gocardless_url']}" class="btn btn-primary" target="_blank">
                            <i class="fas fa-credit-card me-2"></i>Set Up Direct Debit
                        </a>
                    </div>
                </div>
HTML;
                }
                
                // Show bank transfer option if enabled
                if ($currentMembership['bank_details_visible']) {
                    $content .= <<<HTML
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-university me-2"></i>Bank Transfer</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Amount:</strong> £{$currentMembership['price']}</p>
                        <p>Transfer the membership fee directly to our bank account.</p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Bank Details:</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Account Name:</strong></td><td>{$currentMembership['bank_account_name']}</td></tr>
                                    <tr><td><strong>Sort Code:</strong></td><td>{$currentMembership['bank_sort_code']}</td></tr>
                                    <tr><td><strong>Account Number:</strong></td><td>{$currentMembership['bank_account_number']}</td></tr>
                                    <tr><td><strong>Reference:</strong></td><td>MEMBER-{$userId}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Important:</strong> Please include the reference "MEMBER-{$userId}" so we can identify your payment.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
HTML;
                }
                
                if (!$currentMembership['gocardless_visible'] && !$currentMembership['bank_details_visible']) {
                    $content .= <<<HTML
                <div class="alert alert-info mt-3">
                    <i class="fas fa-clock me-2"></i>
                    <strong>Payment Options Coming Soon</strong><br>
                    Your membership has been approved. Payment options will be available shortly. Please contact us if you have any questions.
                </div>
HTML;
                }
                
            } else {
                // Pending admin approval
                $content .= <<<HTML
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-clock me-2"></i>
                    <strong>Awaiting Approval</strong><br>
                    Your membership application is being reviewed. You will receive payment instructions once approved.
                </div>
HTML;
            }
        } else {
            // Payment completed
            $paymentDate = $currentMembership['payment_date'] ? date('M d, Y', strtotime($currentMembership['payment_date'])) : 'N/A';
            $paymentMethod = ucfirst(str_replace('_', ' ', $currentMembership['payment_method']));
            
            $content .= <<<HTML
                <div class="alert alert-success mt-3">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Payment Completed</strong><br>
                    Payment of £{$currentMembership['price']} received via {$paymentMethod} on {$paymentDate}.
                </div>
HTML;
        }
    }

} elseif ($membershipStatus && is_array($membershipStatus) && isset($membershipStatus['status'])) {
    // Handle other membership statuses (pending, expired, etc.)
    $statusBadge = '';
    $statusIcon = '';
    
    switch ($membershipStatus['status']) {
        case 'free_trial_available':
            $statusBadge = 'bg-info';
            $statusIcon = 'fas fa-gift';
            break;
        case 'expired':
            $statusBadge = 'bg-danger';
            $statusIcon = 'fas fa-times-circle';
            break;
        case 'cancelled':
            $statusBadge = 'bg-secondary';
            $statusIcon = 'fas fa-ban';
            break;
        default:
            $statusBadge = 'bg-warning';
            $statusIcon = 'fas fa-exclamation-triangle';
    }
    
    if ($membershipStatus['status'] === 'free_trial_available') {
        $content .= <<<HTML
                <div class="text-center py-4">
                    <span class="badge $statusBadge fs-6">
                        <i class="$statusIcon me-1"></i>
                        Free Trial Available
                    </span>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-gift me-2"></i>
                        <strong>Welcome!</strong> You have one free trial class available. Book your first class to try us out!
                    </div>
                </div>
HTML;
    } else {
        $startDate = isset($membershipStatus['start_date']) ? date('M d, Y', strtotime($membershipStatus['start_date'])) : 'N/A';
        $endDate = isset($membershipStatus['end_date']) ? date('M d, Y', strtotime($membershipStatus['end_date'])) : 'N/A';
        $classLimit = (isset($membershipStatus['monthly_class_limit']) && $membershipStatus['monthly_class_limit']) ? $membershipStatus['monthly_class_limit'] . ' classes' : 'Unlimited';
        $planName = isset($membershipStatus['plan_name']) ? $membershipStatus['plan_name'] : 'Unknown Plan';
        $planDescription = isset($membershipStatus['plan_description']) ? $membershipStatus['plan_description'] : 'No description available';
        $classesUsed = isset($membershipStatus['classes_used_this_month']) ? $membershipStatus['classes_used_this_month'] : 0;
        
        $content .= <<<HTML
                <div class="row">
                    <div class="col-md-6">
                        <h5>Plan: {$planName}</h5>
                        <p class="text-muted">{$planDescription}</p>
                        <p><strong>Monthly Limit:</strong> $classLimit</p>
                        <p><strong>Period:</strong> $startDate - $endDate</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="badge $statusBadge fs-6">
                            <i class="$statusIcon me-1"></i>
                            {$membershipStatus['status']}
                        </span>
                        <div class="mt-3">
                            <p><strong>Classes Used This Month:</strong> {$classesUsed}</p>
HTML;
        
        if (isset($membershipStatus['monthly_class_limit']) && $membershipStatus['monthly_class_limit']) {
            $remaining = $membershipStatus['monthly_class_limit'] - (isset($membershipStatus['classes_used_this_month']) ? $membershipStatus['classes_used_this_month'] : 0);
            $content .= <<<HTML
                            <p><strong>Classes Remaining:</strong> $remaining</p>
HTML;
        }
        
        $content .= <<<HTML
                        </div>
                    </div>
                </div>
HTML;
    }
    
} else {
    // No active membership
    $hasUsedTrial = hasUserUsedFreeTrial($userId);
    
    $content .= <<<HTML
                <div class="text-center py-4">
                    <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                    <h4>No Active Membership</h4>
HTML;
    
    if (!$hasUsedTrial) {
        $content .= <<<HTML
                    <div class="alert alert-info">
                        <i class="fas fa-gift me-2"></i>
                        <strong>Welcome!</strong> You have one free trial class available. Book your first class to try us out!
                    </div>
                    <p class="text-muted">After your free trial, you'll need to purchase a membership to continue booking classes.</p>
HTML;
    } else {
        $content .= <<<HTML
                    <p class="text-muted">You need an active membership to book classes.</p>
HTML;
    }
    
    $content .= <<<HTML
                </div>
HTML;
}

$content .= <<<HTML
            </div>
        </div>
    </div>
</div>

<!-- Available Membership Plans -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-tags me-2"></i>Available Membership Plans</h3>
            </div>
            <div class="card-body">
                <div class="row">
HTML;

foreach ($availablePlans as $plan) {
    $isCurrentPlan = $membershipStatus && isset($membershipStatus['plan_id']) && $membershipStatus['plan_id'] == $plan['id'];
    $cardClass = $isCurrentPlan ? 'border-primary' : '';
    $planLimit = $plan['monthly_class_limit'] ? $plan['monthly_class_limit'] . ' classes/month' : 'Unlimited classes';
    $price = number_format($plan['price'], 2);
    
    // Skip free trial plan from display
    if ($plan['name'] === 'Free Trial') {
        continue;
    }
    
    $content .= <<<HTML
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100 $cardClass">
HTML;
    
    if ($isCurrentPlan) {
        $content .= <<<HTML
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-crown me-2"></i>Current Plan
                            </div>
HTML;
    }
    
    $content .= <<<HTML
                            <div class="card-body">
                                <h5 class="card-title">{$plan['name']}</h5>
                                <p class="card-text">{$plan['description']}</p>
                                <div class="mb-3">
                                    <h4 class="text-primary">£{$price}</h4>
                                    <small class="text-muted">per month</small>
                                </div>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i>$planLimit</li>
                                    <li><i class="fas fa-check text-success me-2"></i>All class types</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Online booking</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Member video content</li>
                                </ul>
                            </div>
                            <div class="card-footer">
HTML;
    
    if ($isCurrentPlan) {
        $content .= <<<HTML
                                <button class="btn btn-secondary w-100" disabled>
                                    <i class="fas fa-check me-2"></i>Current Plan
                                </button>
HTML;
    } else {
        $content .= <<<HTML
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="plan_id" value="{$plan['id']}">
                                    <button type="submit" name="purchase_plan" class="btn btn-primary w-100">
                                        <i class="fas fa-shopping-cart me-2"></i>Select Plan
                                    </button>
                                </form>
HTML;
    }
    
    $content .= <<<HTML
                            </div>
                        </div>
                    </div>
HTML;
}

$content .= <<<HTML
                </div>
HTML;

// Check if user has any approved memberships with payment options enabled
try {
    $stmt = $pdo->prepare("
        SELECT um.gocardless_visible, um.bank_details_visible, um.status,
               mp.gocardless_url, mp.bank_account_name, mp.bank_sort_code, mp.bank_account_number, mp.price
        FROM user_memberships um
        JOIN membership_plans mp ON um.plan_id = mp.id
        WHERE um.user_id = ? AND um.status IN ('pending', 'approved') 
        ORDER BY um.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $paymentAccess = $stmt->fetch();
    
    if ($paymentAccess && $paymentAccess['status'] === 'approved' && 
        ($paymentAccess['gocardless_visible'] || $paymentAccess['bank_details_visible'])) {
        
        $content .= <<<HTML
                <div class="alert alert-success mt-4">
                    <h5><i class="fas fa-check-circle me-2"></i>Payment Options Available</h5>
                    <p class="mb-3">Your membership has been approved! You can now complete payment using one of the following methods:</p>
HTML;
        
        if ($paymentAccess['gocardless_visible']) {
            $content .= <<<HTML
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-credit-card me-2"></i>GoCardless Direct Debit (Recommended)</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">Set up automatic monthly payments with GoCardless Direct Debit:</p>
                            <ul class="mb-3">
                                <li>Secure and regulated by the FCA</li>
                                <li>Automatic monthly payments - never miss a payment</li>
                                <li>Cancel anytime with 30 days notice</li>
                                <li>Protected by the Direct Debit Guarantee</li>
                            </ul>
                            <a href="{$paymentAccess['gocardless_url']}" class="btn btn-primary" target="_blank">
                                <i class="fas fa-external-link-alt me-2"></i>Set Up Direct Debit
                            </a>
                        </div>
                    </div>
HTML;
        }
        
        if ($paymentAccess['bank_details_visible']) {
            $content .= <<<HTML
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-university me-2"></i>Bank Transfer</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">Transfer payment directly to our bank account:</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Account Name:</strong> {$paymentAccess['bank_account_name']}</p>
                                    <p><strong>Sort Code:</strong> {$paymentAccess['bank_sort_code']}</p>
                                    <p><strong>Account Number:</strong> {$paymentAccess['bank_account_number']}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Reference:</strong> Your name + membership</p>
                                    <p><strong>Amount:</strong> £{$paymentAccess['price']}</p>
                                </div>
                            </div>
                            <div class="alert alert-warning">
                                <small><i class="fas fa-exclamation-triangle me-1"></i>
                                Please include your name and "membership" in the reference so we can identify your payment.</small>
                            </div>
                        </div>
                    </div>
HTML;
        }
        
        $content .= <<<HTML
                    <p class="mb-0 text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Once payment is received, your membership will be activated automatically.
                    </p>
                </div>
HTML;
        
    } else {
        $content .= <<<HTML
                <div class="alert alert-info mt-4">
                    <h5><i class="fas fa-info-circle me-2"></i>Payment Information</h5>
                    <p class="mb-2">After selecting a membership plan:</p>
                    <ol>
                        <li>Your membership application will be created and sent for admin review</li>
                        <li>Once approved, you'll see payment options here</li>
                        <li>Complete your payment using the available methods</li>
                        <li>Your membership will be activated once payment is confirmed</li>
                    </ol>
HTML;
        
        if ($paymentAccess && $paymentAccess['status'] === 'pending') {
            $content .= <<<HTML
                    <div class="alert alert-warning">
                        <i class="fas fa-clock me-2"></i>
                        <strong>Application Pending:</strong> Your membership application is currently being reviewed by our admin team. 
                        Payment options will appear here once approved.
                    </div>
HTML;
        }
        
        $content .= <<<HTML
                    <p class="mb-0"><strong>Available Payment Methods:</strong> Direct Debit, Bank Transfer, Cash, Card</p>
                </div>
HTML;
    }
} catch (Exception $e) {
    error_log('Error checking payment access: ' . $e->getMessage());
    $content .= <<<HTML
                <div class="alert alert-info mt-4">
                    <h5><i class="fas fa-info-circle me-2"></i>Payment Information</h5>
                    <p class="mb-2">After selecting a membership plan, contact our admin team to complete payment.</p>
                    <p class="mb-0"><strong>Available Payment Methods:</strong> Direct Debit, Bank Transfer, Cash, Card</p>
                </div>
HTML;
}

$content .= <<<HTML
            </div>
        </div>
    </div>
</div>

<!-- Membership History -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-history me-2"></i>Membership History</h3>
            </div>
            <div class="card-body">
HTML;

// Get membership history
try {
    $stmt = $pdo->prepare("
        SELECT um.*, mp.name as plan_name, mp.price,
               DATE_FORMAT(um.start_date, '%M %d, %Y') as formatted_start,
               DATE_FORMAT(um.end_date, '%M %d, %Y') as formatted_end
        FROM user_memberships um
        JOIN membership_plans mp ON um.plan_id = mp.id
        WHERE um.user_id = ?
        ORDER BY um.created_at DESC
    ");
    $stmt->execute([$userId]);
    $membershipHistory = $stmt->fetchAll();
    
    if ($membershipHistory) {
        $content .= <<<HTML
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Plan</th>
                                <th>Period</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
HTML;
        
        foreach ($membershipHistory as $history) {
            $statusBadge = '';
            switch ($history['status']) {
                case 'active':
                    $statusBadge = 'bg-success';
                    break;
                case 'expired':
                    $statusBadge = 'bg-secondary';
                    break;
                case 'cancelled':
                    $statusBadge = 'bg-danger';
                    break;
            }
            
            $price = number_format($history['price'], 2);
            $created = date('M d, Y', strtotime($history['created_at']));
            
            $content .= <<<HTML
                            <tr>
                                <td>{$history['plan_name']}</td>
                                <td>{$history['formatted_start']} - {$history['formatted_end']}</td>
                                <td>£{$price}</td>
                                <td><span class="badge $statusBadge">{$history['status']}</span></td>
                                <td>$created</td>
                            </tr>
HTML;
        }
        
        $content .= <<<HTML
                        </tbody>
                    </table>
                </div>
HTML;
    } else {
        $content .= <<<HTML
                <div class="text-center py-4">
                    <i class="fas fa-history fa-2x text-muted mb-3"></i>
                    <p class="text-muted">No membership history yet.</p>
                </div>
HTML;
    }
    
} catch (Exception $e) {
    error_log('Error fetching membership history: ' . $e->getMessage());
    $content .= <<<HTML
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error loading membership history.
                </div>
HTML;
}

$content .= <<<HTML
            </div>
        </div>
    </div>
</div>
HTML;

// Include the base template
if (file_exists(__DIR__ . '/../../templates/base.php')) {
    include __DIR__ . '/../../templates/base.php';
} else {
    echo $content;
}
?> 