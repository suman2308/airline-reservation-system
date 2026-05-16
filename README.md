# AeroBook – Smart Airline Reservation System

AeroBook is a professional, production-ready airline reservation system built with PHP and MySQL. It features a modern, mobile-responsive UI and robust security measures.

## 🚀 Live Demo
[https://aerobook.rf.gd](https://aerobook.rf.gd)

## ✨ Key Features
- **User Side:**
  - Real-time flight search (Source, Destination, Date)
  - Seamless booking process with seat generation
  - User Dashboard for booking history
  - E-Ticket generation (Download/Print)
  - Profile management & Password update
  - Premium, modern UI with high-contrast split themes and Bootstrap 5
- **Admin Side:**
  - Comprehensive dashboard with analytics
  - Flight management (Add, Update status, Delete)
  - Seat availability & Schedule management
  - User & Support query management
  - Secure Admin authentication
- **Security:**
  - Prepared statements for SQL Injection prevention
  - CSRF protection on all forms
  - Password hashing (Bcrypt)
  - Input sanitization & XSS protection
  - Secure session management

## 🛠 Tech Stack
- **Frontend:** HTML5, CSS3 (Vanilla + Bootstrap 5), JavaScript
- **Backend:** PHP 8.x
- **Database:** MySQL
- **Assets:** Bootstrap Icons, Google Fonts (Inter, Outfit)

## 💻 Local Setup (XAMPP)
1. **Clone the repository** to `C:\xampp\htdocs\airline-reservation-system`.
2. **Start XAMPP** (Apache & MySQL).
3. **Create Database:** Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin) and create a database named `aerobook_db`.
4. **Import SQL:** Import the `database/aerobook.sql` file into your database.
5. **Configuration:** The project automatically detects `localhost` and uses the following credentials:
   - Host: `localhost`
   - User: `root`
   - Password: `` (empty)
6. **Access Website:** [http://localhost/airline-reservation-system](http://localhost/airline-reservation-system)

## 🌐 Deployment (InfinityFree)
1. Upload all files to the `htdocs` folder via FTP.
2. Import `database/aerobook.sql` via phpMyAdmin in your hosting panel.
3. The system will automatically detect the production environment and use the configured InfinityFree credentials in `includes/config.php`.

## 📂 Project Structure
- `/admin` - Administrative modules & dashboard
- `/css` - Custom styles and animations
- `/database` - SQL schema file
- `/includes` - Core configuration, functions, header/footer
- `/js` - Client-side logic and validations

## 👤 Admin Credentials
- **Username:** `admin`
- **Password:** `admin123`

---
Developed with ❤️ for Academic Portfolio.
