# AeroBook — Airline Reservation & Flight Management Platform

AeroBook is a full-stack airline reservation and flight operations management platform. Engineered with modern PHP, MySQL, Bootstrap 5, and JavaScript, it provides an end-to-end flight booking engine featuring real-time 2D aircraft seat map selection, in-flight add-on customization, live flight operations radar tracking, automated e-ticket boarding pass generation, and an administrator management suite.

---

## Technical Highlights & Features

### Passenger Booking Portal
- **Real-Time Aircraft Cabin Map**: Interactive 2D aircraft seating chart (Airbus A320 arrangement with Business & Economy class layout, aisle separation, and occupied status tracking).
- **Multi-Passenger Booking Engine**: Single or multi-seat selection (1–6 passengers) with dynamic field generation and auto-calculated class surcharges.
- **In-Flight Customization & Add-Ons**: Flexible check-in baggage allowance upgrades (+10kg, +20kg) and in-flight meal preferences (Vegetarian, Non-Veg, Jain Thali).
- **Promo Discount System**: Instant promo code verification engine (`AERO10` for 10% off, `FLY2026` for ₹500 off) with live price recalculation.
- **Live Flight Operations Tracker**: Search flight schedules by flight number or route to view real-time gate assignments, terminal numbers, baggage belts, and destination weather updates.
- **Digital Boarding Pass Generator**: Printable e-ticket boarding passes styled with barcode verification stubs, PNR references, and print CSS layout.
- **Account & Booking Management**: User authentication, password hashing, active reservation history, and single-click cancellation.

### Administrative Control Panel
- **Operational Metrics**: Total revenue analytics, active flight counts, user registrations, and system booking stats.
- **Flight & Inventory Management**: Create new flights, adjust departure/arrival schedules, manage total seat capacities, and update base pricing.
- **Seat Occupancy Auditing**: Real-time monitoring of booked vs available seats across all scheduled flights.

---

## Tech Stack

| Component | Tech / Library |
| :--- | :--- |
| **Frontend** | HTML5, Modern CSS3 (CSS Variables, Flexbox/Grid, Keyframe Animations), Bootstrap 5.3, Bootstrap Icons |
| **Client Scripting** | Vanilla JavaScript (ES6+, DOM Manipulation, Dynamic Pricing Engine) |
| **Backend Engine** | PHP 8.x (Prepared Statements, Session Management, CSRF Validation) |
| **Database Server** | MySQL 8.0 / MariaDB (InnoDB, Foreign Key Constraints, Transactions) |
| **Development Host** | Apache (XAMPP / WAMP / LAMP Stack) |

---

## Database Architecture

The relational schema (`aerobook_db`) guarantees data integrity and prevents double-booking using transactional database locks:

- `users`: Registered passenger profiles (`id`, `name`, `email`, `phone`, `password`, `created_at`).
- `admins`: Administrative accounts (`id`, `username`, `password`).
- `flights`: Master flight listings (`flight_id`, `flight_number`, `airline_name`, `source`, `destination`, `departure_time`, `arrival_time`, `total_seats`, `seats_available`, `price`, `status`).
- `bookings`: Passenger ticket records (`booking_id`, `booking_ref`, `user_id`, `flight_id`, `passenger_name`, `age`, `gender`, `travel_date`, `seat_number`, `booking_status`, `booking_date`).

---

## Local Setup & Quickstart Guide

### Prerequisites
- PHP 8.0 or higher
- MySQL Server / phpMyAdmin
- Apache Web Server (XAMPP recommended)

### Step-by-Step Installation

1. **Clone Repository**
   ```bash
   git clone git@github.com:suman2308/airline-reservation-system.git
   ```
   Copy or move the repository folder into your server's root web directory (e.g., `C:\xampp\htdocs\airline-reservation-system`).

2. **Import Database Schema**
   - Start **Apache** and **MySQL** in XAMPP.
   - Access phpMyAdmin at `http://localhost/phpmyadmin`.
   - Create a new database named `aerobook_db`.
   - Import the SQL dump located at `database/aerobook.sql`.

3. **Database Configuration**
   - Verify connection settings in `includes/config.php`:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'aerobook_db');
     ```

4. **Launch Application**
   - Open your browser and navigate to:
     - **Main Portal**: `http://localhost/airline-reservation-system`
     - **Admin Panel**: `http://localhost/airline-reservation-system/admin`

---

## System Credentials

| Portal | Username / Email | Password |
| :--- | :--- | :--- |
| **Admin Control Panel** | `admin` | `admin123` |
| **User Account** | *(Register a new account on the portal)* | |

---

## Repository Structure

```
airline-reservation-system/
├── admin/                     # Administrator dashboard & management scripts
│   ├── dashboard.php
│   ├── manage-bookings.php
│   ├── manage-flights.php
│   └── manage-seats.php
├── css/                       # Master CSS stylesheet with animation keyframes
│   └── style.css
├── database/                  # Relational SQL schema dump
│   └── aerobook.sql
├── includes/                  # Database config & core PHP functions
│   ├── config.php
│   ├── db.php
│   ├── functions.php
│   ├── header.php
│   └── footer.php
├── js/                        # Client-side validation & interactivity
│   └── script.js
├── booking.php                # Interactive seat map & booking checkout
├── booking-confirmation.php   # Confirmation summary page
├── flight-status.php          # Live flight status & radar tracker
├── generate-ticket.php        # Printable digital boarding pass
├── index.php                  # Landing page & quick search
└── my-bookings.php            # User booking history
```

---

## License & Attribution

Distributed under the MIT License. Designed and developed for academic showcase and portfolio demonstration.
