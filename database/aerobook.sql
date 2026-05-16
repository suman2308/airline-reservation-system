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
-- Sample Flights Data
-- =============================================
INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES
('IndiGo', '6E-2041', 'Delhi', 'Mumbai', '2026-06-15 06:30:00', '2026-06-15 08:45:00', 180, 174, 4599.00, 'Scheduled'),
('Air India', 'AI-502', 'Mumbai', 'Bangalore', '2026-06-15 09:00:00', '2026-06-15 10:45:00', 200, 195, 5250.00, 'Scheduled'),
('SpiceJet', 'SG-8169', 'Kolkata', 'Delhi', '2026-06-16 07:15:00', '2026-06-16 09:40:00', 180, 180, 4150.00, 'Scheduled'),
('Vistara', 'UK-835', 'Chennai', 'Hyderabad', '2026-06-16 11:00:00', '2026-06-16 12:15:00', 160, 158, 3800.00, 'Scheduled'),
('Go First', 'G8-514', 'Bangalore', 'Pune', '2026-06-17 14:30:00', '2026-06-17 16:10:00', 180, 180, 3450.00, 'Scheduled'),
('IndiGo', '6E-6103', 'Delhi', 'Kolkata', '2026-06-17 08:00:00', '2026-06-17 10:30:00', 180, 176, 5100.00, 'Scheduled'),
('Air India', 'AI-680', 'Hyderabad', 'Mumbai', '2026-06-18 10:30:00', '2026-06-18 12:15:00', 200, 200, 4800.00, 'Scheduled'),
('Vistara', 'UK-994', 'Pune', 'Delhi', '2026-06-18 16:00:00', '2026-06-18 18:20:00', 160, 155, 5500.00, 'Scheduled'),
('SpiceJet', 'SG-2731', 'Mumbai', 'Chennai', '2026-06-19 05:45:00', '2026-06-19 07:55:00', 180, 178, 3999.00, 'Scheduled'),
('IndiGo', '6E-5408', 'Bangalore', 'Delhi', '2026-06-19 12:00:00', '2026-06-19 14:45:00', 180, 170, 6200.00, 'Scheduled'),
('Air India', 'AI-113', 'Delhi', 'Chennai', '2026-06-20 09:30:00', '2026-06-20 12:15:00', 200, 198, 5750.00, 'Scheduled'),
('Go First', 'G8-328', 'Kolkata', 'Bangalore', '2026-06-20 15:00:00', '2026-06-20 17:45:00', 180, 180, 6100.00, 'Scheduled');

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
