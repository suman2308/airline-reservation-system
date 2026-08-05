<?php
$pageTitle = 'Smart Fare Results';
require_once 'includes/header.php';
require_once 'includes/helpers.php';
require_once 'includes/FareEngine.php';

$source = trim($_GET['source'] ?? '');
$destination = trim($_GET['destination'] ?? '');
$travel_date = trim($_GET['travel_date'] ?? date('Y-m-d'));
$mode = trim($_GET['mode'] ?? 'smart'); // 'smart' or 'direct'
$region = trim($_GET['region'] ?? '');
// Build reusable query strings so links omit the param when region is empty (backward compatible)
$regionQuery = $region !== '' ? '?region=' . urlencode($region) : '';   // first/standalone param position
$regionAmp   = $region !== '' ? '&region=' . urlencode($region) : '';   // appended after existing params

if (empty($source) || empty($destination)) {
    setFlash('error', 'Please select origin and destination.');
    redirect('search-flights.php' . $regionQuery);
}
if ($source === $destination) {
    setFlash('error', 'Origin and destination cannot be the same.');
    redirect('search-flights.php' . $regionQuery);
}

$display_date = !empty($travel_date) ? $travel_date : date('Y-m-d');
$display_day = date('l', strtotime($display_date));

// Parse filters
$filters = [];
if ($mode === 'direct') {
    $filters['max_stops'] = 0;
}
if (!empty($_GET['max_budget'])) $filters['max_budget'] = floatval($_GET['max_budget']);
if (!empty($_GET['max_stops']) && intval($_GET['max_stops']) >= 0) $filters['max_stops'] = intval($_GET['max_stops']);
if (!empty($_GET['max_duration'])) $filters['max_duration'] = intval($_GET['max_duration']) * 60; // hours to minutes
if (!empty($_GET['airline'])) $filters['preferred_airline'] = $_GET['airline'];
if (!empty($_GET['dep_start'])) $filters['dep_time_start'] = $_GET['dep_start'] . ':00';
if (!empty($_GET['dep_end'])) $filters['dep_time_end'] = $_GET['dep_end'] . ':00';
if (!empty($_GET['arr_start'])) $filters['arr_time_start'] = $_GET['arr_start'] . ':00';
if (!empty($_GET['arr_end'])) $filters['arr_time_end'] = $_GET['arr_end'] . ':00';

// Run the smart search
$result = searchSmartFares($source, $destination, $display_date, $filters);
$stats = $result['stats'];
$directItineraries = $result['direct'];
$connectingItineraries = $result['connecting'];

// Apply region filter (domestic = every leg domestic; international = at least one leg non-domestic)
$directItineraries = filterItinerariesByRegion($directItineraries, $region);
$connectingItineraries = filterItinerariesByRegion($connectingItineraries, $region);

// Apply filters (already applied in search, but filter again for UI)
if (!empty($filters)) {
    $directItineraries = applyFilters($directItineraries, $filters);
    $connectingItineraries = applyFilters($connectingItineraries, $filters);
}
?>

<div class="page-hero-lite">
    <div class="container">
        <span class="kicker kicker-accent"><i class="bi bi-graph-up-arrow me-1"></i> Smart Fare Engine</span>
        <h1><?php echo htmlspecialchars("$source → $destination"); ?></h1>
        <p><i class="bi bi-calendar-event me-1"></i><?php echo formatDate($display_date) . ' (' . $display_day . ')'; ?></p>
        <div class="mt-3">
            <a href="<?php echo BASE_URL; ?>search-flights.php<?php echo $regionQuery; ?>" class="btn btn-outline-accent btn-sm fw-bold rounded-pill px-3">
                <i class="bi bi-sliders me-1"></i>Modify Search
            </a>
        </div>
    </div>
</div>

