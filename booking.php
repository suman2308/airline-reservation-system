<?php 
$pageTitle = 'Book Flight & Select Seats'; 
require_once 'includes/header.php';

if (!isLoggedIn()) { 
    $_SESSION['error'] = 'Please login to book a flight.'; 
    redirect('login.php'); 
}

$flight_id = intval($_GET['flight_id'] ?? 0);
if ($flight_id <= 0) redirect('search-flights.php');

// Fetch flight details safely
$stmt = mysqli_prepare($conn, "SELECT * FROM flights WHERE flight_id=? AND status='Scheduled' AND seats_available > 0");
mysqli_stmt_bind_param($stmt, "i", $flight_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) { 
    $_SESSION['error'] = 'Flight not available or fully booked.'; 
    mysqli_stmt_close($stmt);
    redirect('search-flights.php'); 
}
$f = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Fetch logged in user details for prefilling
$user_id = $_SESSION['user_id'];
$u_stmt = mysqli_prepare($conn, "SELECT name, email, phone FROM users WHERE id=?");
mysqli_stmt_bind_param($u_stmt, "i", $user_id);
mysqli_stmt_execute($u_stmt);
$u_res = mysqli_stmt_get_result($u_stmt);
$user_data = mysqli_fetch_assoc($u_res);
mysqli_stmt_close($u_stmt);

// Capture travel_date
$travel_date = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $travel_date = trim($_POST['travel_date'] ?? '');
} else {
    $travel_date = $_GET['date'] ?? '';
    if (!empty($travel_date)) {
        $d = DateTime::createFromFormat('Y-m-d', $travel_date);
        if (!$d || $d->format('Y-m-d') !== $travel_date) {
            $travel_date = date('Y-m-d', strtotime($f['departure_time']));
        }
    } else {
        $travel_date = date('Y-m-d', strtotime($f['departure_time']));
    }
}

// Fetch already occupied/booked seats for this flight on this specific travel date
$occupied_seats = [];
$occ_stmt = mysqli_prepare($conn, "SELECT seat_number FROM bookings WHERE flight_id=? AND travel_date=? AND booking_status='Confirmed'");
mysqli_stmt_bind_param($occ_stmt, "is", $flight_id, $travel_date);
mysqli_stmt_execute($occ_stmt);
$occ_res = mysqli_stmt_get_result($occ_stmt);
while ($row = mysqli_fetch_assoc($occ_res)) {
    $occupied_seats[] = strtoupper(trim($row['seat_number']));
}
mysqli_stmt_close($occ_stmt);

