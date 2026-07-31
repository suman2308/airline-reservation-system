<?php $pageTitle = 'Home'; require_once 'includes/header.php'; ?>
<!-- Hero Section -->
<section class="hero-section position-relative">
    <div class="container position-relative" style="z-index: 1;">
        <div class="row align-items-center">
            <div class="col-lg-12 text-center">
                <div class="hero-content text-white py-5">
                    <span class="section-badge bg-primary text-white border-0">✈ India's Trusted Flight Booking</span>
                    <h1 class="display-3 fw-bold mb-3">Fly Beyond Your <span style="color: #00d4ff;">Imagination</span></h1>
                    <p class="lead mb-5 text-white-50 mx-auto" style="max-width: 600px;">Discover the easiest way to book flights. Smart, fast and reliable platform with real-time availability.</p>

                    <!-- Portal Selection Cards -->
                    <div class="row justify-content-center g-4 mb-4">
                        <div class="col-md-5">
                            <a href="<?php echo BASE_URL; ?>search-flights.php?region=domestic" class="text-decoration-none">
                                <div class="portal-card text-center p-5 rounded-4 hover-lift" style="background: linear-gradient(135deg, rgba(2, 77, 236, 0.2), rgba(0, 212, 255, 0.1)); border: 2px solid rgba(2, 77, 236, 0.3); backdrop-filter: blur(10px);">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 80px; height: 80px; background: rgba(255,255,255,0.1);">
                                        <span style="font-size: 2.5rem;">🇮🇳</span>
                                    </div>
                                    <h3 class="fw-bold text-white mb-2">Domestic Flights</h3>
                                    <p class="text-white-50 mb-0">Explore flights across India's top cities</p>
                                    <div class="d-flex justify-content-center gap-3 mt-3">
                                        <span class="badge bg-primary-subtle text-white px-3 py-2 rounded-pill">Delhi</span>
                                        <span class="badge bg-primary-subtle text-white px-3 py-2 rounded-pill">Mumbai</span>
                                        <span class="badge bg-primary-subtle text-white px-3 py-2 rounded-pill">Bangalore</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-5">
                            <a href="<?php echo BASE_URL; ?>search-flights.php?region=international" class="text-decoration-none">
                                <div class="portal-card text-center p-5 rounded-4 hover-lift" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(0, 212, 255, 0.1)); border: 2px solid rgba(16, 185, 129, 0.3); backdrop-filter: blur(10px);">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 80px; height: 80px; background: rgba(255,255,255,0.1);">
                                        <span style="font-size: 2.5rem;">🌍</span>
                                    </div>
                                    <h3 class="fw-bold text-white mb-2">International Flights</h3>
                                    <p class="text-white-50 mb-0">Fly to destinations around the world</p>
                                    <div class="d-flex justify-content-center gap-3 mt-3">
                                        <span class="badge bg-success-subtle text-white px-3 py-2 rounded-pill">Dubai</span>
                                        <span class="badge bg-success-subtle text-white px-3 py-2 rounded-pill">Singapore</span>
                                        <span class="badge bg-success-subtle text-white px-3 py-2 rounded-pill">Bangkok</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <div class="d-flex gap-3 justify-content-center mt-4">
                        <a href="<?php echo BASE_URL; ?>search-flights.php?region=domestic" class="btn btn-accent btn-lg px-5 fw-bold"><i class="bi bi-airplane me-2"></i>Book a Flight</a>
                        <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-outline-light btn-lg px-5 fw-bold">Join Now</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5 bg-white">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Why Choose AeroBook?</h2>
            <p class="text-muted">Experience the next generation of flight booking with interactive seat selection</p>
        </div>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="feature-card h-100 p-4 border rounded-4 text-center hover-lift">
                    <div class="feature-icon mb-3 fs-1 text-accent"><i class="bi bi-grid-3x3-gap-fill"></i></div>
                    <h3 class="h5 fw-bold">Interactive Seat Map</h3>
                    <p class="text-muted mb-0">View live vacant & occupied seats on an airplane layout and pick your exact seats.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="feature-card h-100 p-4 border rounded-4 text-center hover-lift">
                    <div class="feature-icon mb-3 fs-1 text-accent"><i class="bi bi-lightning-charge"></i></div>
                    <h3 class="h5 fw-bold">Instant Booking</h3>
                    <p class="text-muted mb-0">Book your tickets in less than 2 minutes with automated real-time seat locks.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="feature-card h-100 p-4 border rounded-4 text-center hover-lift">
                    <div class="feature-icon mb-3 fs-1 text-accent"><i class="bi bi-shield-check"></i></div>
                    <h3 class="h5 fw-bold">Secure Payments</h3>
                    <p class="text-muted mb-0">Your data and transactions are protected by industry-grade encryption.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="feature-card h-100 p-4 border rounded-4 text-center hover-lift">
                    <div class="feature-icon mb-3 fs-1 text-accent"><i class="bi bi-headset"></i></div>
                    <h3 class="h5 fw-bold">24/7 Assistance</h3>
                    <p class="text-muted mb-0">Dedicated support team to assist with reservations and cancellations anytime.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-5 text-white text-center" style="background-color: var(--primary);">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3"><h3>50+</h3><p class="mb-0 text-white" style="color: #ffffff !important; opacity: 0.9;">Airlines</p></div>
            <div class="col-md-3"><h3>100k+</h3><p class="mb-0 text-white" style="color: #ffffff !important; opacity: 0.9;">Happy Travelers</p></div>
            <div class="col-md-3"><h3>500+</h3><p class="mb-0 text-white" style="color: #ffffff !important; opacity: 0.9;">Daily Flights</p></div>
            <div class="col-md-3"><h3>20+</h3><p class="mb-0 text-white" style="color: #ffffff !important; opacity: 0.9;">Major Cities</p></div>
        </div>
    </div>
