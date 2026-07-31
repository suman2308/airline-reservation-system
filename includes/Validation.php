<?php
/**
 * AeroBook – Server-Side Validation
 *
 * All validation functions return an array of error messages.
 * Empty array means validation passed.
 */

// ──────────────────────────────────────────────
// Registration
// ──────────────────────────────────────────────

function validateRegistration($data) {
    $errors = [];
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $password = $data['password'] ?? '';
    $confirm = $data['confirm_password'] ?? '';

    if (empty($name)) {
        $errors[] = 'Full name is required.';
    } elseif (strlen($name) < 3) {
        $errors[] = 'Name must be at least 3 characters.';
    }

    if (empty($email)) {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($phone)) {
        $errors[] = 'Phone number is required.';
    } elseif (!preg_match('/^[6-9]\d{9}$/', $phone)) {
        $errors[] = 'Please enter a valid 10-digit Indian phone number.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    return $errors;
}

// ──────────────────────────────────────────────
// Login
// ──────────────────────────────────────────────

function validateLogin($data) {
    $errors = [];
    if (empty(trim($data['email'] ?? ''))) {
        $errors[] = 'Email is required.';
    }
    if (empty($data['password'] ?? '')) {
        $errors[] = 'Password is required.';
    }
    return $errors;
}

// ──────────────────────────────────────────────
// Admin Login
// ──────────────────────────────────────────────

function validateAdminLogin($data) {
    $errors = [];
    if (empty(trim($data['username'] ?? ''))) {
        $errors[] = 'Username is required.';
    }
    if (empty($data['password'] ?? '')) {
        $errors[] = 'Password is required.';
    }
    return $errors;
}

// ──────────────────────────────────────────────
// Flight Booking
// ──────────────────────────────────────────────

function validateBooking($passengers, $availableSeats, $occupiedSeats) {
    $errors = [];

    if (empty($passengers) || !is_array($passengers)) {
        return ['Please select at least one passenger.'];
    }

    if (count($passengers) > $availableSeats) {
        return ['Selected seat count exceeds available seats on this flight.'];
    }

    $selectedSeats = [];

    foreach ($passengers as $idx => $p) {
        $name = trim($p['name'] ?? '');
        $age = intval($p['age'] ?? 0);
        $gender = trim($p['gender'] ?? '');
        $seat = strtoupper(trim($p['seat'] ?? ''));

        if (empty($name)) {
            $errors[] = 'Please provide the name for Passenger #' . ($idx + 1) . '.';
            continue;
        }
        if ($age < 1 || $age > 120) {
            $errors[] = 'Please provide a valid age (1–120) for Passenger #' . ($idx + 1) . '.';
            continue;
        }
        if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
            $errors[] = 'Please select a valid gender for Passenger #' . ($idx + 1) . '.';
            continue;
        }
        if (!validateSeatNumber($seat)) {
            $errors[] = 'Invalid seat number for Passenger #' . ($idx + 1) . '.';
            continue;
        }
        if (in_array($seat, $occupiedSeats, true)) {
            $errors[] = 'Seat ' . $seat . ' is already booked. Please choose another seat.';
            continue;
        }
        if (in_array($seat, $selectedSeats, true)) {
            $errors[] = 'Duplicate seat selection detected for seat ' . $seat . '.';
            continue;
        }

        $selectedSeats[] = $seat;
    }

    return $errors;
}

// ──────────────────────────────────────────────
// Contact Form
// ──────────────────────────────────────────────

