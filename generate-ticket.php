<?php 
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) redirect('login.php');

$ref = trim($_GET['ref'] ?? '');
if (empty($ref)) redirect('my-bookings.php');

// Fetch booking details securely
$stmt = mysqli_prepare($conn, "SELECT b.*, f.airline_name, f.flight_number, f.source, f.destination, f.departure_time, f.arrival_time, f.price, u.name as booked_by 
                                FROM bookings b 
                                JOIN flights f ON b.flight_id = f.flight_id 
                                JOIN users u ON b.user_id = u.id
                                WHERE b.booking_ref=? AND b.user_id=?");
mysqli_stmt_bind_param($stmt, "si", $ref, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) { 
    $_SESSION['error'] = 'Ticket not found.'; 
    redirect('my-bookings.php'); 
}
$b = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Ticket - <?php echo $b['booking_ref']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; padding: 40px 0; }
        .ticket-container { max-width: 800px; margin: auto; background: #fff; box-shadow: 0 0 30px rgba(0,0,0,0.1); border-radius: 12px; overflow: hidden; }
        .ticket-header { background: #0a1628; color: #fff; padding: 30px; display: flex; justify-content: space-between; align-items: center; }
        .ticket-body { padding: 40px; }
        .brand-name { font-size: 24px; font-weight: 800; }
        .brand-name span { color: #00b4d8; }
        .ticket-ref { text-align: right; }
        .ticket-ref h2 { margin-bottom: 0; color: #00b4d8; font-weight: 800; }
        .route-info { display: flex; justify-content: space-between; align-items: center; margin: 40px 0; border-top: 1px dashed #dee2e6; border-bottom: 1px dashed #dee2e6; padding: 20px 0; }
        .route-point h3 { font-size: 32px; font-weight: 800; margin-bottom: 0; }
        .route-point p { color: #6c757d; margin-bottom: 0; }
        .route-icon { font-size: 24px; color: #00b4d8; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-item label { color: #6c757d; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 4px; }
        .info-item span { font-weight: 700; font-size: 16px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 50px; font-size: 12px; font-weight: 700; }
        .status-confirmed { background: #d1e7dd; color: #0f5132; }
        .footer-note { font-size: 12px; color: #6c757d; border-top: 1px solid #eee; padding-top: 20px; text-align: center; }
        @media print {
            body { padding: 0; background: #fff; }
            .ticket-container { box-shadow: none; border: 1px solid #eee; }
            .btn-print { display: none; }
        }
    </style>
</head>
<body>

<div class="container text-center mb-4 btn-print">
    <button onclick="window.print()" class="btn btn-primary btn-lg px-5">
        <i class="bi bi-printer me-2"></i>Print E-Ticket
    </button>
    <a href="my-bookings.php" class="btn btn-outline-secondary btn-lg ms-2">Back</a>
</div>

<div class="ticket-container">
    <div class="ticket-header">
        <div class="brand-name">
            <i class="bi bi-airplane-engines me-2"></i>Aero<span>Book</span>
        </div>
        <div class="ticket-ref">
            <label class="small text-uppercase opacity-50 d-block">Booking Reference</label>
            <h2><?php echo $b['booking_ref']; ?></h2>
        </div>
    </div>
    
    <div class="ticket-body">
        <div class="info-grid">
            <div class="info-item">
                <label>Passenger Name</label>
                <span><?php echo htmlspecialchars($b['passenger_name']); ?></span>
            </div>
            <div class="info-item">
                <label>Age / Gender</label>
                <span><?php echo $b['age']; ?> Yrs / <?php echo $b['gender']; ?></span>
            </div>
            <div class="info-item">
                <label>Status</label>
                <span class="status-badge status-confirmed"><?php echo strtoupper($b['booking_status']); ?></span>
            </div>
        </div>

        <div class="route-info">
            <div class="route-point text-start">
                <h3><?php echo strtoupper(substr($b['source'], 0, 3)); ?></h3>
                <p><?php echo $b['source']; ?></p>
                <span class="fw-bold mt-2 d-block"><?php echo formatTime($b['departure_time']); ?></span>
                <small class="text-muted"><?php echo formatDate($b['departure_time']); ?></small>
            </div>
            <div class="route-icon">
                <i class="bi bi-airplane"></i>
                <div style="width: 150px; height: 2px; background: #eee; position: relative; top: -14px; z-index: -1;"></div>
            </div>
            <div class="route-point text-end">
                <h3><?php echo strtoupper(substr($b['destination'], 0, 3)); ?></h3>
                <p><?php echo $b['destination']; ?></p>
                <span class="fw-bold mt-2 d-block"><?php echo formatTime($b['arrival_time']); ?></span>
                <small class="text-muted"><?php echo formatDate($b['arrival_time']); ?></small>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <label>Airline</label>
                <span><?php echo $b['airline_name']; ?></span>
            </div>
            <div class="info-item">
                <label>Flight No</label>
                <span><?php echo $b['flight_number']; ?></span>
            </div>
            <div class="info-item">
                <label>Seat Number</label>
                <span class="text-primary fs-4"><?php echo $b['seat_number']; ?></span>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <label>Travel Date</label>
                <span><?php echo formatDate($b['travel_date']); ?></span>
            </div>
            <div class="info-item">
                <label>Booking Date</label>
                <span><?php echo formatDateTime($b['booking_date']); ?></span>
            </div>
            <div class="info-item">
                <label>Total Fare</label>
                <span><?php echo formatPrice($b['price']); ?></span>
            </div>
        </div>

        <div class="footer-note">
            <p class="mb-1"><strong>Important Information:</strong> Please carry a valid Photo ID proof for airport entry. Reporting time is 2 hours before departure.</p>
            <p class="mb-0 text-muted">This is an electronically generated ticket. &copy; <?php echo date('Y'); ?> AeroBook.</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
