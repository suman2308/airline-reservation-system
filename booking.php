<?php
$pageTitle = 'Book Flight';
require_once 'includes/header.php';
require_once 'includes/helpers.php';
require_once 'includes/Validation.php';
require_once 'includes/Security.php';

if (!isLoggedIn()) {
    setFlash('error', 'Please login to book a flight.');
    redirect('login.php');
}

$flight_id = intval($_GET['flight_id'] ?? 0);
if ($flight_id <= 0) redirect('search-flights.php');

$f = getFlightById($flight_id);
if (!$f || $f['status'] !== 'Scheduled' || $f['seats_available'] <= 0) {
    setFlash('error', 'Flight not available or fully booked.');
    redirect('search-flights.php');
}

$user_id = $_SESSION['user_id'];

// Fetch user data for pre-fill
$userStmt = mysqli_prepare($conn, "SELECT name, phone FROM users WHERE id=?");
mysqli_stmt_bind_param($userStmt, "i", $user_id);
mysqli_stmt_execute($userStmt);
$userData = mysqli_fetch_assoc(mysqli_stmt_get_result($userStmt));
mysqli_stmt_close($userStmt);

// Fetch saved passengers for quick-select
$savedPassengers = getSavedPassengers($user_id);

// Fetch occupied seats
$travel_date = validateTravelDate($_GET['date'] ?? '', $f['departure_time']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $travel_date = trim($_POST['travel_date'] ?? $travel_date);
}

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
    if (!requireCsrfToken()) {
        redirect('booking.php?flight_id=' . $flight_id . '&date=' . $travel_date);
    }

    $passengers = $_POST['passengers'] ?? [];
    $card_number = str_replace(' ', '', trim($_POST['card_number'] ?? ''));
    $cvv = trim($_POST['cvv'] ?? '');

    $bookingErrors = validateBooking($passengers, $f['seats_available'], $occupied_seats);
    if (!empty($bookingErrors)) {
        setFlash('error', $bookingErrors[0]);
        redirect('booking.php?flight_id=' . $flight_id . '&date=' . $travel_date);
    }

    $paymentErrors = validatePayment(['card_number' => $card_number, 'cvv' => $cvv]);
    if (!empty($paymentErrors)) {
        setFlash('error', $paymentErrors[0]);
        redirect('booking.php?flight_id=' . $flight_id . '&date=' . $travel_date);
    }

    $baggageCost = floatval(str_replace(',', '', $_POST['baggage_cost'] ?? 0));
    $mealCost = floatval(str_replace(',', '', $_POST['meal_cost'] ?? 0));
    $mealName = trim($_POST['meal_name'] ?? '');
    $promo_code = trim($_POST['promo_code'] ?? '');
    $submitted_promo_discount = floatval($_POST['promo_discount'] ?? 0);

    // Server-side pricing validation — never trust client
    $addonErrors = validateAddonCosts($baggageCost, $mealCost);
    if (!empty($addonErrors)) {
        setFlash('error', $addonErrors[0]);
        redirect('booking.php?flight_id=' . $flight_id . '&date=' . $travel_date);
    }

    // Validate promo code server-side
    $promoCheck = validatePromoCode($promo_code);
    if (!$promoCheck['valid']) {
        setFlash('error', 'Invalid promo code.');
        redirect('booking.php?flight_id=' . $flight_id . '&date=' . $travel_date);
    }
    // Recalculate discount server-side — reject manipulated values
    $passengerCount = count($passengers);
    $baseTotal = floatval($f['price']) * $passengerCount;
    $addonTotal = ($baggageCost + $mealCost) * $passengerCount;
    $calculatedDiscount = 0;
    if ($promoCheck['discount'] > 0) {
        if (($promoCheck['type'] ?? 'fixed') === 'percent') {
            $calculatedDiscount = $baseTotal * $promoCheck['discount'] / 100;
        } else {
            $calculatedDiscount = $promoCheck['discount'];
        }
    }
    // Prevent discount from exceeding total
    $calculatedDiscount = min($calculatedDiscount, $baseTotal + $addonTotal);
    $promo_discount = $calculatedDiscount;

    mysqli_begin_transaction($conn);
    try {
        $lock_stmt = mysqli_prepare($conn, "SELECT seats_available FROM flights WHERE flight_id = ? FOR UPDATE");
        mysqli_stmt_bind_param($lock_stmt, "i", $flight_id);
        mysqli_stmt_execute($lock_stmt);
        $lock_res = mysqli_stmt_get_result($lock_stmt);
        $current_flight = mysqli_fetch_assoc($lock_res);
        mysqli_stmt_close($lock_stmt);

        if (!$current_flight || $current_flight['seats_available'] < count($passengers)) {
            throw new Exception('Insufficient seats available.');
        }

        $created_refs = [];
        $created_ids = [];
        $promo_discount = floatval($_POST['promo_discount'] ?? 0);
        $promo_code = trim($_POST['promo_code'] ?? '');
        $baggage_option = trim($_POST['baggage_option'] ?? '');

        $book_stmt = mysqli_prepare($conn, "INSERT INTO bookings (booking_ref, user_id, flight_id, passenger_name, age, gender, travel_date, seat_number, booking_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Confirmed')");

        foreach ($passengers as $p) {
            $p_name = trim($p['name']);
            $p_age = intval($p['age']);
            $p_gender = trim($p['gender']);
            $p_seat = strtoupper(trim($p['seat']));

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
                throw new Exception("Failed to create booking for " . htmlspecialchars($p_name));
            }
            $booking_id = mysqli_insert_id($conn);
            $created_refs[] = $b_ref;
            $created_ids[] = $booking_id;

            // Save add-ons per booking (only if selected)
            if ($baggageCost > 0) {
                $addStmt = mysqli_prepare($conn, "INSERT INTO booking_addons (booking_id, addon_type, addon_name, amount) VALUES (?, 'baggage', ?, ?)");
                $baggageName = ($baggageCost >= 1400) ? '+20kg Heavy Baggage' : '+10kg Extra Baggage';
                mysqli_stmt_bind_param($addStmt, "isd", $booking_id, $baggageName, $baggageCost);
                mysqli_stmt_execute($addStmt);
                mysqli_stmt_close($addStmt);
            }
            if ($mealCost > 0 && !empty($mealName)) {
                $addStmt = mysqli_prepare($conn, "INSERT INTO booking_addons (booking_id, addon_type, addon_name, amount) VALUES (?, 'meal', ?, ?)");
                mysqli_stmt_bind_param($addStmt, "isd", $booking_id, $mealName, $mealCost);
                mysqli_stmt_execute($addStmt);
                mysqli_stmt_close($addStmt);
            }

            // Save passenger for future reuse
            if (isset($_POST['save_passenger_' . $p_seat])) {
                savePassenger($user_id, $p_name, $p_age, $p_gender);
            }
        }
        mysqli_stmt_close($book_stmt);

        $seat_count = count($passengers);
        $update_stmt = mysqli_prepare($conn, "UPDATE flights SET seats_available = seats_available - ? WHERE flight_id=? AND seats_available >= ?");
        mysqli_stmt_bind_param($update_stmt, "iii", $seat_count, $flight_id, $seat_count);
        mysqli_stmt_execute($update_stmt);

        if (mysqli_affected_rows($conn) > 0) {
            mysqli_commit($conn);
            logInfo('Booking completed', ['flight_id' => $flight_id, 'passengers' => count($passengers), 'user_id' => $user_id]);
            setFlash('success', 'Flight booking confirmed successfully!');
            redirect('booking-confirmation.php?refs=' . implode(',', $created_refs));
        } else {
            throw new Exception('Insufficient seats available.');
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        logError('Booking failed', ['flight_id' => $flight_id, 'message' => $e->getMessage()]);
        setFlash('error', 'Booking failed. Please try again.');
        redirect('booking.php?flight_id=' . $flight_id . '&date=' . $travel_date);
    }
}
?>

