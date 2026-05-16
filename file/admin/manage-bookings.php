<?php 
$pageTitle = 'Manage Bookings'; 
require_once __DIR__ . '/includes/admin-header.php';

// Handle Deletion/Cancellation by Admin
if (isset($_GET['cancel'])) {
    $cancel_id = intval($_GET['cancel']);
    
    // Fetch flight_id to restore seat
    $stmt = mysqli_prepare($conn, "SELECT flight_id FROM bookings WHERE booking_id = ? AND booking_status='Confirmed'");
    mysqli_stmt_bind_param($stmt, "i", $cancel_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    if ($b = mysqli_fetch_assoc($res)) {
        mysqli_stmt_close($stmt);
        
        // Update booking
        $upd = mysqli_prepare($conn, "UPDATE bookings SET booking_status='Cancelled' WHERE booking_id=?");
        mysqli_stmt_bind_param($upd, "i", $cancel_id);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
        
        // Restore seat
        $seat = mysqli_prepare($conn, "UPDATE flights SET seats_available = seats_available + 1 WHERE flight_id=?");
        mysqli_stmt_bind_param($seat, "i", $b['flight_id']);
        mysqli_stmt_execute($seat);
        mysqli_stmt_close($seat);
        
        $_SESSION['success'] = 'Booking cancelled and seat restored.';
    } else {
        mysqli_stmt_close($stmt);
        $_SESSION['error'] = 'Unable to cancel this booking.';
    }
    redirect(BASE_URL . 'admin/manage-bookings.php');
}

$bookings = mysqli_query($conn, "SELECT b.*, f.flight_number, f.airline_name, u.email as user_email FROM bookings b JOIN flights f ON b.flight_id=f.flight_id JOIN users u ON b.user_id=u.id ORDER BY b.booking_date DESC");
?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">All Bookings</h5>
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print Report</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ref</th><th>Passenger</th><th>User Account</th><th>Flight</th><th>Seat</th><th>Date</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($b = mysqli_fetch_assoc($bookings)): ?>
                    <tr>
                        <td><span class="text-accent fw-bold"><?php echo $b['booking_ref']; ?></span></td>
                        <td><?php echo htmlspecialchars($b['passenger_name']); ?><br><small class="text-muted"><?php echo $b['gender'].', '.$b['age']; ?></small></td>
                        <td><?php echo htmlspecialchars($b['user_email']); ?></td>
                        <td><?php echo $b['airline_name']; ?><br><small><?php echo $b['flight_number']; ?></small></td>
                        <td><?php echo $b['seat_number']; ?></td>
                        <td><?php echo formatDate($b['travel_date']); ?></td>
                        <td><?php echo statusBadge($b['booking_status']); ?></td>
                        <td>
                            <?php if ($b['booking_status'] === 'Confirmed'): ?>
                                <a href="?cancel=<?php echo $b['booking_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this booking?')">Cancel</a>
                            <?php else: ?>
                                <span class="text-muted small">N/A</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