function validateContact($data) {
    $errors = [];
    if (empty(trim($data['name'] ?? ''))) $errors[] = 'Your name is required.';
    if (empty(trim($data['email'] ?? ''))) {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (empty(trim($data['subject'] ?? ''))) $errors[] = 'Subject is required.';
    if (empty(trim($data['message'] ?? ''))) $errors[] = 'Message is required.';
    return $errors;
}

// ──────────────────────────────────────────────
// Profile Update
// ──────────────────────────────────────────────

function validateProfileUpdate($data) {
    $errors = [];
    if (empty(trim($data['name'] ?? ''))) {
        $errors[] = 'Name is required.';
    } elseif (strlen(trim($data['name'])) < 3) {
        $errors[] = 'Name must be at least 3 characters.';
    }
    if (empty(trim($data['phone'] ?? ''))) {
        $errors[] = 'Phone number is required.';
    } elseif (!preg_match('/^[6-9]\d{9}$/', trim($data['phone']))) {
        $errors[] = 'Please enter a valid 10-digit phone number.';
    }
    $password = $data['new_password'] ?? '';
    if (!empty($password) && strlen($password) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    }
    return $errors;
}

// ──────────────────────────────────────────────
// Flight Add/Edit
// ──────────────────────────────────────────────

function validateFlightForm($data) {
    $errors = [];
    if (empty(trim($data['flight_number'] ?? ''))) $errors[] = 'Flight number is required.';
    if (empty(trim($data['airline_name'] ?? ''))) $errors[] = 'Airline name is required.';
    if (empty($data['source'] ?? '')) $errors[] = 'Source city is required.';
    if (empty($data['destination'] ?? '')) $errors[] = 'Destination city is required.';
    if (!empty($data['source']) && !empty($data['destination']) && $data['source'] === $data['destination']) {
        $errors[] = 'Source and destination cannot be the same.';
    }
    if (empty($data['departure_time'] ?? '')) $errors[] = 'Departure time is required.';
    if (empty($data['arrival_time'] ?? '')) $errors[] = 'Arrival time is required.';
    $totalSeats = intval($data['total_seats'] ?? 0);
    if ($totalSeats < 1 || $totalSeats > 500) $errors[] = 'Total seats must be between 1 and 500.';
    $price = floatval($data['price'] ?? 0);
    if ($price <= 0) $errors[] = 'Price must be greater than zero.';
    return $errors;
}

// ──────────────────────────────────────────────
// Payment Validation (simulated)
// ──────────────────────────────────────────────

function validatePayment($data) {
    $errors = [];
    $card = str_replace(' ', '', trim($data['card_number'] ?? ''));
    $cvv = trim($data['cvv'] ?? '');

    if (!preg_match('/^\d{16}$/', $card)) {
        $errors[] = 'Please enter a valid 16-digit card number.';
    }
    if (!preg_match('/^\d{3}$/', $cvv)) {
        $errors[] = 'Please enter a valid 3-digit CVV.';
    }
    return $errors;
}

// ──────────────────────────────────────────────
// Password Reset
// ──────────────────────────────────────────────

function validatePasswordReset($data) {
    $errors = [];
    $password = $data['password'] ?? '';
    $confirm = $data['confirm_password'] ?? '';

    if (empty($password)) {
        $errors[] = 'New password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    return $errors;
}

// ──────────────────────────────────────────────
// Forgot Password (Email)
// ──────────────────────────────────────────────

function validateForgotPassword($data) {
    $errors = [];
    if (empty(trim($data['email'] ?? ''))) {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    return $errors;
}

// ──────────────────────────────────────────────
// Password Change (from profile)
// ──────────────────────────────────────────────

function validatePasswordChange($data) {
    $errors = [];
    if (empty($data['current_password'] ?? '')) {
        $errors[] = 'Current password is required to change your password.';
    }
    $newPass = $data['new_password'] ?? '';
    if (empty($newPass)) {
        $errors[] = 'New password is required.';
    } elseif (strlen($newPass) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    }
    if (($data['new_password'] ?? '') !== ($data['confirm_password'] ?? '')) {
        $errors[] = 'Passwords do not match.';
    }
    return $errors;
}

// ──────────────────────────────────────────────
// Server-Side Pricing & Add-On Validation
// ──────────────────────────────────────────────

/**
 * Validate add-on pricing against known rates.
 * Prevents manipulated add-on costs from being submitted.
 */
function validateAddonCosts($baggageCost, $mealCost) {
    $errors = [];
    $allowedBaggage = [0, 800, 1400];
    $allowedMeals = [0, 350, 450];

    if (!in_array($baggageCost, $allowedBaggage, true)) {
        $errors[] = 'Invalid baggage add-on amount.';
    }
    if (!in_array($mealCost, $allowedMeals, true)) {
        $errors[] = 'Invalid meal add-on amount.';
    }
    return $errors;
}

/**
 * Validate promo code and return the discount amount.
 * Returns ['valid' => true, 'discount' => 500] or ['valid' => false].
 */
function validatePromoCode($code) {
    $promos = [
        'AERO10' => ['type' => 'percent', 'value' => 10],
        'WELCOME' => ['type' => 'fixed', 'value' => 300],
        'FLY2026' => ['type' => 'fixed', 'value' => 500],
    ];

    if (empty($code)) {
        return ['valid' => true, 'discount' => 0, 'label' => ''];
    }

    $code = strtoupper(trim($code));
    if (!isset($promos[$code])) {
        return ['valid' => false, 'discount' => 0, 'label' => ''];
    }

    $promo = $promos[$code];
    return [
        'valid' => true,
        'discount' => $promo['value'],
        'type' => $promo['type'],
        'label' => $code,
    ];
}

/**
 * Validate that seat rows correspond to the correct class (business vs economy).
 * Business class = rows 1-2, Economy = rows 3+.
 */
function validateSeatClass($seatNumber) {
    $row = intval(preg_replace('/[^0-9]/', '', $seatNumber));
    if ($row <= 2) return 'business';
    return 'economy';
}

/**
 * Validate arrival time is after departure time.
 */
function validateFlightTimes($departure, $arrival) {
    $dep = strtotime($departure);
    $arr = strtotime($arrival);
    if ($dep === false || $arr === false) {
        return false;
    }
    return $arr > $dep;
}