<div class="container py-5">
    <?php showAlert(); ?>

    <?php if ($stats['has_connecting']): ?>
    <!-- Fare Comparison Panel -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body text-center p-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 mb-2" style="width: 40px; height: 40px;">
                        <i class="bi bi-currency-rupee text-warning fw-bold"></i>
                    </div>
                    <div class="small text-muted text-uppercase fw-bold tracking-wider">Cheapest</div>
                    <div class="fw-bold fs-5 text-dark mt-1">
                        <?php
                        $cheapest = null;
                        foreach (array_merge($directItineraries, $connectingItineraries) as $it) {
                            if ($cheapest === null || $it->total_price < $cheapest->total_price) $cheapest = $it;
                        }
                        echo $cheapest ? formatPrice($cheapest->total_price) : 'N/A';
                        ?>
                    </div>
                    <small class="text-muted">
                        <?php if ($cheapest && $cheapest->type === 'connecting'): ?>
                        <span class="text-success"><i class="bi bi-arrow-up me-1"></i>1 stop</span>
                        <?php else: ?>
                        Non-stop
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body text-center p-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-info bg-opacity-10 mb-2" style="width: 40px; height: 40px;">
                        <i class="bi bi-lightning-fill text-info"></i>
                    </div>
                    <div class="small text-muted text-uppercase fw-bold tracking-wider">Fastest</div>
                    <div class="fw-bold fs-5 text-dark mt-1">
                        <?php
                        $fastest = null;
                        foreach (array_merge($directItineraries, $connectingItineraries) as $it) {
                            if ($fastest === null || $it->total_duration_minutes < $fastest->total_duration_minutes) $fastest = $it;
                        }
                        echo $fastest ? floor($fastest->total_duration_minutes / 60) . 'h ' . ($fastest->total_duration_minutes % 60) . 'm' : 'N/A';
                        ?>
                    </div>
                    <small class="text-muted">
                        <?php if ($fastest && $fastest->type === 'connecting'): ?>
                        <span class="text-info"><i class="bi bi-arrow-up me-1"></i>1 stop</span>
                        <?php else: ?>
                        Non-stop
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-2 border-warning">
                <div class="card-body text-center p-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 mb-2" style="width: 40px; height: 40px;">
                        <i class="bi bi-trophy-fill text-warning"></i>
                    </div>
                    <div class="small text-muted text-uppercase fw-bold tracking-wider">Best Value</div>
                    <div class="fw-bold fs-5 text-warning mt-1">
                        <?php
                        $bestValue = !empty(array_merge($directItineraries, $connectingItineraries)) ? array_merge($directItineraries, $connectingItineraries)[0] : null;
                        echo $bestValue ? formatPrice($bestValue->total_price) : 'N/A';
                        ?>
                    </div>
                    <small class="text-muted">Score <?php echo $bestValue ? $bestValue->score : 0; ?>/100</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body text-center p-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 mb-2" style="width: 40px; height: 40px;">
                        <i class="bi bi-clock text-success"></i>
                    </div>
                    <div class="small text-muted text-uppercase fw-bold tracking-wider">Shortest Layover</div>
                    <div class="fw-bold fs-5 text-dark mt-1">
                        <?php
                        $lowestLayover = null;
                        foreach ($connectingItineraries as $it) {
                            if ($lowestLayover === null || $it->layover_minutes < $lowestLayover->layover_minutes) $lowestLayover = $it;
                        }
                        echo $lowestLayover ? $lowestLayover->layover_minutes . ' min' : 'N/A';
                        ?>
                    </div>
                    <small class="text-muted">
                        <?php if ($lowestLayover): ?>
                        via <?php echo htmlspecialchars($lowestLayover->flights[0]['destination']); ?>
                        <?php else: ?>
                        No connections available
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-2 col-6">
                    <label class="form-label small fw-bold text-muted">Mode</label>
                    <div class="d-flex gap-1">
                        <a href="?source=<?php echo urlencode($source); ?>&destination=<?php echo urlencode($destination); ?>&travel_date=<?php echo urlencode($travel_date); ?>&mode=smart<?php echo $regionAmp; ?>" class="btn btn-sm <?php echo $mode === 'smart' ? 'btn-accent' : 'btn-outline-secondary'; ?> fw-bold flex-fill">Smart</a>
                        <a href="?source=<?php echo urlencode($source); ?>&destination=<?php echo urlencode($destination); ?>&travel_date=<?php echo urlencode($travel_date); ?>&mode=direct<?php echo $regionAmp; ?>" class="btn btn-sm <?php echo $mode === 'direct' ? 'btn-accent' : 'btn-outline-secondary'; ?> fw-bold flex-fill">Direct</a>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small fw-bold text-muted">Max Budget</label>
                    <select name="max_budget" class="form-select form-select-sm" onchange="applyFilter(this, 'max_budget')">
                        <option value="">Any</option>
                        <option value="3000" <?php echo ($_GET['max_budget'] ?? '') == '3000' ? 'selected' : ''; ?>>₹3,000</option>
                        <option value="4000" <?php echo ($_GET['max_budget'] ?? '') == '4000' ? 'selected' : ''; ?>>₹4,000</option>
                        <option value="5000" <?php echo ($_GET['max_budget'] ?? '') == '5000' ? 'selected' : ''; ?>>₹5,000</option>
                        <option value="7000" <?php echo ($_GET['max_budget'] ?? '') == '7000' ? 'selected' : ''; ?>>₹7,000</option>
                        <option value="10000" <?php echo ($_GET['max_budget'] ?? '') == '10000' ? 'selected' : ''; ?>>₹10,000</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small fw-bold text-muted">Max Stops</label>
                    <select name="max_stops" class="form-select form-select-sm" onchange="applyFilter(this, 'max_stops')">
                        <option value="-1">Any</option>
                        <option value="0" <?php echo ($_GET['max_stops'] ?? '') == '0' ? 'selected' : ''; ?>>Non-stop only</option>
                        <option value="1" <?php echo ($_GET['max_stops'] ?? '') == '1' ? 'selected' : ''; ?>>Max 1 stop</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small fw-bold text-muted">Max Duration</label>
                    <select name="max_duration" class="form-select form-select-sm" onchange="applyFilter(this, 'max_duration')">
                        <option value="">Any</option>
                        <option value="3" <?php echo ($_GET['max_duration'] ?? '') == '3' ? 'selected' : ''; ?>>Up to 3h</option>
                        <option value="5" <?php echo ($_GET['max_duration'] ?? '') == '5' ? 'selected' : ''; ?>>Up to 5h</option>
                        <option value="8" <?php echo ($_GET['max_duration'] ?? '') == '8' ? 'selected' : ''; ?>>Up to 8h</option>
                        <option value="12" <?php echo ($_GET['max_duration'] ?? '') == '12' ? 'selected' : ''; ?>>Up to 12h</option>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small fw-bold text-muted">Airline</label>
                    <select name="airline" class="form-select form-select-sm" onchange="applyFilter(this, 'airline')">
                        <option value="">All Airlines</option>
                        <?php
                        $airlines = ['IndiGo', 'Air India', 'SpiceJet', 'Vistara', 'Go First'];
                        foreach ($airlines as $a) {
                            $sel = ($_GET['airline'] ?? '') === $a ? 'selected' : '';
                            echo "<option value=\"$a\" $sel>$a</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 col-12">
                    <label class="form-label small fw-bold text-muted">Departure Time</label>
                    <div class="d-flex gap-1">
                        <select name="dep_start" class="form-select form-select-sm" onchange="applyFilter(this, 'dep_start')">
                            <option value="">From</option>
                            <option value="00:00" <?php echo ($_GET['dep_start'] ?? '') == '00:00' ? 'selected' : ''; ?>>Midnight</option>
                            <option value="06:00" <?php echo ($_GET['dep_start'] ?? '') == '06:00' ? 'selected' : ''; ?>>6 AM</option>
                            <option value="12:00" <?php echo ($_GET['dep_start'] ?? '') == '12:00' ? 'selected' : ''; ?>>Noon</option>
                            <option value="18:00" <?php echo ($_GET['dep_start'] ?? '') == '18:00' ? 'selected' : ''; ?>>6 PM</option>
                        </select>
                        <select name="dep_end" class="form-select form-select-sm" onchange="applyFilter(this, 'dep_end')">
                            <option value="">To</option>
                            <option value="06:00" <?php echo ($_GET['dep_end'] ?? '') == '06:00' ? 'selected' : ''; ?>>6 AM</option>
                            <option value="12:00" <?php echo ($_GET['dep_end'] ?? '') == '12:00' ? 'selected' : ''; ?>>Noon</option>
                            <option value="18:00" <?php echo ($_GET['dep_end'] ?? '') == '18:00' ? 'selected' : ''; ?>>6 PM</option>
                            <option value="23:59" <?php echo ($_GET['dep_end'] ?? '') == '23:59' ? 'selected' : ''; ?>>End of Day</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <label class="form-label small fw-bold text-muted">Arrival Time</label>
                    <div class="d-flex gap-1">
                        <select name="arr_start" class="form-select form-select-sm" onchange="applyFilter(this, 'arr_start')">
                            <option value="">From</option>
                            <option value="00:00" <?php echo ($_GET['arr_start'] ?? '') == '00:00' ? 'selected' : ''; ?>>Midnight</option>
                            <option value="06:00" <?php echo ($_GET['arr_start'] ?? '') == '06:00' ? 'selected' : ''; ?>>6 AM</option>
                            <option value="12:00" <?php echo ($_GET['arr_start'] ?? '') == '12:00' ? 'selected' : ''; ?>>Noon</option>
                            <option value="18:00" <?php echo ($_GET['arr_start'] ?? '') == '18:00' ? 'selected' : ''; ?>>6 PM</option>
                        </select>
                        <select name="arr_end" class="form-select form-select-sm" onchange="applyFilter(this, 'arr_end')">
                            <option value="">To</option>
                            <option value="06:00" <?php echo ($_GET['arr_end'] ?? '') == '06:00' ? 'selected' : ''; ?>>6 AM</option>
                            <option value="12:00" <?php echo ($_GET['arr_end'] ?? '') == '12:00' ? 'selected' : ''; ?>>Noon</option>
                            <option value="18:00" <?php echo ($_GET['arr_end'] ?? '') == '18:00' ? 'selected' : ''; ?>>6 PM</option>
                            <option value="23:59" <?php echo ($_GET['arr_end'] ?? '') == '23:59' ? 'selected' : ''; ?>>End of Day</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label small fw-bold text-muted">&nbsp;</label>
                    <a href="?source=<?php echo urlencode($source); ?>&destination=<?php echo urlencode($destination); ?>&travel_date=<?php echo urlencode($travel_date); ?><?php echo $regionAmp; ?>" class="btn btn-sm btn-outline-secondary fw-bold w-100">Clear Filters</a>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Initialize Bootstrap popovers for "Why this price?" buttons
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
            new bootstrap.Popover(el);
        });
    });

    function applyFilter(select, name) {
        const url = new URL(window.location.href);
        if (select.value) {
            url.searchParams.set(name, select.value);
        } else {
            url.searchParams.delete(name);
        }
        window.location.href = url.toString();
    }
    </script>

    <?php
    // Merge and sort by score
    $allItineraries = array_merge($directItineraries, $connectingItineraries);
    usort($allItineraries, function($a, $b) { return $b->score <=> $a->score; });

    if (count($allItineraries) > 0):
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted mb-0 fw-semibold">
            <i class="bi bi-check-circle-fill text-success me-1"></i>
            <strong><?php echo count($allItineraries); ?></strong> fare option<?php echo count($allItineraries) > 1 ? 's' : ''; ?> found
            <?php if ($stats['has_connecting']): ?>
            · <span class="text-accent"><strong><?php echo $stats['connecting_count']; ?></strong> connecting</span>
            <?php endif; ?>
        </p>
        <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary" onclick="sortResults('score')">Best Value</button>
            <button class="btn btn-outline-secondary" onclick="sortResults('price')">Cheapest</button>
            <button class="btn btn-outline-secondary" onclick="sortResults('duration')">Fastest</button>
        </div>
    </div>

    <div id="itinerariesContainer">
        <?php foreach ($allItineraries as $itinerary):
            renderItineraryCard($itinerary, $display_date);
        endforeach; ?>
    </div>

    <script>
    function sortResults(by) {
        const container = document.getElementById('itinerariesContainer');
        const cards = Array.from(container.children);
        cards.sort((a, b) => {
            if (by === 'price') {
                return parseFloat(a.dataset.price || 0) - parseFloat(b.dataset.price || 0);
            } else if (by === 'duration') {
                return parseInt(a.dataset.duration || 0) - parseInt(b.dataset.duration || 0);
            } else {
                return parseInt(b.dataset.score || 0) - parseInt(a.dataset.score || 0);
            }
        });
        cards.forEach(c => container.appendChild(c));
    }

    // Add data attributes to cards for sorting
    document.querySelectorAll('#itinerariesContainer .flight-card').forEach(card => {
        // Extract price, duration, score from the content
        const priceText = card.querySelector('.flight-price')?.textContent?.replace(/[₹,]/g, '') || '0';
        card.dataset.price = priceText;

        const durationText = card.querySelector('.text-center.small.text-muted')?.textContent || '0h 0m';
        const match = durationText.match(/(\d+)h\s*(\d+)?m?/);
        card.dataset.duration = match ? (parseInt(match[1]) * 60 + parseInt(match[2] || 0)) : 0;

        const scoreText = card.querySelector('.badge.bg-secondary, .badge.bg-success, .badge.bg-warning')?.textContent || '0';
        card.dataset.score = parseInt(scoreText) || 0;
    });
    </script>

    <?php else: ?>
    <div class="empty-state bg-white rounded-4 shadow-sm border p-5 text-center">
        <i class="bi bi-emoji-frown text-muted display-1 mb-3 d-block"></i>
        <h4 class="fw-bold">No Flight Options Found</h4>
        <p class="text-muted mb-2">We couldn't find any flights matching your search criteria for <strong><?php echo htmlspecialchars("$source → $destination"); ?></strong> on <strong><?php echo formatDate($display_date); ?></strong>.</p>
        <?php if (!empty($filters)): ?>
        <p class="text-muted mb-3"><i class="bi bi-funnel me-1"></i>Try clearing some filters for more results.</p>
        <?php endif; ?>
        <div class="d-flex gap-2 justify-content-center">
            <a href="<?php echo BASE_URL; ?>search-flights.php<?php echo $regionQuery; ?>" class="btn btn-accent btn-lg px-4 fw-bold">Try Another Route</a>
            <?php if (!empty($filters)): ?>
            <a href="?source=<?php echo urlencode($source); ?>&destination=<?php echo urlencode($destination); ?>&travel_date=<?php echo urlencode($travel_date); ?><?php echo $regionAmp; ?>" class="btn btn-outline-secondary btn-lg px-4 fw-bold">Clear Filters</a>
            <?php endif; ?>
        </div>

        <!-- Debug: explain why no results -->
        <?php if (!$stats['has_connecting']): ?>
        <div class="mt-4 p-3 bg-light rounded-3 border text-start">
            <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-2 text-accent"></i>Why no connecting options?</h6>
            <p class="small text-muted mb-1">The Smart Fare Engine searches for connecting flights through intermediate cities. For this route on this day, no valid connections were found that meet the minimum layover requirement (90 min) and maximum layover limit (8 hours).</p>
            <p class="small text-muted mb-0">Available hub cities: <?php
                $stmt = mysqli_prepare($conn, "SELECT DISTINCT destination FROM flights WHERE source=? AND status='Scheduled' AND seats_available > 0 AND DAYOFWEEK(departure_time)=DAYOFWEEK(?)");
                mysqli_stmt_bind_param($stmt, "ss", $source, $display_date);
                mysqli_stmt_execute($stmt);
                $hubs = [];
                $res = mysqli_stmt_get_result($stmt);
                while ($r = mysqli_fetch_assoc($res)) $hubs[] = $r['destination'];
                mysqli_stmt_close($stmt);
                echo !empty($hubs) ? implode(', ', $hubs) : 'No hub cities available from ' . htmlspecialchars($source) . ' on this day.';
            ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Algorithm Info -->
    <div class="mt-4 p-3 bg-white rounded-3 border small text-muted">
        <details>
            <summary class="fw-bold text-accent"><i class="bi bi-info-circle me-1"></i> About the Smart Fare Engine</summary>
            <div class="mt-2">
                <p class="mb-1"><strong>How it works:</strong> The engine searches for both direct flights and valid connecting itineraries through hub cities.</p>
                <p class="mb-1"><strong>Scoring formula:</strong> Score = (PriceScore × 50%) + (DurationScore × 25%) + (LayoverScore × 15%) + (StopScore × 10%)</p>
                <p class="mb-1"><strong>Minimum layover:</strong> <?php echo FARE_MIN_LAYOVER_MINUTES; ?> min &middot; <strong>Maximum layover:</strong> <?php echo FARE_MAX_LAYOVER_MINUTES; ?> min &middot; <strong>Max connections:</strong> <?php echo FARE_MAX_CONNECTIONS; ?></p>
                <p class="mb-1"><strong>Algorithm:</strong> Queries distinct destinations from source (1 query) → queries distinct sources to destination (2nd query) → finds intersection (valid hubs) → fetches actual flight pairs through each hub (3rd+ queries). Time complexity: O(H·F₁·F₂) where H ≤ 6 hubs, F₁·F₂ ≤ 64 flight combinations per hub. Effectively constant time for realistic data.</p>
                <p class="mb-0"><strong>All data is deterministic.</strong> Every fare comes from the existing flight database. No fabricated data.</p>
            </div>
        </details>
    </div>
</div>

<style>
.tracking-wider { letter-spacing: 0.5px; }
.popover { max-width: 300px; }
</style>

<?php require_once 'includes/footer.php'; ?>
