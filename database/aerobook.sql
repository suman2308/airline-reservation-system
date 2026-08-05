-- =============================================
-- AeroBook – Airline Reservation System
-- Database: aerobook_db
-- =============================================

-- =============================================
-- Table: admins
-- =============================================
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- Table: users
-- =============================================
-- =============================================
-- Table: users
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    email_verified_at DATETIME DEFAULT NULL,
    last_login_at DATETIME DEFAULT NULL,
    last_login_ip VARCHAR(45) DEFAULT NULL,
    failed_logins INT DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- Table: flights
-- =============================================
CREATE TABLE IF NOT EXISTS flights (
    flight_id INT AUTO_INCREMENT PRIMARY KEY,
    airline_name VARCHAR(100) NOT NULL,
    flight_number VARCHAR(20) NOT NULL UNIQUE,
    source VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    departure_time DATETIME NOT NULL,
    arrival_time DATETIME NOT NULL,
    total_seats INT NOT NULL DEFAULT 180,
    seats_available INT NOT NULL DEFAULT 180,
    price DECIMAL(10, 2) NOT NULL,
    status ENUM('Scheduled', 'Delayed', 'Cancelled', 'Completed') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Seat sanity guard: a flight can never advertise more seats than it has.
    -- This keeps the admin Data Quality panel clean and prevents stale seeds
    -- (older versions defaulted seats_available to 180 even for 160-seat aircraft).
    CONSTRAINT chk_flights_seats CHECK (seats_available <= total_seats)
) ENGINE=InnoDB;

-- =============================================
-- Table: bookings
-- =============================================
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_ref VARCHAR(10) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    flight_id INT NOT NULL,
    passenger_name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    booking_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    travel_date DATE NOT NULL,
    seat_number VARCHAR(5) NOT NULL,
    booking_status ENUM('Confirmed', 'Cancelled') DEFAULT 'Confirmed',
    promo_code VARCHAR(20) DEFAULT NULL,
    promo_discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (flight_id) REFERENCES flights(flight_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Default Admin Account
-- Password: admin123 (hashed with password_hash)
-- =============================================
INSERT INTO admins (username, password) VALUES 
('admin', '$2y$12$bWVCjab6QG667kpA1N.rzekVNzZgLTUb6GwoD/6pKdqmFywWeH.G6'); -- password_hash('admin123')

-- =============================================
-- Table: email_verifications
-- Stores tokens for email verification.
-- =============================================
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

-- =============================================
-- Table: password_resets
-- Stores tokens for password reset requests.
-- =============================================
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

-- =============================================
-- Table: remember_tokens
-- Stores persistent login tokens for "Remember Me".
-- =============================================
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

-- =============================================
-- Table: login_history
-- Records all login attempts (successful and failed).
-- =============================================
CREATE TABLE IF NOT EXISTS login_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT DEFAULT NULL,
    success TINYINT(1) DEFAULT 0,
    login_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_success (success),
    INDEX idx_login_at (login_at)
) ENGINE=InnoDB;

