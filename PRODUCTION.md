# AeroBook – Production Deployment Guide

## Architecture Overview

AeroBook is a PHP 8.2+ / MySQL 8.0 airline reservation system designed for shared hosting environments. It follows a modular architecture with clear separation between presentation (PHP pages), business logic (`includes/`), and database (`database/`).

```
aerobook/
├── admin/           # Admin operations center
├── css/             # Stylesheets
├── database/        # SQL schema + sample data
├── includes/        # Core libraries (config, helpers, auth, integrations)
├── js/              # JavaScript
├── lib/             # Optional: PHPMailer, tFPDF (manual install)
├── logs/            # Application logs (auto-created)
├── uploads/         # User uploads (avatars, documents)
│   ├── avatars/
│   └── documents/
└── *.php            # Public pages (index, search, booking, etc.)
```

## Requirements

| Requirement | Version |
|------------|---------|
| PHP | 8.0 or higher (8.2+ recommended) |
| MySQL | 8.0 or higher (5.7 compatible) |
| Apache | 2.4+ with mod_rewrite, mod_headers, mod_expires |
| Disk | 100MB minimum (50MB for code, rest for uploads/logs) |
| PHP Extensions | `mysqli`, `gd`, `json`, `mbstring` |

## Installation

### 1. Standard Hosting (Shared Hosting)

```bash
# Upload files to your web root
# Example: /home/username/public_html/
# Then visit: https://yourdomain.com/
```

1. Upload all files to your web root directory
2. Make directories writable:
   ```bash
   chmod 775 uploads/ uploads/avatars/ uploads/documents/ logs/
   ```
3. Import the database schema:
   ```bash
   mysql -u YOUR_USER -p YOUR_DATABASE < database/aerobook.sql
   ```
4. Configure `includes/config.php` with your database credentials
5. Set `BASE_URL` to your domain URL

### 2. Docker Deployment

```bash
# Build and start
docker-compose up -d --build

# Access at: http://localhost:8080

# Seed the database (already done via init script)
# Default admin: admin / admin123

# View logs
docker-compose logs -f app
```

### 3. Manual Apache Setup

```bash
# Ensure mod_rewrite, mod_headers, mod_expires are enabled
sudo a2enmod rewrite headers expires
sudo systemctl restart apache2
```

## Configuration

### Database (`includes/config.php`)

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');
define('BASE_URL', 'https://yourdomain.com/');
```

### Email (SMTP)

1. Set `MAIL_MODE` to `'smtp'` in `includes/config.php`
2. Configure your SMTP credentials:
   ```php
   define('MAIL_HOST', 'smtp.gmail.com');
   define('MAIL_PORT', 587);
   define('MAIL_USER', 'your@email.com');
   define('MAIL_PASS', 'your-app-password');
   define('MAIL_ENCRYPTION', 'tls');
   ```
3. For Gmail: use an App Password (not your regular password)

### Payment (Demo Payment)

AeroBook uses a **Demo Payment** system for testing. No real payment gateway is required.

The demo payment page simulates successful payment processing. All transaction records are stored in the `transactions` table. The system supports both simulated and cURL-based modes.

**Important:** No credit card information is ever stored or logged.

### Uploads

Directories that must be writable:
- `uploads/avatars/` — Profile pictures
- `uploads/documents/` — Generated PDFs and invoices
- `logs/` — Application log files

## Database

### Schema

The complete schema is in `database/aerobook.sql`. Key tables:

| Table | Purpose | Records |
|-------|---------|---------|
| `users` | User accounts | Core |
| `flights` | Flight schedules | 84 sample flights (12/day) |
| `bookings` | Booking records | User bookings |
| `notifications` | In-app notifications | Auto-created |
| `transactions` | Payment records | Demo payment transactions |

### Indexes

The schema includes indexes for:
- `idx_flights_route` — Flight search by source/destination
- `idx_flights_number` — Flight lookups by number
- `idx_flights_departure` — Today's flights queries
- `idx_bookings_user_status` — User booking history
- `idx_bookings_flight_date` — Seat availability checks
- Plus indexes on all foreign key columns

## Cron Jobs (Optional)

```bash
# Clean up old notifications (daily)
0 3 * * * php /path/to/aerobook/cron/cleanup.php

# Generate sitemap (weekly)
0 5 * * 0 php /path/to/aerobook/cron/sitemap.php
```

## Backup Strategy

### Database
```bash
# Automated backup via cron
0 2 * * * mysqldump -u USER -pPASS DB_NAME > /backups/aerobook_$(date +\%Y\%m\%d).sql

# Keep last 30 days
0 3 * * * find /backups/ -name "aerobook_*.sql" -mtime +30 -delete
```

### Uploads
```bash
# Backup uploads directory
0 4 * * * tar -czf /backups/uploads_$(date +\%Y\%m\%d).tar.gz /path/to/aerobook/uploads/
```

## Security Checklist

- [ ] `REQUIRE_EMAIL_VERIFICATION` set to `true` in production
- [ ] `MAINTENANCE_MODE` handles update periods
- [ ] Demo Payment is used in test mode
- [ ] `MAIL_PASS` uses an app-specific password
- [ ] `display_errors` is `Off` in production
- [ ] HTTPS is enforced (see `.htaccess`)
- [ ] Logs directory is not web-accessible
- [ ] Uploads directory has proper permissions (775)
- [ ] Default admin password (`admin123`) is changed
- [ ] File upload extensions are restricted

## Performance

### Caching
- Static query cache via `includes/Cache.php` (request-level)
- Browser caching via `.htaccess` (1 year for assets)
- CDN for Bootstrap, Bootstrap Icons, Google Fonts

### Optimization
- All SQL queries use prepared statements
- Analytics queries use aggregate SQL (no N+1)
- Database has proper indexes for all query patterns
- CSS/JS loaded from CDN (not bundled)
- Images should be served as WebP where possible

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Blank page on login | Check `includes/config.php` database credentials |
| 500 Server Error | Check `logs/` directory for error logs |
| Email not sending | Set `MAIL_MODE=log` to verify emails are being generated |
| Payment fails | Check payment configuration settings
| Images not uploading | Check `uploads/avatars/` permissions (775) |
| Session expired | Increase `SESSION_TIMEOUT_MINUTES` in config |

## Support

For issues and feature requests, contact: support@aerobook.in
