<?php $pageTitle = 'Home'; require_once 'includes/header.php'; ?>
<!-- Hero Section -->
<section class="hero-section position-relative">
    <div class="video-background position-absolute top-0 start-0 w-100 h-100" style="z-index: 0; overflow: hidden; background: #000;">
        <iframe src="https://www.youtube.com/embed/XqP1YkO25_g?autoplay=1&mute=1&loop=1&playlist=XqP1YkO25_g&controls=0&showinfo=0&rel=0&disablekb=1" frameborder="0" allow="autoplay; fullscreen" style="position: absolute; top: 50%; left: 50%; width: 150vw; height: 150vh; transform: translate(-50%, -50%); pointer-events: none; opacity: 0.5;"></iframe>
    </div>
    <div class="container position-relative" style="z-index: 1;">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="hero-content text-white">
                    <span class="section-badge bg-primary text-white border-0">✈ India's Trusted Flight Booking</span>
                    <h1 class="display-4 fw-bold mb-3">Fly Beyond Your <br><span style="color: #00d4ff;">Imagination</span></h1>
                    <p class="lead mb-4 text-white-50">Discover the easiest way to book flights across India. Smart, fast and reliable platform with real-time availability.</p>
                    <div class="d-flex gap-3 mb-5">
                        <a href="<?php echo BASE_URL; ?>search-flights.php" class="btn btn-accent btn-lg px-4"><i class="bi bi-airplane me-2"></i>Book a Flight</a>
                        <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-outline-light btn-lg px-4 fw-bold">Join Now</a>
                    </div>
                    <div class="hero-stats d-flex gap-4">
                        <div class="stat-item text-white"><h3>500+</h3><p class="text-white-50">Daily Flights</p></div>
                        <div class="stat-item text-white"><h3>50+</h3><p class="text-white-50">Destinations</p></div>
                        <div class="stat-item text-white"><h3>10K+</h3><p class="text-white-50">Happy Travelers</p></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="search-panel">
                    <h4 class="mb-4"><i class="bi bi-search me-2 text-accent"></i>Quick Flight Search</h4>
                    <form action="<?php echo BASE_URL; ?>flight-results.php" method="GET">
                        <div class="mb-3">
                            <label class="form-label">From</label>
                            <select name="source" class="form-select" required>
                                <option value="">Select Origin</option>
                                <option>Delhi</option><option>Mumbai</option><option>Bangalore</option>
                                <option>Kolkata</option><option>Chennai</option><option>Hyderabad</option><option>Pune</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To</label>
                            <select name="destination" class="form-select" required>
                                <option value="">Select Destination</option>
                                <option>Delhi</option><option>Mumbai</option><option>Bangalore</option>
                                <option>Kolkata</option><option>Chennai</option><option>Hyderabad</option><option>Pune</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Travel Date</label>
                            <input type="date" name="travel_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <button type="submit" class="btn btn-search w-100 py-2"><i class="bi bi-search me-2"></i>Search Flights</button>
                    </form>
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
            <p class="text-muted">Experience the next generation of flight booking</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card h-100 p-4 border rounded-4 text-center">
                    <div class="feature-icon mb-3 fs-1 text-accent"><i class="bi bi-lightning-charge"></i></div>
                    <h3 class="h4">Fast Booking</h3>
                    <p class="text-muted mb-0">Book your tickets in less than 2 minutes with our streamlined checkout process.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card h-100 p-4 border rounded-4 text-center">
                    <div class="feature-icon mb-3 fs-1 text-accent"><i class="bi bi-shield-check"></i></div>
                    <h3 class="h4">Secure Payments</h3>
                    <p class="text-muted mb-0">Your data and transactions are protected by industry-leading security protocols.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card h-100 p-4 border rounded-4 text-center">
                    <div class="feature-icon mb-3 fs-1 text-accent"><i class="bi bi-headset"></i></div>
                    <h3 class="h4">24/7 Support</h3>
                    <p class="text-muted mb-0">Our dedicated team is always here to help you with your travel needs anytime.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-5 text-white text-center" style="background-color: var(--primary);">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-3"><h3>50+</h3><p class="mb-0 text-white-50">Airlines</p></div>
            <div class="col-md-3"><h3>100k+</h3><p class="mb-0 text-white-50">Happy Travelers</p></div>
            <div class="col-md-3"><h3>500+</h3><p class="mb-0 text-white-50">Daily Flights</p></div>
            <div class="col-md-3"><h3>20+</h3><p class="mb-0 text-white-50">Major Cities</p></div>
        </div>
    </div>
</section>

<!-- Popular Routes -->
<section class="py-5">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">Popular Flight Routes</h2>
            <a href="<?php echo BASE_URL; ?>search-flights.php" class="text-accent fw-bold text-decoration-none">View All <i class="bi bi-arrow-right"></i></a>
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
                <div class="flight-card text-center h-100">
                    <h5 class="mb-1"><?php echo $r[0]; ?></h5>
                    <i class="bi bi-arrow-down text-accent my-2"></i>
                    <h5 class="mb-2"><?php echo $r[1]; ?></h5>
                    <p class="text-muted small mb-2"><i class="bi bi-clock me-1"></i><?php echo $r[3]; ?></p>
                    <div class="flight-price fs-4 fw-bold text-accent"><?php echo $r[2]; ?><small class="text-muted fs-6">/person</small></div>
                    <a href="<?php echo BASE_URL; ?>search-flights.php" class="btn btn-accent btn-sm mt-3 w-100">View Flights</a>
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
