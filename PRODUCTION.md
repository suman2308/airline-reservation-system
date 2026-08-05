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
4. Configure your environment — copy `.env.example` to `.env` and set your credentials:
   ```bash
   cp .env.example .env
   ```
   ```
   DB_HOST=localhost
   DB_USER=your_db_user
   DB_PASS=your_db_password
   DB_NAME=your_db_name
   BASE_URL=https://yourdomain.com/
   ```
5. `BASE_URL` is auto-detected if left empty (works behind proxies too)

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

All configuration lives in the **`.env` file** (root of the project) — copied from `.env.example`. The app reads environment variables first, then `.env`, then safe defaults.

### Database

```
DB_HOST=localhost
DB_USER=your_db_user
DB_PASS=your_db_password
DB_NAME=your_db_name
```

### Email (SMTP)

1. Set `MAIL_MODE=smtp` in `.env` (default is `log` — writes to `logs/email.log`)
2. Configure your SMTP credentials in `.env`:
   ```
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USER=your@email.com
   MAIL_PASS=your-app-password
   MAIL_ENCRYPTION=tls
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
| `flights` | Flight schedules | 84 sample flights (weekly schedule) |
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

There is no `cron/` directory in this repo — maintenance (session GC, log rotation) is handled by the web server. If you want automated database backups on shared hosting, add a cron entry like:

```bash
# Automated database backup (daily at 2 AM)
0 2 * * * mysqldump -u USER -pPASS DB_NAME > /backups/aerobook_$(date +\%Y\%m\%d).sql
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

- [ ] `REQUIRE_EMAIL_VERIFICATION=true` in `.env` for production
- [ ] `MAINTENANCE_MODE` handles update periods
- [ ] Demo Payment is used in test mode
- [ ] `MAIL_PASS` uses an app-specific password
- [ ] `display_errors` is `Off` (set in `.htaccess` / PHP config)
- [ ] HTTPS is enforced (see `.htaccess`)
- [ ] Logs directory is not web-accessible
- [ ] Uploads directory has proper permissions (775)
- [ ] Default admin password (`admin123`) is changed after first login
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
| Blank page on login | Check `.env` database credentials |
| 500 Server Error | Check `logs/` directory for error logs |
| Email not sending | Set `MAIL_MODE=log` to verify emails are being generated |
| Payment fails | Check payment configuration settings
| Images not uploading | Check `uploads/avatars/` permissions (775) |
| Session expired | Increase `SESSION_TIMEOUT_MINUTES` in `.env` |

## Support

For issues and feature requests, contact: support@aerobook.in
