# AeroBook – Airline Reservation System

AeroBook is a web-based airline reservation system built using PHP and MySQL. It provides a complete solution for users to search and book flights, and for administrators to manage airline operations. This project was developed to demonstrate full-stack web development skills.

## 🚀 Live Website
[http://aerobook.rf.gd](http://aerobook.rf.gd)

## ✨ Project Features

### For Users (Passengers):
- **Flight Search:** Easily search for flights by selecting the source, destination, and travel date.
- **Ticket Booking:** Smooth booking process with instant confirmation.
- **My Bookings:** A dedicated dashboard for users to view their past and upcoming flight bookings.
- **E-Tickets:** Generate and download a digital boarding pass/e-ticket after booking.
- **Profile Management:** Users can update their profile information and password.

### For Administrators:
- **Admin Dashboard:** Overview of total flights, users, and bookings.
- **Flight Management:** Add new flights, update existing flight details, or remove cancelled flights.
- **User Management:** View all registered users and their details.
- **Secure Access:** Separate login portal for admin functionality.

## 🛠 Tech Stack Used
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5 (for responsive design)
- **Backend:** PHP 8
- **Database:** MySQL

## 💻 How to Run Locally
1. **Setup Environment:** Install [XAMPP](https://www.apachefriends.org/index.html) on your computer.
2. **Clone the Project:** Place the project folder into `C:\xampp\htdocs\`. Rename it to `airline-reservation-system`.
3. **Start Servers:** Open the XAMPP Control Panel and start **Apache** and **MySQL**.
4. **Setup Database:**
   - Go to [http://localhost/phpmyadmin](http://localhost/phpmyadmin) in your browser.
   - Create a new database named `aerobook_db`.
   - Import the `database/aerobook.sql` file provided in the project folder.
5. **Run the Project:** Open [http://localhost/airline-reservation-system](http://localhost/airline-reservation-system) to view the site.

## 📂 Project Structure
- `/admin` - Contains files for the admin panel
- `/css` - Custom stylesheets
- `/database` - Contains the `aerobook.sql` file for database setup
- `/includes` - Reusable PHP components (header, footer, database connection)
- `/js` - JavaScript files for interactive elements

## 👤 Login Credentials for Testing

**Admin Account:**
- **Username:** `admin`
- **Password:** `admin123`

*(You can also register a new user account on the website to test the user features).*

---
**Note for Evaluator:** The database connection is configured to work out-of-the-box on XAMPP (localhost with username `root` and no password) and has been deployed live for easy access.
