# Configuration Guide

## Configuration Priority

AeroBook loads configuration in this order (higher priority wins):

1. **System environment variables** (e.g., Docker `environment:` block)
2. **`.env` file** (in the project root)
3. **Hardcoded defaults** in `includes/config.php`

This means you can:
- Override any value via environment variables for Docker deployment
- Use a `.env` file for shared hosting without modifying PHP files
- Rely on defaults if no configuration is provided

## Database Configuration

```env
DB_HOST=localhost           # MySQL host
DB_USER=root                # MySQL username
DB_PASS=                    # MySQL password
DB_NAME=aerobook_db         # MySQL database name
```

## Application URL

```env
BASE_URL=http://localhost/airline-reservation-system/
SITE_NAME=AeroBook
SITE_TAGLINE="Smart, Fast and Easy Flight Booking Platform"
```

**Important:** `BASE_URL` must include the trailing slash. This value is used for:
- Redirect URLs
- Asset paths (CSS, JS, images)
- Email links (verification, password reset)
- QR code URLs

## Email (SMTP)

```env
MAIL_MODE=log                # 'log' (default) or 'smtp'
MAIL_FROM=noreply@aerobook.in
MAIL_HOST=smtp.gmail.com     # SMTP server
MAIL_PORT=587                # SMTP port (587 for TLS, 465 for SSL)
MAIL_USER=                   # SMTP username
MAIL_PASS=                   # SMTP password
MAIL_ENCRYPTION=tls          # 'tls' or 'ssl'
```

**Modes:**
- `log` — Emails are written to `logs/email.log`. No actual sending.
- `smtp` — Emails are sent via SMTP using PHPMailer (requires manual install of PHPMailer library).

See [SMTP Setup Guide](SETUP_SMTP.md) for detailed instructions.

## Payment (Demo Payment)

```env
PAYMENT_MODE=simulated          # 'simulated' (default) or 'curl'
```

**Simulated Mode (default):** No external API calls. Payment is processed entirely within the application. Suitable for development and testing.

**cURL Mode:** Sends a real cURL request to a configurable payment endpoint. Requires additional configuration.

**Important:** No credit card information is ever stored or logged.

See [Payment Setup Guide](SETUP_PAYMENT.md) for detailed instructions.

## Security

```env
REQUIRE_EMAIL_VERIFICATION=false
SESSION_TIMEOUT_MINUTES=30
```

- `REQUIRE_EMAIL_VERIFICATION` — Set to `true` in production to prevent login before email verification
- `SESSION_TIMEOUT_MINUTES` — Session idle timeout in minutes

## Uploads

```env
MAX_AVATAR_SIZE=2097152                 # 2MB in bytes
ALLOWED_AVATAR_EXTENSIONS=jpg,jpeg,png,gif,webp
```

## Maintenance Mode

```env
MAINTENANCE_MODE=false
```

Set to `true` during updates or maintenance. During maintenance mode, all pages (except admin with override) show a maintenance message.

## Configuration via `.env`

1. Copy the example file:
   ```bash
   cp .env.example .env
   ```
2. Edit `.env` with your values
3. The file is read on every request (it's <1KB, <1ms overhead)
4. Never commit `.env` to version control! It contains secrets.

## Configuration via Environment Variables

For Docker or cloud hosting, set environment variables directly:

```bash
export DB_HOST=production-db.internal
export DB_USER=aerobook
export DB_PASS=secure_password
```
```

## Complete Configuration Reference

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_HOST` | `localhost` | MySQL hostname |
| `DB_USER` | `root` | MySQL username |
| `DB_PASS` | `` | MySQL password |
| `DB_NAME` | `aerobook_db` | MySQL database name |
| `BASE_URL` | `http://localhost/...` | Application base URL (with trailing /) |
| `SITE_NAME` | `AeroBook` | Site name used in emails and titles |
| `SITE_TAGLINE` | `Smart, Fast and Easy...` | Site tagline |
| `SESSION_TIMEOUT_MINUTES` | `30` | Session idle timeout |
| `REQUIRE_EMAIL_VERIFICATION` | `false` | Require email verification for login |
| `ALLOWED_AVATAR_EXTENSIONS` | `jpg,jpeg,png,gif,webp` | Allowed avatar file types |
| `MAX_AVATAR_SIZE` | `2097152` | Max avatar size in bytes |
| `MAINTENANCE_MODE` | `false` | Enable maintenance mode |
| `PAYMENT_MODE` | `simulated` | Payment mode: 'simulated' or 'curl' |
| `MAIL_MODE` | `log` | Email mode: 'log' or 'smtp' |
| `MAIL_FROM` | `noreply@aerobook.in` | From email address |
| `MAIL_HOST` | `` | SMTP hostname |
| `MAIL_PORT` | `587` | SMTP port |
| `MAIL_USER` | `` | SMTP username |
| `MAIL_PASS` | `` | SMTP password |
| `MAIL_ENCRYPTION` | `tls` | SMTP encryption method |
| `IS_DOCKER` | `false` | Set to `true` in Docker environment |
