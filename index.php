<?php
$pageTitle = 'Smart Flight Booking';
$landingNav = true;
require_once 'includes/header.php';
require_once 'includes/helpers.php';
?>
<!-- ═══════════ Hero ═══════════ -->
<section class="hero-lp" id="start">
    <div class="hero-vignette"></div>
    <div class="hero-content">
        <div class="hero-main">
            <div class="hero-copy">
                <span class="hero-kicker">Flight Booking</span>
                <h1 class="hero-headline">
                    <span class="hero-line hero-line-1">Fly more.</span>
                    <span class="hero-line hero-line-2">Pay less.</span>
                </h1>
                <p class="hero-subtitle">Search hundreds of flights, compare fares in seconds, and book with confidence — all in one place.</p>
                <div class="hero-actions">
                    <a href="#story" class="btn btn-gray btn-pill px-4 py-2">Explore</a>
                    <a href="<?php echo BASE_URL; ?>search-flights.php" class="btn btn-ink btn-pill px-4 py-2">Search Flights</a>
                </div>
            </div>
        </div>

        <!-- ═══ Flight Search Panel (visible in the hero) ═══ -->
        <div class="hero-search-panel">
            <form action="<?php echo BASE_URL; ?>fare-results.php" method="GET" class="hero-search-form">
                <input type="hidden" name="region" value="domestic">
                <div class="hs-field">
                    <label class="hs-label" for="hsFrom"><i class="bi bi-geo-alt me-1"></i>From</label>
                    <select name="source" id="hsFrom" class="form-select" required>
                        <option value="">Select origin</option>
                        <?php cityOptions(null, 'domestic'); ?>
                    </select>
                </div>
                <div class="hs-field">
                    <label class="hs-label" for="hsTo"><i class="bi bi-geo-alt-fill me-1"></i>To</label>
                    <select name="destination" id="hsTo" class="form-select" required>
                        <option value="">Select destination</option>
                        <?php cityOptions(null, 'domestic'); ?>
                    </select>
                </div>
                <div class="hs-field">
                    <label class="hs-label" for="hsDate"><i class="bi bi-calendar3 me-1"></i>Travel date</label>
                    <input type="date" name="travel_date" id="hsDate" class="form-control" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <button type="submit" class="btn btn-ink btn-pill hs-submit"><i class="bi bi-search me-1"></i>Search</button>
            </form>
        </div>
    </div>
</section>

<!-- ═══════════ Story ═══════════ -->
<section class="lp-section" id="story">
    <div class="lp-section-inner">
        <div class="row align-items-stretch g-5">
            <div class="col-lg-6 reveal d-flex">
                <div class="w-100 d-flex flex-column justify-content-center">
                    <span class="kicker kicker-accent">Our Story</span>
                    <h2 class="lp-heading">Flight booking, <span class="dim">reimagined</span> for the modern traveller.</h2>
                    <p class="lp-lead mb-4">AeroBook was built on a simple belief: booking a flight shouldn't be a chore. We pair live fares, transparent pricing, and effortless booking — so your time is spent where it matters, not in endless tabs.</p>
                    <p class="text-gray-500 mb-4">Every journey is handled end-to-end. Real-time availability, secure checkout, instant e-tickets, and 24/7 support — standard on every booking.</p>
                    <a href="<?php echo BASE_URL; ?>about.php" class="btn btn-outline-accent btn-pill px-4 py-2 align-self-start">More about us</a>
                </div>
            </div>
            <div class="col-lg-6 reveal d-flex" style="--reveal-delay: 120ms;">
                <div class="lp-story-panel w-100">
                    <i class="bi bi-airplane-engines plane-icon"></i>
                    <div>
                        <h3>From search to boarding pass.</h3>
                        <p>Compare fares, pick your seat, and check in online — all before you even reach the airport.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-5 pt-3 g-3 reveal">
            <?php
            $stats = [
                ['120+', 'Routes'],
                ['45+', 'Airlines'],
                ['1M+', 'Happy travellers'],
                ['24/7', 'Support'],
            ];
            foreach ($stats as $s): ?>
            <div class="col-6 col-md-3">
                <div class="lp-stat">
                    <div class="lp-stat-value"><?php echo $s[0]; ?></div>
                    <div class="lp-stat-label"><?php echo $s[1]; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════ Fares ═══════════ -->
