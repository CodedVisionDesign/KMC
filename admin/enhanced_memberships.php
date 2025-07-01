<?php
require_once 'includes/admin_common.php';

// Start session for CSRF token (if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    // Verify CSRF token for all form submissions
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $error = ErrorMessages::CSRF_INVALID;
    } else {
        if ($action === 'approve_membership') {
            $membership_id = intval($_POST['membership_id'] ?? 0);
            $enable_gocardless = isset($_POST['enable_gocardless']) ? 1 : 0;
            $enable_bank_details = isset($_POST['enable_bank_details']) ? 1 : 0;
            
            if ($membership_id > 0) {
                try {
                    $stmt = $pdo->prepare('UPDATE user_memberships SET 
                        status = "approved", 
                        admin_approved_at = NOW(), 
                        admin_approved_by = ?, 
                        gocardless_visible = ?, 
                        bank_details_visible = ? 
                        WHERE id = ?');
                    $stmt->execute([1, $enable_gocardless, $enable_bank_details, $membership_id]); // TODO: Use actual admin ID
                    $message = 'Membership approved successfully';
                } catch (Exception $e) {
                    error_log('Failed to approve membership: ' . $e->getMessage());
                    $error = 'Failed to approve membership';
                }
            }
        } elseif ($action === 'record_payment') {
            $membership_id = intval($_POST['membership_id'] ?? 0);
            $payment_method = $_POST['payment_method'] ?? '';
            $payment_reference = trim($_POST['payment_reference'] ?? '');
            $payment_notes = trim($_POST['payment_notes'] ?? '');
            
            if ($membership_id > 0 && in_array($payment_method, ['gocardless', 'bank_transfer', 'cash', 'card'])) {
                try {
                    $stmt = $pdo->prepare('UPDATE user_memberships SET 
                        payment_received = 1, 
                        payment_method = ?, 
                        payment_date = NOW(), 
                        payment_reference = ?, 
                        payment_notes = ?, 
                        admin_approved_by = ? 
                        WHERE id = ?');
                    $stmt->execute([$payment_method, $payment_reference, $payment_notes, 1, $membership_id]); // TODO: Use actual admin ID
                    $message = 'Payment recorded successfully';
                } catch (Exception $e) {
                    error_log('Failed to record payment: ' . $e->getMessage());
                    $error = 'Failed to record payment';
                }
            } else {
                $error = 'Invalid payment method or membership ID';
            }
        } elseif ($action === 'reject_membership') {
            $membership_id = intval($_POST['membership_id'] ?? 0);
            $rejection_reason = trim($_POST['rejection_reason'] ?? '');
            
            if ($membership_id > 0) {
                try {
                    $stmt = $pdo->prepare('UPDATE user_memberships SET 
                        status = "rejected", 
                        admin_approved_at = NOW(), 
                        admin_approved_by = ?, 
                        payment_notes = ? 
                        WHERE id = ?');
                    $stmt->execute([1, $rejection_reason, $membership_id]); // TODO: Use actual admin ID
                    $message = 'Membership rejected';
                } catch (Exception $e) {
                    error_log('Failed to reject membership: ' . $e->getMessage());
                    $error = 'Failed to reject membership';
                }
            }
        }
    }
}

// Handle success message from redirect
if (isset($_GET['success'])) {
    $message = $_GET['success'];
}

// Get all memberships with user info
try {
    $stmt = $pdo->query('
        SELECT 
            um.*,
            u.first_name,
            u.last_name,
            u.email,
            u.phone,
            mp.name as plan_name,
            mp.price as plan_price,
            mp.duration_days
        FROM user_memberships um 
        LEFT JOIN users u ON um.user_id = u.id 
        LEFT JOIN membership_plans mp ON um.plan_id = mp.id 
        ORDER BY um.created_at DESC
    ');
    $memberships = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Failed to load memberships: ' . $e->getMessage());
    $memberships = [];
    $error = 'Failed to load memberships';
}

// Generate CSRF token
$csrfToken = generateCSRFToken();

require_once 'templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-credit-card"></i> Enhanced Membership Management</h1>
                <div>
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Memberships Table -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-users"></i> Membership Applications & Payments</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($memberships)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No membership applications found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Member</th>
                                        <th>Plan</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Applied</th>
                                        <th>Payment Access</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($memberships as $membership): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary">#<?= $membership['id'] ?></span></td>
                                        <td>
                                            <strong><?= htmlspecialchars($membership['first_name'] . ' ' . $membership['last_name']) ?></strong>
                                            <br><small class="text-muted"><?= htmlspecialchars($membership['email']) ?></small>
                                            <?php if ($membership['phone']): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($membership['phone']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($membership['plan_name']) ?></strong>
                                            <br><small class="text-muted">£<?= number_format($membership['plan_price'], 2) ?></small>
                                            <br><small class="text-muted"><?= $membership['duration_days'] ?> days</small>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = match($membership['status']) {
                                                'pending' => 'bg-warning',
                                                'approved' => 'bg-success',
                                                'rejected' => 'bg-danger',
                                                'expired' => 'bg-secondary',
                                                default => 'bg-info'
                                            };
                                            ?>
                                            <span class="badge <?= $statusClass ?>"><?= ucfirst($membership['status']) ?></span>
                                            <?php if ($membership['admin_approved_at']): ?>
                                                <br><small class="text-muted">
                                                    <?= date('M j, Y', strtotime($membership['admin_approved_at'])) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($membership['payment_received']): ?>
                                                <span class="badge bg-success">Paid</span>
                                                <br><small class="text-muted"><?= ucfirst(str_replace('_', ' ', $membership['payment_method'])) ?></small>
                                                <?php if ($membership['payment_date']): ?>
                                                    <br><small class="text-muted"><?= date('M j, Y', strtotime($membership['payment_date'])) ?></small>
                                                <?php endif; ?>
                                                <?php if ($membership['payment_reference']): ?>
                                                    <br><small class="text-muted">Ref: <?= htmlspecialchars($membership['payment_reference']) ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Pending</span>
                                                <br><small class="text-muted"><?= ucfirst(str_replace('_', ' ', $membership['payment_method'])) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= date('M j, Y', strtotime($membership['created_at'])) ?>
                                            <br><small class="text-muted"><?= date('g:i A', strtotime($membership['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <?php if ($membership['gocardless_visible']): ?>
                                                    <span class="badge bg-info">GoCardless</span><br>
                                                <?php endif; ?>
                                                <?php if ($membership['bank_details_visible']): ?>
                                                    <span class="badge bg-info">Bank Details</span><br>
                                                <?php endif; ?>
                                                <?php if (!$membership['gocardless_visible'] && !$membership['bank_details_visible']): ?>
                                                    <span class="text-muted">Hidden</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($membership['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-outline-success" 
                                                            onclick="showApprovalModal(<?= $membership['id'] ?>, '<?= htmlspecialchars($membership['first_name'] . ' ' . $membership['last_name'], ENT_QUOTES) ?>')">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="showRejectionModal(<?= $membership['id'] ?>, '<?= htmlspecialchars($membership['first_name'] . ' ' . $membership['last_name'], ENT_QUOTES) ?>')">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if (!$membership['payment_received']): ?>
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            onclick="showPaymentModal(<?= $membership['id'] ?>, '<?= htmlspecialchars($membership['first_name'] . ' ' . $membership['last_name'], ENT_QUOTES) ?>')">
                                                        <i class="fas fa-money-bill"></i> Record Payment
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button type="button" class="btn btn-outline-info" 
                                                        onclick="showDetailsModal(<?= htmlspecialchars(json_encode($membership), ENT_QUOTES) ?>)">
                                                    <i class="fas fa-eye"></i> Details
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Membership</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="approve_membership">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="membership_id" id="approval_membership_id">
                    
                    <p>Approve membership for <strong id="approval_member_name"></strong>?</p>
                    
                    <div class="mb-3">
                        <label class="form-label">Enable Payment Options for User:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="enable_gocardless" name="enable_gocardless" value="1">
                            <label class="form-check-label" for="enable_gocardless">
                                Show GoCardless Direct Debit option
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="enable_bank_details" name="enable_bank_details" value="1">
                            <label class="form-check-label" for="enable_bank_details">
                                Show Bank Transfer details
                            </label>
                        </div>
                        <div class="form-text">Select which payment methods the user can see after approval.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Membership</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payment Recording Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="record_payment">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="membership_id" id="payment_membership_id">
                    
                    <p>Record payment for <strong id="payment_member_name"></strong></p>
                    
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method *</label>
                        <select class="form-select" name="payment_method" id="payment_method" required>
                            <option value="">Select payment method...</option>
                            <option value="gocardless">GoCardless Direct Debit</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card Payment</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_reference" class="form-label">Payment Reference</label>
                        <input type="text" class="form-control" name="payment_reference" id="payment_reference" 
                               placeholder="Transaction ID, reference number, etc.">
                        <div class="form-text">Optional: Any reference number or transaction ID</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_notes" class="form-label">Admin Notes</label>
                        <textarea class="form-control" name="payment_notes" id="payment_notes" rows="3" 
                                  placeholder="Any additional notes about this payment..."></textarea>
                        <div class="form-text">Internal notes for audit purposes</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Record Payment</button>
                </div>
            </form>
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
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject_membership">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="membership_id" id="rejection_membership_id">
                    
                    <p>Reject membership for <strong id="rejection_member_name"></strong>?</p>
                    
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Reason for Rejection</label>
                        <textarea class="form-control" name="rejection_reason" id="rejection_reason" rows="3" 
                                  placeholder="Explain why this membership is being rejected..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Membership</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Membership Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsModalBody">
                <!-- Details will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function showApprovalModal(membershipId, memberName) {
    document.getElementById('approval_membership_id').value = membershipId;
    document.getElementById('approval_member_name').textContent = memberName;
    new bootstrap.Modal(document.getElementById('approvalModal')).show();
}

function showPaymentModal(membershipId, memberName) {
    document.getElementById('payment_membership_id').value = membershipId;
    document.getElementById('payment_member_name').textContent = memberName;
    new bootstrap.Modal(document.getElementById('paymentModal')).show();
}

function showRejectionModal(membershipId, memberName) {
    document.getElementById('rejection_membership_id').value = membershipId;
    document.getElementById('rejection_member_name').textContent = memberName;
    new bootstrap.Modal(document.getElementById('rejectionModal')).show();
}

function showDetailsModal(membershipData) {
    const membership = JSON.parse(membershipData);
    const modalBody = document.getElementById('detailsModalBody');
    
    modalBody.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>Member Information</h6>
                <p><strong>Name:</strong> ${membership.first_name} ${membership.last_name}</p>
                <p><strong>Email:</strong> ${membership.email}</p>
                <p><strong>Phone:</strong> ${membership.phone || 'Not provided'}</p>
            </div>
            <div class="col-md-6">
                <h6>Membership Details</h6>
                <p><strong>Plan:</strong> ${membership.plan_name}</p>
                <p><strong>Price:</strong> £${parseFloat(membership.plan_price).toFixed(2)}</p>
                <p><strong>Duration:</strong> ${membership.duration_days} days</p>
                <p><strong>Status:</strong> <span class="badge bg-info">${membership.status}</span></p>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <h6>Payment Information</h6>
                <p><strong>Payment Received:</strong> ${membership.payment_received ? 'Yes' : 'No'}</p>
                <p><strong>Payment Method:</strong> ${membership.payment_method.replace('_', ' ')}</p>
                ${membership.payment_date ? `<p><strong>Payment Date:</strong> ${new Date(membership.payment_date).toLocaleDateString()}</p>` : ''}
                ${membership.payment_reference ? `<p><strong>Reference:</strong> ${membership.payment_reference}</p>` : ''}
            </div>
            <div class="col-md-6">
                <h6>Admin Actions</h6>
                <p><strong>GoCardless Visible:</strong> ${membership.gocardless_visible ? 'Yes' : 'No'}</p>
                <p><strong>Bank Details Visible:</strong> ${membership.bank_details_visible ? 'Yes' : 'No'}</p>
                ${membership.admin_approved_at ? `<p><strong>Approved:</strong> ${new Date(membership.admin_approved_at).toLocaleDateString()}</p>` : ''}
            </div>
        </div>
        ${membership.payment_notes ? `
        <hr>
        <h6>Admin Notes</h6>
        <p>${membership.payment_notes}</p>
        ` : ''}
    `;
    
    new bootstrap.Modal(document.getElementById('detailsModal')).show();
}
</script>

<?php require_once 'templates/footer.php'; ?> 