-- =============================================
-- Table: user_sessions
-- Tracks active sessions for multi-device management.
-- =============================================
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_identifier VARCHAR(64) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    device_name VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    logged_in_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_session (session_identifier),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- =============================================
-- Table: admin_activity_log
-- Records all admin actions for audit trail.
-- =============================================
CREATE TABLE IF NOT EXISTS admin_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE CASCADE,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- =============================================
-- Sample Flights Data (Weekly Schedule August 2 - August 8, 2026)
-- =============================================
INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES
-- Sunday (August 2, 2026)
('IndiGo', '6E-101', 'Delhi', 'Mumbai', '2026-08-02 07:00:00', '2026-08-02 09:15:00', 180, 180, 4500.00, 'Scheduled'),
('Air India', 'AI-102', 'Delhi', 'Mumbai', '2026-08-02 18:30:00', '2026-08-02 20:45:00', 200, 200, 5100.00, 'Scheduled'),
('SpiceJet', 'SG-103', 'Mumbai', 'Bangalore', '2026-08-02 08:15:00', '2026-08-02 10:00:00', 180, 180, 3900.00, 'Scheduled'),
('Vistara', 'UK-104', 'Mumbai', 'Bangalore', '2026-08-02 15:45:00', '2026-08-02 17:30:00', 160, 160, 5500.00, 'Scheduled'),
('IndiGo', '6E-105', 'Kolkata', 'Delhi', '2026-08-02 09:30:00', '2026-08-02 11:55:00', 180, 180, 4200.00, 'Scheduled'),
('Air India', 'AI-106', 'Kolkata', 'Delhi', '2026-08-02 20:00:00', '2026-08-02 22:25:00', 200, 200, 4800.00, 'Scheduled'),
('Go First', 'G8-107', 'Chennai', 'Hyderabad', '2026-08-02 11:00:00', '2026-08-02 12:15:00', 180, 180, 3100.00, 'Scheduled'),
('Vistara', 'UK-108', 'Chennai', 'Hyderabad', '2026-08-02 17:30:00', '2026-08-02 18:45:00', 160, 160, 3600.00, 'Scheduled'),

-- Monday (August 3, 2026)
('IndiGo', '6E-201', 'Delhi', 'Mumbai', '2026-08-03 06:30:00', '2026-08-03 08:45:00', 180, 180, 4599.00, 'Scheduled'),
('Air India', 'AI-202', 'Delhi', 'Mumbai', '2026-08-03 19:15:00', '2026-08-03 21:30:00', 200, 200, 5250.00, 'Scheduled'),
('SpiceJet', 'SG-203', 'Mumbai', 'Bangalore', '2026-08-03 09:00:00', '2026-08-03 10:45:00', 180, 180, 5000.00, 'Scheduled'),
('Vistara', 'UK-204', 'Mumbai', 'Bangalore', '2026-08-03 16:30:00', '2026-08-03 18:15:00', 160, 160, 5800.00, 'Scheduled'),
('IndiGo', '6E-205', 'Kolkata', 'Delhi', '2026-08-03 07:15:00', '2026-08-03 09:40:00', 180, 180, 4150.00, 'Scheduled'),
('Air India', 'AI-206', 'Kolkata', 'Delhi', '2026-08-03 21:00:00', '2026-08-03 23:25:00', 200, 200, 4900.00, 'Scheduled'),
('Go First', 'G8-207', 'Chennai', 'Hyderabad', '2026-08-03 11:00:00', '2026-08-03 12:15:00', 180, 180, 3800.00, 'Scheduled'),
('Vistara', 'UK-208', 'Chennai', 'Hyderabad', '2026-08-03 18:45:00', '2026-08-03 20:00:00', 160, 160, 3950.00, 'Scheduled'),

-- Tuesday (August 4, 2026)
('IndiGo', '6E-301', 'Delhi', 'Mumbai', '2026-08-04 08:00:00', '2026-08-04 10:15:00', 180, 180, 4600.00, 'Scheduled'),
('Air India', 'AI-302', 'Delhi', 'Mumbai', '2026-08-04 17:00:00', '2026-08-04 19:15:00', 200, 200, 5300.00, 'Scheduled'),
('SpiceJet', 'SG-303', 'Mumbai', 'Bangalore', '2026-08-04 10:15:00', '2026-08-04 12:00:00', 180, 180, 4800.00, 'Scheduled'),
('Vistara', 'UK-304', 'Mumbai', 'Bangalore', '2026-08-04 14:00:00', '2026-08-04 15:45:00', 160, 160, 5600.00, 'Scheduled'),
('IndiGo', '6E-305', 'Kolkata', 'Delhi', '2026-08-04 06:45:00', '2026-08-04 09:10:00', 180, 180, 4300.00, 'Scheduled'),
('Air India', 'AI-306', 'Kolkata', 'Delhi', '2026-08-04 19:30:00', '2026-08-04 21:55:00', 200, 200, 4750.00, 'Scheduled'),
('Go First', 'G8-307', 'Chennai', 'Hyderabad', '2026-08-04 12:00:00', '2026-08-04 13:15:00', 180, 180, 3200.00, 'Scheduled'),
('Vistara', 'UK-308', 'Chennai', 'Hyderabad', '2026-08-04 16:15:00', '2026-08-04 17:30:00', 160, 160, 3700.00, 'Scheduled'),

