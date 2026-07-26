# AeroBook — Airline Reservation & Flight Management System

AeroBook is a modern web application for searching flights, booking tickets with an interactive airplane seat map, and managing reservations. Built with PHP 8, MySQL, and modern CSS/JS, it features dynamic seat availability tracking, multi-passenger selection, real-time fare calculation, printable e-tickets, and a comprehensive admin control panel.

## Key Features

### User & Passenger Portal
- **Flight Search & Schedule**: Search flights across Indian metro routes with flexible date selection and real-time seat availability.
- **Interactive Aircraft Seat Map**: Visual 2D cabin layout (Cockpit, Business Class, Economy Class) with real-time status indicators (Vacant, Selected, Occupied).
- **Multi-Passenger Selection**: Choose 1 to 6 seats per booking with dynamic form fields and auto-filled passenger details.
- **Dynamic Fare Breakdown**: Real-time fare summary calculating base ticket prices, seat class upgrades (Business vs. Economy), and instant total pricing.
- **E-Ticket Generation**: Generate, view, and print PDF-ready digital boarding passes with QR/Ref details.
- **My Bookings & Cancellation**: View all past and upcoming flights, with single-click booking cancellation.
- **Secure Authentication**: Password hashing using `password_hash`, CSRF token validation on all forms, and session management.

### Admin Dashboard
- **System Metrics Overview**: Track total users, active flights, total bookings, and system revenue.
- **Flight Management**: Schedule new flights, update departure/arrival times, manage total capacity, and set pricing.
- **Seat Availability & Status Control**: Real-time control over seats left and flight operational status (`Scheduled`, `Delayed`, `Cancelled`, `Completed`).
- **User & Booking Auditing**: View all registered users and manage active/cancelled reservations.

---

## Tech Stack

- **Frontend**: HTML5, CSS3 (Vanilla CSS with design tokens), Bootstrap 5, Bootstrap Icons, JavaScript (ES6)
- **Backend**: PHP 8 (Procedural with Prepared Statements)
- **Database**: MySQL 8.0 (InnoDB, Foreign Key Constraints, Transactions)
- **Environment**: Apache Web Server (XAMPP / LAMP)

---

## Database Architecture Overview

The system uses a relational database schema (`aerobook_db`) designed with ACID compliance and row-level locking for seat reservations:

- `users`: Stores passenger accounts (`id`, `name`, `email`, `phone`, `password`, `created_at`).
- `admins`: Stores administrator credentials.
- `flights`: Stores flight details (`flight_id`, `flight_number`, `airline_name`, `source`, `destination`, `departure_time`, `arrival_time`, `total_seats`, `seats_available`, `price`, `status`).
- `bookings`: Stores confirmed reservations (`booking_id`, `booking_ref`, `user_id`, `flight_id`, `passenger_name`, `age`, `gender`, `travel_date`, `seat_number`, `booking_status`).

---

## Installation & Local Setup

### Prerequisites
- [XAMPP](https://www.apachefriends.org/index.html) (PHP 8.0+ and MySQL)
- Git

### Quickstart Guide

1. **Clone Repository**
   ```bash
   git clone git@github.com:suman2308/airline-reservation-system.git
   ```
   Place the project directory inside `C:\xampp\htdocs\airline-reservation-system`.

2. **Configure Database**
   - Start Apache and MySQL from XAMPP Control Panel.
   - Open phpMyAdmin (`http://localhost/phpmyadmin`).
   - Create a database named `aerobook_db`.
   - Import `database/aerobook.sql`.

3. **Launch Application**
   - User Interface: `http://localhost/airline-reservation-system`
   - Admin Panel: `http://localhost/airline-reservation-system/admin`

---

## Demo Account Credentials

| Portal | Username / Email | Password |
| :--- | :--- | :--- |
| **Admin Panel** | `admin` | `admin123` |
| **User Account** | *(Register a new account or log in)* | |

---

## Repository Structure

```
airline-reservation-system/
├── admin/                     # Admin control panel pages & actions
│   ├── dashboard.php
│   ├── manage-bookings.php
│   ├── manage-flights.php
│   └── manage-seats.php
├── css/                       # Core stylesheet & UI design system
│   └── style.css
├── database/                  # SQL schema & initial dataset
│   └── aerobook.sql
├── includes/                  # PHP helper functions & DB configuration
│   ├── config.php
│   ├── db.php
│   ├── functions.php
│   ├── header.php
│   └── footer.php
├── js/                        # Client-side JavaScript
│   └── script.js
├── booking.php                # Interactive seat map & booking page
├── booking-confirmation.php   # Reservation confirmation & refs summary
├── generate-ticket.php        # Printable digital e-ticket view
├── index.php                  # Landing page & quick flight search
└── my-bookings.php            # User reservation history
```

---

## License & Attribution

Developed for academic demonstration and portfolio showcase. Free to modify for educational purposes.
