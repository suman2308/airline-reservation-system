<?php $pageTitle = 'Contact Us'; require_once 'includes/header.php'; ?>
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-headset me-2"></i>Contact Support</h1>
        <p>We're here to help you with your travel queries</p>
    </div>
</div>
<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-5">
            <h2 class="fw-bold mb-4">Get in <span class="text-accent">Touch</span></h2>
            <p class="text-muted mb-5">Have questions about your booking? Our team is available 24/7 to assist you.</p>
            
            <div class="contact-info-item d-flex mb-4">
                <div class="icon-box me-3 fs-3 text-accent"><i class="bi bi-geo-alt"></i></div>
                <div>
                    <h5 class="mb-1">Our Office</h5>
                    <p class="text-muted mb-0">123 Aviation Way, New Delhi, India</p>
                </div>
            </div>
            
            <div class="contact-info-item d-flex mb-4">
                <div class="icon-box me-3 fs-3 text-accent"><i class="bi bi-envelope"></i></div>
                <div>
                    <h5 class="mb-1">Email Us</h5>
                    <p class="text-muted mb-0">support@aerobook.com</p>
                </div>
            </div>
            
            <div class="contact-info-item d-flex">
                <div class="icon-box me-3 fs-3 text-accent"><i class="bi bi-telephone"></i></div>
                <div>
                    <h5 class="mb-1">Call Us</h5>
                    <p class="text-muted mb-0">+91 1800-AERO-BOOK</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-7">
            <div class="flight-card p-4 border rounded-4 bg-white shadow-sm">
                <?php 
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $name = trim($_POST['name']);
                    $email = trim($_POST['email']);
                    $subject = trim($_POST['subject']);
                    $message = trim($_POST['message']);

                    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
                        echo '<div class="alert alert-danger">All fields are required.</div>';
                    } else {
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
                ?>
                <form method="POST">
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
