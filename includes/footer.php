</div>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <i class="fas fa-print"></i>
                    <span><?php echo APP_NAME; ?></span>
                </div>
                <div class="footer-info">
                    <p>&copy; <?php echo date('Y'); ?> University Print System. All rights reserved.</p>
                </div>
                <div class="footer-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Help & Support</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <?php if (isset($_GET['page']) && $_GET['page'] === 'admin'): ?>
    <script src="assets/js/admin.js"></script>
    <?php endif; ?>
</body>
</html>