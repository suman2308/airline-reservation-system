<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/helpers.php';
require_once 'includes/QRCode.php';
require_once 'includes/PDF.php';
require_once 'includes/ICS.php';

if (!isLoggedIn()) redirect('login.php');

$ref = trim($_GET['ref'] ?? '');
if (empty($ref)) redirect('my-bookings.php');

$b = getBookingByRef($ref, $_SESSION['user_id']);
if (!$b) {
    setFlash('error', 'Ticket not found.');
    redirect('my-bookings.php');
}

$seat_row = intval(substr($b['seat_number'], 0, -1));
$seat_class = ($seat_row >= 1 && $seat_row <= 2) ? 'Business Class' : 'Economy Class';

$dep_time = date('H:i:s', strtotime($b['departure_time']));
$arr_time = date('H:i:s', strtotime($b['arrival_time']));
$dep_datetime = $b['travel_date'] . ' ' . $dep_time;
$arr_datetime = $b['travel_date'] . ' ' . $arr_time;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Ticket Boarding Pass - <?php echo $b['booking_ref']; ?></title>
    <script>
        // Apply saved/system theme before CSS paints to avoid a flash of the wrong theme.
        (function () {
            try { var t = localStorage.getItem('aerobook-theme');
                if (!t) t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) { document.documentElement.setAttribute('data-theme', 'light'); }
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+39+Text&family=Outfit:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #eef2f7; font-family: 'Inter', sans-serif; padding: 40px 0; }
        [data-theme="dark"] body { background: #0b1220; }
        [data-theme="dark"] .ticket-card { background: #fff; }
        .ticket-page-toggle { position: fixed; top: 16px; right: 16px; z-index: 50; }
        .ticket-card { max-width: 860px; margin: auto; background: #fff; border-radius: 20px; box-shadow: 0 15px 35px rgba(5, 19, 54, 0.1); overflow: hidden; border: 1px solid #e2e8f0; }
        .ticket-header { background: linear-gradient(135deg, #051336 0%, #0b1f4d 100%); color: #fff; padding: 28px 36px; display: flex; justify-content: space-between; align-items: center; }
        .brand-logo { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; }
        .brand-logo span { color: #024dec; }
        .ticket-ref-box { text-align: right; }
        .ticket-ref-box h3 { margin: 0; color: #38bdf8; font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 24px; letter-spacing: 1px; }
        .ticket-body { padding: 36px; position: relative; }
        .route-flex { display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px 32px; margin-bottom: 28px; }
        .city-code h2 { font-family: 'Outfit', sans-serif; font-size: 38px; font-weight: 800; margin: 0; color: #051336; }
        .plane-path { flex: 1; margin: 0 30px; text-align: center; position: relative; }
        .plane-path-line { height: 2px; background: linear-gradient(90deg, #024dec, #10b981); width: 100%; position: absolute; top: 50%; transform: translateY(-50%); z-index: 1; }
        .plane-path i { position: relative; z-index: 2; background: #f8fafc; padding: 0 10px; color: #024dec; font-size: 22px; }
        .grid-info { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px; }
        .info-cell label { color: #64748b; font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.8px; display: block; margin-bottom: 4px; }
        .info-cell span { font-weight: 700; font-size: 16px; color: #0f172a; }
        .status-badge-confirmed { background: #dcfce7; color: #15803d; padding: 4px 12px; border-radius: 50px; font-size: 12px; font-weight: 700; display: inline-block; }
        .barcode-section { background: #f8fafc; border-top: 2px dashed #cbd5e1; padding: 24px 36px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; }
        .barcode-font { font-family: 'Libre Barcode 39 Text', cursive; font-size: 46px; line-height: 1; color: #0f172a; }
        .tear-stub-notice { font-size: 12px; color: #64748b; margin: 0; text-align: center; }
        @media print {
            body { padding: 0; background: #fff; }
            .ticket-card { box-shadow: none; border: 1px solid #ccc; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <button type="button" class="theme-toggle theme-toggle-light ticket-page-toggle" id="themeToggle" aria-label="Toggle dark mode" title="Toggle dark mode" aria-pressed="false">
        <i class="bi bi-moon-stars-fill"></i>
    </button>
    <?php
    $qr = new AeroQR();
    $qrDataUri = $qr->bookingQR($b['booking_ref'], 120);
    $pdf = new AeroPDF();
    $ics = new AeroICS();
    $gcalUrl = AeroICS::googleCalLink($b, $b);
    ?>
    <div class="container text-center mb-4 no-print">
        <button onclick="window.print()" class="btn btn-primary btn-lg px-5 fw-bold shadow-sm">
            <i class="bi bi-printer me-2"></i>Print Boarding Pass
        </button>
        <a href="<?php echo $gcalUrl; ?>" target="_blank" class="btn btn-outline-success btn-lg ms-2 fw-bold">
            <i class="bi bi-calendar-plus me-2"></i>Add to Calendar
        </a>
        <a href="my-bookings.php" class="btn btn-outline-secondary btn-lg ms-2 fw-bold">Back to Bookings</a>
    </div>

<div class="ticket-card">
    <div class="ticket-header">
        <div class="brand-logo">
            <i class="bi bi-airplane-engines me-2" style="color: #38bdf8;"></i>Aero<span>Book</span>
        </div>
        <div class="ticket-ref-box">
            <span class="small text-uppercase opacity-75 d-block">PNR / Reference Code</span>
            <h3><?php echo htmlspecialchars($b['booking_ref']); ?></h3>
        </div>
    </div>
    
    <div class="ticket-body">
        <div class="grid-info">
            <div class="info-cell">
                <label>Passenger Name</label>
                <span><?php echo htmlspecialchars($b['passenger_name']); ?></span>
            </div>
            <div class="info-cell">
                <label>Age & Gender</label>
                <span><?php echo $b['age']; ?> Yrs / <?php echo htmlspecialchars($b['gender']); ?></span>
            </div>
            <div class="info-cell">
                <label>Seat & Class</label>
                <span class="text-primary fw-extrabold fs-4"><?php echo htmlspecialchars($b['seat_number']); ?></span>
                <small class="d-block text-muted font-bold"><?php echo $seat_class; ?></small>
            </div>
            <div class="info-cell">
                <label>Booking Status</label>
                <div><span class="status-badge-confirmed"><i class="bi bi-check-circle-fill me-1"></i><?php echo strtoupper($b['booking_status']); ?></span></div>
            </div>
        </div>

        <div class="route-flex">
            <div class="city-code text-start">
                <h2><?php echo strtoupper(substr($b['source'], 0, 3)); ?></h2>
                <div class="fw-bold text-secondary"><?php echo htmlspecialchars($b['source']); ?></div>
                <div class="fw-extrabold text-dark mt-2 fs-5"><?php echo formatTime($dep_datetime); ?></div>
                <small class="text-muted"><?php echo formatDate($dep_datetime); ?></small>
            </div>

            <div class="plane-path">
                <div class="plane-path-line"></div>
                <i class="bi bi-airplane-fill"></i>
                <small class="d-block text-muted fw-bold mt-2"><?php echo calcDuration($dep_datetime, $arr_datetime); ?></small>
            </div>

            <div class="city-code text-end">
                <h2><?php echo strtoupper(substr($b['destination'], 0, 3)); ?></h2>
                <div class="fw-bold text-secondary"><?php echo htmlspecialchars($b['destination']); ?></div>
                <div class="fw-extrabold text-dark mt-2 fs-5"><?php echo formatTime($arr_datetime); ?></div>
                <small class="text-muted"><?php echo formatDate($arr_datetime); ?></small>
            </div>
        </div>

        <div class="grid-info mb-0">
            <div class="info-cell">
                <label>Operating Airline</label>
                <span><?php echo htmlspecialchars($b['airline_name']); ?></span>
            </div>
            <div class="info-cell">
                <label>Flight No</label>
                <span><?php echo htmlspecialchars($b['flight_number']); ?></span>
            </div>
            <div class="info-cell">
                <label>Boarding Gate / Terminal</label>
                <span>Gate A-12 (T3)</span>
            </div>
            <div class="info-cell">
                <label>Baggage Belt</label>
                <span>Belt 04 (15kg Included)</span>
            </div>
        </div>
    </div>

    <!-- QR Code + Barcode -->
    <div class="barcode-section">
        <div class="d-flex align-items-center gap-3">
            <img src="<?php echo $qrDataUri; ?>" alt="QR" style="width:70px;height:70px;">
            <div>
                <div class="barcode-font">*<?php echo htmlspecialchars($b['booking_ref']); ?>*</div>
                <div class="small text-muted fw-semibold mt-1"><i class="bi bi-shield-check me-1 text-success"></i>Verified Electronic Boarding Pass</div>
            </div>
        </div>
        <div class="text-end">
            <span class="badge bg-dark text-white px-3 py-2 fs-6">BOARDING TIME: 45 MINS BEFORE DEP</span>
        </div>
    </div>
</div>

<div class="container text-center mt-3 no-print">
    <p class="tear-stub-notice">Please present this boarding pass along with government-issued photo ID at security checkpoint. &copy; <?php echo date('Y'); ?> AeroBook.</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo asset('js/script.js'); ?>"></script>
</body>
</html>
