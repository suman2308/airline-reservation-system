    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="footer-brand mb-3">
                        <a href="<?php echo isset($isSubDir) ? '../index.php' : 'index.php'; ?>" class="text-decoration-none d-flex align-items-center">
                            <i class="bi bi-airplane-engines fs-3 me-2" style="color: #00d4ff;"></i>
                            <span class="brand-text fs-3 text-white fw-bold" style="font-family: var(--font-heading);">Aero<span style="color: #00d4ff;">Book</span></span>
                        </a>
                    </div>
                    <p class="footer-desc">Smart, Fast and Easy Flight Booking Platform. Book your next journey with confidence and ease.</p>
                    <div class="social-links">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-twitter-x"></i></a>
                        <a href="#"><i class="bi bi-instagram"></i></a>
                        <a href="#"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5 class="footer-heading">Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="<?php echo isset($isSubDir) ? '../index.php' : 'index.php'; ?>">Home</a></li>
                        <li><a href="<?php echo isset($isSubDir) ? '../search-flights.php' : 'search-flights.php'; ?>">Search Flights</a></li>
                        <li><a href="<?php echo isset($isSubDir) ? '../about.php' : 'about.php'; ?>">About Us</a></li>
                        <li><a href="<?php echo isset($isSubDir) ? '../contact.php' : 'contact.php'; ?>">Contact</a></li>
                        <li><a href="<?php echo isset($isSubDir) ? '../admin/login.php' : 'admin/login.php'; ?>">Admin Login</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="footer-heading">Popular Routes</h5>
                    <ul class="footer-links">
                        <li><a href="#">Delhi → Mumbai</a></li>
                        <li><a href="#">Mumbai → Bangalore</a></li>
                        <li><a href="#">Kolkata → Delhi</a></li>
                        <li><a href="#">Chennai → Hyderabad</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="footer-heading">Contact Info</h5>
                    <ul class="footer-contact">
                        <li><i class="bi bi-geo-alt"></i> New Delhi, India 110001</li>
                        <li><i class="bi bi-telephone"></i> +91 98765 43210</li>
                        <li><i class="bi bi-envelope"></i> support@aerobook.in</li>
                        <li><i class="bi bi-clock"></i> 24/7 Customer Support</li>
                    </ul>
                </div>
            </div>
            <hr class="footer-divider">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> AeroBook. All rights reserved. | Designed for Academic Purpose</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo isset($isSubDir) ? '../js/script.js' : 'js/script.js'; ?>"></script>
</body>
</html>
