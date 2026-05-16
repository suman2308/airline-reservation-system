<?php $pageTitle = 'Flight Results'; require_once 'includes/header.php';
$source = trim($_GET['source'] ?? '');
$destination = trim($_GET['destination'] ?? '');
$travel_date = trim($_GET['travel_date'] ?? '');

if (empty($source) || empty($destination)) { $_SESSION['error'] = 'Please select origin and destination.'; redirect('search-flights.php'); }
if ($source === $destination) { $_SESSION['error'] = 'Origin and destination cannot be the same.'; redirect('search-flights.php'); }
?>
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-airplane me-2"></i><?php echo htmlspecialchars("$source → $destination"); ?></h1>
        <p><?php echo $travel_date ? formatDate($travel_date) : 'All available flights'; ?></p>
    </div>
</div>
<div class="container py-5">
    <?php showAlert(); ?>
    <a href="<?php echo BASE_URL; ?>search-flights.php" class="btn btn-outline-secondary mb-4"><i class="bi bi-arrow-left me-2"></i>Modify Search</a>
    <?php
    $sql = "SELECT * FROM flights WHERE source=? AND destination=? AND status='Scheduled' AND seats_available > 0";
    if (!empty($travel_date)) {
        $sql .= " AND DATE(departure_time) = ?";
        $sql .= " ORDER BY departure_time ASC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $source, $destination, $travel_date);
    } else {
        $sql .= " ORDER BY departure_time ASC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $source, $destination);
    }
    
    mysqli_stmt_execute($stmt);
    $flights = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($flights) > 0):
        echo '<p class="text-muted mb-3"><strong>' . mysqli_num_rows($flights) . '</strong> flight(s) found</p>';
        while ($f = mysqli_fetch_assoc($flights)): ?>
    <div class="flight-card">
        <div class="row align-items-center">
            <div class="col-md-3">
                <div class="flight-airline">
                    <div class="airline-logo"><?php echo strtoupper(substr($f['airline_name'],0,2)); ?></div>
                    <div>
                        <div class="airline-name"><?php echo $f['airline_name']; ?></div>
                        <div class="flight-number"><?php echo $f['flight_number']; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="flight-route">
                    <div class="route-point">
                        <div class="route-time"><?php echo formatTime($f['departure_time']); ?></div>
                        <div class="route-city"><?php echo $f['source']; ?></div>
                    </div>
                    <div class="route-line"><i class="bi bi-airplane-fill"></i></div>
                    <div class="route-point">
                        <div class="route-time"><?php echo formatTime($f['arrival_time']); ?></div>
                        <div class="route-city"><?php echo $f['destination']; ?></div>
                    </div>
                </div>
                <div class="route-duration"><?php echo calcDuration($f['departure_time'], $f['arrival_time']); ?> · <?php echo $f['seats_available']; ?> seats left</div>
            </div>
            <div class="col-md-2 text-center">
                <div class="flight-price"><?php echo formatPrice($f['price']); ?></div>
            </div>
            <div class="col-md-2 text-end">
                <a href="<?php echo BASE_URL; ?>flight-details.php?id=<?php echo $f['flight_id']; ?>" class="btn btn-accent w-100">Book Now</a>
            </div>
        </div>
    </div>
    <?php endwhile; mysqli_stmt_close($stmt); else: mysqli_stmt_close($stmt); ?>
    <div class="empty-state">
        <i class="bi bi-emoji-frown"></i>
        <h4>No flights found</h4>
        <p>No flights available for <strong><?php echo htmlspecialchars("$source → $destination"); ?></strong><?php echo $travel_date ? ' on ' . formatDate($travel_date) : ''; ?>.</p>
        <a href="<?php echo BASE_URL; ?>search-flights.php" class="btn btn-accent mt-3">Try Another Search</a>
    </div>
    <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>

