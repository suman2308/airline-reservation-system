<?php 
$pageTitle = 'User Dashboard'; 
require_once 'includes/header.php';
if (!isLoggedIn()) redirect('login.php');

// Fetch user stats
$user_id = $_SESSION['user_id'];
$total_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE user_id=$user_id"))['c'];
$active_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE user_id=$user_id AND booking_status='Confirmed'"))['c'];
$cancelled_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE user_id=$user_id AND booking_status='Cancelled'"))['c'];

// Fetch recent bookings
$recent = mysqli_query($conn, "SELECT b.*, f.airline_name, f.flight_number, f.source, f.destination FROM bookings b JOIN flights f ON b.flight_id=f.flight_id WHERE b.user_id=$user_id ORDER BY b.booking_date DESC LIMIT 3");
?>

<div class="page-header">
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
        <p>Manage your account, view bookings, and search for your next adventure.</p>
    </div>
</div>

<div class="container py-5">
    <?php showAlert(); ?>
    
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="bi bi-journal-bookmark"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_bookings; ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
                <div class="stat-info">
                    <h3><?php echo $active_bookings; ?></h3>
                    <p>Confirmed Flights</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon red"><i class="bi bi-x-circle"></i></div>
                <div class="stat-info">
                    <h3><?php echo $cancelled_bookings; ?></h3>
                    <p>Cancelled</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Recent Bookings</h4>
                <a href="my-bookings.php" class="btn btn-outline-accent btn-sm">View All</a>
            </div>
            
            <?php if (mysqli_num_rows($recent) > 0): while($b = mysqli_fetch_assoc($recent)): ?>
            <div class="flight-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6><?php echo $b['airline_name']; ?> (<?php echo $b['flight_number']; ?>)</h6>
                        <p class="mb-0 text-muted"><?php echo $b['source']; ?> → <?php echo $b['destination']; ?> · <?php echo formatDate($b['travel_date']); ?></p>
                    </div>
                    <div class="col-md-2 text-center">
                        <?php echo statusBadge($b['booking_status']); ?>
                    </div>
                    <div class="col-md-2 text-end">
                        <a href="booking-confirmation.php?ref=<?php echo $b['booking_ref']; ?>" class="btn btn-sm btn-accent">Details</a>
                    </div>
                </div>
            </div>
            <?php endwhile; else: ?>
            <div class="empty-state p-4 border rounded bg-white">
                <i class="bi bi-ticket-perforated"></i>
                <p>No recent bookings. Ready for a trip?</p>
                <a href="search-flights.php" class="btn btn-accent btn-sm">Search Flights</a>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <div class="flight-card">
                <h5 class="mb-3">Quick Actions</h5>
                <div class="list-group list-group-flush">
                    <a href="search-flights.php" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center">
                        <i class="bi bi-search me-3 text-accent"></i> Search Flights
                    </a>
                    <a href="my-bookings.php" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center">
                        <i class="bi bi-journal-bookmark me-3 text-accent"></i> My Bookings
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center">
                        <i class="bi bi-person-gear me-3 text-accent"></i> Manage Profile
                    </a>
                    <a href="contact.php" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center">
                        <i class="bi bi-headset me-3 text-accent"></i> Get Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
