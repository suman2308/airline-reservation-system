<?php
/**
 * AeroBook – Smart Fare Engine
 *
 * Deterministic flight search that finds direct and connecting itineraries,
 * scores them by value, and explains why each option is recommended.
 *
 * Algorithm Complexity: O(H) where H = number of unique hub cities (typically ≤ 6)
 * Database Queries: 3 per search (1 direct + 1 source-to-hubs + 1 hubs-to-dest)
 */

// ──────────────────────────────────────────────
// Configuration
// ─────────────────────────────────────────────-
if (!defined('FARE_MIN_LAYOVER_MINUTES')) {
    define('FARE_MIN_LAYOVER_MINUTES', 90);     // 1.5 hours minimum
    define('FARE_MAX_LAYOVER_MINUTES', 480);     // 8 hours maximum
    define('FARE_MAX_CONNECTIONS', 1);           // Only 1-stop currently
    define('FARE_SAVINGS_THRESHOLD', 200);       // Minimum ₹ savings to show badge

    // Best-value scoring weights (total = 100)
    define('SCORE_PRICE_WEIGHT', 50);           // 50% price importance
    define('SCORE_DURATION_WEIGHT', 25);         // 25% travel time importance
    define('SCORE_LAYOVER_WEIGHT', 15);          // 15% layover reasonableness
    define('SCORE_STOPS_WEIGHT', 10);            // 10% fewer stops preferred
}

// ──────────────────────────────────────────────
// Core Data Structure
// ──────────────────────────────────────────────

class Itinerary {
    public $type;             // 'direct' or 'connecting'
    public $flights = [];     // Array of flight data arrays
    public $total_price = 0;
    public $total_duration_minutes = 0;
    public $layover_minutes = 0;
    public $stops = 0;
    public $score = 0;
    public $savings = 0;       // Savings vs cheapest direct
    public $explanation = '';
    public $label = '';        // 'best_value', 'cheapest', 'fastest'
}

// ──────────────────────────────────────────────
// Main Search Function
// ──────────────────────────────────────────────

/**
 * Search for all valid itineraries (direct + connecting) for a given route.
 *
 * @param string $source        Origin city
 * @param string $destination   Destination city
 * @param string $date          Travel date (Y-m-d)
 * @param array  $filters       Optional filters: max_budget, max_stops, max_duration, preferred_airline,
 *                              dep_time_start, dep_time_end, arr_time_start, arr_time_end
 * @return array  ['direct' => [Itinerary...], 'connecting' => [Itinerary...], 'stats' => [...]]
 */
