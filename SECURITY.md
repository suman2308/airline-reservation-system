# Security Policy

## Supported Versions

| Version | Supported          |
|---------|--------------------|
| 1.0.x   | ✅ Active support  |
| < 1.0   | ❌ Not supported   |

## Reporting a Vulnerability

We take the security of AeroBook seriously. If you discover a security vulnerability, please:

1. **Do NOT open a public GitHub issue** — this could allow attackers to exploit the vulnerability before it's fixed.
2. **Email the details to**: `support@aerobook.in` with the subject "Security Vulnerability"
3. **Include**:
   - Description of the vulnerability
   - Steps to reproduce
   - Affected version(s)
   - Potential impact
   - Suggested fix (if any)

You'll receive a response within 48 hours. We'll work with you to:
- Confirm the vulnerability
- Develop and test a fix
- Release a patched version
- Credit you for the discovery (if desired)

## Security Practices Implemented

### Authentication & Authorization
- Password hashing via `password_hash(PASSWORD_DEFAULT)` — never stored in plaintext
- Account locking after 5 failed login attempts (30-minute lock)
- Rate limiting on login, registration, password reset, and admin login
- Session regeneration on login/logout
- HTTPOnly, SameSite=Lax, Secure (when HTTPS) session cookies
- Session timeout validation (configurable, default 30 minutes)
- Email verification (configurable via `REQUIRE_EMAIL_VERIFICATION`)
- Remember-me tokens with rotation and secure hashing

### CSRF Protection
- CSRF token on every POST form
- One-time tokens (15-minute expiry) on admin GET-based delete operations
- Token validation on all state-changing requests

### SQL Injection Prevention
- 100% prepared statements via `mysqli_prepare()` + `mysqli_stmt_bind_param()`
- No raw SQL concatenation with user input
- Table/column name sanitization in generic functions

### XSS Prevention
- `htmlspecialchars()` on all dynamic output
- Content Security Policy via `.htaccess` + `emitSecurityHeaders()`
- Input validation on all form fields

### File Upload Security
- MIME type validation via `finfo` (not extension-based)
- Image dimension validation
- File size limits (2MB for avatars)
- Random filenames (no user-controlled paths)
- WebP conversion for uploaded images
- Automatic cleanup of replaced avatars

### Security Headers
- `Content-Security-Policy` — restricts scripts, styles, fonts, images to trusted origins
- `X-Frame-Options: SAMEORIGIN` — prevents clickjacking
- `X-Content-Type-Options: nosniff` — prevents MIME sniffing
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy` — disallows camera, microphone, geolocation, payment
- `X-XSS-Protection: 1; mode=block`

### Error Handling
- No SQL errors or stack traces exposed to users (in production)
- Centralized error handler with logging
- Graceful 404/403/500 error pages
- Maintenance mode support

### Logging & Monitoring
- All authentication failures logged via `logSecurity()`
- All critical database operations logged via `logError()`
- All admin actions logged in `admin_activity_log`
- No passwords, card numbers, or sensitive data logged
- Daily-rotated log files with `.htaccess` protection

## Configuration Checklist for Production

- [ ] Set `REQUIRE_EMAIL_VERIFICATION = true` in config or .env
- [ ] Set `MAINTENANCE_MODE = false` (only true during updates)
- [ ] Configure HTTPS and enable HSTS in `.htaccess`
- [ ] Change default admin password (`admin/admin123`)
- [ ] Set strong SMTP credentials (app-specific password)
- [ ] Use production Demo Payment keys (not test)
- [ ] Set `display_errors = Off` (already done via .htaccess)
- [ ] Restrict file upload extensions (already done via config)
- [ ] Set proper directory permissions (775 for uploads, logs)
- [ ] Enable CSP in `.htaccess`
