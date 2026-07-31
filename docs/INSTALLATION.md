# Installation Guide

## Prerequisites

| Requirement | Version | Notes |
|------------|---------|-------|
| PHP | 8.0+ | 8.2 recommended |
| MySQL | 8.0+ | 5.7 compatible |
| Apache | 2.4+ | With mod_rewrite, mod_headers, mod_expires |
| PHP Extensions | mysqli, gd, json, mbstring | |

## Option 1: Standard Installation (Shared Hosting)

### Step 1: Upload Files

Upload the entire project to your web root directory (e.g., `public_html/`).

### Step 2: Create the Database

```bash
# Via command line
mysql -u YOUR_USER -p -e "CREATE DATABASE aerobook_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# Import the schema
mysql -u YOUR_USER -p aerobook_db < database/aerobook.sql
```

Or via phpMyAdmin:
1. Create a new database named `aerobook_db`
2. Select the database
3. Click "Import"
4. Choose `database/aerobook.sql`
5. Click "Go"

### Step 3: Configure Database Connection

**Option A: Edit `includes/config.php`** (simplest)

Find the section matching your environment and update the credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_NAME', 'your_database_name');
define('BASE_URL', 'https://yourdomain.com/');
```

**Option B: Create a `.env` file** (recommended)

```bash
cp .env.example .env
```

Edit `.env` with your credentials:
```
DB_HOST=localhost
DB_USER=your_user
DB_PASS=your_password
DB_NAME=aerobook_db
BASE_URL=https://yourdomain.com/
```

### Step 4: Set Directory Permissions

```bash
chmod 775 uploads/
chmod 775 uploads/avatars/
chmod 775 uploads/documents/
chmod 775 logs/
```

### Step 5: Configure Base URL

Update `BASE_URL` in `includes/config.php` or `.env` to match your domain:
```php
define('BASE_URL', 'https://yourdomain.com/');
```

### Step 6: Verify Installation

Visit `https://yourdomain.com/` — you should see the AeroBook homepage.

**Admin panel:** `https://yourdomain.com/admin/`
- Username: `admin`
- Password: `admin123`

**Important:** Change the admin password immediately after first login.

## Option 2: Docker Installation

See [Docker Setup Guide](SETUP_DOCKER.md).

## Option 3: Local Development (XAMPP/WAMP)

### Step 1: Install XAMPP

Download from [apachefriends.org](https://www.apachefriends.org/) and install.

### Step 2: Copy Files

Copy the project folder to `C:\xampp\htdocs\airline-reservation-system\`.

### Step 3: Create Database

1. Start Apache and MySQL in XAMPP Control Panel
2. Visit `http://localhost/phpmyadmin`
3. Create a new database: `aerobook_db`
4. Import `database/aerobook.sql`

### Step 4: Configure

Edit `includes/config.php` — the localhost settings should work by default:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'aerobook_db');
define('BASE_URL', 'http://localhost/airline-reservation-system/');
```

### Step 5: Access

- Website: `http://localhost/airline-reservation-system/`
- Admin: `http://localhost/airline-reservation-system/admin/`

## Post-Installation Checklist

- [ ] Change default admin password
- [ ] Configure SMTP for email (see [SMTP Setup](SETUP_SMTP.md))
- [ ] Configure Demo Payment for payments (see [Payment Setup](SETUP_PAYMENT.md))
- [ ] Set `REQUIRE_EMAIL_VERIFICATION = true` in production
- [ ] Enable HTTPS (see `.htaccess` for HSTS configuration)
- [ ] Set up log rotation (see PRODUCTION.md)

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Blank page on login | Check database credentials in config.php or .env |
| 500 Server Error | Check `logs/` directory for error logs |
| "Service temporarily unavailable" | Database connection failed — check credentials |
| White screen on admin | Enable PHP error display temporarily to see the error |
| Images not uploading | Check `uploads/avatars/` permissions (775) |
| Session expired too quickly | Increase `SESSION_TIMEOUT_MINUTES` in config |
| Email not sending | Set `MAIL_MODE=log` to verify emails are generated correctly |
