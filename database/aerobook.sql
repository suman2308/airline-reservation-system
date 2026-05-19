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
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (flight_id) REFERENCES flights(flight_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Default Admin Account
-- Password: admin123 (hashed with password_hash)
-- =============================================
INSERT INTO admins (username, password) VALUES 
('admin', '$2y$10$8K1p/a0dR1xLX8uGq7mnSOGy0O9xTBKrecYFQr0JBV3xbRvN1Dxqi');

-- =============================================
-- Sample Flights Data (Weekly Schedule June 14 - June 20, 2026)
-- =============================================
INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES
-- Sunday (June 14, 2026)
('IndiGo', '6E-101', 'Delhi', 'Mumbai', '2026-06-14 07:00:00', '2026-06-14 09:15:00', 180, 180, 4500.00, 'Scheduled'),
('Air India', 'AI-102', 'Delhi', 'Mumbai', '2026-06-14 18:30:00', '2026-06-14 20:45:00', 200, 200, 5100.00, 'Scheduled'),
('SpiceJet', 'SG-103', 'Mumbai', 'Bangalore', '2026-06-14 08:15:00', '2026-06-14 10:00:00', 180, 180, 3900.00, 'Scheduled'),
('Vistara', 'UK-104', 'Mumbai', 'Bangalore', '2026-06-14 15:45:00', '2026-06-14 17:30:00', 160, 160, 5500.00, 'Scheduled'),
('IndiGo', '6E-105', 'Kolkata', 'Delhi', '2026-06-14 09:30:00', '2026-06-14 11:55:00', 180, 180, 4200.00, 'Scheduled'),
('Air India', 'AI-106', 'Kolkata', 'Delhi', '2026-06-14 20:00:00', '2026-06-14 22:25:00', 200, 200, 4800.00, 'Scheduled'),
('Go First', 'G8-107', 'Chennai', 'Hyderabad', '2026-06-14 11:00:00', '2026-06-14 12:15:00', 180, 180, 3100.00, 'Scheduled'),
('Vistara', 'UK-108', 'Chennai', 'Hyderabad', '2026-06-14 17:30:00', '2026-06-14 18:45:00', 160, 160, 3600.00, 'Scheduled'),

-- Monday (June 15, 2026)
('IndiGo', '6E-201', 'Delhi', 'Mumbai', '2026-06-15 06:30:00', '2026-06-15 08:45:00', 180, 180, 4599.00, 'Scheduled'),
('Air India', 'AI-202', 'Delhi', 'Mumbai', '2026-06-15 19:15:00', '2026-06-15 21:30:00', 200, 200, 5250.00, 'Scheduled'),
('SpiceJet', 'SG-203', 'Mumbai', 'Bangalore', '2026-06-15 09:00:00', '2026-06-15 10:45:00', 180, 180, 5000.00, 'Scheduled'),
('Vistara', 'UK-204', 'Mumbai', 'Bangalore', '2026-06-15 16:30:00', '2026-06-15 18:15:00', 160, 160, 5800.00, 'Scheduled'),
('IndiGo', '6E-205', 'Kolkata', 'Delhi', '2026-06-15 07:15:00', '2026-06-15 09:40:00', 180, 180, 4150.00, 'Scheduled'),
('Air India', 'AI-206', 'Kolkata', 'Delhi', '2026-06-15 21:00:00', '2026-06-15 23:25:00', 200, 200, 4900.00, 'Scheduled'),
('Go First', 'G8-207', 'Chennai', 'Hyderabad', '2026-06-15 11:00:00', '2026-06-15 12:15:00', 180, 180, 3800.00, 'Scheduled'),
('Vistara', 'UK-208', 'Chennai', 'Hyderabad', '2026-06-15 18:45:00', '2026-06-15 20:00:00', 160, 160, 3950.00, 'Scheduled'),

-- Tuesday (June 16, 2026)
('IndiGo', '6E-301', 'Delhi', 'Mumbai', '2026-06-16 08:00:00', '2026-06-16 10:15:00', 180, 180, 4600.00, 'Scheduled'),
('Air India', 'AI-302', 'Delhi', 'Mumbai', '2026-06-16 17:00:00', '2026-06-16 19:15:00', 200, 200, 5300.00, 'Scheduled'),
('SpiceJet', 'SG-303', 'Mumbai', 'Bangalore', '2026-06-16 10:15:00', '2026-06-16 12:00:00', 180, 180, 4800.00, 'Scheduled'),
('Vistara', 'UK-304', 'Mumbai', 'Bangalore', '2026-06-16 14:00:00', '2026-06-16 15:45:00', 160, 160, 5600.00, 'Scheduled'),
('IndiGo', '6E-305', 'Kolkata', 'Delhi', '2026-06-16 06:45:00', '2026-06-16 09:10:00', 180, 180, 4300.00, 'Scheduled'),
('Air India', 'AI-306', 'Kolkata', 'Delhi', '2026-06-16 19:30:00', '2026-06-16 21:55:00', 200, 200, 4750.00, 'Scheduled'),
('Go First', 'G8-307', 'Chennai', 'Hyderabad', '2026-06-16 12:00:00', '2026-06-16 13:15:00', 180, 180, 3200.00, 'Scheduled'),
('Vistara', 'UK-308', 'Chennai', 'Hyderabad', '2026-06-16 16:15:00', '2026-06-16 17:30:00', 160, 160, 3700.00, 'Scheduled'),

-- Wednesday (June 17, 2026)
('IndiGo', '6E-401', 'Delhi', 'Mumbai', '2026-06-17 07:30:00', '2026-06-17 09:45:00', 180, 180, 4400.00, 'Scheduled'),
('Air India', 'AI-402', 'Delhi', 'Mumbai', '2026-06-17 20:30:00', '2026-06-17 22:45:00', 200, 200, 5400.00, 'Scheduled'),
('SpiceJet', 'SG-403', 'Mumbai', 'Bangalore', '2026-06-17 08:45:00', '2026-06-17 10:30:00', 180, 180, 4999.00, 'Scheduled'),
('Vistara', 'UK-404', 'Mumbai', 'Bangalore', '2026-06-17 15:00:00', '2026-06-17 16:45:00', 160, 160, 5750.00, 'Scheduled'),
('IndiGo', '6E-405', 'Kolkata', 'Delhi', '2026-06-17 08:00:00', '2026-06-17 10:25:00', 180, 180, 5100.00, 'Scheduled'),
('Air India', 'AI-406', 'Kolkata', 'Delhi', '2026-06-17 18:00:00', '2026-06-17 20:25:00', 200, 200, 4650.00, 'Scheduled'),
('Go First', 'G8-407', 'Chennai', 'Hyderabad', '2026-06-17 13:00:00', '2026-06-17 14:15:00', 180, 180, 3350.00, 'Scheduled'),
('Vistara', 'UK-408', 'Chennai', 'Hyderabad', '2026-06-17 19:00:00', '2026-06-17 20:15:00', 160, 160, 3850.00, 'Scheduled'),

-- Thursday (June 18, 2026)
('IndiGo', '6E-501', 'Delhi', 'Mumbai', '2026-06-18 06:00:00', '2026-06-18 08:15:00', 180, 180, 4700.00, 'Scheduled'),
('Air India', 'AI-502', 'Delhi', 'Mumbai', '2026-06-18 19:00:00', '2026-06-18 21:15:00', 200, 200, 5200.00, 'Scheduled'),
('SpiceJet', 'SG-503', 'Mumbai', 'Bangalore', '2026-06-18 09:30:00', '2026-06-18 11:15:00', 180, 180, 5100.00, 'Scheduled'),
('Vistara', 'UK-504', 'Mumbai', 'Bangalore', '2026-06-18 16:00:00', '2026-06-18 17:45:00', 160, 160, 5900.00, 'Scheduled'),
('IndiGo', '6E-505', 'Kolkata', 'Delhi', '2026-06-18 10:30:00', '2026-06-18 12:55:00', 180, 180, 4500.00, 'Scheduled'),
('Air India', 'AI-506', 'Kolkata', 'Delhi', '2026-06-18 21:30:00', '2026-06-18 23:55:00', 200, 200, 5000.00, 'Scheduled'),
('Go First', 'G8-507', 'Chennai', 'Hyderabad', '2026-06-18 10:30:00', '2026-06-18 11:45:00', 180, 180, 3450.00, 'Scheduled'),
('Vistara', 'UK-508', 'Chennai', 'Hyderabad', '2026-06-18 17:00:00', '2026-06-18 18:15:00', 160, 160, 3750.00, 'Scheduled'),

-- Friday (June 19, 2026)
('IndiGo', '6E-601', 'Delhi', 'Mumbai', '2026-06-19 08:30:00', '2026-06-19 10:45:00', 180, 180, 4800.00, 'Scheduled'),
('Air India', 'AI-602', 'Delhi', 'Mumbai', '2026-06-19 18:00:00', '2026-06-19 20:15:00', 200, 200, 5500.00, 'Scheduled'),
('SpiceJet', 'SG-603', 'Mumbai', 'Bangalore', '2026-06-19 05:45:00', '2026-06-19 07:30:00', 180, 180, 3999.00, 'Scheduled'),
('Vistara', 'UK-604', 'Mumbai', 'Bangalore', '2026-06-19 14:45:00', '2026-06-19 16:30:00', 160, 160, 6200.00, 'Scheduled'),
('IndiGo', '6E-605', 'Kolkata', 'Delhi', '2026-06-19 09:00:00', '2026-06-19 11:25:00', 180, 180, 4400.00, 'Scheduled'),
('Air India', 'AI-606', 'Kolkata', 'Delhi', '2026-06-19 20:15:00', '2026-06-19 22:40:00', 200, 200, 5150.00, 'Scheduled'),
('Go First', 'G8-607', 'Chennai', 'Hyderabad', '2026-06-19 11:30:00', '2026-06-19 12:45:00', 180, 180, 3250.00, 'Scheduled'),
('Vistara', 'UK-608', 'Chennai', 'Hyderabad', '2026-06-19 18:00:00', '2026-06-19 19:15:00', 160, 160, 3900.00, 'Scheduled'),

-- Saturday (June 20, 2026)
('IndiGo', '6E-701', 'Delhi', 'Mumbai', '2026-06-20 09:15:00', '2026-06-20 11:30:00', 180, 180, 4900.00, 'Scheduled'),
('Air India', 'AI-702', 'Delhi', 'Mumbai', '2026-06-20 21:00:00', '2026-06-20 23:15:00', 200, 200, 5600.00, 'Scheduled'),
('SpiceJet', 'SG-703', 'Mumbai', 'Bangalore', '2026-06-20 10:00:00', '2026-06-20 11:45:00', 180, 180, 5250.00, 'Scheduled'),
('Vistara', 'UK-704', 'Mumbai', 'Bangalore', '2026-06-20 16:15:00', '2026-06-20 18:00:00', 160, 160, 6000.00, 'Scheduled'),
('IndiGo', '6E-705', 'Kolkata', 'Delhi', '2026-06-20 08:15:00', '2026-06-20 10:40:00', 180, 180, 4250.00, 'Scheduled'),
('Air India', 'AI-706', 'Kolkata', 'Delhi', '2026-06-20 19:45:00', '2026-06-20 22:10:00', 200, 200, 4950.00, 'Scheduled'),
('Go First', 'G8-707', 'Chennai', 'Hyderabad', '2026-06-20 12:30:00', '2026-06-20 13:45:00', 180, 180, 3500.00, 'Scheduled'),
('Vistara', 'UK-708', 'Chennai', 'Hyderabad', '2026-06-20 15:00:00', '2026-06-20 16:15:00', 160, 160, 4100.00, 'Scheduled');

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