<section class="lp-section" id="rates">
    <div class="lp-section-inner">
        <div class="text-center mb-5 reveal">
            <span class="kicker kicker-accent">Fares</span>
            <h2 class="lp-heading">Transparent fares. <span class="dim">Zero surprises.</span></h2>
            <p class="lp-lead">Choose how you fly. No hidden fees, no last-minute markups — just clear pricing on every route.</p>
        </div>
        <div class="pricing-grid">
            <div class="pricing-card reveal">
                <div class="pricing-name">Saver</div>
                <div class="pricing-desc">Pay-as-you-go fares, perfect for quick trips.</div>
                <div class="pricing-price">₹2,499<small> / one-way</small></div>
                <ul class="pricing-list">
                    <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>1 cabin bag included</li>
                    <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Standard seat selection</li>
                    <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Online check-in</li>
                    <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Free 24-hr cancellation</li>
                </ul>
                <a href="<?php echo BASE_URL; ?>search-flights.php" class="btn btn-gray btn-pill py-2 w-100">Book this fare</a>
            </div>

            <div class="pricing-card featured reveal" style="--reveal-delay: 120ms;">
                <span class="popular">Most popular</span>
                <div class="pricing-name">Flexi</div>
                <div class="pricing-desc">More flexibility, more baggage, less to worry about.</div>
                <div class="pricing-price">₹4,299<small> / one-way</small></div>
                <ul class="pricing-list">
                    <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>2 bags (1 checked)</li>
                    <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Free date change</li>
                    <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Priority boarding</li>
                    <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Refundable fare</li>
                </ul>
                <a href="<?php echo BASE_URL; ?>search-flights.php" class="btn btn-white btn-pill py-2 w-100">Book your seat</a>
            </div>

            <div class="pricing-card reveal" style="--reveal-delay: 240ms;">
                <div class="pricing-name">Business</div>
                <div class="pricing-desc">Full comfort and priority treatment on every journey.</div>
                <div class="pricing-price">₹8,999<small> / one-way</small></div>
                <ul class="pricing-list">
                    <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>2 checked bags</li>
                    <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Extra legroom seat</li>
                    <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Priority check-in & security</li>
                    <li><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Dedicated support line</li>
                </ul>
                <a href="<?php echo BASE_URL; ?>search-flights.php" class="btn btn-gray btn-pill py-2 w-100">Book business</a>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════ Features ═══════════ -->
<section class="lp-section" id="benefits">
    <div class="lp-section-inner">
        <div class="text-center mb-5 reveal">
            <span class="kicker">Features</span>
            <h2 class="lp-heading">Why fly <span class="dim">AeroBook.</span></h2>
            <p class="lp-lead">Every detail engineered around one thing — getting you where you need to be, effortlessly.</p>
        </div>
        <div class="row g-4">
            <?php
            $benefits = [
                ['bi bi-clock-history', 'Real-time availability', 'Live fares and seat maps, updated the moment airlines publish them.'],
                ['bi bi-lightning-charge', 'Instant confirmation', 'Book in seconds and get your e-ticket delivered immediately.'],
                ['bi bi-ticket-perforated', 'Smart seat selection', 'Pick your exact seat on an interactive cabin map before you pay.'],
                ['bi bi-globe-asia-australia', 'Wide network', 'One booking, seamless connections across 120+ domestic and international routes.'],
                ['bi bi-shield-lock', 'Secure payments', 'Bank-grade encryption with multiple payment options at checkout.'],
                ['bi bi-headset', '24/7 support', 'A real person, any time of day, who sorts out the rest.'],
            ];
            foreach ($benefits as $b): ?>
            <div class="col-md-6 col-lg-4 reveal">
                <div class="benefit-card">
                    <div class="benefit-icon"><i class="<?php echo $b[0]; ?>"></i></div>
                    <h3><?php echo $b[1]; ?></h3>
                    <p><?php echo $b[2]; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════ FAQ ═══════════ -->
<section class="lp-section" id="faq">
    <div class="lp-section-inner" style="max-width: 52rem;">
        <div class="text-center mb-5 reveal">
            <span class="kicker kicker-accent">FAQ</span>
            <h2 class="lp-heading">Answers, <span class="dim">before you ask.</span></h2>
        </div>
        <div class="accordion faq-accordion" id="faqAccordion">
            <?php
            $faqs = [
                ['How far in advance should I book?', 'For domestic routes, booking 3–4 weeks ahead usually gets the best fares. International trips are best planned 6–8 weeks out.'],
                ['What does my fare include?', 'Every fare includes airline taxes and standard cabin baggage. Seat selection, checked luggage, and meals are added transparently at checkout.'],
                ['Can I change or cancel my booking?', 'Yes. Flexi fares include one free date change, and all fares can be cancelled within 24 hours of booking for a full refund. Full policies are shown before you pay.'],
                ['How do I check in?', 'Online check-in opens 48 hours before departure. You can check in from your My Bookings page and download your boarding pass instantly.'],
                ['How do I get my boarding pass?', 'After check-in, your boarding pass is available as a PDF or QR code on your confirmation page and in My Bookings.'],
                ['Is my payment secure?', 'Absolutely. Payments run over encrypted connections with multiple gateway options, and refunds are processed back to your original payment method.'],
            ];
            foreach ($faqs as $i => $f): ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="faqHeading<?php echo $i; ?>">
                    <button class="accordion-button <?php echo $i === 0 ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse<?php echo $i; ?>" aria-expanded="<?php echo $i === 0 ? 'true' : 'false'; ?>" aria-controls="faqCollapse<?php echo $i; ?>">
                        <?php echo $f[0]; ?>
                    </button>
                </h2>
                <div id="faqCollapse<?php echo $i; ?>" class="accordion-collapse collapse <?php echo $i === 0 ? 'show' : ''; ?>" aria-labelledby="faqHeading<?php echo $i; ?>" data-bs-parent="#faqAccordion">
                    <div class="accordion-body"><?php echo $f[1]; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════ CTA ═══════════ -->
<section class="lp-section" id="book">
    <div class="lp-section-inner">
        <div class="lp-cta reveal">
            <h2>Ready to take off?</h2>
            <p>Your next journey is a few clicks away. Book with AeroBook and fly with confidence.</p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="<?php echo BASE_URL; ?>search-flights.php" class="btn btn-white">Search Flights</a>
                <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-ghost-white">Create free account</a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
