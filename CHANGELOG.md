# Changelog

All notable changes to AeroBook are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] — 2026-07-31

### Added
- **Domestic & International Portals**: Homepage now features two prominent entry cards — 🇮🇳 Domestic Flights and 🌍 International Flights. Both lead into the same booking flow with automatic region filtering (`search-flights.php?region=domestic|international`).
- **Region-aware search**: `getFlightsByRouteRegion()` and `getTodaysFlightsRegion()` helpers filter results by domestic/international without duplicating booking logic.
- **Admin region filter**: Flight management page now supports filtering by All / Domestic / International using an SQL `IN` clause over `INDIAN_CITIES` (pagination-correct).

### Changed
- **Email System Simplified**: AeroMailer now supports only Email Verification and Password Reset. Removed `sendBookingConfirmation()` and `sendCancellation()` methods. Deleted legacy `includes/mail.php` abstraction (`sendMail`, `sendVerificationEmail`, `sendPasswordResetEmail`). Booking confirmation and cancellation no longer send emails; verification and reset flows are unchanged.
- **Email System**: now 2 HTML templates (verification, password reset) instead of 4.

### Fixed
- **Smart Fare Engine fatal error**: `searchSmartFares()` called `mysqli_fetch_assoc()` on the array returned by `getFlightsByRoute()`, causing a PHP 8 TypeError on every Smart Fare search. Now iterates the array directly with `foreach`.
- **Redirect region preservation**: `fare-results.php` validation redirects now preserve the `region` parameter.

## [1.0.0] — 2026-07-30

### Added
- **Smart Fare Engine**: Finds valid connecting flights (two-leg itineraries) when direct flights are unavailable or expensive. Scores by price (40%), travel time (25%), stops (20%), layover (15%). Shows "Save ₹X" labels and "Why This Is Cheaper" explanations.
- **Travel Hub**: Centralized dashboard with upcoming trip timeline, travel statistics, milestones (First Flight, 5 Trips, Window Seat Lover), saved routes, price watches, and profile completion bar.
- **Travel Calendar**: Monthly calendar view of user trips with navigation and trip details.
- **Travel Documents**: Centralized access to boarding passes, e-tickets, and trip summaries.
- **Admin Operations Center**: 12-page admin panel with operations dashboard, airline analytics, route analytics, CSV reports, activity log, and system diagnostics.
- **Admin Analytics**: Revenue by month/route/airline, occupancy analysis, top customers, booking/cancellation trends.
- **Admin Activity Log**: Searchable audit trail with action filter, date range, pagination.
- **Admin Diagnostics**: Database status, PHP info, application stats, 7 automated data quality checks.
- **Integration Libraries**: `AeroMailer`, `AeroQR`, `AeroPDF`, `AeroICS`, `AeroNotifications`, `AeroUpload` — all with graceful fallback modes.
- **Email System**: 2 HTML email templates (verification, password reset) via PHPMailer with log fallback.
- **QR Code Generator**: Scannable SVG QR codes encoding booking confirmation URLs. Standard byte encoding, version 3 (29×29).
- **PDF Generator**: Boarding pass, invoice, trip summary as HTML/PDF via tFPDF (optional).
- **Demo Payment**: Simulated payment processing with transaction recording. cURL mode available.
- **ICS Calendar**: RFC 5545 compliant calendar files + Google Calendar links with 24h alarm.
- **In-App Notifications**: Create, fetch, mark read/unread, count, header dropdown, notification center page.
- **Avatar Upload**: MIME validation via `finfo`, WebP conversion, dimension checks, random filenames, old-file cleanup.
- **Request-Level Caching**: `AeroCache` for expensive queries with TTL expiration.
- **Environment Configuration**: `.env` file support with cascade: env var → .env → hardcoded default.
- **Docker Support**: Dockerfile (PHP 8.2 Apache + mysqli/GD) + docker-compose.yml (MySQL 8.0).
- **Security Hardening**: CSRF on all forms + admin GET deletes, rate limiting, account locking after 5 failed attempts, session timeout, security headers (CSP, XSS, clickjacking).
- **Error Handling**: Centralized error handler with logging. No stack traces in production. 404/403/500 pages.
- **Comprehensive Documentation**: README, ARCHITECTURE, CONTRIBUTING, SECURITY, CHANGELOG, PRODUCTION.md, `.env.example`, and docs/ guides.
- **UI Polish**: Focus-visible outlines, loading spinner overlay, animated loading dots, table header styling, inline validation error styles, reduced motion support, print styles.

### Changed
- **Architecture**: Complete cleanup — extracted reusable database helpers to `includes/helpers.php`, centralized validation to `includes/Validation.php`, centralized security to `includes/Security.php`.
- **Configuration**: Environment-aware detection (localhost vs production vs Docker). All constants overridable via `.env`.
- **Booking Flow**: Multi-step booking (Passengers → Seats → Add-ons → Review & Pay) with progress indicator.
- **Seat Map**: Improved interactive 2D cabin layout with Business/Economy distinction, window/aisle/middle indicators, exit-row legroom labels.
- **Authentication**: Email verification, forgot password, remember-me tokens, login history, session management, account locking.
- **Profile Management**: Avatar upload/removal, password change, active sessions, login history, account deletion.
- **Admin Panel**: Complete redesign — operations dashboard, analytics, route analytics, reports, activity log, diagnostics.
- **CSS**: Organized with CSS variables for theming. Added lazy loading, print, and reduced motion styles.
- **Database Schema**: Added 13 indexes. Added tables: `email_verifications`, `password_resets`, `remember_tokens`, `login_history`, `user_sessions`, `saved_passengers`, `booking_addons`, `saved_routes`, `notifications`, `transactions`, `price_watches`, `admin_activity_log`.

### Fixed
- CSRF vulnerability in admin GET-based delete/cancel operations — now uses one-time tokens
- Broken links to forgot-password.php, verify-email.php, travel-documents.php, travel-calendar.php — all created
- Missing 404/403/500 error pages — created with consistent branding
- Stale `dummy.db` file in web root — removed
- Duplicated `.btn-outline-accent` CSS across 4 files — consolidated in master stylesheet
- Loading overlay trap in booking flow — spinner now properly dismissable
- Missing `htmlspecialchars()` on booking ref in admin ARIA label

### Removed
- `dummy.db` — stale SQLite test database in web root
- Inline `.btn-outline-accent` CSS — now in master stylesheet
- Legacy card payment validation JS — replaced by Demo payment integration
- Redundant `brand-text` CSS declaration
- Dead `$label` parameter from `deleteLink()` function

### Security
- Session hardening: HTTPOnly, SameSite=Lax, secure flag on HTTPS, strict mode
- CSRF on all state-changing operations
- Rate limiting on authentication endpoints
- Account locking after 5 failed attempts
- File upload MIME validation via `finfo`
- Security headers: CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy
- Prepared statements for ALL SQL queries

## [0.9.0] — 2026-07-15

### Added
- Initial admin panel with login, flight management, booking management
- User registration and login with CSRF protection
- Basic flight search and booking
- Interactive seat map (initial version)
- E-ticket / boarding pass generation
- Flight status tracker
- Multi-passenger booking

### Security
- Password hashing via `password_hash()`
- Basic CSRF protection on forms
- Prepared statements for SQL queries

## [0.1.0] — 2026-06-01

### Added
- Initial project scaffold
- Database schema with users, flights, bookings, contacts, admins
- Sample flight data (56 flights, weekly schedule)
- Basic PHP structure with config, functions, header, footer
