# Changelog

All notable changes to AeroBook are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed
- **Seed flight dates were hardcoded** (`2026-08-02` … `2026-08-08` in `database/aerobook.sql`): any install after ~Aug 8, 2026 had **zero upcoming flights**, so search, booking, check-in, and the Travel Hub's upcoming trips were empty. The seed now uses **relative dates** (`CURDATE()+1` … `+7` days), so a fresh install always ships a full week of upcoming flights regardless of when it's deployed.

### Changed
- **README screenshots refreshed**: replaced the four-image gallery with 14 live captures covering the public site, Travel Hub, and Admin Operations Center.
- **README now documents every page**: added a full Site Map section (24 passenger pages + 16 admin pages) and expanded the screenshot gallery to 35 live captures covering all major pages — including previously undocumented features like **Online Check-in**, **Cancel Booking**, **Flight Details**, and the **Notifications Center**.
- **Smart Fare Engine docs corrected to match the code**: weights were listed as 40/25/20/15 and layover 1h–6h; the real constants (`includes/FareEngine.php`) are **50/25/15/10** with a **90-min min / 8-h max** layover window, 1 connection max, and a ₹200 savings-badge threshold.
- **AviationStack live-data retrieval documented**: added a step-by-step "how it works" section to the README and `docs/CONFIGURATION.md` (client → paginated cURL fetch → mapper → `aviation_*` upsert → `api_sync_logs`), confirming the key is never exposed and sync is admin-triggered only.

## [1.3.1] — 2026-08-04

### Fixed
- **Logout threw a 500 error** (critical, found in final audit): both `logout.php` and `admin/logout.php` called `redirect()`, `logInfo()`, and `logAdminAction()` without including `includes/functions.php`/`helpers.php` — so logging out crashed with "Call to undefined function". Both now include the required helpers; verified live (302 redirect, session cleared).
- **Smoke tests strengthened**: the suite now asserts that user and admin logout return 302 (no 500) and that protected pages redirect after logout — so this class of regression is caught automatically.

## [1.3.0] — 2026-08-04

### Added
- **All-in-one Docker deployment (no external database required)**: the `Dockerfile` now bundles MariaDB inside the app container. `docker/entrypoint.sh` boots it, creates the `aerobook` user + `aerobook_db`, and auto-seeds `database/aerobook.sql` (+ `aviationstack.sql`) on first boot — deployable to Render with one click and zero database setup.
- **`docker/mariadb.cnf`** — lean MariaDB config (32M buffer pool, 30 connections, `performance_schema=OFF`) so Apache + PHP + DB fit comfortably in a 512 MB free-tier instance.
- **`render.yaml`** — DB env vars pre-filled for all-in-one mode (`DB_HOST=127.0.0.1`, `DB_USER=aerobook`, `DB_PASS=aerobook_secret`, `DB_NAME=aerobook_db`); override them in the dashboard to switch to an external persistent MySQL.
- **`.dockerignore`** — now allows `database/*.sql` through so the entrypoint can seed.

### Notes
- ⚠️ Render free tier has an **ephemeral filesystem** + spin-down: all runtime data (users, bookings) resets on restart/redeploy. The DB is re-seeded fresh each boot — ideal for demos/submissions. Use an external MySQL for persistent data.

## [1.2.1] — 2026-08-04

### Added
- **Automated smoke test suite** (`tests/smoke.php`): dependency-free (no PHPUnit/Composer). Verifies page availability, auth guards, admin + user login flows, registration, flight search, booking validation, and DB integrity — 44 checks, exit code 0/1. Creates and cleans up its own test user.
- **Contacts CSV export**: `reports.php` now supports `type=contacts`; the Support Queries page's Export button was previously pointing at `type=bookings` and exporting the wrong data.

### Removed
- `includes/RenderHelper.php` — dead code; `renderFlightCard()` had zero call sites. Removed its require from `includes/helpers.php`.

## [1.2.0] — 2026-08-04

### Added
- **Live AviationStack integration configured**: `AVIATIONSTACK_API_KEY` set in `.env` and `AVIATIONSTACK_ENABLED=true`. Live connection verified (3,200 airports synced into the `aviation_*` tables). Admin → Data Synchronization now reports "API Key: Configured" and shows synced record counts.
- **Bundled CA certificate** (`includes/cacert.pem`): Windows PHP builds without a system CA bundle can now reach `api.aviationstack.com` over HTTPS (previously failed with an OpenSSL "unable to get local issuer certificate" error).