-- Wednesday (August 5, 2026)
('IndiGo', '6E-401', 'Delhi', 'Mumbai', '2026-08-05 07:30:00', '2026-08-05 09:45:00', 180, 180, 4400.00, 'Scheduled'),
('Air India', 'AI-402', 'Delhi', 'Mumbai', '2026-08-05 20:30:00', '2026-08-05 22:45:00', 200, 200, 5400.00, 'Scheduled'),
('SpiceJet', 'SG-403', 'Mumbai', 'Bangalore', '2026-08-05 08:45:00', '2026-08-05 10:30:00', 180, 180, 4999.00, 'Scheduled'),
('Vistara', 'UK-404', 'Mumbai', 'Bangalore', '2026-08-05 15:00:00', '2026-08-05 16:45:00', 160, 160, 5750.00, 'Scheduled'),
('IndiGo', '6E-405', 'Kolkata', 'Delhi', '2026-08-05 08:00:00', '2026-08-05 10:25:00', 180, 180, 5100.00, 'Scheduled'),
('Air India', 'AI-406', 'Kolkata', 'Delhi', '2026-08-05 18:00:00', '2026-08-05 20:25:00', 200, 200, 4650.00, 'Scheduled'),
('Go First', 'G8-407', 'Chennai', 'Hyderabad', '2026-08-05 13:00:00', '2026-08-05 14:15:00', 180, 180, 3350.00, 'Scheduled'),
('Vistara', 'UK-408', 'Chennai', 'Hyderabad', '2026-08-05 19:00:00', '2026-08-05 20:15:00', 160, 160, 3850.00, 'Scheduled'),

-- Thursday (August 6, 2026)
('IndiGo', '6E-501', 'Delhi', 'Mumbai', '2026-08-06 06:00:00', '2026-08-06 08:15:00', 180, 180, 4700.00, 'Scheduled'),
('Air India', 'AI-502', 'Delhi', 'Mumbai', '2026-08-06 19:00:00', '2026-08-06 21:15:00', 200, 200, 5200.00, 'Scheduled'),
('SpiceJet', 'SG-503', 'Mumbai', 'Bangalore', '2026-08-06 09:30:00', '2026-08-06 11:15:00', 180, 180, 5100.00, 'Scheduled'),
('Vistara', 'UK-504', 'Mumbai', 'Bangalore', '2026-08-06 16:00:00', '2026-08-06 17:45:00', 160, 160, 5900.00, 'Scheduled'),
('IndiGo', '6E-505', 'Kolkata', 'Delhi', '2026-08-06 10:30:00', '2026-08-06 12:55:00', 180, 180, 4500.00, 'Scheduled'),
('Air India', 'AI-506', 'Kolkata', 'Delhi', '2026-08-06 21:30:00', '2026-08-06 23:55:00', 200, 200, 5000.00, 'Scheduled'),
('Go First', 'G8-507', 'Chennai', 'Hyderabad', '2026-08-06 10:30:00', '2026-08-06 11:45:00', 180, 180, 3450.00, 'Scheduled'),
('Vistara', 'UK-508', 'Chennai', 'Hyderabad', '2026-08-06 17:00:00', '2026-08-06 18:15:00', 160, 160, 3750.00, 'Scheduled'),

