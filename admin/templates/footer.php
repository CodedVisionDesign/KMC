        <!-- Page Content Ends Here -->
    </div>

    <!-- Admin Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-dumbbell me-2"></i>Fitness Studio Admin</h6>
                    <p class="small mb-0">Managing classes, instructors, students, and bookings</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-flex justify-content-md-end justify-content-start gap-3">
                        <a href="dashboard.php" class="text-light text-decoration-none">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                        <a href="../public/index.php" class="text-light text-decoration-none" target="_blank">
                            <i class="fas fa-external-link-alt me-1"></i>Public Site
                        </a>
                        <a href="logout.php" class="text-light text-decoration-none">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </div>
                    <p class="small mb-0 mt-2">© <?= date('Y') ?> Fitness Studio. Admin Panel.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Common Admin JavaScript -->
    <script>
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
            
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Initialize popovers
            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            const popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
            
            // Confirm dialogs for delete actions
            document.querySelectorAll('[data-confirm]').forEach(function(element) {
                element.addEventListener('click', function(e) {
                    const message = this.getAttribute('data-confirm');
                    if (!confirm(message)) {
                        e.preventDefault();
                        return false;
                    }
                });
            });
            
            // Auto-focus first input in modals
            document.querySelectorAll('.modal').forEach(function(modal) {
                modal.addEventListener('shown.bs.modal', function() {
                    const firstInput = this.querySelector('input:not([type="hidden"]), select, textarea');
                    if (firstInput) {
                        firstInput.focus();
                    }
                });
            });
        });
        
        // Utility function for AJAX requests
        function adminAjaxRequest(url, data, successCallback, errorCallback) {
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (successCallback) successCallback(data);
                } else {
                    if (errorCallback) errorCallback(data.error || 'Request failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (errorCallback) errorCallback(error.message);
            });
        }
        
        // Show loading spinner
        function showLoading(element) {
            const originalContent = element.innerHTML;
            element.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Loading...';
            element.disabled = true;
            return originalContent;
        }
        
        // Hide loading spinner
        function hideLoading(element, originalContent) {
            element.innerHTML = originalContent;
            element.disabled = false;
        }
        
        // Toast notification function
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toast-container') || createToastContainer();
            const toast = createToast(message, type);
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            // Remove toast after it's hidden
            toast.addEventListener('hidden.bs.toast', function() {
                toast.remove();
            });
        }
        
        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '1055';
            document.body.appendChild(container);
            return container;
        }
        
        function createToast(message, type) {
            const icons = {
                success: 'fa-check-circle text-success',
                error: 'fa-exclamation-triangle text-danger',
                warning: 'fa-exclamation-triangle text-warning',
                info: 'fa-info-circle text-info'
            };
            
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="toast-header">
                    <i class="fas ${icons[type] || icons.info} me-2"></i>
                    <strong class="me-auto">Admin Notification</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            `;
            return toast;
        }
    </script>
    
    <!-- Page-specific JavaScript can be added by including $additionalJS variable -->
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $jsFile): ?>
            <script src="<?= htmlspecialchars($jsFile) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline JavaScript can be added by including $inlineJS variable -->
    <?php if (isset($inlineJS)): ?>
        <script>
            <?= $inlineJS ?>
        </script>
    <?php endif; ?>
</body>
</html> 