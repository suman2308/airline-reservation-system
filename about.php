<?php $pageTitle = 'About Us'; $bareNav = true; require_once 'includes/header.php'; ?>
<div class="page-hero-lite">
    <div class="container">
        <span class="kicker">Our Story</span>
        <h1>Flying, <span class="dim">perfected.</span></h1>
        <p>Your trusted partner for seamless, premium flight journeys — from quick domestic hops to long-haul international travel.</p>
    </div>
</div>

<div class="container py-5" style="padding-top: 5rem;">
    <div class="row align-items-center g-5">
        <div class="col-lg-6 reveal">
            <span class="kicker">Who we are</span>
            <h2 class="lp-heading">Redefining the way you <span class="dim">travel</span></h2>
            <p class="lp-lead mb-4">AeroBook was born out of a simple idea: making flight booking as easy as sending a text message.</p>
            <p class="text-gray-500 mb-4">Our platform combines cutting-edge technology with a user-centric design to provide you with the fastest and most reliable booking experience. Whether you're travelling for business or leisure, we ensure you get the best deals and a seamless journey from search to landing.</p>
            <div class="row g-3">
                <div class="col-6 col-md-6 col-xl-3">
                    <div class="lp-stat">
                        <div class="lp-stat-value">500+</div>
                        <div class="lp-stat-label">Daily Flights Tracked</div>
                    </div>
                </div>
                <div class="col-6 col-md-6 col-xl-3">
                    <div class="lp-stat">
                        <div class="lp-stat-value">20+</div>
                        <div class="lp-stat-label">Major Cities Covered</div>
                    </div>
                </div>
                <div class="col-6 col-md-6 col-xl-3">
                    <div class="lp-stat">
                        <div class="lp-stat-value">100k+</div>
                        <div class="lp-stat-label">Happy Travelers</div>
                    </div>
                </div>
                <div class="col-6 col-md-6 col-xl-3">
                    <div class="lp-stat">
                        <div class="lp-stat-value">50+</div>
                        <div class="lp-stat-label">Partner Airlines</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 reveal" style="--reveal-delay: 120ms;">
            <div class="lp-story-panel" style="min-height: 380px;">
                <i class="bi bi-airplane-engines plane-icon"></i>
                <div>
                    <h3>Built for travellers, trusted by thousands.</h3>
                    <p>Real-time availability, interactive seat maps, and instant confirmation — the modern way to fly.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-5 pt-5 g-4">
        <?php
        $values = [
            ['bi bi-eye', 'Our Vision', 'To be India\'s most loved and reliable travel companion, connecting people and places effortlessly.'],
            ['bi bi-bullseye', 'Our Mission', 'Providing a transparent, fast, and secure booking platform that empowers travellers with choice and convenience.'],
            ['bi bi-heart', 'Our Values', 'Transparency, innovation, and a customer-first approach are the core principles that drive us forward.'],
        ];
        foreach ($values as $v): ?>
        <div class="col-md-4 reveal">
            <div class="benefit-card">
                <div class="benefit-icon"><i class="<?php echo $v[0]; ?>"></i></div>
                <h3><?php echo $v[1]; ?></h3>
                <p><?php echo $v[2]; ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- CTA -->
<section class="lp-section" style="padding-top: 1rem;">
    <div class="container">
        <div class="lp-cta reveal">
            <h2>Experience the difference.</h2>
            <p>Join thousands of happy travellers who trust AeroBook for their flight bookings.</p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="<?php echo BASE_URL; ?>search-flights.php" class="btn btn-white">Search Flights</a>
                <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-ghost-white">Create free account</a>
            </div>
        </div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
