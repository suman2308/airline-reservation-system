<?php
require_once 'includes/config.php';

$queries = [
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('IndiGo', '6E-501', 'Mumbai', 'Bangalore', '2026-05-21 10:00:00', '2026-05-21 11:45:00', 180, 180, 4999.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Air India', 'AI-202', 'Delhi', 'Mumbai', '2026-05-21 14:00:00', '2026-05-21 16:15:00', 200, 200, 5500.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('SpiceJet', 'SG-303', 'Bangalore', 'Delhi', '2026-05-21 08:30:00', '2026-05-21 11:15:00', 180, 180, 4200.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Vistara', 'UK-404', 'Kolkata', 'Chennai', '2026-05-21 17:00:00', '2026-05-21 19:30:00', 160, 160, 5800.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('IndiGo', '6E-101', 'Mumbai', 'Delhi', DATE_ADD(NOW(), INTERVAL 5 DAY) + INTERVAL 10 HOUR, DATE_ADD(NOW(), INTERVAL 5 DAY) + INTERVAL 12 HOUR, 180, 180, 4500.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Air India', 'AI-102', 'Delhi', 'Mumbai', DATE_ADD(NOW(), INTERVAL 5 DAY) + INTERVAL 14 HOUR, DATE_ADD(NOW(), INTERVAL 5 DAY) + INTERVAL 16 HOUR, 200, 200, 5000.00, 'Scheduled')",
    "INSERT INTO flights (airline_name, flight_number, source, destination, departure_time, arrival_time, total_seats, seats_available, price, status) VALUES ('Go First', 'G8-514', 'Bangalore', 'Pune', '2026-05-22 14:30:00', '2026-05-22 16:10:00', 180, 180, 3450.00, 'Scheduled')"
];

$added = 0;
foreach ($queries as $q) {
    if (mysqli_query($conn, $q)) {
        $added++;
    }
}

echo "<h1>Setup Complete!</h1>";
echo "<p>Successfully added $added future flights to your live database.</p>";
echo "<p>You can now go back to your website and search for flights on <strong>2026-05-21</strong>, <strong>2026-05-22</strong>, or <strong>" . date('Y-m-d', strtotime('+5 days')) . "</strong>.</p>";
echo "<p><a href='index.php'>Return to Home</a></p>";
?>