### Fixed
- **notifications.php crashed** with "Call to undefined function timeSince()" — the helper lived in `includes/Auth.php`, which that page never loads. Moved `timeSince()` to `includes/functions.php` (loaded on every page) and removed the duplicate from `Auth.php`.
- **user-dashboard.php crashed** with "mysqli_num_rows(): Argument must be of type mysqli_result, array given" — `getUserBookings()` returns an array but the page treated it as a statement result. Now iterates the array directly.
- **`curl_close()` deprecation** in `AviationStackClient.php` (PHP 8.5): guarded so it only runs on PHP < 8.0.

## [1.1.1] — 2026-08-04

### Fixed
- **Admin panel fully broken after login** (critical): all 13 top-level admin pages used `__DIR__ . '/../../includes/...'`, which resolved two levels *above* the project root and fatally failed. Corrected to `../includes/`. The admin dashboard, analytics, reports, flight/booking/user management, route analytics, and aviation sync pages all render again.
- **Admin login 500 "Commands out of sync"**: the auth SELECT statement was still open when `logAdminAction()` ran a new query on the same connection. The statement is now closed before logging.
- **Bookings were impossible with no add-ons**: `validateAddonCosts()` used strict `in_array(..., true)` against integer arrays while the handler passed `floatval()` values, so `0.0` never matched and every booking was rejected with "Invalid baggage add-on amount." Values are now cast to int.
- **Admin default login broken**: the seeded `admin` hash in `database/aerobook.sql` didn't match `admin123`. Replaced with a valid `password_hash()` output; live DB updated too.
- **Flight status "Live Operations" cards**: arrival status rendered raw CSS classes as text (`bg-success-subtle text-success…`). Sample array indices were mismatched — restructured so status, class, terminal, and belt each use their own index.
- **Promo discount tampering**: the booking handler overwrote the server-calculated discount with the client-submitted `promo_discount` value. The server value is now authoritative; client values are ignored.
- **Promo discount not persisted**: `promo_code`/`promo_discount` are now stored on each booking (`bookings` table) and shown on the confirmation page, so the total paid matches the wizard's discounted total.
- **Minor**: deprecated `mysqli_ping()` replaced in `health.php`/`admin/diagnostics.php`; duplicated `mysqli_stmt_close()` removed in `admin/manage-flights.php`; `Logger.php` now keeps `message`/`file`/`line` so exception details actually reach the log; duplicate `IS_ADMIN_PANEL` constant definition guarded.

## [1.1.0] — 2026-08-04

### Changed
- **Dark-only theme**: The light/dark toggle was removed everywhere. `data-theme="dark"` is now pinned by an inline `<head>` script on every entry point (public header, admin header, admin login, ticket page, error pages), so the site is always dark with no flash of light mode.
- **Navbar trimmed**: About and Contact links removed from the app navbar (now Home · Search Flights · Flight Status only). About/Contact pages render a bare navbar with a centered "← Back to Home" item so Login/Register stay anchored right.
- **Dark-mode contrast fixes**: mobile menu panels (landing + app), hero gradient/text, pricing cards, FAQ items, benefit-card headings, stat tiles, kicker pills, and CTA buttons all corrected so no text washes out on dark surfaces.
- **Flight status page**: active tab now persists after submit (route searches stay on the Route tab); plane icons removed from result cards; `bg-primary` result headers/badges use a readable deep violet in dark mode.
- **Admin login**: wider card, light-mode card styling, black typed text (incl. autofill), header visible on mobile, tighter spacing between the Login button and "Back to Website", button text shortened to "Login".
- **Prices**: `formatPrice()` now rounds to whole rupees (no more `.00` decimals).

### Removed
- `flight-results.php` — orphaned legacy page; nothing linked to it (search now uses `fare-results.php`).
- Dead `.theme-toggle` / `.theme-toggle-light` CSS in `css/style.css` and `css/aerobook.css`.
- Dead `.hero-video` selector in the print media query (the hero video was removed earlier).
- Theme-system JS (localStorage persistence, icon sync, system-preference listener) from `js/script.js`.

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