-- Friday (August 7, 2026)
('IndiGo', '6E-601', 'Delhi', 'Mumbai', '2026-08-07 08:30:00', '2026-08-07 10:45:00', 180, 180, 4800.00, 'Scheduled'),
('Air India', 'AI-602', 'Delhi', 'Mumbai', '2026-08-07 18:00:00', '2026-08-07 20:15:00', 200, 200, 5500.00, 'Scheduled'),
('SpiceJet', 'SG-603', 'Mumbai', 'Bangalore', '2026-08-07 05:45:00', '2026-08-07 07:30:00', 180, 180, 3999.00, 'Scheduled'),
('Vistara', 'UK-604', 'Mumbai', 'Bangalore', '2026-08-07 14:45:00', '2026-08-07 16:30:00', 160, 160, 6200.00, 'Scheduled'),
('IndiGo', '6E-605', 'Kolkata', 'Delhi', '2026-08-07 09:00:00', '2026-08-07 11:25:00', 180, 180, 4400.00, 'Scheduled'),
('Air India', 'AI-606', 'Kolkata', 'Delhi', '2026-08-07 20:15:00', '2026-08-07 22:40:00', 200, 200, 5150.00, 'Scheduled'),
('Go First', 'G8-607', 'Chennai', 'Hyderabad', '2026-08-07 11:30:00', '2026-08-07 12:45:00', 180, 180, 3250.00, 'Scheduled'),
('Vistara', 'UK-608', 'Chennai', 'Hyderabad', '2026-08-07 18:00:00', '2026-08-07 19:15:00', 160, 160, 3900.00, 'Scheduled'),

-- Saturday (August 8, 2026)
('IndiGo', '6E-701', 'Delhi', 'Mumbai', '2026-08-08 09:15:00', '2026-08-08 11:30:00', 180, 180, 4900.00, 'Scheduled'),
('Air India', 'AI-702', 'Delhi', 'Mumbai', '2026-08-08 21:00:00', '2026-08-08 23:15:00', 200, 200, 5600.00, 'Scheduled'),
('SpiceJet', 'SG-703', 'Mumbai', 'Bangalore', '2026-08-08 10:00:00', '2026-08-08 11:45:00', 180, 180, 5250.00, 'Scheduled'),
('Vistara', 'UK-704', 'Mumbai', 'Bangalore', '2026-08-08 16:15:00', '2026-08-08 18:00:00', 160, 160, 6000.00, 'Scheduled'),
('IndiGo', '6E-705', 'Kolkata', 'Delhi', '2026-08-08 08:15:00', '2026-08-08 10:40:00', 180, 180, 4250.00, 'Scheduled'),
('Air India', 'AI-706', 'Kolkata', 'Delhi', '2026-08-08 19:45:00', '2026-08-08 22:10:00', 200, 200, 4950.00, 'Scheduled'),
('Go First', 'G8-707', 'Chennai', 'Hyderabad', '2026-08-08 12:30:00', '2026-08-08 13:45:00', 180, 180, 3500.00, 'Scheduled'),
('Vistara', 'UK-708', 'Chennai', 'Hyderabad', '2026-08-08 15:00:00', '2026-08-08 16:15:00', 160, 160, 4100.00, 'Scheduled'),

-- =============================================
-- International Flights (weekly schedule)
-- =============================================
-- Delhi → Dubai (daily)
('Air India', 'AI-901', 'Delhi', 'Dubai', '2026-08-02 02:30:00', '2026-08-02 05:45:00', 220, 220, 16500.00, 'Scheduled'),
('Air India', 'AI-902', 'Delhi', 'Dubai', '2026-08-03 02:30:00', '2026-08-03 05:45:00', 220, 220, 16900.00, 'Scheduled'),
('Air India', 'AI-903', 'Delhi', 'Dubai', '2026-08-04 02:30:00', '2026-08-04 05:45:00', 220, 220, 16200.00, 'Scheduled'),
('Air India', 'AI-904', 'Delhi', 'Dubai', '2026-08-05 02:30:00', '2026-08-05 05:45:00', 220, 220, 15800.00, 'Scheduled'),
('Air India', 'AI-905', 'Delhi', 'Dubai', '2026-08-06 02:30:00', '2026-08-06 05:45:00', 220, 220, 17100.00, 'Scheduled'),
('Air India', 'AI-906', 'Delhi', 'Dubai', '2026-08-07 02:30:00', '2026-08-07 05:45:00', 220, 220, 16600.00, 'Scheduled'),
('Air India', 'AI-907', 'Delhi', 'Dubai', '2026-08-08 02:30:00', '2026-08-08 05:45:00', 220, 220, 17400.00, 'Scheduled'),

