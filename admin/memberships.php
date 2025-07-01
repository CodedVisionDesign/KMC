<?php
require_once 'includes/admin_common.php';

// Handle GET requests for fetching plan data
if (isset($_GET['action']) && $_GET['action'] === 'get_plan' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    try {
        $planId = (int)$_GET['id'];
        
        if ($planId <= 0) {
            throw new Exception('Invalid plan ID');
        }
        
        $stmt = $pdo->prepare("SELECT * FROM membership_plans WHERE id = ?");
        $stmt->execute([$planId]);
        $planData = $stmt->fetch();
        
        if ($planData) {
            // Ensure all expected fields are present with defaults
            $planResult = [
                'id' => $planData['id'],
                'name' => $planData['name'] ?? 'Unknown Plan',
                'description' => $planData['description'] ?? '',
                'price' => $planData['price'] ?? 0,
                'monthly_class_limit' => $planData['monthly_class_limit'] ?? null,
                'status' => $planData['status'] ?? 'active',
                'gocardless_url' => $planData['gocardless_url'] ?? '',
                'bank_account_name' => $planData['bank_account_name'] ?? '',
                'bank_sort_code' => $planData['bank_sort_code'] ?? '',
                'bank_account_number' => $planData['bank_account_number'] ?? ''
            ];
            
            echo json_encode(['success' => true, 'plan' => $planResult]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Plan not found']);
        }
    } catch (Exception $e) {
        error_log('Error in get_plan: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to fetch plan data']);
    }
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'approve_membership':
                $membershipId = (int)$_POST['membership_id'];
                $stmt = $pdo->prepare("UPDATE user_memberships SET status = 'active', start_date = CURRENT_DATE, end_date = DATE_ADD(CURRENT_DATE, INTERVAL 1 MONTH) WHERE id = ?");
                $stmt->execute([$membershipId]);
                echo json_encode(['success' => true, 'message' => 'Membership approved successfully']);
                break;
                
            case 'reject_membership':
                $membershipId = (int)$_POST['membership_id'];
                $reason = $_POST['reason'] ?? 'No reason provided';
                
                // Check if notes column exists, if not just update status
                try {
                    $stmt = $pdo->prepare("UPDATE user_memberships SET status = 'rejected', notes = ? WHERE id = ?");
                    $stmt->execute([$reason, $membershipId]);
                } catch (PDOException $e) {
                    // If notes column doesn't exist, just update status
                    if (strpos($e->getMessage(), 'Unknown column') !== false) {
                        $stmt = $pdo->prepare("UPDATE user_memberships SET status = 'rejected' WHERE id = ?");
                        $stmt->execute([$membershipId]);
                    } else {
                        throw $e; // Re-throw if it's a different error
                    }
                }
                echo json_encode(['success' => true, 'message' => 'Membership rejected']);
                break;
                
            case 'confirm_payment':
                $paymentId = (int)$_POST['payment_id'];
                $method = $_POST['payment_method'] ?? 'unknown';
                $reference = $_POST['reference'] ?? '';
                
                $stmt = $pdo->prepare("UPDATE membership_payments SET status = 'completed', payment_method = ?, reference_number = ?, confirmed_at = NOW() WHERE id = ?");
                $stmt->execute([$method, $reference, $paymentId]);
                
                // Update membership status to active if payment confirmed
                $stmt = $pdo->prepare("
                    UPDATE user_memberships um 
                    JOIN membership_payments mp ON um.id = mp.user_membership_id 
                    SET um.status = 'active', um.start_date = CURRENT_DATE, um.end_date = DATE_ADD(CURRENT_DATE, INTERVAL 1 MONTH)
                    WHERE mp.id = ? AND um.status = 'pending'
                ");
                $stmt->execute([$paymentId]);
                
                echo json_encode(['success' => true, 'message' => 'Payment confirmed and membership activated']);
                break;
                
            case 'cancel_membership':
                $membershipId = (int)$_POST['membership_id'];
                $stmt = $pdo->prepare("UPDATE user_memberships SET status = 'cancelled', end_date = CURRENT_DATE WHERE id = ?");
                $stmt->execute([$membershipId]);
                echo json_encode(['success' => true, 'message' => 'Membership cancelled']);
                break;
                
            case 'add_plan':
                $name = $_POST['name'] ?? '';
                $description = $_POST['description'] ?? '';
                $price = (float)($_POST['price'] ?? 0);
                $classLimit = (int)($_POST['monthly_class_limit'] ?? 0);
                $status = $_POST['status'] ?? 'active';
                $gocardlessUrl = $_POST['gocardless_url'] ?? '';
                $bankAccountName = $_POST['bank_account_name'] ?? '';
                $bankSortCode = $_POST['bank_sort_code'] ?? '';
                $bankAccountNumber = $_POST['bank_account_number'] ?? '';
                
                $stmt = $pdo->prepare("INSERT INTO membership_plans (name, description, price, monthly_class_limit, status, gocardless_url, bank_account_name, bank_sort_code, bank_account_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $price, $classLimit, $status, $gocardlessUrl, $bankAccountName, $bankSortCode, $bankAccountNumber]);
                echo json_encode(['success' => true, 'message' => 'Plan added successfully']);
                break;
                
            case 'update_plan':
                $planId = (int)$_POST['plan_id'];
                $name = $_POST['name'] ?? '';
                $description = $_POST['description'] ?? '';
                $price = (float)($_POST['price'] ?? 0);
                $classLimit = (int)($_POST['monthly_class_limit'] ?? 0);
                $status = $_POST['status'] ?? 'active';
                $gocardlessUrl = $_POST['gocardless_url'] ?? '';
                $bankAccountName = $_POST['bank_account_name'] ?? '';
                $bankSortCode = $_POST['bank_sort_code'] ?? '';
                $bankAccountNumber = $_POST['bank_account_number'] ?? '';
                
                $stmt = $pdo->prepare("UPDATE membership_plans SET name = ?, description = ?, price = ?, monthly_class_limit = ?, status = ?, gocardless_url = ?, bank_account_name = ?, bank_sort_code = ?, bank_account_number = ? WHERE id = ?");
                $stmt->execute([$name, $description, $price, $classLimit, $status, $gocardlessUrl, $bankAccountName, $bankSortCode, $bankAccountNumber, $planId]);
                echo json_encode(['success' => true, 'message' => 'Plan updated successfully']);
                break;
                
            case 'delete_plan':
                $planId = (int)$_POST['plan_id'];
                
                // Check if plan is in use
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_memberships WHERE plan_id = ?");
                $stmt->execute([$planId]);
                $inUse = $stmt->fetchColumn() > 0;
                
                if ($inUse) {
                    echo json_encode(['success' => false, 'message' => 'Cannot delete plan - it is currently in use by members']);
                } else {
                    $stmt = $pdo->prepare("DELETE FROM membership_plans WHERE id = ?");
                    $stmt->execute([$planId]);
                    echo json_encode(['success' => true, 'message' => 'Plan deleted successfully']);
                }
                break;
                
            case 'get_plan':
                $planId = (int)$_POST['plan_id'];
                
                if ($planId <= 0) {
                    throw new Exception('Invalid plan ID');
                }
                
                $stmt = $pdo->prepare("SELECT * FROM membership_plans WHERE id = ?");
                $stmt->execute([$planId]);
                $planData = $stmt->fetch();
                
                if ($planData) {
                    // Ensure all expected fields are present with defaults
                    $planResult = [
                        'id' => $planData['id'],
                        'name' => $planData['name'] ?? 'Unknown Plan',
                        'description' => $planData['description'] ?? '',
                        'price' => $planData['price'] ?? 0,
                        'monthly_class_limit' => $planData['monthly_class_limit'] ?? null,
                        'status' => $planData['status'] ?? 'active',
                        'gocardless_url' => $planData['gocardless_url'] ?? '',
                        'bank_account_name' => $planData['bank_account_name'] ?? '',
                        'bank_sort_code' => $planData['bank_sort_code'] ?? '',
                        'bank_account_number' => $planData['bank_account_number'] ?? ''
                    ];
                    
                    echo json_encode(['success' => true, 'plan' => $planResult]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Plan not found']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

$activeTab = $_GET['tab'] ?? 'requests';

// Fetch pending membership requests
$pendingMemberships = [];
try {
    $stmt = $pdo->query("
        SELECT um.*, u.first_name, u.last_name, u.email, mp.name as plan_name, mp.price
        FROM user_memberships um
        JOIN users u ON um.user_id = u.id
        JOIN membership_plans mp ON um.plan_id = mp.id
        WHERE um.status = 'pending'
        ORDER BY um.created_at DESC
    ");
    $pendingMemberships = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching pending memberships: ' . $e->getMessage());
}

// Fetch active memberships
$activeMemberships = [];
try {
    $stmt = $pdo->query("
        SELECT um.*, u.first_name, u.last_name, u.email, mp.name as plan_name, mp.price
        FROM user_memberships um
        JOIN users u ON um.user_id = u.id
        JOIN membership_plans mp ON um.plan_id = mp.id
        WHERE um.status = 'active'
        ORDER BY um.end_date ASC
    ");
    $activeMemberships = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching active memberships: ' . $e->getMessage());
}

// Fetch pending payments
$pendingPayments = [];
try {
    $stmt = $pdo->query("
        SELECT mp.*, um.id as membership_id, u.first_name, u.last_name, u.email, 
               plan.name as plan_name, plan.price
        FROM membership_payments mp
        JOIN user_memberships um ON mp.user_membership_id = um.id
        JOIN users u ON um.user_id = u.id
        JOIN membership_plans plan ON um.plan_id = plan.id
        WHERE mp.status = 'pending'
        ORDER BY mp.created_at DESC
    ");
    $pendingPayments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching pending payments: ' . $e->getMessage());
}

// Fetch membership plans
$membershipPlans = [];
try {
    $stmt = $pdo->query("SELECT * FROM membership_plans ORDER BY price ASC");
    $membershipPlans = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching membership plans: ' . $e->getMessage());
}

// Check if tables exist and show setup message if not
$tablesExist = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'membership_plans'");
    $tablesExist = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    $tablesExist = false;
}

if (!$tablesExist) {
    $content = <<<HTML
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="alert alert-warning">
                <h4><i class="fas fa-exclamation-triangle me-2"></i>Membership System Not Set Up</h4>
                <p>The membership management system needs to be configured before you can manage memberships.</p>
                <div class="mt-3">
                    <a href="setup_membership_video.php" class="btn btn-primary">
                        <i class="fas fa-cog me-2"></i>Set Up Membership System
                    </a>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;
} else {
    // Prepare tab navigation variables
    $requestsActive = $activeTab === 'requests' ? 'active' : '';
    $activeActive = $activeTab === 'active' ? 'active' : '';
    $paymentsActive = $activeTab === 'payments' ? 'active' : '';
    $plansActive = $activeTab === 'plans' ? 'active' : '';
    
    $requestsShow = $activeTab === 'requests' ? 'show active' : '';
    $activeShow = $activeTab === 'active' ? 'show active' : '';
    $paymentsShow = $activeTab === 'payments' ? 'show active' : '';
    $plansShow = $activeTab === 'plans' ? 'show active' : '';
    
    $pendingCount = count($pendingMemberships);
    $activeMembershipCount = count($activeMemberships);
    $pendingPaymentCount = count($pendingPayments);
    $planCount = count($membershipPlans);

    $content = <<<HTML
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-crown me-2"></i>Membership Management</h1>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPlanModal">
            <i class="fas fa-plus me-2"></i>Add Plan
        </button>
    </div>
</div>

<!-- Navigation Tabs -->
<ul class="nav nav-tabs mb-4" id="membershipTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link {$requestsActive}" id="requests-tab" data-bs-toggle="tab" data-bs-target="#requests" type="button" role="tab">
            <i class="fas fa-clock me-2"></i>Pending Requests
            <span class="badge bg-warning ms-2">{$pendingCount}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link {$activeActive}" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab">
            <i class="fas fa-check-circle me-2"></i>Active Memberships
            <span class="badge bg-success ms-2">{$activeMembershipCount}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link {$paymentsActive}" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">
            <i class="fas fa-credit-card me-2"></i>Pending Payments
            <span class="badge bg-info ms-2">{$pendingPaymentCount}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link {$plansActive}" id="plans-tab" data-bs-toggle="tab" data-bs-target="#plans" type="button" role="tab">
            <i class="fas fa-list me-2"></i>Membership Plans
            <span class="badge bg-secondary ms-2">{$planCount}</span>
        </button>
    </li>
</ul>

<div class="tab-content" id="membershipTabContent">
    <!-- Pending Requests Tab -->
    <div class="tab-pane fade {$requestsShow}" id="requests" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-clock me-2"></i>Pending Membership Requests</h5>
            </div>
            <div class="card-body">
HTML;

if (empty($pendingMemberships)) {
    $content .= <<<HTML
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No pending membership requests.
                </div>
HTML;
} else {
    $content .= <<<HTML
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Plan</th>
                                <th>Price</th>
                                <th>Request Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
HTML;

    foreach ($pendingMemberships as $membership) {
        $requestDate = date('M j, Y', strtotime($membership['created_at']));
        $price = number_format($membership['price'], 2);
        
        $content .= <<<HTML
                            <tr>
                                <td>
                                    <strong>{$membership['first_name']} {$membership['last_name']}</strong><br>
                                    <small class="text-muted">{$membership['email']}</small>
                                </td>
                                <td>{$membership['plan_name']}</td>
                                <td>£{$price}/month</td>
                                <td>{$requestDate}</td>
                                <td>
                                    <button class="btn btn-success btn-sm me-2" onclick="showPaymentOptions({$membership['id']}, {$membership['plan_id']})">
                                        <i class="fas fa-check me-1"></i>Approve & Show Payment
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="rejectMembership({$membership['id']})">
                                        <i class="fas fa-times me-1"></i>Reject
                                    </button>
                                </td>
                            </tr>
HTML;
    }
    
    $content .= <<<HTML
                        </tbody>
                    </table>
                </div>
HTML;
}

$content .= <<<HTML
            </div>
        </div>
    </div>

    <!-- Active Memberships Tab -->
    <div class="tab-pane fade {$activeShow}" id="active" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-check-circle me-2"></i>Active Memberships</h5>
            </div>
            <div class="card-body">
HTML;

if (empty($activeMemberships)) {
    $content .= <<<HTML
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No active memberships.
                </div>
HTML;
} else {
    $content .= <<<HTML
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Plan</th>
                                <th>Price</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
HTML;

    foreach ($activeMemberships as $membership) {
        $startDate = date('M j, Y', strtotime($membership['start_date']));
        $endDate = date('M j, Y', strtotime($membership['end_date']));
        $price = number_format($membership['price'], 2);
        $isExpiring = strtotime($membership['end_date']) < strtotime('+7 days');
        $statusClass = $isExpiring ? 'warning' : 'success';
        $statusText = $isExpiring ? 'Expiring Soon' : 'Active';
        
        $content .= <<<HTML
                            <tr>
                                <td>
                                    <strong>{$membership['first_name']} {$membership['last_name']}</strong><br>
                                    <small class="text-muted">{$membership['email']}</small>
                                </td>
                                <td>{$membership['plan_name']}</td>
                                <td>£{$price}/month</td>
                                <td>{$startDate}</td>
                                <td>{$endDate}</td>
                                <td><span class="badge bg-{$statusClass}">{$statusText}</span></td>
                                <td>
                                    <button class="btn btn-danger btn-sm" onclick="cancelMembership({$membership['id']})">
                                        <i class="fas fa-ban me-1"></i>Cancel
                                    </button>
                                </td>
                            </tr>
HTML;
    }
    
    $content .= <<<HTML
                        </tbody>
                    </table>
                </div>
HTML;
}

$content .= <<<HTML
            </div>
        </div>
    </div>

    <!-- Pending Payments Tab -->
    <div class="tab-pane fade {$paymentsShow}" id="payments" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-credit-card me-2"></i>Pending Payments</h5>
            </div>
            <div class="card-body">
HTML;

if (empty($pendingPayments)) {
    $content .= <<<HTML
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No pending payments.
                </div>
HTML;
} else {
    $content .= <<<HTML
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Payment Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
HTML;

    foreach ($pendingPayments as $payment) {
        $paymentDate = date('M j, Y', strtotime($payment['created_at']));
        $amount = number_format($payment['amount'], 2);
        
        $content .= <<<HTML
                            <tr>
                                <td>
                                    <strong>{$payment['first_name']} {$payment['last_name']}</strong><br>
                                    <small class="text-muted">{$payment['email']}</small>
                                </td>
                                <td>{$payment['plan_name']}</td>
                                <td>£{$amount}</td>
                                <td>{$paymentDate}</td>
                                <td>
                                    <button class="btn btn-success btn-sm" onclick="confirmPayment({$payment['id']})">
                                        <i class="fas fa-check me-1"></i>Confirm Payment
                                    </button>
                                </td>
                            </tr>
HTML;
    }
    
    $content .= <<<HTML
                        </tbody>
                    </table>
                </div>
HTML;
}

$content .= <<<HTML
            </div>
        </div>
    </div>

    <!-- Membership Plans Tab -->
    <div class="tab-pane fade {$plansShow}" id="plans" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>Membership Plans</h5>
            </div>
            <div class="card-body">
                <div class="row">
HTML;

foreach ($membershipPlans as $plan) {
    $price = number_format($plan['price'], 2);
    $classLimit = $plan['monthly_class_limit'] ? $plan['monthly_class_limit'] . ' classes' : 'Unlimited classes';
    
    $content .= <<<HTML
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">{$plan['name']}</h5>
                                <p class="card-text">{$plan['description']}</p>
                                <div class="mb-3">
                                    <strong>Price:</strong> £{$price}/month<br>
                                    <strong>Classes:</strong> {$classLimit}
                                </div>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-outline-primary btn-sm" onclick="editPlan({$plan['id']})">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" onclick="deletePlan({$plan['id']})">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
HTML;
}

$content .= <<<HTML
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Options Modal -->
<div class="modal fade" id="paymentOptionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Membership Approved - Payment Options</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>Membership has been approved! Please provide the customer with the following payment options:
                </div>
                
                <div id="paymentOptionsContent">
                    <!-- Payment options will be populated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Plan Modal -->
<div class="modal fade" id="addPlanModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="planModalTitle">Add New Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="planForm">
                    <input type="hidden" id="planId" name="plan_id">
                    <div class="mb-3">
                        <label for="planName" class="form-label">Plan Name</label>
                        <input type="text" class="form-control" id="planName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="planDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="planDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="planPrice" class="form-label">Price (£)</label>
                                <input type="number" class="form-control" id="planPrice" name="price" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="planClassLimit" class="form-label">Monthly Class Limit</label>
                                <input type="number" class="form-control" id="planClassLimit" name="monthly_class_limit" min="0" placeholder="0 for unlimited">
                                <small class="form-text text-muted">Leave 0 for unlimited classes</small>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="planStatus" class="form-label">Status</label>
                        <select class="form-select" id="planStatus" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <hr>
                    <h6 class="mb-3">Payment Options</h6>
                    <div class="mb-3">
                        <label for="gocardlessUrl" class="form-label">GoCardless Payment URL</label>
                        <input type="url" class="form-control" id="gocardlessUrl" name="gocardless_url" placeholder="https://pay.gocardless.com/billing/...">
                        <small class="form-text text-muted">Direct link for customers to pay via GoCardless</small>
                    </div>
                    <h6 class="mb-3">Bank Transfer Details</h6>
                    <div class="mb-3">
                        <label for="bankAccountName" class="form-label">Account Name</label>
                        <input type="text" class="form-control" id="bankAccountName" name="bank_account_name" placeholder="Fitness Studio Ltd">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bankSortCode" class="form-label">Sort Code</label>
                                <input type="text" class="form-control" id="bankSortCode" name="bank_sort_code" placeholder="20-00-00" maxlength="8">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bankAccountNumber" class="form-label">Account Number</label>
                                <input type="text" class="form-control" id="bankAccountNumber" name="bank_account_number" placeholder="12345678" maxlength="12">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitPlan()">Save Plan</button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Confirmation Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="paymentForm">
                    <input type="hidden" id="paymentId" name="payment_id">
                    <div class="mb-3">
                        <label for="paymentMethod" class="form-label">Payment Method</label>
                        <select class="form-select" id="paymentMethod" name="payment_method" required>
                            <option value="">Select payment method</option>
                            <option value="cash">Cash</option>
                            <option value="card">Credit/Debit Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="paypal">PayPal</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="reference" class="form-label">Reference Number (Optional)</label>
                        <input type="text" class="form-control" id="reference" name="reference" placeholder="Transaction ID, check number, etc.">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitPaymentConfirmation()">Confirm Payment</button>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Membership</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="rejectionForm">
                    <input type="hidden" id="rejectionMembershipId" name="membership_id">
                    <div class="mb-3">
                        <label for="rejectionReason" class="form-label">Reason for Rejection</label>
                        <textarea class="form-control" id="rejectionReason" name="reason" rows="3" placeholder="Please provide a reason for rejection..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="submitRejection()">Reject Membership</button>
            </div>
        </div>
    </div>
</div>

<script>
function approveMembership(membershipId) {
    if (confirm('Are you sure you want to approve this membership?')) {
        fetch('memberships.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=approve_membership&membership_id=' + membershipId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function showPaymentOptions(membershipId, planId) {
    // First approve the membership
    fetch('memberships.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=approve_membership&membership_id=' + membershipId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Get plan details for payment options
            fetch('memberships.php?action=get_plan&id=' + planId)
                .then(response => response.json())
                .then(planData => {
                    if (planData.success) {
                        const plan = planData.plan;
                        showPaymentModal(plan);
                    }
                });
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function showPaymentModal(plan) {
    let content = `
        <div class="row">
            <div class="col-md-12 mb-4">
                <h6><i class="fas fa-info-circle me-2"></i>Plan Details</h6>
                <div class="card">
                    <div class="card-body">
                        <h5>` + plan.name + ` - £` + parseFloat(plan.price).toFixed(2) + `/month</h5>
                        <p class="text-muted">` + (plan.description || 'No description available') + `</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // GoCardless option
    if (plan.gocardless_url) {
        content += `
            <div class="row mb-4">
                <div class="col-md-12">
                    <h6><i class="fas fa-credit-card me-2"></i>Option 1: GoCardless Direct Debit</h6>
                    <div class="card">
                        <div class="card-body">
                            <p>Send this link to the customer for automatic monthly payments:</p>
                            <div class="input-group">
                                <input type="text" class="form-control" id="gocardlessUrl" value="` + plan.gocardless_url + `" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('gocardlessUrl')">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                            <small class="text-muted">Customer can set up automatic monthly payments via this secure link.</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Bank transfer option
    if (plan.bank_account_name && plan.bank_sort_code && plan.bank_account_number) {
        content += `
            <div class="row">
                <div class="col-md-12">
                    <h6><i class="fas fa-university me-2"></i>Option 2: Bank Transfer</h6>
                    <div class="card">
                        <div class="card-body">
                            <p>For manual bank transfer payments, provide these details:</p>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Account Name:</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="bankAccountName" value="` + plan.bank_account_name + `" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('bankAccountName')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Sort Code:</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="bankSortCode" value="` + plan.bank_sort_code + `" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('bankSortCode')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Account Number:</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="bankAccountNumber" value="` + plan.bank_account_number + `" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('bankAccountNumber')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Payment Amount:</strong> £` + parseFloat(plan.price).toFixed(2) + ` per month<br>
                                <strong>Reference:</strong> Customer should include their name and "Membership" in the reference
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    document.getElementById('paymentOptionsContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('paymentOptionsModal')).show();
    
    // Reload page after modal is closed
    document.getElementById('paymentOptionsModal').addEventListener('hidden.bs.modal', function () {
        location.reload();
    });
}

function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    element.select();
    element.setSelectionRange(0, 99999); // For mobile devices
    document.execCommand('copy');
    
    // Show feedback
    const button = element.nextElementSibling;
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check"></i> Copied!';
    button.classList.remove('btn-outline-secondary');
    button.classList.add('btn-success');
    
    setTimeout(() => {
        button.innerHTML = originalHTML;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-secondary');
    }, 2000);
}

function rejectMembership(membershipId) {
    document.getElementById('rejectionMembershipId').value = membershipId;
    new bootstrap.Modal(document.getElementById('rejectionModal')).show();
}

function submitRejection() {
    const formData = new FormData(document.getElementById('rejectionForm'));
    formData.append('action', 'reject_membership');
    
    fetch('memberships.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function confirmPayment(paymentId) {
    document.getElementById('paymentId').value = paymentId;
    new bootstrap.Modal(document.getElementById('paymentModal')).show();
}

function submitPaymentConfirmation() {
    const formData = new FormData(document.getElementById('paymentForm'));
    formData.append('action', 'confirm_payment');
    
    fetch('memberships.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function cancelMembership(membershipId) {
    if (confirm('Are you sure you want to cancel this membership?')) {
        fetch('memberships.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=cancel_membership&membership_id=' + membershipId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function editPlan(planId) {
    // Fetch plan data and populate form
    fetch('memberships.php?action=get_plan&id=' + planId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const plan = data.plan;
                document.getElementById('planModalTitle').textContent = 'Edit Plan';
                document.getElementById('planId').value = plan.id;
                document.getElementById('planName').value = plan.name;
                document.getElementById('planDescription').value = plan.description;
                document.getElementById('planPrice').value = plan.price;
                document.getElementById('planClassLimit').value = plan.monthly_class_limit || 0;
                document.getElementById('planStatus').value = plan.status;
                document.getElementById('gocardlessUrl').value = plan.gocardless_url || '';
                document.getElementById('bankAccountName').value = plan.bank_account_name || '';
                document.getElementById('bankSortCode').value = plan.bank_sort_code || '';
                document.getElementById('bankAccountNumber').value = plan.bank_account_number || '';
                new bootstrap.Modal(document.getElementById('addPlanModal')).show();
            } else {
                alert('Error loading plan: ' + data.message);
            }
        });
}

function deletePlan(planId) {
    if (confirm('Are you sure you want to delete this plan? This action cannot be undone.')) {
        fetch('memberships.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=delete_plan&plan_id=' + planId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function submitPlan() {
    const formData = new FormData(document.getElementById('planForm'));
    const action = document.getElementById('planId').value ? 'update_plan' : 'add_plan';
    formData.append('action', action);
    
    fetch('memberships.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// Reset form when adding new plan
function resetPlanForm() {
    document.getElementById('planModalTitle').textContent = 'Add New Plan';
    document.getElementById('planForm').reset();
    document.getElementById('planId').value = '';
}

// Set active tab based on URL parameter
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab) {
        const tabElement = document.getElementById(tab + '-tab');
        if (tabElement) {
            tabElement.click();
        }
    }
    
    // Reset form when Add Plan button is clicked
    document.querySelector('[data-bs-target="#addPlanModal"]').addEventListener('click', resetPlanForm);
});
</script>
HTML;
}

// Render the admin page
renderAdminPage($content, [
    'pageDescription' => 'Manage memberships, approve requests, and track payments'
]); 