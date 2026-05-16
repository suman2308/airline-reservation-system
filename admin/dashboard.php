<?php 
$pageTitle = 'Admin Dashboard'; 
require_once __DIR__ . '/includes/admin-header.php';

// Fetch stats
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users"))['c'];
$total_flights = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM flights"))['c'];
$total_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE booking_status='Confirmed'"))['c'];
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(f.price) as s FROM bookings b JOIN flights f ON b.flight_id=f.flight_id WHERE b.booking_status='Confirmed'"))['s'] ?? 0;

// Fetch recent bookings
$recent_bookings = mysqli_query($conn, "SELECT b.*, f.flight_number, f.source, f.destination FROM bookings b JOIN flights f ON b.flight_id=f.flight_id ORDER BY b.booking_date DESC LIMIT 5");
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-people"></i></div>
            <div class="stat-info">
                <h3><?php echo $total_users; ?></h3>
                <p>Total Users</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-airplane"></i></div>
            <div class="stat-info">
                <h3><?php echo $total_flights; ?></h3>
                <p>Total Flights</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="bi bi-journal-check"></i></div>
            <div class="stat-info">
                <h3><?php echo $total_bookings; ?></h3>
                <p>Confirmed Bookings</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="bi bi-currency-rupee"></i></div>
            <div class="stat-info">
                <h3><?php echo formatPrice($total_revenue); ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Recent Bookings</h5>
                <a href="manage-bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ref</th><th>Passenger</th><th>Flight</th><th>Route</th><th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($b = mysqli_fetch_assoc($recent_bookings)): ?>
                            <tr>
                                <td><span class="text-accent fw-bold"><?php echo $b['booking_ref']; ?></span></td>
                                <td><?php echo htmlspecialchars($b['passenger_name']); ?></td>
                                <td><?php echo $b['flight_number']; ?></td>
                                <td><?php echo $b['source']; ?> → <?php echo $b['destination']; ?></td>
                                <td><?php echo statusBadge($b['booking_status']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white p-3 border-bottom">
                <h5 class="mb-0 fw-bold">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="add-flight.php" class="btn btn-primary py-2"><i class="bi bi-plus-circle me-2"></i>Add New Flight</a>
                    <a href="manage-flights.php" class="btn btn-outline-secondary py-2">Manage All Flights</a>
                    <a href="manage-seats.php" class="btn btn-outline-secondary py-2">Update Seat Availability</a>
                </div>
                <hr>
                <div class="bg-light p-3 rounded-3">
                    <h6 class="fw-bold mb-2">System Info</h6>
                    <ul class="list-unstyled small mb-0">
                        <li class="d-flex justify-content-between mb-1"><span>PHP Version</span> <span><?php echo phpversion(); ?></span></li>
                        <li class="d-flex justify-content-between mb-1"><span>MySQL</span> <span>Connected</span></li>
                        <li class="d-flex justify-content-between"><span>Environment</span> <span><?php echo $is_localhost ? 'Localhost' : 'InfinityFree'; ?></span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