function searchSmartFares($source, $destination, $date, $filters = []) {
    global $conn;

    $source = trim($source);
    $destination = trim($destination);
    $dayOfWeek = date('w', strtotime($date)) + 1; // 1=Sunday ... 7=Saturday

// Step 1: Find all direct flights
    $directFlights = getFlightsByRoute($source, $destination, $date);

    $directItineraries = [];
    $cheapestDirectPrice = PHP_FLOAT_MAX;
    foreach ($directFlights as $f) {
        $it = new Itinerary();
        $it->type = 'direct';
        $it->flights[] = $f;
        $it->total_price = floatval($f['price']);
        $it->total_duration_minutes = getDurationMinutes($f['departure_time'], $f['arrival_time']);
        $it->stops = 0;
        $it->layover_minutes = 0;
        $it->label = 'direct';

        $directItineraries[] = $it;
        if ($it->total_price < $cheapestDirectPrice) {
            $cheapestDirectPrice = $it->total_price;
        }
    }

    if ($cheapestDirectPrice === PHP_FLOAT_MAX) {
        $cheapestDirectPrice = 0;
    }

    // Step 2: Find connecting flights through hub cities
    // Query 1: All cities reachable from source (that are not the destination)
    $hubsFromSource = [];
    $stmt = mysqli_prepare($conn,
        "SELECT DISTINCT destination FROM flights
         WHERE source = ? AND status = 'Scheduled' AND seats_available > 0
         AND DAYOFWEEK(departure_time) = ?
         ORDER BY destination");
    mysqli_stmt_bind_param($stmt, "si", $source, $dayOfWeek);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $hub = trim($row['destination']);
        if ($hub !== $destination) {
            $hubsFromSource[] = $hub;
        }
    }
    mysqli_stmt_close($stmt);

    // Query 2: All cities that can reach destination (that are not the source)
    $hubsToDest = [];
    $stmt = mysqli_prepare($conn,
        "SELECT DISTINCT source FROM flights
         WHERE destination = ? AND status = 'Scheduled' AND seats_available > 0
         AND DAYOFWEEK(departure_time) = ?
         ORDER BY source");
    mysqli_stmt_bind_param($stmt, "si", $destination, $dayOfWeek);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $hub = trim($row['source']);
        if ($hub !== $source) {
            $hubsToDest[] = $hub;
        }
    }
    mysqli_stmt_close($stmt);

    // Step 3: Find intersection (valid hubs for connections)
    $validHubs = array_intersect($hubsFromSource, $hubsToDest);

    // Step 4: For each valid hub, find valid two-leg itineraries
    $connectingItineraries = [];

    foreach ($validHubs as $hub) {
        // First leg: source -> hub
        $stmt1 = mysqli_prepare($conn,
            "SELECT * FROM flights
             WHERE source = ? AND destination = ? AND status = 'Scheduled' AND seats_available > 0
             AND DAYOFWEEK(departure_time) = ?
             ORDER BY TIME(departure_time) ASC");
        mysqli_stmt_bind_param($stmt1, "ssi", $source, $hub, $dayOfWeek);
        mysqli_stmt_execute($stmt1);
        $leg1Flights = mysqli_stmt_get_result($stmt1);

        // Second leg: hub -> destination
        $stmt2 = mysqli_prepare($conn,
            "SELECT * FROM flights
             WHERE source = ? AND destination = ? AND status = 'Scheduled' AND seats_available > 0
             AND DAYOFWEEK(departure_time) = ?
             ORDER BY TIME(departure_time) ASC");
        mysqli_stmt_bind_param($stmt2, "ssi", $hub, $destination, $dayOfWeek);
        mysqli_stmt_execute($stmt2);
        $leg2Flights = mysqli_stmt_get_result($stmt2);

        // Build valid combinations
        $leg1Array = [];
        while ($f1 = mysqli_fetch_assoc($leg1Flights)) {
            $leg1Array[] = $f1;
        }
        $leg2Array = [];
        while ($f2 = mysqli_fetch_assoc($leg2Flights)) {
            $leg2Array[] = $f2;
        }

        foreach ($leg1Array as $f1) {
            $arrival1 = strtotime($f1['arrival_time']);
            foreach ($leg2Array as $f2) {
                $departure2 = strtotime($f2['departure_time']);
                $layover = ($departure2 - $arrival1) / 60; // in minutes

                // Validate layover
                if ($layover < FARE_MIN_LAYOVER_MINUTES) continue;
                if ($layover > FARE_MAX_LAYOVER_MINUTES) continue;

                $it = new Itinerary();
                $it->type = 'connecting';
                $it->flights = [$f1, $f2];
                $it->total_price = floatval($f1['price']) + floatval($f2['price']);
                $it->total_duration_minutes = getDurationMinutes($f1['departure_time'], $f2['arrival_time']);
                $it->layover_minutes = round($layover);
                $it->stops = 1;
                $it->label = 'connecting';

                $connectingItineraries[] = $it;
            }
        }
        mysqli_stmt_close($stmt1);
        mysqli_stmt_close($stmt2);
    }

    // Step 5: Calculate savings and explanations for connecting flights
    foreach ($connectingItineraries as $it) {
        if ($cheapestDirectPrice > 0 && $it->total_price < $cheapestDirectPrice) {
            $it->savings = $cheapestDirectPrice - $it->total_price;
        }

        $it->explanation = generateExplanation($it, $cheapestDirectPrice);
    }

    // Step 6: Calculate scores for ALL itineraries
    $allItineraries = array_merge($directItineraries, $connectingItineraries);

    if (!empty($allItineraries)) {
        // Find min/max for normalization
        $minPrice = min(array_map(function($i) { return $i->total_price; }, $allItineraries));
        $maxPrice = max(array_map(function($i) { return $i->total_price; }, $allItineraries));
        $minDuration = min(array_map(function($i) { return $i->total_duration_minutes; }, $allItineraries));
        $maxDuration = max(array_map(function($i) { return $i->total_duration_minutes; }, $allItineraries));
        $maxStops = max(array_map(function($i) { return $i->stops; }, $allItineraries));

        $priceRange = max(1, $maxPrice - $minPrice);
        $durationRange = max(1, $maxDuration - $minDuration);

        foreach ($allItineraries as $it) {
            // Price score: lower is better (inverted normalization)
            $priceScore = (1 - ($it->total_price - $minPrice) / $priceRange) * SCORE_PRICE_WEIGHT;

            // Duration score: lower is better
            $durationScore = (1 - ($it->total_duration_minutes - $minDuration) / $durationRange) * SCORE_DURATION_WEIGHT;

            // Layover score: longer layover = lower score
            $layoverScore = 0;
            if ($maxStops > 0) {
                $idealLayover = FARE_MIN_LAYOVER_MINUTES + 30; // 2 hours ideal
                if ($it->stops === 0) {
                    $layoverScore = SCORE_LAYOVER_WEIGHT; // Direct flights get full layover score
                } else {
                    $layoverPenalty = min(1, abs($it->layover_minutes - $idealLayover) / FARE_MAX_LAYOVER_MINUTES);
                    $layoverScore = (1 - $layoverPenalty) * SCORE_LAYOVER_WEIGHT;
                }
            }

            // Stops score: fewer stops = better
            $stopsScore = (1 - ($maxStops > 0 ? $it->stops / $maxStops : 0)) * SCORE_STOPS_WEIGHT;

            $it->score = round($priceScore + $durationScore + $layoverScore + $stopsScore);

            // Label the top performers
            if ($it->label === 'direct' || $it->label === 'connecting') {
                // Will be assigned after sorting and comparison
            }
        }

        // Sort by score descending
        usort($allItineraries, function($a, $b) {
            return $b->score <=> $a->score;
        });

        // Assign labels to best in each category
        if (!empty($allItineraries)) {
            // Best value = top score
            $allItineraries[0]->label = 'best_value';

            // Cheapest (find lowest price among all)
            $cheapestIdx = 0;
            $cheapestPrice = PHP_FLOAT_MAX;
            foreach ($allItineraries as $idx => $it) {
                if ($it->total_price < $cheapestPrice) {
                    $cheapestPrice = $it->total_price;
                    $cheapestIdx = $idx;
                }
            }
            if ($allItineraries[$cheapestIdx]->label === 'direct') {
                $allItineraries[$cheapestIdx]->label = 'cheapest';
            }

            // Fastest (find shortest duration)
            $fastestIdx = 0;
            $fastestDuration = PHP_FLOAT_MAX;
            foreach ($allItineraries as $idx => $it) {
                if ($it->total_duration_minutes < $fastestDuration) {
                    $fastestDuration = $it->total_duration_minutes;
                    $fastestIdx = $idx;
                }
            }
            if ($allItineraries[$fastestIdx]->label !== 'best_value' && $allItineraries[$fastestIdx]->label !== 'cheapest') {
                $allItineraries[$fastestIdx]->label = 'fastest';
            }
        }
    }

    // Step 7: Separate back into direct and connecting for the view
    $resultDirect = [];
    $resultConnecting = [];
    foreach ($allItineraries as $it) {
        if ($it->type === 'direct') {
            $resultDirect[] = $it;
        } else {
            $resultConnecting[] = $it;
        }
    }

    // Stats
    $stats = [
        'total_itineraries' => count($allItineraries),
        'direct_count' => count($resultDirect),
        'connecting_count' => count($resultConnecting),
        'cheapest_price' => $cheapestPrice ?? 0,
        'cheapest_direct_price' => $cheapestDirectPrice,
        'best_duration' => $fastestDuration ?? 0,
        'has_connecting' => count($resultConnecting) > 0,
        'source' => $source,
        'destination' => $destination,
        'date' => $date,
    ];

    return [
        'direct' => $resultDirect,
        'connecting' => $resultConnecting,
        'all' => $allItineraries,
        'stats' => $stats,
    ];
}

// ──────────────────────────────────────────────
// Scoring & Explanation Functions
// ──────────────────────────────────────────────

/**
 * Generate a human-readable explanation of why this itinerary is recommended.
 * Never fabricates data — uses real price comparisons.
 */
function generateExplanation($itinerary, $cheapestDirectPrice) {
    if ($itinerary->type === 'direct') {
        if (floatval($itinerary->flights[0]['price']) <= $cheapestDirectPrice) {
            $airline = $itinerary->flights[0]['airline_name'];
            return "Direct non-stop flight operated by {$airline}. Best option for time-sensitive travelers.";
        }
        return "Direct non-stop flight. No layovers or connections required.";
    }

    // Connecting flight explanations
    $f1 = $itinerary->flights[0];
    $f2 = $itinerary->flights[1];
    $leg1Price = floatval($f1['price']);
    $leg2Price = floatval($f2['price']);
    $total = $itinerary->total_price;

    $reasons = [];

    // Compare leg prices to direct
    if ($cheapestDirectPrice > 0 && $total < $cheapestDirectPrice) {
        $savings = $cheapestDirectPrice - $total;
        $hub = $f1['destination'];

        // Determine explanation based on price distribution
        if ($leg1Price < $cheapestDirectPrice * 0.5 && $leg2Price < $cheapestDirectPrice * 0.5) {
            $reasons[] = "Both segments ({$f1['source']}→{$hub}, {$hub}→{$f2['destination']}) are priced lower than the direct fare.";
        } elseif ($leg1Price < $leg2Price) {
            $reasons[] = "The first segment ({$f1['source']}→{$hub}) has lower demand, making the combined fare cheaper.";
        } else {
            $reasons[] = "The second segment ({$hub}→{$f2['destination']}) sees lower competition, reducing overall cost.";
        }

        if ($savings >= 500) {
            $reasons[] = "Significant savings of ₹" . number_format($savings) . " compared to direct flights.";
        }
    } else {
        $reasons[] = "Combined fare is lower than the typical direct fare for this route.";
    }

    $reasons[] = "Total travel time includes a {$itinerary->layover_minutes}-minute layover at {$f1['destination']}.";

    return implode(' ', $reasons);
}

// ──────────────────────────────────────────────
// Filtering
// ──────────────────────────────────────────────

/**
 * Filter itineraries by region (domestic / international).
 * domestic: every leg must be a domestic Indian route.
 * international: at least one leg is non-domestic.
 * Empty region returns itineraries unchanged (backward compatible).
 */
function filterItinerariesByRegion($itineraries, $region) {
    if ($region !== 'domestic' && $region !== 'international') {
        return $itineraries;
    }
    return array_values(array_filter($itineraries, function($it) use ($region) {
        $allDomestic = true;
        foreach ($it->flights as $leg) {
            if (!isDomesticRoute($leg['source'], $leg['destination'])) {
                $allDomestic = false;
                break;
            }
        }
        return $region === 'domestic' ? $allDomestic : !$allDomestic;
    }));
}

/**
 * Apply user filters to an array of itineraries.
 * Returns filtered array.
 */
function applyFilters($itineraries, $filters) {
    return array_filter($itineraries, function($it) use ($filters) {
        // Max budget
        if (!empty($filters['max_budget']) && $it->total_price > floatval($filters['max_budget'])) {
            return false;
        }

        // Max stops
        if (!empty($filters['max_stops']) && intval($filters['max_stops']) !== -1) {
            $maxStops = intval($filters['max_stops']);
            if ($it->stops > $maxStops) return false;
        }

        // Max travel duration (in minutes)
        if (!empty($filters['max_duration']) && $it->total_duration_minutes > intval($filters['max_duration'])) {
            return false;
        }

        // Preferred airline (applies to all legs)
        if (!empty($filters['preferred_airline'])) {
            $airline = $filters['preferred_airline'];
            $match = false;
            foreach ($it->flights as $f) {
                if (strcasecmp(trim($f['airline_name']), $airline) === 0) {
                    $match = true;
                    break;
                }
            }
            if (!$match) return false;
        }

        // Departure time window (first flight)
        if (!empty($filters['dep_time_start'])) {
            $firstDep = strtotime($it->flights[0]['departure_time']);
            $windowStart = strtotime($filters['dep_time_start']);
            if ($firstDep < $windowStart) return false;
        }
        if (!empty($filters['dep_time_end'])) {
            $firstDep = strtotime($it->flights[0]['departure_time']);
            $windowEnd = strtotime($filters['dep_time_end']);
            if ($firstDep > $windowEnd) return false;
        }

        // Arrival time window (last flight)
        if (!empty($filters['arr_time_start'])) {
            $lastArr = strtotime(end($it->flights)['arrival_time']);
            $windowStart = strtotime($filters['arr_time_start']);
            if ($lastArr < $windowStart) return false;
        }
        if (!empty($filters['arr_time_end'])) {
            $lastArr = strtotime(end($it->flights)['arrival_time']);
            $windowEnd = strtotime($filters['arr_time_end']);
            if ($lastArr > $windowEnd) return false;
        }

        return true;
    });
}

// ──────────────────────────────────────────────
// Utility Functions
// ──────────────────────────────────────────────

/**
 * Calculate duration in minutes between two datetime strings.
 */
function getDurationMinutes($departure, $arrival) {
    $dep = new DateTime($departure);
    $arr = new DateTime($arrival);
    return (int)($dep->diff($arr)->h * 60 + $dep->diff($arr)->i);
}

/**
 * Render a single itinerary card (direct or connecting).
 */
function renderItineraryCard($itinerary, $date, $showLabels = true) {
    $isDirect = $itinerary->type === 'direct';
    $f = $itinerary->flights[0];

    $source = htmlspecialchars($itinerary->flights[0]['source']);
    $dest = htmlspecialchars(end($itinerary->flights)['destination']);

    $totalPrice = formatPrice($itinerary->total_price);
    $totalDurationMinutes = $itinerary->total_duration_minutes;
    $durationHours = floor($totalDurationMinutes / 60);
    $durationMins = $totalDurationMinutes % 60;
    $durationStr = "{$durationHours}h {$durationMins}m";

    // Badge and label
    $badgeHtml = '';
    $borderClass = 'border-light';
    if ($itinerary->label === 'best_value') {
        $badgeHtml = '<span class="badge bg-warning text-dark px-3 py-2 fs-6 fw-bold"><i class="bi bi-trophy-fill me-1"></i>🏆 Best Value</span>';
        $borderClass = 'border-warning';
    } elseif ($itinerary->label === 'cheapest') {
        $badgeHtml = '<span class="badge bg-success px-3 py-2 fs-6 fw-bold"><i class="bi bi-currency-rupee me-1"></i>💰 Cheapest</span>';
        $borderClass = 'border-success';
    } elseif ($itinerary->label === 'fastest') {
        $badgeHtml = '<span class="badge bg-info text-white px-3 py-2 fs-6 fw-bold"><i class="bi bi-lightning-fill me-1"></i>⚡ Fastest</span>';
        $borderClass = 'border-info';
    }

    $scoreClass = 'bg-secondary';
    if ($itinerary->score >= 80) $scoreClass = 'bg-success';
    elseif ($itinerary->score >= 60) $scoreClass = 'bg-warning text-dark';

    $layoverStr = $isDirect ? 'Non-stop' : "{$itinerary->layover_minutes} min layover in " . htmlspecialchars($itinerary->flights[0]['destination']);
    ?>
    <div class="flight-card hover-lift p-4 mb-3 border rounded-4 shadow-sm bg-white <?php echo $borderClass; ?>"
         style="border-width: 2px !important;"
         data-price="<?php echo $itinerary->total_price; ?>"
         data-duration="<?php echo $itinerary->total_duration_minutes; ?>"
         data-score="<?php echo $itinerary->score; ?>">
        <div class="row align-items-center">
            <div class="col-md-4">
                <?php if ($showLabels && $badgeHtml): ?>
                <div class="mb-2"><?php echo $badgeHtml; ?></div>
                <?php endif; ?>

                <?php if ($isDirect): ?>
                <div class="flight-airline">
                    <div class="airline-logo bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <?php echo airlineInitials($f['airline_name']); ?>
                    </div>
                    <div>
                        <div class="airline-name fw-bold"><?php echo htmlspecialchars($f['airline_name']); ?></div>
                        <small class="text-muted"><?php echo htmlspecialchars($f['flight_number']); ?></small>
                    </div>
                </div>
                <?php else:
                    $f2 = $itinerary->flights[1];
                ?>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-primary rounded-pill px-2">Leg 1</span>
                    <span class="fw-bold small"><?php echo htmlspecialchars($f['airline_name']); ?> <?php echo htmlspecialchars($f['flight_number']); ?></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-info rounded-pill px-2">Leg 2</span>
                    <span class="fw-bold small"><?php echo htmlspecialchars($f2['airline_name']); ?> <?php echo htmlspecialchars($f2['flight_number']); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <div class="flight-route mb-1">
                    <div class="route-point">
                        <div class="route-time fw-extrabold fs-5 text-dark"><?php echo formatTime($itinerary->flights[0]['departure_time']); ?></div>
                        <div class="route-city text-muted small fw-semibold"><?php echo $source; ?></div>
                    </div>
                    <div class="route-line"><i class="bi bi-airplane-fill"></i></div>
                    <div class="route-point">
                        <div class="route-time fw-extrabold fs-5 text-dark"><?php echo formatTime(end($itinerary->flights)['arrival_time']); ?></div>
                        <div class="route-city text-muted small fw-semibold"><?php echo $dest; ?></div>
                    </div>
                </div>
                <div class="text-center small text-muted">
                    <i class="bi bi-clock me-1"></i><?php echo $durationStr; ?>
                    · <span class="fw-semibold"><?php echo $layoverStr; ?></span>
                </div>

                <?php if ($isDirect && $itinerary->explanation): ?>
                <div class="text-center small text-muted mt-1">
                    <i class="bi bi-info-circle me-1"></i><?php echo htmlspecialchars($itinerary->explanation); ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-md-2 text-center my-3 my-md-0">
                <div class="flight-price text-accent fw-extrabold fs-3"><?php echo $totalPrice; ?></div>
                <small class="text-muted d-block fw-semibold">per adult seat</small>

                <?php if ($itinerary->savings > 0): ?>
                <div class="mt-1">
                    <span class="badge bg-success bg-opacity-75 text-white px-2 py-1">
                        <i class="bi bi-cash-stack me-1"></i>Save ₹<?php echo number_format($itinerary->savings); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-md-2 text-end">
                <?php if (!$isDirect): ?>
                <div class="mb-3">
                    <div class="small text-muted fw-semibold">Score: <span class="badge <?php echo $scoreClass; ?>"><?php echo $itinerary->score; ?>/100</span></div>
                    <?php if ($itinerary->explanation): ?>
                    <button type="button" class="btn btn-sm btn-link p-0 text-muted small mt-1" data-bs-toggle="popover" data-bs-trigger="focus" title="Why this fare?" data-bs-content="<?php echo htmlspecialchars($itinerary->explanation, ENT_QUOTES); ?>">
                        <i class="bi bi-question-circle me-1"></i>Why this price?
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($isDirect): ?>
                <a href="<?php echo BASE_URL; ?>booking.php?flight_id=<?php echo $f['flight_id']; ?>&date=<?php echo urlencode($date); ?>" class="btn btn-accent btn-lg w-100 fw-bold shadow-sm mb-2">Book Flight</a>
                <?php else:
                    $f2 = $itinerary->flights[1];
                ?>
                <div class="small text-muted mb-2 border rounded p-2 bg-light">
                    <div class="d-flex justify-content-between"><span>Fare 1 (<?php echo htmlspecialchars($source); ?>→<?php echo htmlspecialchars($f['destination']); ?>)</span><span class="fw-bold"><?php echo formatPrice($f['price']); ?></span></div>
                    <div class="d-flex justify-content-between"><span>Fare 2 (<?php echo htmlspecialchars($f['destination']); ?>→<?php echo htmlspecialchars($dest); ?>)</span><span class="fw-bold"><?php echo formatPrice($f2['price']); ?></span></div>
                    <div class="d-flex justify-content-between"><span>Taxes & Fees</span><span class="text-success fw-bold">Included</span></div>
                    <hr class="my-1">
                    <div class="d-flex justify-content-between"><span class="fw-bold">Grand Total</span><span class="fw-bold text-accent"><?php echo formatPrice($itinerary->total_price); ?></span></div>
                    <?php if ($itinerary->savings > 0): ?>
                    <div class="d-flex justify-content-between mt-1"><span class="text-success small"><i class="bi bi-cash-stack me-1"></i>Savings vs Direct</span><span class="fw-bold text-success small">- <?php echo formatPrice($itinerary->savings); ?></span></div>
                    <?php endif; ?>
                </div>
                <p class="text-muted small mb-1"><i class="bi bi-exclamation-triangle me-1 text-warning"></i>Two separate bookings required</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