-- Dubai → Delhi (return)
('Emirates', 'EK-910', 'Dubai', 'Delhi', '2026-08-02 11:00:00', '2026-08-02 16:15:00', 250, 250, 18900.00, 'Scheduled'),
('Emirates', 'EK-911', 'Dubai', 'Delhi', '2026-08-03 11:00:00', '2026-08-03 16:15:00', 250, 250, 19500.00, 'Scheduled'),
('Emirates', 'EK-912', 'Dubai', 'Delhi', '2026-08-04 11:00:00', '2026-08-04 16:15:00', 250, 250, 18200.00, 'Scheduled'),
('Emirates', 'EK-913', 'Dubai', 'Delhi', '2026-08-05 11:00:00', '2026-08-05 16:15:00', 250, 250, 17600.00, 'Scheduled'),
('Emirates', 'EK-914', 'Dubai', 'Delhi', '2026-08-06 11:00:00', '2026-08-06 16:15:00', 250, 250, 19800.00, 'Scheduled'),
('Emirates', 'EK-915', 'Dubai', 'Delhi', '2026-08-07 11:00:00', '2026-08-07 16:15:00', 250, 250, 18400.00, 'Scheduled'),
('Emirates', 'EK-916', 'Dubai', 'Delhi', '2026-08-08 11:00:00', '2026-08-08 16:15:00', 250, 250, 20100.00, 'Scheduled'),

-- Mumbai → Singapore (daily)
('Singapore Airlines', 'SQ-921', 'Mumbai', 'Singapore', '2026-08-02 23:45:00', '2026-08-03 07:50:00', 260, 260, 21500.00, 'Scheduled'),
('Singapore Airlines', 'SQ-922', 'Mumbai', 'Singapore', '2026-08-03 23:45:00', '2026-08-04 07:50:00', 260, 260, 22100.00, 'Scheduled'),
('Singapore Airlines', 'SQ-923', 'Mumbai', 'Singapore', '2026-08-04 23:45:00', '2026-08-05 07:50:00', 260, 260, 20900.00, 'Scheduled'),
('Singapore Airlines', 'SQ-924', 'Mumbai', 'Singapore', '2026-08-05 23:45:00', '2026-08-06 07:50:00', 260, 260, 20300.00, 'Scheduled'),
('Singapore Airlines', 'SQ-925', 'Mumbai', 'Singapore', '2026-08-06 23:45:00', '2026-08-07 07:50:00', 260, 260, 22800.00, 'Scheduled'),
('Singapore Airlines', 'SQ-926', 'Mumbai', 'Singapore', '2026-08-07 23:45:00', '2026-08-08 07:50:00', 260, 260, 21200.00, 'Scheduled'),
('Singapore Airlines', 'SQ-927', 'Mumbai', 'Singapore', '2026-08-08 23:45:00', '2026-08-09 07:50:00', 260, 260, 23500.00, 'Scheduled'),

-- Delhi → Bangkok (daily)
('Thai Airways', 'TG-931', 'Delhi', 'Bangkok', '2026-08-02 01:15:00', '2026-08-02 06:30:00', 230, 230, 14800.00, 'Scheduled'),
('Thai Airways', 'TG-932', 'Delhi', 'Bangkok', '2026-08-03 01:15:00', '2026-08-03 06:30:00', 230, 230, 15200.00, 'Scheduled'),
('Thai Airways', 'TG-933', 'Delhi', 'Bangkok', '2026-08-04 01:15:00', '2026-08-04 06:30:00', 230, 230, 14500.00, 'Scheduled'),
('Thai Airways', 'TG-934', 'Delhi', 'Bangkok', '2026-08-05 01:15:00', '2026-08-05 06:30:00', 230, 230, 13900.00, 'Scheduled'),
('Thai Airways', 'TG-935', 'Delhi', 'Bangkok', '2026-08-06 01:15:00', '2026-08-06 06:30:00', 230, 230, 15500.00, 'Scheduled'),
('Thai Airways', 'TG-936', 'Delhi', 'Bangkok', '2026-08-07 01:15:00', '2026-08-07 06:30:00', 230, 230, 14900.00, 'Scheduled'),
('Thai Airways', 'TG-937', 'Delhi', 'Bangkok', '2026-08-08 01:15:00', '2026-08-08 06:30:00', 230, 230, 15700.00, 'Scheduled');

