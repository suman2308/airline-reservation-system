<?php
$pageTitle = 'Travel Calendar';
require_once 'includes/header.php';
require_once 'includes/helpers.php';

if (!isLoggedIn()) redirect('login.php');

$user_id = $_SESSION['user_id'];
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$month = max(1, min(12, $month));
$year = max(2024, min(2030, $year));

$stmt = mysqli_prepare($conn, "SELECT b.*, f.airline_name, f.flight_number, f.source, f.destination, f.departure_time, f.arrival_time
                                FROM bookings b JOIN flights f ON b.flight_id = f.flight_id
                                WHERE b.user_id = ? AND b.booking_status = 'Confirmed'
                                AND MONTH(f.departure_time) = ? AND YEAR(f.departure_time) = ?
                                ORDER BY f.departure_time ASC");
mysqli_stmt_bind_param($stmt, "iii", $user_id, $month, $year);
mysqli_stmt_execute($stmt);
$bookings = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

$tripDays = [];
while ($b = mysqli_fetch_assoc($bookings)) {
    $day = (int)date('j', strtotime($b['departure_time']));
    $tripDays[$day][] = $b;
}

$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$startDow = (int)date('w', $firstDay);
$prevMonth = $month === 1 ? 12 : $month - 1;
$prevYear = $month === 1 ? $year - 1 : $year;
$nextMonth = $month === 12 ? 1 : $month + 1;
$nextYear = $month === 12 ? $year + 1 : $year;
?>
<div class="page-hero-lite">
    <div class="container">
        <span class="kicker">Planning</span>
        <h1>Travel <span class="dim">Calendar</span></h1>
        <p>View your upcoming flights at a glance</p>
    </div>
</div>
<div class="container py-5">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white p-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i></a>
                <h5 class="mb-0 fw-bold"><?php echo date('F Y', $firstDay); ?></h5>
                <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-right"></i></a>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-1">
                <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
                <div class="col text-center fw-bold small text-muted py-2"><?php echo $d; ?></div>
                <?php endforeach; ?>
            </div>
            <?php $day = 1; ?>
            <div class="row g-1">
                <?php for ($i = 0; $i < $startDow; $i++): ?>
                <div class="col" style="min-height:80px;"></div>
                <?php endfor; ?>
                <?php for ($d = 1; $d <= $daysInMonth; $d++): ?>
                <?php $hasTrip = isset($tripDays[$d]); ?>
                <div class="col <?php echo $hasTrip ? '' : ''; ?>" style="min-height:80px;">
                    <div class="p-1 rounded-3 <?php echo $hasTrip ? 'bg-accent bg-opacity-10 border border-accent' : ($d === intval(date('j')) && $month === intval(date('m')) && $year === intval(date('Y')) ? 'bg-light' : ''); ?>" style="min-height:80px;">
                        <div class="fw-bold small <?php echo $d === intval(date('j')) && $month === intval(date('m')) && $year === intval(date('Y')) ? 'text-accent' : ''; ?>"><?php echo $d; ?></div>
                        <?php if ($hasTrip): foreach ($tripDays[$d] as $t): ?>
                        <a href="<?php echo BASE_URL; ?>booking-confirmation.php?ref=<?php echo $t['booking_ref']; ?>" class="d-block text-truncate text-decoration-none small bg-accent text-white rounded px-1 mt-1" style="font-size:0.65rem;">
                            <?php echo htmlspecialchars($t['source']); ?>→<?php echo htmlspecialchars($t['destination']); ?>
                        </a>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
                <?php if (($startDow + $d) % 7 === 0 && $d < $daysInMonth): ?>
            </div><div class="row g-1 mt-1">
                <?php endif; ?>
                <?php endfor; ?>
            </div>
        </div>
    </div>
    <?php if (!empty($tripDays)): ?>
    <div class="mt-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-list-check me-2 text-accent"></i>Trips This Month</h5>
        <?php foreach ($tripDays as $day => $trips): foreach ($trips as $t): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-2">
            <div class="card-body py-3 px-4">
                <div class="row align-items-center">
                    <div class="col-auto text-center">
                        <div class="fw-bold text-accent fs-4"><?php echo $day; ?></div>
                        <small class="text-muted"><?php echo date('M', $firstDay); ?></small>
                    </div>
                    <div class="col">
                        <strong><?php echo htmlspecialchars($t['airline_name'] . ' ' . $t['flight_number']); ?></strong><br>
                        <small class="text-muted"><?php echo htmlspecialchars($t['source']); ?> → <?php echo htmlspecialchars($t['destination']); ?> · <?php echo formatTime($t['departure_time']); ?></small>
                    </div>
                    <div class="col-auto">
                        <a href="booking-confirmation.php?ref=<?php echo $t['booking_ref']; ?>" class="btn btn-sm btn-outline-accent"><i class="bi bi-eye"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
