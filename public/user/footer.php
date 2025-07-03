    </div> <!-- End container -->

    <!-- Footer -->
    <footer class="mt-5 py-4 bg-light border-top">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">&copy; 2024 Class Booking System. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i> Your data is secure and protected
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS (offline) -->
    <script src="../assets/js/bootstrap-offline.js"></script>
    
    <!-- Custom JavaScript for user dashboard -->
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        // Confirm before deleting anything
        function confirmDelete(message) {
            return confirm(message || 'Are you sure you want to delete this item?');
        }

        // Form validation helper
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (form) {
                return form.checkValidity();
            }
            return true;
        }
    </script>
</body>
</html> 