</section>

<!-- Popular Routes -->
<section class="py-5">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">Popular Domestic Routes</h2>
            <a href="<?php echo BASE_URL; ?>search-flights.php?region=domestic" class="text-accent fw-bold text-decoration-none">View All <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-4">
            <?php
            $routes = [
                ['Delhi', 'Mumbai', '₹4,599', '2h 15m'],
                ['Mumbai', 'Bangalore', '₹5,250', '1h 45m'],
                ['Kolkata', 'Delhi', '₹4,150', '2h 25m'],
                ['Chennai', 'Hyderabad', '₹3,800', '1h 15m'],
            ];
            foreach ($routes as $r): ?>
            <div class="col-md-6 col-lg-3">
                <div class="flight-card text-center h-100 hover-lift">
                    <h5 class="mb-1"><?php echo $r[0]; ?></h5>
                    <i class="bi bi-arrow-down text-accent my-2"></i>
                    <h5 class="mb-2"><?php echo $r[1]; ?></h5>
                    <p class="text-muted small mb-2"><i class="bi bi-clock me-1"></i><?php echo $r[3]; ?></p>
                    <div class="flight-price fs-4 fw-bold text-accent"><?php echo $r[2]; ?><small class="text-muted fs-6">/person</small></div>
                    <a href="<?php echo BASE_URL; ?>search-flights.php?region=domestic&source=<?php echo urlencode($r[0]); ?>&destination=<?php echo urlencode($r[1]); ?>" class="btn btn-accent btn-sm mt-3 w-100">View Flights</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-5 text-center text-white">
    <div class="container py-4">
        <h2 class="display-5 fw-bold mb-3">Ready to Take Off?</h2>
        <p class="lead mb-4">Join thousands of happy travelers who trust AeroBook for their flight bookings.</p>
        <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-light btn-lg px-5 fw-bold text-accent">Create Free Account</a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
