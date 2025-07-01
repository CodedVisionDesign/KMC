    </main>
    
    <footer class="bg-light text-center py-3 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6 text-start">
                    <small>&copy; <?php echo date('Y'); ?> <?php echo isset($footerText) ? $footerText : 'Class Booking System'; ?></small>
                </div>
                <div class="col-md-6 text-end">
                    <?php if (isset($footerLinks) && is_array($footerLinks)): ?>
                        <?php foreach ($footerLinks as $link): ?>
                            <a href="<?php echo $link['url']; ?>" class="text-decoration-none me-3">
                                <small><?php echo $link['title']; ?></small>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <a href="<?php echo isset($adminUrl) ? $adminUrl : '../admin/login.php'; ?>" class="text-decoration-none">
                            <small>Admin Panel</small>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Real-time Availability System - Disabled -->
    <!-- <script src="<?php echo isset($jsPath) ? str_replace('main.js', 'realtime-availability.js', $jsPath) : '../assets/js/realtime-availability.js'; ?>"></script> -->
    
    <!-- Additional JavaScript can be added by individual pages -->
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Additional footer content can be added by individual pages -->
    <?php if (isset($additionalFooter)): ?>
        <?php echo $additionalFooter; ?>
    <?php endif; ?>
    
</body>
</html> 