// Handle POST Booking Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid session request. Please try again.';
        redirect('booking.php?flight_id=' . $flight_id . '&date=' . $travel_date);
    }

    $passengers = $_POST['passengers'] ?? [];
    $card_number = str_replace(' ', '', trim($_POST['card_number'] ?? ''));
    $cvv = trim($_POST['cvv'] ?? '');

    if (empty($passengers) || !is_array($passengers) || count($passengers) === 0) {
        $_SESSION['error'] = 'Please select at least one seat to proceed with booking.';
    } elseif (count($passengers) > $f['seats_available']) {
        $_SESSION['error'] = 'Selected seat count exceeds available seats on this flight.';
    } elseif (!preg_match('/^\d{16}$/', $card_number)) {
        $_SESSION['error'] = 'Please enter a valid 16-digit card number.';
    } elseif (!preg_match('/^\d{3}$/', $cvv)) {
        $_SESSION['error'] = 'Please enter a valid 3-digit CVV.';
    } else {
        // Validate each passenger & seat selection
        $selected_seats = [];
        $valid_passengers = true;

        foreach ($passengers as $idx => $p) {
            $name = trim($p['name'] ?? '');
            $age = intval($p['age'] ?? 0);
            $gender = trim($p['gender'] ?? '');
            $seat = strtoupper(trim($p['seat'] ?? ''));

            if (empty($name) || $age < 1 || $age > 120 || empty($gender) || empty($seat)) {
                $_SESSION['error'] = 'Please provide complete information for Passenger #' . ($idx + 1) . '.';
                $valid_passengers = false;
                break;
            }

            if (in_array($seat, $occupied_seats, true)) {
                $_SESSION['error'] = 'Seat ' . $seat . ' is already booked. Please choose another seat.';
                $valid_passengers = false;
                break;
            }

            if (in_array($seat, $selected_seats, true)) {
                $_SESSION['error'] = 'Duplicate seat selection detected for seat ' . $seat . '.';
                $valid_passengers = false;
                break;
            }

            $selected_seats[] = $seat;
        }

        if ($valid_passengers) {
            mysqli_begin_transaction($conn);
            try {
                $created_refs = [];
                $book_stmt = mysqli_prepare($conn, "INSERT INTO bookings (booking_ref, user_id, flight_id, passenger_name, age, gender, travel_date, seat_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                foreach ($passengers as $p) {
                    $p_name = trim($p['name']);
                    $p_age = intval($p['age']);
                    $p_gender = trim($p['gender']);
                    $p_seat = strtoupper(trim($p['seat']));

                    // Generate unique booking reference
                    $b_ref = generateBookingRef();
                    $chk_stmt = mysqli_prepare($conn, "SELECT booking_id FROM bookings WHERE booking_ref=?");
                    mysqli_stmt_bind_param($chk_stmt, "s", $b_ref);
                    mysqli_stmt_execute($chk_stmt);
                    mysqli_stmt_store_result($chk_stmt);
                    while (mysqli_stmt_num_rows($chk_stmt) > 0) {
                        $b_ref = generateBookingRef();
                        mysqli_stmt_execute($chk_stmt);
                        mysqli_stmt_store_result($chk_stmt);
                    }
                    mysqli_stmt_close($chk_stmt);

                    mysqli_stmt_bind_param($book_stmt, "siisisss", $b_ref, $user_id, $flight_id, $p_name, $p_age, $p_gender, $travel_date, $p_seat);
                    if (!mysqli_stmt_execute($book_stmt)) {
                        throw new Exception("Failed to insert booking for passenger " . $p_name);
                    }
                    $created_refs[] = $b_ref;
                }
                mysqli_stmt_close($book_stmt);

                // Update available seats count
                $seat_count = count($passengers);
                $update_stmt = mysqli_prepare($conn, "UPDATE flights SET seats_available = seats_available - ? WHERE flight_id=? AND seats_available >= ?");
                mysqli_stmt_bind_param($update_stmt, "iii", $seat_count, $flight_id, $seat_count);
                mysqli_stmt_execute($update_stmt);

                if (mysqli_affected_rows($conn) > 0) {
                    mysqli_commit($conn);
                    $_SESSION['success'] = 'Flight booking confirmed successfully!';
                    redirect('booking-confirmation.php?refs=' . implode(',', $created_refs));
                } else {
                    throw new Exception("Insufficient seats available.");
                }
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $_SESSION['error'] = 'Booking failed: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-ticket-perforated me-2"></i>Choose Seats & Book Flight</h1>
        <p class="mb-0"><?php echo htmlspecialchars($f['source'] . ' → ' . $f['destination'] . ' · ' . $f['airline_name'] . ' (' . $f['flight_number'] . ')'); ?></p>
        <span class="badge bg-white text-dark mt-2 px-3 py-2 border"><i class="bi bi-calendar-event me-2 text-accent"></i>Travel Date: <?php echo formatDate($travel_date); ?></span>
    </div>
</div>

<div class="container py-5">
    <?php showAlert(); ?>
    
    <form method="POST" id="seatBookingForm">
        <?php csrfField(); ?>
        <input type="hidden" name="travel_date" value="<?php echo htmlspecialchars($travel_date); ?>">
        
        <div class="row g-4">
            <!-- Left Column: Seat Map -->
            <div class="col-lg-6">
                <div class="airplane-container">
                    <div class="airplane-nose">
                        <div class="cockpit-windshield">
                            <div class="cockpit-glass"></div>
                            <div class="cockpit-glass"></div>
                        </div>
                        <h6><i class="bi bi-airplane-engines me-2"></i>Flight Cabin Layout</h6>
                        <p><?php echo htmlspecialchars($f['airline_name']); ?> Airbus A320 · Front Cockpit</p>
                    </div>

                    <!-- Legend Bar -->
                    <div class="seat-legend-bar">
                        <div class="legend-item">
                            <span class="legend-box available"></span> Vacant
                        </div>
                        <div class="legend-item">
                            <span class="legend-box selected"><i class="bi bi-check"></i></span> Selected
                        </div>
                        <div class="legend-item">
                            <span class="legend-box occupied">X</span> Occupied
                        </div>
                        <div class="legend-item">
                            <span class="legend-box business">★</span> Business
                        </div>
                    </div>

                    <!-- Passenger Count Selector -->
                    <div class="d-flex align-items-center justify-content-between bg-light p-3 rounded-3 mb-4 border">
                        <div>
                            <label class="form-label fw-bold mb-0 text-dark">Number of Passengers</label>
                            <div class="small text-muted">Choose seats on the layout below</div>
                        </div>
                        <select id="passengerCountSelect" class="form-select w-auto fw-bold text-accent">
                            <?php 
                            $max_p = min(6, $f['seats_available']);
                            for ($i = 1; $i <= $max_p; $i++) {
                                echo "<option value='$i'>$i Passenger" . ($i > 1 ? 's' : '') . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Cabin Sections -->
                    <div class="cabin-divider"><i class="bi bi-star-fill text-warning me-1"></i> Business Class (Rows 1–2)</div>
                    <div class="cabin-seats-wrapper" id="businessClassGrid">
                        <!-- Rows 1 to 2 rendered by JS -->
                    </div>

                    <div class="cabin-divider"><i class="bi bi-person-workspace text-primary me-1"></i> Economy Class (Rows 3–10)</div>
                    <div class="cabin-seats-wrapper" id="economyClassGrid">
                        <!-- Rows 3 to 10 rendered by JS -->
                    </div>
                </div>
            </div>

            <!-- Right Column: Passenger Info & Payment -->
            <div class="col-lg-6">
                <!-- Selected Seats & Passenger Info Card -->
                <div class="flight-card mb-4">
                    <h4 class="mb-3 d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-people-fill me-2 text-accent"></i>Passenger Details</span>
                        <span class="badge bg-accent fs-6" id="selectedSeatsCounter">0 / 1 Seat Selected</span>
                    </h4>
                    <p class="text-muted small">Please select seat(s) on the cabin layout to enter passenger details.</p>
                    
                    <div id="passengerFormsContainer">
                        <!-- Dynamically populated passenger forms -->
                    </div>
                </div>

                <!-- Payment Details Card -->
                <div class="flight-card mb-4">
                    <h4 class="mb-3"><i class="bi bi-credit-card-2-front me-2 text-accent"></i>Payment & Confirmation</h4>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Card Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-credit-card"></i></span>
                                <input type="text" name="card_number" id="card_number" class="form-control" placeholder="4111 2222 3333 4444" pattern="\d{16}" maxlength="16" required value="4111222233334444">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CVV Code</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                                <input type="password" name="cvv" id="cvv" class="form-control" placeholder="123" pattern="\d{3}" maxlength="3" required value="123">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Price Breakdown & Submit -->
                <div class="price-breakdown-card">
                    <h5 class="fw-bold mb-3"><i class="bi bi-receipt me-2 text-accent"></i>Fare Summary</h5>
                    <div class="price-row">
                        <span class="text-muted">Base Fare (<span id="summarySeatCount">1</span> seat)</span>
                        <span class="fw-bold" id="summaryBasePrice">₹<?php echo number_format($f['price'], 2); ?></span>
                    </div>
                    <div class="price-row" id="businessSurchargeRow" style="display:none;">
                        <span class="text-muted">Business Class Upgrade</span>
                        <span class="fw-bold text-warning" id="summarySurcharge">+ ₹0.00</span>
                    </div>
                    <div class="price-row">
                        <span class="text-muted">Taxes & Airport Fee</span>
                        <span class="fw-bold text-success">Included</span>
                    </div>
                    <div class="price-row total-row">
                        <span>Total Amount</span>
                        <span class="text-accent" id="summaryTotalPrice">₹<?php echo number_format($f['price'], 2); ?></span>
                    </div>

                    <button type="submit" class="btn btn-accent btn-lg w-100 mt-4 py-3 fw-bold" id="confirmBookingBtn">
                        <i class="bi bi-check-circle-fill me-2"></i>Confirm & Pay <span id="payBtnAmount">₹<?php echo number_format($f['price'], 2); ?></span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const occupiedSeats = <?php echo json_encode($occupied_seats); ?>;
    const basePrice = <?php echo floatval($f['price']); ?>;
    const initialUserName = <?php echo json_encode($user_data['name'] ?? ''); ?>;
    const businessClassUpgrade = 1000; // ₹1,000 surcharge for Business seats (Rows 1-2)

    let requiredSeatCount = 1;
    let selectedSeats = [];

    const businessGrid = document.getElementById('businessClassGrid');
    const economyGrid = document.getElementById('economyClassGrid');
    const passengerCountSelect = document.getElementById('passengerCountSelect');
    const passengerFormsContainer = document.getElementById('passengerFormsContainer');
    const selectedSeatsCounter = document.getElementById('selectedSeatsCounter');
    
    const summarySeatCount = document.getElementById('summarySeatCount');
    const summaryBasePrice = document.getElementById('summaryBasePrice');
    const businessSurchargeRow = document.getElementById('businessSurchargeRow');
    const summarySurcharge = document.getElementById('summarySurcharge');
    const summaryTotalPrice = document.getElementById('summaryTotalPrice');
    const payBtnAmount = document.getElementById('payBtnAmount');

    // Build Aircraft Cabin Seats
    const cols = ['A', 'B', 'C', 'D', 'E', 'F'];

    function createRowElement(rowNum, isBusiness) {
        const rowDiv = document.createElement('div');
        rowDiv.className = 'seat-row-grid';

        const rowLabelLeft = document.createElement('div');
        rowLabelLeft.className = 'row-label';
        rowLabelLeft.textContent = rowNum;
        rowDiv.appendChild(rowLabelLeft);

        cols.forEach((col, idx) => {
            if (idx === 3) {
                const aisle = document.createElement('div');
                aisle.className = 'aisle-gap';
                aisle.textContent = '';
                rowDiv.appendChild(aisle);
            }

            const seatNum = rowNum + col;
            const seatBtn = document.createElement('div');
            seatBtn.className = 'seat-item';
            seatBtn.dataset.seat = seatNum;
            seatBtn.dataset.isBusiness = isBusiness ? 'true' : 'false';

            const isOccupied = occupiedSeats.includes(seatNum);
            
            if (isOccupied) {
                seatBtn.classList.add('occupied');
                seatBtn.title = `Seat ${seatNum} is Occupied`;
                seatBtn.innerHTML = `<span>${seatNum}</span>`;
            } else if (isBusiness) {
                seatBtn.classList.add('business-seat');
                seatBtn.title = `Business Seat ${seatNum} (+₹1,000)`;
                seatBtn.innerHTML = `<span>${seatNum}</span><span class="seat-type-indicator">★</span>`;
            } else {
                seatBtn.classList.add('available');
                seatBtn.title = `Standard Seat ${seatNum}`;
                seatBtn.innerHTML = `<span>${seatNum}</span>`;
            }

            seatBtn.addEventListener('click', () => handleSeatClick(seatNum, isOccupied, isBusiness));
            rowDiv.appendChild(seatBtn);
        });

        const rowLabelRight = document.createElement('div');
        rowLabelRight.className = 'row-label';
        rowLabelRight.textContent = rowNum;
        rowDiv.appendChild(rowLabelRight);

        return rowDiv;
    }

    // Render Rows 1 & 2 (Business)
    for (let r = 1; r <= 2; r++) {
        businessGrid.appendChild(createRowElement(r, true));
    }

    // Render Rows 3 to 10 (Economy)
    for (let r = 3; r <= 10; r++) {
        economyGrid.appendChild(createRowElement(r, false));
    }

    // Auto-select first available seats by default
    function autoSelectInitialSeats(count) {
        selectedSeats = [];
        const allSeats = document.querySelectorAll('.seat-item:not(.occupied)');
        for (let i = 0; i < Math.min(count, allSeats.length); i++) {
            selectedSeats.push(allSeats[i].dataset.seat);
        }
        updateUI();
    }

    function handleSeatClick(seatNum, isOccupied, isBusiness) {
        if (isOccupied) return;

        const index = selectedSeats.indexOf(seatNum);
        if (index > -1) {
            // Deselect seat
            selectedSeats.splice(index, 1);
        } else {
            // Select seat
            if (selectedSeats.length >= requiredSeatCount) {
                // If capacity reached, remove the first selected seat and add this one
                selectedSeats.shift();
            }
            selectedSeats.push(seatNum);
        }
        updateUI();
    }

    passengerCountSelect.addEventListener('change', function () {
        requiredSeatCount = parseInt(this.value);
        if (selectedSeats.length > requiredSeatCount) {
            selectedSeats = selectedSeats.slice(0, requiredSeatCount);
        } else if (selectedSeats.length < requiredSeatCount) {
            autoSelectInitialSeats(requiredSeatCount);
            return;
        }
        updateUI();
    });

    function updateUI() {
        // Highlight seats on layout
        document.querySelectorAll('.seat-item').forEach(btn => {
            const seatNum = btn.dataset.seat;
            if (selectedSeats.includes(seatNum)) {
                btn.classList.add('selected');
            } else {
                btn.classList.remove('selected');
            }
        });

        // Update Counter
        selectedSeatsCounter.textContent = `${selectedSeats.length} / ${requiredSeatCount} Seat${requiredSeatCount > 1 ? 's' : ''} Selected`;

        // Render Passenger Form Inputs
        passengerFormsContainer.innerHTML = '';
        
        selectedSeats.forEach((seatNum, idx) => {
            const isBiz = parseInt(seatNum.substring(0, seatNum.length - 1)) <= 2;
            const defaultName = (idx === 0) ? initialUserName : '';

            const pCard = document.createElement('div');
            pCard.className = 'passenger-card';
            pCard.innerHTML = `
                <div class="passenger-card-header">
                    <h6 class="fw-bold mb-0"><i class="bi bi-person-badge me-2 text-accent"></i>Passenger #${idx + 1}</h6>
                    <span class="selected-seat-badge"><i class="bi bi-card-heading me-1"></i>Seat ${seatNum} ${isBiz ? '(Business)' : ''}</span>
                </div>
                <input type="hidden" name="passengers[${idx}][seat]" value="${seatNum}">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="passengers[${idx}][name]" class="form-control" placeholder="As on official ID proof" required value="${defaultName}">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="form-label">Age</label>
                        <input type="number" name="passengers[${idx}][age]" class="form-control" min="1" max="120" placeholder="e.g. 25" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Gender</label>
                        <select name="passengers[${idx}][gender]" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
            `;
            passengerFormsContainer.appendChild(pCard);
        });

        // Calculate Pricing
        let totalBusinessUpgrades = 0;
        selectedSeats.forEach(seatNum => {
            const rowNum = parseInt(seatNum.substring(0, seatNum.length - 1));
            if (rowNum <= 2) {
                totalBusinessUpgrades += businessClassUpgrade;
            }
        });

        const totalBaseFare = basePrice * selectedSeats.length;
        const totalAmount = totalBaseFare + totalBusinessUpgrades;

        summarySeatCount.textContent = selectedSeats.length;
        summaryBasePrice.textContent = `₹${totalBaseFare.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
        
        if (totalBusinessUpgrades > 0) {
            businessSurchargeRow.style.display = 'flex';
            summarySurcharge.textContent = `+ ₹${totalBusinessUpgrades.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
        } else {
            businessSurchargeRow.style.display = 'none';
        }

        summaryTotalPrice.textContent = `₹${totalAmount.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
        payBtnAmount.textContent = `₹${totalAmount.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
    }

    // Initialize initial seats
    autoSelectInitialSeats(1);
});
</script>

<?php require_once 'includes/footer.php'; ?>