-- =============================================
-- Performance Indexes
-- =============================================
-- All indexes are documented with the query patterns they accelerate.
-- Run `EXPLAIN SELECT ...` to verify index usage.

-- Flight search by route and date (flight-results.php, search-flights.php, Smart Fare Engine)
CREATE INDEX idx_flights_route ON flights (source, destination);

-- Flight lookups by number (flight-status.php, admin)
CREATE INDEX idx_flights_number ON flights (flight_number);

-- Departure time queries (today's flights, ordering, scheduling)
CREATE INDEX idx_flights_departure ON flights (departure_time);

-- Flight status filter (admin flight listing, search filtering)
CREATE INDEX idx_flights_status ON flights (status);

-- User booking history (my-bookings.php, user-dashboard.php)
CREATE INDEX idx_bookings_user_status ON bookings (user_id, booking_status);

-- Seat availability checks (booking.php, seat selection)
CREATE INDEX idx_bookings_flight_date ON bookings (flight_id, travel_date, booking_status);

-- Booking date ordering (admin, user dashboard)
CREATE INDEX idx_bookings_created ON bookings (booking_date);

-- User management sorting (admin/manage-users.php)
CREATE INDEX idx_users_created ON users (created_at);

-- Admin activity log date queries (admin/activity-log.php)
CREATE INDEX idx_adminlog_action ON admin_activity_log (action, created_at);

-- =============================================
-- Table structure for table `contacts`
CREATE TABLE IF NOT EXISTS `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contacts listing (admin/manage-contacts.php)
CREATE INDEX idx_contacts_created ON contacts (created_at);

-- =============================================
-- Table: saved_passengers
-- Stores frequently-used passenger details for quick booking.
-- =============================================
CREATE TABLE IF NOT EXISTS saved_passengers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

-- =============================================
-- Table: booking_addons
-- Stores add-ons (baggage, meals) per booking.
-- =============================================
CREATE TABLE IF NOT EXISTS booking_addons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    addon_type VARCHAR(50) NOT NULL,
    addon_name VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_booking_id (booking_id)
) ENGINE=InnoDB;

-- =============================================
-- Table: saved_routes
-- Stores user's favorite routes for quick search.
-- =============================================
CREATE TABLE IF NOT EXISTS saved_routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    source VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    label VARCHAR(200) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    UNIQUE KEY unique_user_route (user_id, source, destination)
) ENGINE=InnoDB;

-- =============================================
-- Table: notifications
-- Stores in-app notifications for users.
-- =============================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT DEFAULT NULL,
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- =============================================
-- Table: transactions
-- Stores demo payment transaction records.
-- =============================================
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    booking_ref VARCHAR(10) NOT NULL,
    payment_order_id VARCHAR(100) DEFAULT NULL,
    payment_payment_id VARCHAR(100) DEFAULT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'INR',
    status ENUM('created', 'paid', 'failed') DEFAULT 'created',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_booking_ref (booking_ref),
    INDEX idx_payment_order (payment_order_id)
) ENGINE=InnoDB;

-- =============================================
-- Table: price_watches
-- Stores user's price watch alerts for routes.
-- =============================================
CREATE TABLE IF NOT EXISTS price_watches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    source VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    max_fare DECIMAL(10,2) DEFAULT NULL,
    preferred_month VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;
