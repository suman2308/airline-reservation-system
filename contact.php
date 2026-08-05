<?php
$pageTitle = 'Contact Us';
$bareNav = true;
require_once 'includes/header.php';
require_once 'includes/helpers.php';
require_once 'includes/Validation.php';
?>
<div class="page-hero-lite">
    <div class="container">
        <span class="kicker">Contact</span>
        <h1>We're here to <span class="dim">help.</span></h1>
        <p>Have questions about your booking? Our team is available 24/7 to assist you.</p>
    </div>
</div>
<div class="container py-5" style="padding-top: 5rem;">
    <div class="row g-5">
        <div class="col-lg-5 reveal">
            <span class="kicker">Get in touch</span>
            <h2 class="lp-heading" style="margin-bottom: 1.5rem;">Questions, <span class="dim">answered.</span></h2>

            <div class="contact-info-item d-flex mb-4">
                <div class="icon-box me-3 fs-5"><i class="bi bi-geo-alt"></i></div>
                <div>
                    <h5 class="mb-1">Our Office</h5>
                    <p class="mb-0">123 Aviation Way, New Delhi, India</p>
                </div>
            </div>

            <div class="contact-info-item d-flex mb-4">
                <div class="icon-box me-3 fs-5"><i class="bi bi-envelope"></i></div>
                <div>
                    <h5 class="mb-1">Email Us</h5>
                    <p class="mb-0">support@aerobook.com</p>
                </div>
            </div>

            <div class="contact-info-item d-flex mb-4">
                <div class="icon-box me-3 fs-5"><i class="bi bi-telephone"></i></div>
                <div>
                    <h5 class="mb-1">Call Us</h5>
                    <p class="mb-0">+91 1800-AERO-BOOK</p>
                </div>
            </div>

            <div class="contact-info-item d-flex">
                <div class="icon-box me-3 fs-5"><i class="bi bi-clock-history"></i></div>
                <div>
                    <h5 class="mb-1">Availability</h5>
                    <p class="mb-0">24/7 support — we never sleep</p>
                </div>
            </div>
        </div>

        <div class="col-lg-7 reveal" style="--reveal-delay: 120ms;">
            <div class="card border-0 shadow-lg p-4" style="border-radius: 1.75rem;">
                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!requireCsrfToken()) {
                        echo '<div class="alert alert-danger">Invalid request. Please try again.</div>';
                    } elseif (!checkRateLimit('contact', 3, 300)) {
                        logSecurity('Contact form rate limit exceeded', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
                        echo '<div class="alert alert-danger">Too many submissions. Please try again later.</div>';
                    } else {
                        $errors = validateContact($_POST);
                        if (!empty($errors)) {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($errors[0]) . '</div>';
                        } else {
                            $name = trim($_POST['name']);
                            $email = trim($_POST['email']);
                            $subject = trim($_POST['subject']);
                            $message = trim($_POST['message']);
                            $stmt = mysqli_prepare($conn, "INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)");
                            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $subject, $message);
                            if (mysqli_stmt_execute($stmt)) {
                                echo '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Message sent! We will get back to you soon.</div>';
                            } else {
                                echo '<div class="alert alert-danger">Something went wrong. Please try again.</div>';
                            }
                            mysqli_stmt_close($stmt);
                        }
                    }
                }
                ?>
                <form method="POST" class="needs-loading">
                    <?php csrfField(); ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Your Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Enter your name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control" placeholder="What is this about?" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="5" placeholder="How can we help you?" required></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-accent btn-lg w-100 mt-2">Send Message</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
