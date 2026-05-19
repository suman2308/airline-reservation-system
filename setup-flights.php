<?php
require_once 'includes/config.php';

// First, disable foreign key checks and truncate tables to start fresh
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
mysqli_query($conn, "TRUNCATE TABLE bookings");
mysqli_query($conn, "TRUNCATE TABLE flights");
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

$queries = [
    // Sunday (June 14, 2026)
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('IndiGo', '6E-101', 'Delhi', 'Mumbai', '2026-06-14 07:00:00', '2026-06-14 09:15:00', 180, 180, 4500.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Air India', 'AI-102', 'Delhi', 'Mumbai', '2026-06-14 18:30:00', '2026-06-14 20:45:00', 200, 200, 5100.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('SpiceJet', 'SG-103', 'Mumbai', 'Bangalore', '2026-06-14 08:15:00', '2026-06-14 10:00:00', 180, 180, 3900.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Vistara', 'UK-104', 'Mumbai', 'Bangalore', '2026-06-14 15:45:00', '2026-06-14 17:30:00', 160, 160, 5500.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('IndiGo', '6E-105', 'Kolkata', 'Delhi', '2026-06-14 09:30:00', '2026-06-14 11:55:00', 180, 180, 4200.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Air India', 'AI-106', 'Kolkata', 'Delhi', '2026-06-14 20:00:00', '2026-06-14 22:25:00', 200, 200, 4800.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Go First', 'G8-107', 'Chennai', 'Hyderabad', '2026-06-14 11:00:00', '2026-06-14 12:15:00', 180, 180, 3100.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Vistara', 'UK-108', 'Chennai', 'Hyderabad', '2026-06-14 17:30:00', '2026-06-14 18:45:00', 160, 160, 3600.00, 'Scheduled')",

    // Monday (June 15, 2026)
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('IndiGo', '6E-201', 'Delhi', 'Mumbai', '2026-06-15 06:30:00', '2026-06-15 08:45:00', 180, 180, 4599.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Air India', 'AI-202', 'Delhi', 'Mumbai', '2026-06-15 19:15:00', '2026-06-15 21:30:00', 200, 200, 5250.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('SpiceJet', 'SG-203', 'Mumbai', 'Bangalore', '2026-06-15 09:00:00', '2026-06-15 10:45:00', 180, 180, 5000.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Vistara', 'UK-204', 'Mumbai', 'Bangalore', '2026-06-15 16:30:00', '2026-06-15 18:15:00', 160, 160, 5800.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('IndiGo', '6E-205', 'Kolkata', 'Delhi', '2026-06-15 07:15:00', '2026-06-15 09:40:00', 180, 180, 4150.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Air India', 'AI-206', 'Kolkata', 'Delhi', '2026-06-15 21:00:00', '2026-06-15 23:25:00', 200, 200, 4900.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Go First', 'G8-207', 'Chennai', 'Hyderabad', '2026-06-15 11:00:00', '2026-06-15 12:15:00', 180, 180, 3800.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Vistara', 'UK-208', 'Chennai', 'Hyderabad', '2026-06-15 18:45:00', '2026-06-15 20:00:00', 160, 160, 3950.00, 'Scheduled')",

    // Tuesday (June 16, 2026)
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('IndiGo', '6E-301', 'Delhi', 'Mumbai', '2026-06-16 08:00:00', '2026-06-16 10:15:00', 180, 180, 4600.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Air India', 'AI-302', 'Delhi', 'Mumbai', '2026-06-16 17:00:00', '2026-06-16 19:15:00', 200, 200, 5300.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('SpiceJet', 'SG-303', 'Mumbai', 'Bangalore', '2026-06-16 10:15:00', '2026-06-16 12:00:00', 180, 180, 4800.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Vistara', 'UK-304', 'Mumbai', 'Bangalore', '2026-06-16 14:00:00', '2026-06-16 15:45:00', 160, 160, 5600.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('IndiGo', '6E-305', 'Kolkata', 'Delhi', '2026-06-16 06:45:00', '2026-06-16 09:10:00', 180, 180, 4300.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Air India', 'AI-306', 'Kolkata', 'Delhi', '2026-06-16 19:30:00', '2026-06-16 21:55:00', 200, 200, 4750.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Go First', 'G8-307', 'Chennai', 'Hyderabad', '2026-06-16 12:00:00', '2026-06-16 13:15:00', 180, 180, 3200.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Vistara', 'UK-308', 'Chennai', 'Hyderabad', '2026-06-16 16:15:00', '2026-06-16 17:30:00', 160, 160, 3700.00, 'Scheduled')",

    // Wednesday (June 17, 2026)
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('IndiGo', '6E-401', 'Delhi', 'Mumbai', '2026-06-17 07:30:00', '2026-06-17 09:45:00', 180, 180, 4400.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Air India', 'AI-402', 'Delhi', 'Mumbai', '2026-06-17 20:30:00', '2026-06-17 22:45:00', 200, 200, 5400.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('SpiceJet', 'SG-403', 'Mumbai', 'Bangalore', '2026-06-17 08:45:00', '2026-06-17 10:30:00', 180, 180, 4999.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Vistara', 'UK-404', 'Mumbai', 'Bangalore', '2026-06-17 15:00:00', '2026-06-17 16:45:00', 160, 160, 5750.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('IndiGo', '6E-405', 'Kolkata', 'Delhi', '2026-06-17 08:00:00', '2026-06-17 10:25:00', 180, 180, 5100.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Air India', 'AI-406', 'Kolkata', 'Delhi', '2026-06-17 18:00:00', '2026-06-17 20:25:00', 200, 200, 4650.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Go First', 'G8-407', 'Chennai', 'Hyderabad', '2026-06-17 13:00:00', '2026-06-17 14:15:00', 180, 180, 3350.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Vistara', 'UK-408', 'Chennai', 'Hyderabad', '2026-06-17 19:00:00', '2026-06-17 20:15:00', 160, 160, 3850.00, 'Scheduled')",

    // Thursday (June 18, 2026)
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('IndiGo', '6E-501', 'Delhi', 'Mumbai', '2026-06-18 06:00:00', '2026-06-18 08:15:00', 180, 180, 4700.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Air India', 'AI-502', 'Delhi', 'Mumbai', '2026-06-18 19:00:00', '2026-06-18 21:15:00', 200, 200, 5200.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('SpiceJet', 'SG-503', 'Mumbai', 'Bangalore', '2026-06-18 09:30:00', '2026-06-18 11:15:00', 180, 180, 5100.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Vistara', 'UK-504', 'Mumbai', 'Bangalore', '2026-06-18 16:00:00', '2026-06-18 17:45:00', 160, 160, 5900.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('IndiGo', '6E-505', 'Kolkata', 'Delhi', '2026-06-18 10:30:00', '2026-06-18 12:55:00', 180, 180, 4500.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Air India', 'AI-506', 'Kolkata', 'Delhi', '2026-06-18 21:30:00', '2026-06-18 23:55:00', 200, 200, 5000.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Go First', 'G8-507', 'Chennai', 'Hyderabad', '2026-06-18 10:30:00', '2026-06-18 11:45:00', 180, 180, 3450.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Vistara', 'UK-508', 'Chennai', 'Hyderabad', '2026-06-18 17:00:00', '2026-06-18 18:15:00', 160, 160, 3750.00, 'Scheduled')",

    // Friday (June 19, 2026)
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('IndiGo', '6E-601', 'Delhi', 'Mumbai', '2026-06-19 08:30:00', '2026-06-19 10:45:00', 180, 180, 4800.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Air India', 'AI-602', 'Delhi', 'Mumbai', '2026-06-19 18:00:00', '2026-06-19 20:15:00', 200, 200, 5500.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('SpiceJet', 'SG-603', 'Mumbai', 'Bangalore', '2026-06-19 05:45:00', '2026-06-19 07:30:00', 180, 180, 3999.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Vistara', 'UK-604', 'Mumbai', 'Bangalore', '2026-06-19 14:45:00', '2026-06-19 16:30:00', 160, 160, 6200.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('IndiGo', '6E-605', 'Kolkata', 'Delhi', '2026-06-19 09:00:00', '2026-06-19 11:25:00', 180, 180, 4400.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Air India', 'AI-606', 'Kolkata', 'Delhi', '2026-06-19 20:15:00', '2026-06-19 22:40:00', 200, 200, 5150.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Go First', 'G8-607', 'Chennai', 'Hyderabad', '2026-06-19 11:30:00', '2026-06-19 12:45:00', 180, 180, 3250.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Vistara', 'UK-608', 'Chennai', 'Hyderabad', '2026-06-19 18:00:00', '2026-06-19 19:15:00', 160, 160, 3900.00, 'Scheduled')",

    // Saturday (June 20, 2026)
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('IndiGo', '6E-701', 'Delhi', 'Mumbai', '2026-06-20 09:15:00', '2026-06-20 11:30:00', 180, 180, 4900.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Air India', 'AI-702', 'Delhi', 'Mumbai', '2026-06-20 21:00:00', '2026-06-20 23:15:00', 200, 200, 5600.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('SpiceJet', 'SG-703', 'Mumbai', 'Bangalore', '2026-06-20 10:00:00', '2026-06-20 11:45:00', 180, 180, 5250.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Vistara', 'UK-704', 'Mumbai', 'Bangalore', '2026-06-20 16:15:00', '2026-06-20 18:00:00', 160, 160, 6000.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('IndiGo', '6E-705', 'Kolkata', 'Delhi', '2026-06-20 08:15:00', '2026-06-20 10:40:00', 180, 180, 4250.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Air India', 'AI-706', 'Kolkata', 'Delhi', '2026-06-20 19:45:00', '2026-06-20 22:10:00', 200, 200, 4950.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Go First', 'G8-707', 'Chennai', 'Hyderabad', '2026-06-20 12:30:00', '2026-06-20 13:45:00', 180, 180, 3500.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Vistara', 'UK-708', 'Chennai', 'Hyderabad', '2026-06-20 15:00:00', '2026-06-20 16:15:00', 160, 160, 4100.00, 'Scheduled')"
];

$added = 0;
foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        $added++;
    } else {
        echo "Error: " . mysqli_error($conn) . "<br>";
    }
}

echo "<h1>Setup Complete!</h1>";
echo "<p>Successfully added $added flights (full 7-day weekly schedule) to your database.</p>";
echo "<p><a href='index.php'>Return to Home</a></p>";
?>