<div class="page-header py-5" style="padding: 6rem 0 2rem;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1 class="mb-1 fs-2"><i class="bi bi-ticket-perforated me-2 text-accent"></i>Complete Your Booking</h1>
                <p class="mb-0 text-muted">
                    <?php echo htmlspecialchars($f['airline_name'] . ' ' . $f['flight_number'] . ' · ' . $f['source'] . ' → ' . $f['destination']); ?>
                    · <i class="bi bi-calendar-event ms-1 me-1"></i><?php echo formatDate($travel_date); ?>
                </p>
            </div>
            <div class="text-end d-none d-md-block">
                <div class="fw-bold text-accent fs-4"><?php echo formatPrice($f['price']); ?></div>
                <small class="text-muted">per adult seat</small>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <?php showAlert(); ?>

    <!-- Booking Progress Steps -->
    <div class="booking-steps mb-5">
        <div class="step active" data-step="1">
            <div class="step-circle">1</div>
            <div class="step-label">Passengers</div>
        </div>
        <div class="step-connector"></div>
        <div class="step" data-step="2">
            <div class="step-circle">2</div>
            <div class="step-label">Seats</div>
        </div>
        <div class="step-connector"></div>
        <div class="step" data-step="3">
            <div class="step-circle">3</div>
            <div class="step-label">Add-ons</div>
        </div>
        <div class="step-connector"></div>
        <div class="step" data-step="4">
            <div class="step-circle">4</div>
            <div class="step-label">Review & Pay</div>
        </div>
    </div>

    <form method="POST" id="seatBookingForm" novalidate>
        <?php csrfField(); ?>
        <input type="hidden" name="travel_date" value="<?php echo htmlspecialchars($travel_date); ?>">
        <input type="hidden" name="baggage_cost" id="baggageCost" value="0">
        <input type="hidden" name="meal_cost" id="mealCost" value="0">
        <input type="hidden" name="meal_name" id="mealName" value="">
        <input type="hidden" name="baggage_option" id="baggageOptionHidden" value="">
        <input type="hidden" name="promo_discount" id="promoDiscount" value="0">
        <input type="hidden" name="promo_code" id="promoCode" value="">

        <input type="hidden" name="current_step" id="currentStep" value="1">
        <input type="hidden" name="passenger_count" id="passengerCount" value="1">

        <!-- ===== STEP 1: PASSENGER INFO ===== -->
        <div class="booking-step-content" id="step1Content">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="fw-bold mb-0"><i class="bi bi-people-fill me-2 text-accent"></i>Passenger Information</h3>
                        <select id="passengerCountSelect" class="form-select w-auto fw-bold">
                            <?php
                            $max_p = min(6, $f['seats_available']);
                            for ($i = 1; $i <= $max_p; $i++) {
                                echo "<option value='$i'>$i Passenger" . ($i > 1 ? 's' : '') . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <p class="text-muted mb-4">Enter details for each passenger. You can save passengers for future bookings.</p>

                    <!-- Saved Passengers Quick-Select -->
                    <?php if (!empty($savedPassengers)): ?>
                    <div class="mb-4 p-3 bg-light rounded-3 border">
                        <label class="fw-bold mb-2 small text-muted text-uppercase"><i class="bi bi-clock-history me-1"></i>Quick-Select Saved Passenger</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php foreach ($savedPassengers as $sp): ?>
                            <button type="button" class="btn btn-outline-secondary btn-sm saved-passenger-btn"
                                    data-name="<?php echo htmlspecialchars($sp['name']); ?>"
                                    data-age="<?php echo $sp['age']; ?>"
                                    data-gender="<?php echo $sp['gender']; ?>">
                                <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($sp['name']); ?>
                                <small class="text-muted">(<?php echo $sp['age'] . 'y, ' . $sp['gender']; ?>)</small>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div id="passengerFormsContainer">
                        <!-- Dynamically generated by JS -->
                    </div>

                    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                        <div></div>
                        <button type="button" class="btn btn-accent btn-lg px-5 next-step-btn" data-next="2">
                            Continue to Seat Selection <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== STEP 2: SEAT SELECTION ===== -->
        <div class="booking-step-content d-none" id="step2Content">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h4 class="fw-bold mb-3"><i class="bi bi-grid-3x3-gap me-2 text-accent"></i>Select Your Seats</h4>

                            <!-- Seat Legend -->
                            <div class="d-flex flex-wrap gap-3 mb-3 pb-3 border-bottom">
                                <div class="d-flex align-items-center gap-1 small">
                                    <span class="d-inline-block" style="width:18px;height:18px;background:#e8f5e9;border:1px solid #c8e6c9;border-radius:3px;"></span> Vacant
                                </div>
                                <div class="d-flex align-items-center gap-1 small">
                                    <span class="d-inline-block" style="width:18px;height:18px;background:var(--accent);border-radius:3px;"></span> Selected
                                </div>
                                <div class="d-flex align-items-center gap-1 small">
                                    <span class="d-inline-block legend-box occupied" style="width:18px;height:18px;"></span> Occupied
                                </div>
                                <div class="d-flex align-items-center gap-1 small">
                                    <span class="d-inline-block legend-box business" style="width:18px;height:18px;"></span> Business
                                </div>
                                <div class="d-flex align-items-center gap-1 small">
                                    <span class="d-inline-block exit-row" style="width:18px;height:18px;border:1px solid;"></span> Exit Row
                                </div>
                            </div>

                            <!-- Seat Map -->
                            <div id="seatMapContainer">
                                <div class="text-center mb-3">
                                    <div class="d-inline-block bg-dark rounded-top-4 px-5 py-1"><small class="text-white-50">Cockpit</small></div>
                                </div>
                                <div class="cabin-section mb-3">
                                    <div class="cabin-label text-center fw-bold small text-uppercase text-muted mb-2 py-1 bg-light rounded">
                                        <i class="bi bi-star-fill text-warning me-1"></i> Business Class (Rows 1-2)
                                    </div>
                                    <div id="businessClassGrid" class="cabin-seats-wrapper"></div>
                                </div>
                                <div class="cabin-section mb-3">
                                    <div class="cabin-label text-center fw-bold small text-uppercase text-muted mb-2 py-1 bg-light rounded">
                                        <i class="bi bi-people text-primary me-1"></i> Economy Class (Rows 3-10)
                                    </div>
                                    <div id="economyClassGrid" class="cabin-seats-wrapper"></div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                <button type="button" class="btn btn-outline-secondary btn-lg prev-step-btn" data-prev="1">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Passengers
                                </button>
                                <button type="button" class="btn btn-accent btn-lg px-5 next-step-btn" data-next="3">
                                    Continue to Add-ons <i class="bi bi-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="bi bi-card-checklist me-2 text-accent"></i>Selected Seats</h5>
                            <div id="selectedSeatsSummary" class="mb-3">
                                <p class="text-muted small mb-0">No seats selected yet. Click on available seats in the cabin layout.</p>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Passengers:</span>
                                <span class="fw-bold" id="seatStepCount">0</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Seats Selected:</span>
                                <span class="fw-bold text-success" id="seatStepSelected">0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== STEP 3: ADD-ONS ===== -->
        <div class="booking-step-content d-none" id="step3Content">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h3 class="fw-bold mb-4"><i class="bi bi-box-seam me-2 text-accent"></i>Baggage & In-Flight Add-Ons</h3>
                    <p class="text-muted mb-4">Enhance your journey with extra baggage allowance and meal preferences. These apply to all passengers.</p>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 border rounded-3 bg-light">
                                <label class="fw-bold mb-2 d-block"><i class="bi bi-luggage me-2 text-accent"></i>Check-in Baggage</label>
                                <select id="baggageOption" class="form-select">
                                    <option value="0">Standard 15kg (Included)</option>
                                    <option value="800">+10kg Extra Baggage (+₹800 per person)</option>
                                    <option value="1400">+20kg Heavy Baggage (+₹1,400 per person)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded-3 bg-light">
                                <label class="fw-bold mb-2 d-block"><i class="bi bi-cup-hot me-2 text-accent"></i>In-Flight Meal</label>
                                <select id="mealOption" class="form-select">
                                    <option value="0">No Meal Selected</option>
                                    <option value="350">Vegetarian Gourmet Meal (+₹350 per person)</option>
                                    <option value="450">Non-Veg Chicken Feast (+₹450 per person)</option>
                                    <option value="350 jain">Special Jain Thali (+₹350 per person)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-white border rounded-3">
                        <h6 class="fw-bold mb-2">Add-On Summary</h6>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">Baggage Upgrade</span>
                            <span class="fw-bold" id="addonBaggageSummary">Standard (Included)</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">Meal Preference</span>
                            <span class="fw-bold" id="addonMealSummary">Not Selected</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-top mt-1 pt-1">
                            <span class="fw-bold">Additional Cost Per Person</span>
                            <span class="fw-bold text-accent" id="addonPerPersonCost">₹0</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-outline-secondary btn-lg prev-step-btn" data-prev="2">
                            <i class="bi bi-arrow-left me-2"></i>Back to Seats
                        </button>
                        <button type="button" class="btn btn-accent btn-lg px-5 next-step-btn" data-next="4">
                            Review & Confirm <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== STEP 4: REVIEW & PAY ===== -->
        <div class="booking-step-content d-none" id="step4Content">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <h4 class="fw-bold mb-3"><i class="bi bi-eye me-2 text-accent"></i>Review Your Booking</h4>

                            <!-- Flight Summary -->
                            <div class="p-3 bg-light rounded-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($f['source']); ?> → <?php echo htmlspecialchars($f['destination']); ?></h5>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($f['airline_name'] . ' ' . $f['flight_number']); ?>
                                            · <?php echo formatDate($travel_date); ?>
                                            · <?php echo formatTime($f['departure_time']); ?> – <?php echo formatTime($f['arrival_time']); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-accent fs-6"><?php echo calcDuration($f['departure_time'], $f['arrival_time']); ?></span>
                                </div>
                            </div>

                            <!-- Passengers Summary -->
                            <h6 class="fw-bold mb-2"><i class="bi bi-people me-1 text-accent"></i>Passengers & Seats</h6>
                            <div id="reviewPassengersList" class="mb-3">
                                <!-- Populated by JS -->
                                <p class="text-muted small">Complete steps 1-2 to see passenger details.</p>
                            </div>

                            <!-- Add-ons Summary -->
                            <h6 class="fw-bold mb-2"><i class="bi bi-box-seam me-1 text-accent"></i>Add-Ons</h6>
                            <div id="reviewAddons" class="mb-3">
                                <p class="text-muted small">Standard baggage (15kg). No meal selected.</p>
                            </div>

                            <!-- Promo Code -->
                            <div class="p-2 bg-light rounded-3 border mb-3">
                                <div class="input-group input-group-sm">
                                    <input type="text" id="promoInput" class="form-control text-uppercase" placeholder="Promo code (AERO10, FLY2026, WELCOME)">
                                    <button type="button" id="applyPromoBtn" class="btn btn-outline-primary fw-bold">Apply</button>
                                </div>
                                <div id="promoMsg" class="small mt-1 fw-semibold" style="display:none;"></div>
                            </div>

                            <!-- Cancellation Policy -->
                            <div class="alert alert-warning small py-2 mb-3">
                                <i class="bi bi-info-circle me-1"></i>
                                <strong>Cancellation Policy:</strong> Free cancellation up to 24 hours before departure. ₹500 fee applies after that.
                            </div>

                            <!-- Acknowledgement -->
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="termsCheck" required>
                                <label class="form-check-label small" for="termsCheck">
                                    I confirm that the passenger information provided is correct and accept the <a href="#" class="text-accent">terms and conditions</a>.
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Card -->
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="bi bi-credit-card me-2 text-accent"></i>Payment Details</h5>
                            <p class="text-muted small mb-3">This is a simulated payment. No real charges will be made.</p>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold">Card Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-credit-card"></i></span>
                                        <input type="text" name="card_number" id="card_number" class="form-control" placeholder="4111 2222 3333 4444" pattern="\d{16}" maxlength="16" required value="4111222233334444">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">CVV</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                                        <input type="password" name="cvv" id="cvv" class="form-control" placeholder="123" pattern="\d{3}" maxlength="3" required value="123">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Fare Summary -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4" style="position: sticky; top: 100px;">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="bi bi-receipt me-2 text-accent"></i>Fare Summary</h5>

                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Base Fare (<span id="summarySeatCount">1</span> seat × <?php echo formatPrice($f['price']); ?>)</span>
                                <span class="fw-bold" id="summaryBasePrice"><?php echo formatPrice($f['price']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom" id="businessSurchargeRow" style="display:none;">
                                <span class="text-muted">Business Class Upgrade</span>
                                <span class="fw-bold text-warning" id="summarySurcharge">+ ₹0</span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom" id="addonSurchargeRow" style="display:none;">
                                <span class="text-muted">Baggage & Meals (× <span id="addonCountLabel">1</span> passenger)</span>
                                <span class="fw-bold" id="summaryAddonSurcharge">+ ₹0</span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom" id="promoDiscountRow" style="display:none;">
                                <span class="text-success"><i class="bi bi-tag me-1"></i><span id="promoLabelText">Promo Discount</span></span>
                                <span class="fw-bold text-success" id="summaryDiscount">- ₹0</span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Taxes & Fees</span>
                                <span class="fw-bold text-success">Included</span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Cancellation Protection</span>
                                <span class="fw-bold text-success">Free</span>
                            </div>

                            <div class="d-flex justify-content-between py-3 mt-2 border-top">
                                <span class="fw-bold fs-5">Total Amount</span>
                                <span class="text-accent fw-bold fs-4" id="summaryTotalPrice"><?php echo formatPrice($f['price']); ?></span>
                            </div>

                            <div class="d-flex justify-content-between mt-4 pt-3 border-top flex-column gap-2">
                                <button type="button" class="btn btn-outline-secondary prev-step-btn" data-prev="3">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Add-ons
                                </button>
                                <button type="submit" class="btn btn-accent btn-lg py-3 fw-bold" id="confirmBookingBtn" disabled>
                                    <i class="bi bi-check-circle-fill me-2"></i>Confirm & Pay <span id="payBtnAmount"><?php echo formatPrice($f['price']); ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Loading overlay (only on final step submit)
    const spinner = document.createElement('div');
    spinner.className = 'spinner-overlay';
    spinner.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(spinner);

    function showSpinner() {
        spinner.classList.add('active');
        document.getElementById('confirmBookingBtn').disabled = true;
        document.getElementById('confirmBookingBtn').innerHTML = '<span class="loading-dots"><span></span><span></span><span></span></span> Processing...';
    }

    function hideSpinner() {
        spinner.classList.remove('active');
        document.getElementById('confirmBookingBtn').disabled = false;
    }

    const bookingForm = document.getElementById('seatBookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            if (document.getElementById('termsCheck').checked) {
                showSpinner();
            } else {
                e.preventDefault();
                document.getElementById('termsCheck').classList.add('is-invalid');
            }
        });
    }
    const occupiedSeats = <?php echo json_encode($occupied_seats); ?>;
    const basePrice = <?php echo floatval($f['price']); ?>;
    const initialUserName = <?php echo json_encode($userData['name'] ?? ''); ?>;
    const businessClassUpgrade = 1000;
    const savedPassengers = <?php echo json_encode($savedPassengers); ?>;

    let currentStep = 1;
    let requiredSeatCount = 1;
    let selectedSeats = [];
    let appliedDiscount = 0;
    let appliedPromoCode = '';

    // ───────── DOM References ─────────
    const steps = document.querySelectorAll('.step');
    const stepContents = {
        1: document.getElementById('step1Content'),
        2: document.getElementById('step2Content'),
        3: document.getElementById('step3Content'),
        4: document.getElementById('step4Content'),
    };
    const businessGrid = document.getElementById('businessClassGrid');
    const economyGrid = document.getElementById('economyClassGrid');
    const passengerCountSelect = document.getElementById('passengerCountSelect');
    const passengerFormsContainer = document.getElementById('passengerFormsContainer');
    const selectedSeatsSummary = document.getElementById('selectedSeatsSummary');
    const seatStepCount = document.getElementById('seatStepCount');
    const seatStepSelected = document.getElementById('seatStepSelected');
    const reviewPassengersList = document.getElementById('reviewPassengersList');
    const reviewAddons = document.getElementById('reviewAddons');
    const summarySeatCount = document.getElementById('summarySeatCount');
    const summaryBasePrice = document.getElementById('summaryBasePrice');
    const businessSurchargeRow = document.getElementById('businessSurchargeRow');
    const summarySurcharge = document.getElementById('summarySurcharge');
    const addonSurchargeRow = document.getElementById('addonSurchargeRow');
    const addonCountLabel = document.getElementById('addonCountLabel');
    const summaryAddonSurcharge = document.getElementById('summaryAddonSurcharge');
    const promoDiscountRow = document.getElementById('promoDiscountRow');
    const promoLabelText = document.getElementById('promoLabelText');
    const summaryDiscount = document.getElementById('summaryDiscount');
    const summaryTotalPrice = document.getElementById('summaryTotalPrice');
    const payBtnAmount = document.getElementById('payBtnAmount');
    const confirmBookingBtn = document.getElementById('confirmBookingBtn');
    const termsCheck = document.getElementById('termsCheck');
    const baggageOption = document.getElementById('baggageOption');
    const mealOption = document.getElementById('mealOption');
    const addonBaggageSummary = document.getElementById('addonBaggageSummary');
    const addonMealSummary = document.getElementById('addonMealSummary');
    const addonPerPersonCost = document.getElementById('addonPerPersonCost');
    const promoInput = document.getElementById('promoInput');
    const applyPromoBtn = document.getElementById('applyPromoBtn');
    const promoMsg = document.getElementById('promoMsg');
    const currentStepHidden = document.getElementById('currentStep');
    const passengerCountHidden = document.getElementById('passengerCount');
    const baggageCostHidden = document.getElementById('baggageCost');
    const mealCostHidden = document.getElementById('mealCost');
    const mealNameHidden = document.getElementById('mealName');
    const baggageOptionHidden = document.getElementById('baggageOptionHidden');
    const promoDiscountHidden = document.getElementById('promoDiscount');
    const promoCodeHidden = document.getElementById('promoCode');

    // ───────── Step Navigation ─────────
    function goToStep(step) {
        currentStep = step;
        currentStepHidden.value = step;
        Object.keys(stepContents).forEach(s => {
            stepContents[s].classList.toggle('d-none', parseInt(s) !== step);
        });
        steps.forEach((el, idx) => {
            el.classList.toggle('active', idx + 1 <= step);
            el.classList.toggle('current', idx + 1 === step);
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
        if (step === 4) updateReviewPage();
        updateFareSummary();
    }

    document.querySelectorAll('.next-step-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const next = parseInt(btn.dataset.next);
            // Validate passenger names filled in before leaving step 1
            if (currentStep === 1) {
                const nameInputs = passengerFormsContainer.querySelectorAll('input[name$="[name]"]');
                for (const input of nameInputs) {
                    if (!input.value.trim()) {
                        input.classList.add('is-invalid');
                        input.focus();
                        return;
                    }
                    input.classList.remove('is-invalid');
                }
            }
            goToStep(next);
        });
    });

    document.querySelectorAll('.prev-step-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            goToStep(parseInt(btn.dataset.prev));
        });
    });

    // ───────── Build Seat Map ─────────
    const cols = ['A', 'B', 'C', 'D', 'E', 'F'];
    const exitRows = [3, 10];
    const windowCols = ['A', 'F'];
    const aisleCols = ['C', 'D'];
    const middleCols = ['B', 'E'];

    function getSeatType(rowNum, col) {
        if (rowNum <= 2) return 'business';
        if (exitRows.includes(rowNum)) return 'exit';
        return 'economy';
    }

    function getColType(col) {
        if (windowCols.includes(col)) return 'window';
        if (aisleCols.includes(col)) return 'aisle';
        return 'middle';
    }

    function createSeatRow(rowNum, isBusiness) {
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
                rowDiv.appendChild(aisle);
            }

            const seatNum = rowNum + col;
            const seatBtn = document.createElement('div');
            seatBtn.className = 'seat-item';
            seatBtn.dataset.seat = seatNum;
            seatBtn.dataset.row = rowNum;
            seatBtn.dataset.col = col;
            seatBtn.dataset.isBusiness = isBusiness ? 'true' : 'false';

            const isOccupied = occupiedSeats.includes(seatNum);
            const seatType = getSeatType(rowNum, col);
            const colType = getColType(col);
            const isExit = exitRows.includes(rowNum);

            let tooltip = `Seat ${seatNum}`;
            let cssClass = '';
            let indicator = '';

            if (isOccupied) {
                cssClass = 'occupied';
                tooltip = `Seat ${seatNum} — Occupied`;
                seatBtn.innerHTML = `<span>${seatNum}</span><div class="seat-label">X</div>`;
            } else if (isBusiness) {
                cssClass = 'business-seat';
                tooltip = `Seat ${seatNum} — Business · ${colType} · +₹1,000`;
                seatBtn.innerHTML = `<span>${seatNum}</span><span class="seat-type-indicator">★</span><div class="seat-label">${colType === 'window' ? '🔲' : colType === 'aisle' ? '🚪' : '⬜'}</div>`;
            } else if (isExit) {
                cssClass = 'economy-seat exit-row';
                tooltip = `Seat ${seatNum} — Economy · ${colType} · Exit Row (extra legroom)`;
                seatBtn.innerHTML = `<span>${seatNum}</span><div class="seat-label">${colType === 'window' ? '🔲' : colType === 'aisle' ? '🚪' : '⬜'}</div><span class="exit-icon">⬆</span>`;
            } else {
                cssClass = 'economy-seat';
                tooltip = `Seat ${seatNum} — Economy · ${colType}`;
                seatBtn.innerHTML = `<span>${seatNum}</span><div class="seat-label">${colType === 'window' ? '🔲' : colType === 'aisle' ? '🚪' : '⬜'}</div>`;
            }

            seatBtn.className = 'seat-item ' + cssClass;
            seatBtn.title = tooltip;
            seatBtn.dataset.type = seatType;
            seatBtn.dataset.coltype = colType;

            seatBtn.addEventListener('click', () => {
                if (isOccupied) return;
                const idx = selectedSeats.indexOf(seatNum);
                if (idx > -1) {
                    selectedSeats.splice(idx, 1);
                } else {
                    if (selectedSeats.length >= requiredSeatCount) {
                        selectedSeats.shift();
                    }
                    selectedSeats.push(seatNum);
                }
                updateSeatUI();
            });

            rowDiv.appendChild(seatBtn);
        });

        const rowLabelRight = document.createElement('div');
        rowLabelRight.className = 'row-label';
        rowLabelRight.textContent = rowNum;
        rowDiv.appendChild(rowLabelRight);

        return rowDiv;
    }

    for (let r = 1; r <= 2; r++) businessGrid.appendChild(createSeatRow(r, true));
    for (let r = 3; r <= 10; r++) economyGrid.appendChild(createSeatRow(r, false));

    // ───────── Passenger Count ─────────
    passengerCountSelect.addEventListener('change', function() {
        requiredSeatCount = parseInt(this.value);
        passengerCountHidden.value = requiredSeatCount;
        if (selectedSeats.length > requiredSeatCount) {
            selectedSeats = selectedSeats.slice(0, requiredSeatCount);
        } else if (selectedSeats.length < requiredSeatCount) {
            autoSelectSeats(requiredSeatCount);
            return;
        }
        updateSeatUI();
    });

    function autoSelectSeats(count) {
        selectedSeats = [];
        const allSeats = document.querySelectorAll('.seat-item:not(.occupied)');
        for (let i = 0; i < Math.min(count, allSeats.length); i++) {
            selectedSeats.push(allSeats[i].dataset.seat);
        }
        updateSeatUI();
    }

    // ───────── Update Seat UI ─────────
    function updateSeatUI() {
        document.querySelectorAll('.seat-item').forEach(btn => {
            const seat = btn.dataset.seat;
            btn.classList.toggle('selected', selectedSeats.includes(seat));
        });

        // Passenger forms for step 1
        passengerFormsContainer.innerHTML = '';
        selectedSeats.forEach((seatNum, idx) => {
            const isBiz = parseInt(seatNum.substring(0, seatNum.length - 1)) <= 2;
            const defaultName = (idx === 0) ? initialUserName : '';
            const pCard = document.createElement('div');
            pCard.className = 'passenger-card mb-3 p-3 border rounded-3 bg-white';
            pCard.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0"><i class="bi bi-person-badge me-2 text-accent"></i>Passenger #${idx + 1}</h6>
                    <span class="badge bg-accent rounded-pill">Seat ${seatNum} ${isBiz ? ' · Business' : ''}</span>
                </div>
                <input type="hidden" name="passengers[${idx}][seat]" value="${seatNum}">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small">Full Name</label>
                        <input type="text" name="passengers[${idx}][name]" class="form-control form-control-sm" placeholder="As on ID proof" required value="${defaultName}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Age</label>
                        <input type="number" name="passengers[${idx}][age]" class="form-control form-control-sm" min="1" max="120" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Gender</label>
                        <select name="passengers[${idx}][gender]" class="form-select form-select-sm" required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-check mt-2">
                    <input type="checkbox" class="form-check-input" name="save_passenger_${seatNum}" id="savePass_${idx}">
                    <label class="form-check-label small text-muted" for="savePass_${idx}">Save this passenger for future bookings</label>
                </div>
            `;
            passengerFormsContainer.appendChild(pCard);
        });

        // Selected seats summary
        selectedSeatsSummary.innerHTML = '';
        if (selectedSeats.length === 0) {
            selectedSeatsSummary.innerHTML = '<p class="text-muted small mb-0">No seats selected yet.</p>';
        } else {
            const ul = document.createElement('div');
            ul.className = 'd-flex flex-column gap-1';
            selectedSeats.forEach((s, idx) => {
                const rowNum = parseInt(s.substring(0, s.length - 1));
                const col = s.substring(s.length - 1);
                const colType = getColType(col);
                const seatType = rowNum <= 2 ? 'Business' : exitRows.includes(rowNum) ? 'Exit Row' : 'Economy';
                const item = document.createElement('div');
                item.className = 'd-flex justify-content-between align-items-center py-1 border-bottom small';
                item.innerHTML = `<span><strong>${s}</strong> <span class="text-muted">— ${colType}</span></span>
                                  <span class="badge bg-light text-dark">${seatType}</span>`;
                ul.appendChild(item);
            });
            selectedSeatsSummary.appendChild(ul);
        }
        seatStepCount.textContent = requiredSeatCount;
        seatStepSelected.textContent = selectedSeats.length;
    }

    // ───────── Saved Passengers Quick-Select ─────────
    document.querySelectorAll('.saved-passenger-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const name = this.dataset.name;
            const age = this.dataset.age;
            const gender = this.dataset.gender;

            // Auto-fill first empty passenger form
            const nameInputs = passengerFormsContainer.querySelectorAll('input[name$="[name]"]');
            for (const input of nameInputs) {
                if (!input.value) {
                    input.value = name;
                    // Find corresponding age/gender inputs
                    const parent = input.closest('.passenger-card');
                    const ageInput = parent.querySelector('input[name$="[age]"]');
                    const genderSelect = parent.querySelector('select[name$="[gender]"]');
                    if (ageInput) ageInput.value = age;
                    if (genderSelect) genderSelect.value = gender;
                    break;
                }
            }
        });
    });

    // ───────── Add-ons ─────────
    baggageOption.addEventListener('change', updateAddonSummary);
    mealOption.addEventListener('change', function() {
        updateAddonSummary();
        // Update meal name hidden field
        const val = this.value;
        if (val.includes(' ')) {
            const parts = val.split(' ');
            mealNameHidden.value = this.options[this.selectedIndex].text;
        } else if (val === '350') {
            mealNameHidden.value = 'Vegetarian Gourmet Meal';
        } else if (val === '450') {
            mealNameHidden.value = 'Non-Veg Chicken Feast';
        } else {
            mealNameHidden.value = '';
        }
    });

    function updateAddonSummary() {
        const baggageVal = parseFloat(baggageOption.value) || 0;
        const mealVal = parseFloat(mealOption.value.split(' ')[0]) || 0;
        const perPerson = baggageVal + mealVal;

        baggageCostHidden.value = baggageVal;
        mealCostHidden.value = mealVal;
        baggageOptionHidden.value = baggageOption.options[baggageOption.selectedIndex].text;

        addonBaggageSummary.textContent = baggageVal === 0 ? 'Standard (Included)' : baggageOption.options[baggageOption.selectedIndex].text;
        addonMealSummary.textContent = mealVal === 0 ? 'Not Selected' : mealOption.options[mealOption.selectedIndex].text;
        addonPerPersonCost.textContent = perPerson > 0 ? '+₹' + perPerson.toLocaleString('en-IN') : '₹0';
    }

    // ───────── Promo Code ─────────
    applyPromoBtn.addEventListener('click', function() {
        const code = promoInput.value.trim().toUpperCase();
        if (code === 'AERO10') {
            appliedDiscount = 0.10;
            appliedPromoCode = 'AERO10';
            promoMsg.className = 'small mt-1 text-success fw-bold';
            promoMsg.textContent = '✓ Code AERO10 applied! 10% off on total fare.';
            promoMsg.style.display = 'block';
        } else if (code === 'FLY2026') {
            appliedDiscount = 500;
            appliedPromoCode = 'FLY2026';
            promoMsg.className = 'small mt-1 text-success fw-bold';
            promoMsg.textContent = '✓ Code FLY2026 applied! ₹500 off on total fare.';
            promoMsg.style.display = 'block';
        } else if (code === 'WELCOME') {
            appliedDiscount = 300;
            appliedPromoCode = 'WELCOME';
            promoMsg.className = 'small mt-1 text-success fw-bold';
            promoMsg.textContent = '✓ Welcome offer applied! ₹300 off on total fare.';
            promoMsg.style.display = 'block';
        } else {
            appliedDiscount = 0;
            appliedPromoCode = '';
            promoMsg.className = 'small mt-1 text-danger fw-bold';
            promoMsg.textContent = '✕ Invalid code. Try AERO10 (10% off) or FLY2026 (₹500 off).';
            promoMsg.style.display = 'block';
        }
        promoDiscountHidden.value = typeof appliedDiscount === 'number' ? appliedDiscount : 0;
        promoCodeHidden.value = appliedPromoCode;
        updateFareSummary();
    });

    // ───────── Fare Summary ─────────
    function updateFareSummary() {
        const passengerCount = selectedSeats.length || 1;
        let totalBizUpgrades = 0;
        selectedSeats.forEach(seat => {
            const row = parseInt(seat.substring(0, seat.length - 1));
            if (row <= 2) totalBizUpgrades += businessClassUpgrade;
        });

        const baggageCost = parseFloat(baggageOption.value) || 0;
        const mealCost = parseFloat(mealOption.value.split(' ')[0]) || 0;
        const totalAddonPerPerson = baggageCost + mealCost;
        const totalAddonCost = totalAddonPerPerson * passengerCount;

        const totalBase = basePrice * passengerCount;
        const subTotal = totalBase + totalBizUpgrades + totalAddonCost;

        let discount = 0;
        if (typeof appliedDiscount === 'number' && appliedDiscount < 1 && appliedDiscount > 0) {
            discount = subTotal * appliedDiscount;
        } else if (typeof appliedDiscount === 'number' && appliedDiscount >= 1) {
            discount = appliedDiscount;
        }
        const grandTotal = Math.max(0, subTotal - discount);

        summarySeatCount.textContent = passengerCount;
        summaryBasePrice.textContent = '₹' + totalBase.toLocaleString('en-IN', {minimumFractionDigits: 2});

        businessSurchargeRow.style.display = totalBizUpgrades > 0 ? 'flex' : 'none';
        if (totalBizUpgrades > 0) summarySurcharge.textContent = '+ ₹' + totalBizUpgrades.toLocaleString('en-IN', {minimumFractionDigits: 2});

        addonSurchargeRow.style.display = totalAddonCost > 0 ? 'flex' : 'none';
        addonCountLabel.textContent = passengerCount;
        if (totalAddonCost > 0) summaryAddonSurcharge.textContent = '+ ₹' + totalAddonCost.toLocaleString('en-IN', {minimumFractionDigits: 2});

        promoDiscountRow.style.display = discount > 0 ? 'flex' : 'none';
        promoLabelText.textContent = appliedPromoCode ? appliedPromoCode + ' Discount' : 'Promo Discount';
        if (discount > 0) summaryDiscount.textContent = '- ₹' + discount.toLocaleString('en-IN', {minimumFractionDigits: 2});

        summaryTotalPrice.textContent = '₹' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2});
        payBtnAmount.textContent = '₹' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2});
    }

    // ───────── Review Page ─────────
    function updateReviewPage() {
        // Passenger list
        reviewPassengersList.innerHTML = '';
        selectedSeats.forEach((seat, idx) => {
            const row = parseInt(seat.substring(0, seat.length - 1));
            const col = seat.substring(seat.length - 1);
            const colType = getColType(col);
            const seatType = row <= 2 ? 'Business' : 'Economy';
            const div = document.createElement('div');
            div.className = 'd-flex justify-content-between align-items-center py-1 border-bottom small';
            const nameInput = document.querySelector(`input[name="passengers[${idx}][name]"]`);
            const ageInput = document.querySelector(`input[name="passengers[${idx}][age]"]`);
            const genderSelect = document.querySelector(`select[name="passengers[${idx}][gender]"]`);
            const name = nameInput ? nameInput.value : 'Passenger ' + (idx + 1);
            const age = ageInput ? ageInput.value : '';
            const gender = genderSelect ? genderSelect.value : '';
            div.innerHTML = `<span><strong>${htmlspecialchars(name)}</strong> <span class="text-muted">(${gender}, ${age}y)</span></span>
                              <span class="badge bg-accent">${seat} · ${seatType}</span>`;
            reviewPassengersList.appendChild(div);
        });

        // Add-ons
        const baggageVal = parseFloat(baggageOption.value) || 0;
        const mealVal = parseFloat(mealOption.value.split(' ')[0]) || 0;
        let addonText = 'Standard baggage (15kg). No meal selected.';
        if (baggageVal > 0 || mealVal > 0) {
            const parts = [];
            if (baggageVal > 0) parts.push(baggageOption.options[baggageOption.selectedIndex].text);
            if (mealVal > 0) parts.push(mealOption.options[mealOption.selectedIndex].text);
            addonText = parts.join('. ');
        }
        reviewAddons.innerHTML = `<p class="text-muted small mb-0">${addonText}</p>`;

        updateFareSummary();
    }

    // ───────── Terms Toggle ─────────
    termsCheck.addEventListener('change', function() {
        confirmBookingBtn.disabled = !this.checked;
    });

    // ───────── Initial Load ─────────
    autoSelectSeats(1);
    updateAddonSummary();

    // Handle URL param for step
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('step')) {
        goToStep(parseInt(urlParams.get('step')));
    } else {
        goToStep(1);
    }
});

function htmlspecialchars(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
</script>


<?php require_once 'includes/footer.php'; ?>
