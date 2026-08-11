# ✈️ AeroBook — Airline Reservation & Flight Operations Platform

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&style=flat-square)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&style=flat-square)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&style=flat-square)](https://getbootstrap.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&style=flat-square)](Dockerfile)
[![PRs Welcome](https://img.shields.io/badge/PRs-Welcome-brightgreen?style=flat-square)](CONTRIBUTING.md)
[![License](https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Stable-10b981?style=flat-square)](#)
[![GitHub Stars](https://img.shields.io/github/stars/suman2308/airline-reservation-system?style=social)](https://github.com/suman2308/airline-reservation-system)

> **AeroBook** is a full-stack airline reservation and flight operations management platform. It provides an end-to-end flight booking engine featuring a real-time 2D aircraft seat map, Smart Fare Engine with connecting flight discovery, a Travel Hub for journey management, and an airline Operations Center for administration. Built with PHP 8+, MySQL, and Bootstrap 5.

[🚀 Quick Start](#-quick-start) •
[📖 Documentation](#-documentation) •
[🏛️ Architecture](#-architecture) •
[🗺️ Roadmap](#-roadmap) •
[🤝 Contributing](#-contributing)

---

## 📊 Feature Comparison

| Feature | AeroBook | Basic Booking Systems | Enterprise Solutions |
|---------|:--------:|:---------------------:|:--------------------:|
| **Interactive Seat Map** | ✅ Real-time 2D cabin | ❌ Text-only | ✅ |
| **Smart Fare Engine** (Connecting Flights) | ✅ Scoring + badges | ❌ Direct only | ✅ |
| **Multi-Passenger Booking** (1–6) | ✅ Dynamic forms | ✅ | ✅ |
| **In-Flight Add-Ons** | ✅ Baggage + Meals | ❌ | ✅ |
| **Promo Code Engine** | ✅ Real-time discounts | ❌ | ✅ |
| **QR Code Boarding Pass** | ✅ Scannable SVG | ❌ | ✅ |
| **Travel Hub Dashboard** | ✅ Stats + milestones | ❌ | ✅ |
| **Email Integration** | ✅ PHPMailer | ❌ | ✅ |
| **Payment** | ✅ Demo (Simulated) | ❌ | ✅ |
| **Admin Operations Center** | ✅ 14-page panel | ❌ Basic CRUD | ✅ |
| **Airline Analytics** | ✅ Revenue + trends | ❌ | ✅ |
| **CSV Reports** | ✅ 6 report types | ❌ | ✅ |
| **Activity Log / Audit Trail** | ✅ Search + filter | ❌ | ✅ |
| **Docker Support** | ✅ docker-compose | ❌ | ✅ |
| **Shared Hosting Compatible** | ✅ Zero dependencies | ✅ | ❌ |
| **No Framework Required** | ✅ Pure PHP 8+ | ✅ | ❌ |

---

## 🏛️ Architecture

### System Overview

```mermaid
graph TB
    subgraph Browser["🌐 Browser"]
        U[User]
        A[Admin]
    end

    subgraph WebServer["🖥️ Apache Web Server"]
        direction LR
        PHP[PHP Files]
        CSS[CSS/JS Assets]
        HT[.htaccess Security]
    end

    subgraph Application["⚙️ PHP Application Layer"]
        direction LR
        CFG[Config]
        FN[Functions]
        HL[Helpers]
        AUTH[Auth]
        SEC[Security]
        VAL[Validation]
        LOG[Logger]
        ERR[Error Handler]
        CACHE[Cache]
    end

    subgraph Integrations["🔌 Integration Layer"]
        ML[Mailer]
        QR[QRCode]
        PDF[PDF]
        PAY[Payment]
        ICS[ICS]
        NOT[Notifications]
        AV[Avatar]
    end

    subgraph Database["🗄️ MySQL Database"]
        USR[users]
        FL[flights]
        BK[bookings]
        NT[notifications]
        TX[transactions]
        LH[login_history]
        SS[sessions]
        AL[admin_activity_log]
    end

    U --> PHP
    A --> PHP
    PHP --> HT
    PHP --> CSS
    PHP --> Application
    PHP --> Integrations
    Application --> Database
    Integrations --> Database

    classDef browser fill:#1a1a2e,color:#fff,stroke:#16213e
    classDef server fill:#0f3460,color:#fff,stroke:#16213e
    classDef app fill:#024dec,color:#fff,stroke:#033bb2
    classDef int fill:#10b981,color:#fff,stroke:#059669
    classDef db fill:#7c3aed,color:#fff,stroke:#6d28d9
    class U,A browser
    class PHP,CSS,HT server
    class CFG,FN,HL,AUTH,SEC,VAL,LOG,ERR,CACHE app
    class ML,QR,PDF,PAY,ICS,NOT,AV int
    class USR,FL,BK,NT,TX,LH,SS,AL db
```

### Request Flow

```mermaid
sequenceDiagram
    participant Browser
    participant Apache
    participant Config
    participant Page
    participant Helpers
    participant MySQL
    participant Integrations

    Browser->>Apache: HTTP Request
    Apache->>Config: config.php (error handler, .env, session, DB connect)
    Config->>MySQL: Connect
    Config-->>Apache: Ready
    Apache->>Page: Page-specific PHP
    Page->>Helpers: Query data
    Helpers->>MySQL: Prepared statement
    MySQL-->>Helpers: Results
    Helpers-->>Page: Data
    Page->>Integrations: Email/QR/PDF/Notification
    Integrations-->>Page: Result
    Page-->>Apache: HTML response
    Apache-->>Browser: Rendered page
```

---

## 📁 Folder Structure

```mermaid
graph LR
    subgraph Root["📁 aerobook/"]
        direction LR
        ROOT_FILES["*.php (25 pages) 🟦"]
        ADMIN["admin/ 🟥"]
        CSS["css/ 🟪"]
        JS["js/ 🟨"]
        INC["includes/ 🟩"]
        DB["database/ 🟧"]
        DOCS["docs/ ⬜"]
        UPLOADS["uploads/ ⬜"]
        LOGS["logs/ ⬜"]
        DOCKER["Dockerfile, docker-compose.yml, render.yaml ⬜"]
        CFG[".htaccess, .env.example ⬜"]
    end

    ADMIN --> ADASH[dashboard.php]
    ADMIN --> AANA[analytics.php]
    ADMIN --> AROUTE[route-analytics.php]
    ADMIN --> AREP[reports.php]
    ADMIN --> ALOG[activity-log.php]
    ADMIN --> ADIAG[diagnostics.php]
    ADMIN --> AFLT[manage-flights.php]
    ADMIN --> ABK[manage-bookings.php]
    ADMIN --> AUSR[manage-users.php]

    INC --> ICONF[config.php]
    INC --> IFUNC[functions.php]
    INC --> IHELP[helpers.php]
    INC --> IAUTH[Auth.php]
    INC --> ISEC[Security.php]
    INC --> IVAL[Validation.php]
    INC --> ILOG[Logger.php]
    INC --> IERR[ErrorHandler.php]
    INC --> ICACHE[Cache.php]
    INC --> IML[Mailer.php]
    INC --> IQR[QRCode.php]
    INC --> IPDF[PDF.php]
    INC --> IICS[ICS.php]
    INC --> INOT[Notifications.php]
    INC --> IAV[Avatar.php]

    classDef blue fill:#024dec,color:#fff
    classDef red fill:#dc2626,color:#fff
    classDef purple fill:#7c3aed,color:#fff
    classDef yellow fill:#d97706,color:#fff
    classDef green fill:#059669,color:#fff
    classDef orange fill:#ea580c,color:#fff
    classDef white fill:#e2e8f0,color:#1e293b

    class ROOT_FILES blue
    class ADMIN,ADASH,AANA,AROUTE,AREP,ALOG,ADIAG,AFLT,ABK,AUSR red
    class CSS purple
    class JS yellow
    class INC,ICONF,IFUNC,IHELP,IAUTH,ISEC,IVAL,ILOG,IERR,ICACHE,IML,IQR,IPDF,IICS,INOT,IAV green
    class DB orange
    class DOCS,UPLOADS,LOGS,DOCKER,CFG white
```

---

## 🗄️ Database Schema

```mermaid
erDiagram
    users ||--o{ bookings : "has"
    users ||--o{ email_verifications : "has"
    users ||--o{ password_resets : "has"
    users ||--o{ remember_tokens : "has"
    users ||--o{ login_history : "has"
    users ||--o{ user_sessions : "has"
    users ||--o{ saved_passengers : "has"
    users ||--o{ saved_routes : "has"
    users ||--o{ notifications : "has"
    users ||--o{ transactions : "has"
    users ||--o{ price_watches : "has"
    bookings ||--o{ booking_addons : "has"
    bookings }o--|| flights : "on"
    admins ||--o{ admin_activity_log : "performs"

    users {
        int id PK
        string name
        string email UK
        string phone
        string password
        string avatar
        datetime email_verified_at
        datetime last_login_at
        string last_login_ip
        int failed_logins
        datetime locked_until
        datetime created_at
    }

    flights {
        int flight_id PK
        string airline_name
        string flight_number UK
        string source
        string destination
        datetime departure_time
        datetime arrival_time
        int total_seats
        int seats_available
        decimal price
        enum status
        datetime created_at
    }

    bookings {
        int booking_id PK
        string booking_ref UK
        int user_id FK
        int flight_id FK
        string passenger_name
        int age
        enum gender
        date travel_date
        string seat_number
        enum booking_status
        datetime booking_date
    }

    notifications {
        int id PK
        int user_id FK
        string type
        string title
        text message
        string link
        bool is_read
        datetime created_at
    }

    transactions {
        int id PK
        int user_id FK
        string booking_ref
    string payment_order_id
    string payment_payment_id
        decimal amount
        enum status
        datetime created_at
        datetime paid_at
    }

    admin_activity_log {
        int id PK
        int admin_id FK
        string action
        text details
        string ip_address
        datetime created_at
    }
```

### Database Tables (all 24)

| Table | Purpose |
|-------|---------|
| `admins` | Administrator accounts (login for the Operations Center) |
| `users` | Passenger accounts — auth, lockout, avatar, email verification, stats |
| `flights` | Flight schedule & inventory (weekday-based scheduling) |
| `bookings` | Reservations — passenger, seat, status, promo code & discount, business surcharge |
| `booking_addons` | Per-booking baggage and meal add-ons |
| `notifications` | In-app notifications (unread badges, center page) |
| `transactions` | Simulated payment records |
| `contacts` | Contact-form submissions (Support Queries in admin) |
| `login_history` | Authentication audit trail (success/failure, IP, user agent) |
| `user_sessions` | Active sessions — view & revoke from the profile page |
| `email_verifications` | Email verification tokens |
| `password_resets` | Password reset tokens |
| `remember_tokens` | "Remember me" tokens (rotated, hashed) |
| `saved_passengers` | Quick-select passenger profiles |
| `saved_routes` | Favorite routes in the Travel Hub |
| `price_watches` | Fare price watches (Travel Hub UI) |
| `admin_activity_log` | Admin audit trail — every admin action, searchable |
| `aviation_airports` | AviationStack-synced airport reference data |
| `aviation_airlines` | AviationStack-synced airline reference data |
| `aviation_aircraft_types` | AviationStack-synced aircraft types |
| `aviation_countries` | AviationStack-synced country reference data |
| `aviation_flights` | AviationStack-synced live flight records |
| `aviation_airplanes` | AviationStack-synced airplane/registration data |
| `api_sync_logs` | AviationStack sync history (endpoint, counts, last-sync) |

---

## ✨ Major Features

### 🇮🇳🌍 Domestic & International Portals

| Portal | Description |
|--------|-------------|
| **🇮🇳 Domestic Flights** | Search flights across 30+ Indian cities. Shows domestic routes, filters by domestic connections only. |
| **🌍 International Flights** | Search international routes. Filters for cross-border itineraries. |

Both portals use the **same booking engine**. The region filter is applied automatically — no duplicate logic.

### 👨‍✈️ Passenger Portal

| Feature | Description |
|---------|-------------|
| **Smart Fare Engine** | Finds valid connecting flights (e.g., Delhi→Mumbai→Goa) when direct flights are expensive. Scores by price, duration, stops, and layover. |
| **Interactive Seat Map** | Real-time 2D Airbus A320 cabin layout. Business (rows 1–2, optional **₹1,000 upgrade surcharge**) and Economy (rows 3–10) with window/aisle/middle indicators, exit-row legroom, and occupied status. |
| **Multi-Passenger Booking** | Book 1–6 passengers simultaneously with dynamic form generation, auto-seat assignment, and saved passenger quick-select. |
| **In-Flight Add-Ons** | Baggage upgrades (+10kg/+20kg) and meal preferences (Vegetarian, Non-Veg, Jain Thali) with live price recalculation. |
| **Promo Code Engine** | Instant promo code verification (`AERO10` = 10% off, `FLY2026` = ₹500 off) with live fare summary updates. |
| **Booking Confirmation** | Professional confirmation page with QR code, Google Calendar export, and notification. |
| **E-Ticket / Boarding Pass** | Printable boarding pass with QR code, print-optimized CSS, and calendar export link. |
| **Travel Hub** | Centralized dashboard with upcoming trip timeline, travel statistics, milestones, saved passengers, saved routes, and price watches. |
| **Travel Calendar** | Monthly calendar view of upcoming flights with trip highlighting and one-click booking details. |
| **Travel Documents** | Centralized access to boarding passes, e-tickets, and trip summaries. |
| **Account Management** | Profile, avatar (WebP), password change, email verification, login history, session management. |
| **Flight Status Tracker** | Real-time flight status lookup by flight number or route with gate, terminal, and weather. |
| **Online Check-in** | Check in for upcoming flights within the 48-hour check-in window (48h before departure → 2h before), with a live countdown and confirmation notification. |
| **Cancel Booking** | Request cancellation for a confirmed booking directly from your bookings list. |
| **Flight Details** | Full flight breakdown — schedule, price, seat-map preview — with a one-click booking flow. |
| **Notifications Center** | In-app notification center: check-in confirmations, booking updates, and alerts, with unread badges in the header. |

### 🧠 Smart Fare Engine

When a user searches for a route, AeroBook goes beyond direct flights — it intelligently searches for valid two-flight connecting itineraries.

**Algorithm:**
1. Find all direct flights matching the route
2. Find all indirect connections via every intermediate city
3. Validate each connection: second flight departs after first arrives, **1.5h–8h layover**, no duplicate airports
4. Score every itinerary (each factor normalized 0–100, total = 100):

| Factor | Weight | Why |
|--------|--------|-----|
| 💰 **Price** | 50% | Lower fare = higher score |
| ⏱️ **Travel Time** | 25% | Shorter journey = higher score |
| ⏳ **Layover Duration** | 15% | Closer to the ideal ~2h layover = higher score |
| 🔢 **Number of Stops** | 10% | Fewer stops = higher score |

5. Display badges: 🏆 **Best Value**, 💰 **Cheapest**, ⚡ **Fastest**
6. Show **"Save ₹X,XXX"** when a connection beats the cheapest direct flight by ≥ ₹200
7. Explain **"Why This Is Cheaper"** — never fabricates data

**Filters:** results can be narrowed by **max budget, max stops, max duration, airline, and departure/arrival time windows**.

> **Configuration** (constants in `includes/FareEngine.php`): min layover **90 min**, max layover **8 h**, max connections **1**, savings badge threshold **₹200**. The engine runs 3 DB queries per search (1 direct + 1 source→hubs + 1 hubs→destination).

### 📧 Email System

AeroBook supports transactional emails for:
1. **Email Verification** — Sent after registration to verify the user's email address
2. **Password Reset** — Sent when a user requests a password reset link

Email delivery is handled by `AeroMailer` (in `includes/Mailer.php`):
- **SMTP mode:** Sends real emails via PHPMailer (requires manual install to `lib/phpmailer/`)
- **Log mode (default):** Emails are written to `logs/email.log` — never crashes the application

Configure via `.env`:
```
MAIL_MODE=log
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=your@email.com
MAIL_PASS=your-app-password
MAIL_ENCRYPTION=tls
```

### 🔌 AviationStack Live Data

AeroBook can pull real airport / airline / aircraft / flight metadata from the [AviationStack](https://aviationstack.com) API. This is **optional** — the app ships with fully working sample data and runs without a key.

**How live data retrieval works:**

1. An admin opens **Operations Center → Data Synchronization** (`admin/aviation-sync.php`) and clicks **Test Connection** or a per-endpoint sync button.
2. `AviationSyncService` (`includes/AviationSyncService.php`) calls `AviationStackClient` (`includes/AviationStackClient.php`), which makes cURL requests to `https://api.aviationstack.com/v1/{endpoint}` with the key from `AVIATIONSTACK_API_KEY`.
3. The client **auto-paginates** (100 records/page, capped at 10,000 for the free plan), with a 15-second timeout, retries, and a bundled CA bundle so SSL verification works even on PHP builds without a system CA store.
4. Each raw API record is field-mapped by `AviationMapper` (`includes/AviationMapper.php`) and **upserted** into the `aviation_*` tables — `aviation_airports`, `aviation_airlines`, `aviation_aircraft_types`, `aviation_countries`, `aviation_flights`, `aviation_airplanes` — via `INSERT … ON DUPLICATE KEY UPDATE`.
5. Every sync is recorded in `api_sync_logs` and the app logger; the admin page shows per-endpoint record counts and last-sync times.

**Security:** the API key lives only in `.env` and is never exposed to logs, HTML, or JavaScript. Passenger-facing pages never call AviationStack directly — sync is admin-triggered, on demand only.

**Setup:** get a free key at [aviationstack.com](https://aviationstack.com), import `database/aviationstack.sql`, then set `AVIATIONSTACK_API_KEY` and `AVIATIONSTACK_ENABLED=true` in `.env`. Full walkthrough: [docs/CONFIGURATION.md](docs/CONFIGURATION.md).

### 🏢 Admin Operations Center

| Page | What It Does |
|------|-------------|
| **Operations Dashboard** | 4 KPI cards, Boarding Soon, Upcoming Departures, Recent Bookings, Low Occupancy Alerts, Top Customers, Data Quality |
| **Airline Analytics** | Revenue by month/route/airline, occupancy analysis, top customers, booking/cancellation trends |
| **Route Analytics** | Profitability, inactive routes, occupancy distribution (high/medium/low/empty) |
| **Download Reports** | CSV exports for bookings, flights, revenue, passengers, routes with date range filters |
| **Activity Log** | Searchable admin audit trail with action filter, date range, pagination |
| **System Diagnostics** | Database status, PHP info, app stats, 7 automated data quality checks |
| **Flight Management** | CRUD + search + status/airline/route filters + occupancy progress bars |
| **Seat Management** | Per-flight seat count and status updates |
| **Booking Management** | Search, status filter, confirmed/cancelled stat badges, admin cancellation |
| **User Management** | Search, view, and delete user accounts |
| **Support Queries** | Review and manage contact-form submissions from the site |
| **Data Synchronization** | One-click live data sync from AviationStack (airports, airlines, flights) with status + logs |

### 🔌 Integrations

| Integration | Status | Details |
|-------------|--------|---------|
| **Email (PHPMailer)** | ✅ Backend complete | 4 HTML templates, log fallback |
| **QR Code Generator** | ✅ Complete | Scannable SVG, standard byte encoding |
| **PDF Documents** | ✅ Backend complete | Boarding pass/invoice/summary, tFPDF optional |
| **Demo Payment** | ✅ Backend complete | Simulated mode, transaction storage |
| **ICS Calendar** | ✅ Complete | RFC 5545 ICS + Google Calendar links |
| **In-App Notifications** | ✅ Complete | Create, fetch, mark read, header dropdown, center page |
| **Avatar Upload** | ✅ Complete | MIME via finfo, WebP, dimension/size validation |
| **AviationStack (Live Data)** | ✅ Complete | Optional API key; syncs airports/airlines/flights into `aviation_*` tables |

---

## 🗺️ Site Map — Every Page

AeroBook ships **24 passenger pages** and **16 operations-center pages** (including the JSON health endpoint). Every page is a real, working screen of the app.

### 🧑‍✈️ Passenger Portal (public root)

| Page | What It Does |
|------|-------------|
| `index.php` | Landing page — hero, flight search widget, fares, benefits, FAQ |
| `search-flights.php` | Flight search with 🇮🇳 domestic / 🌍 international region filter |
| `fare-results.php` | Smart Fare Engine results — direct + connecting itineraries with badges |
| `flight-details.php` | Flight details, date picker, seat-map preview, booking CTA |
| `booking.php` | Multi-passenger booking — interactive seat map, add-ons, promo codes |
| `booking-confirmation.php` | Confirmation with QR code, Google Calendar export, notification |
| `generate-ticket.php` | Printable e-ticket / boarding pass with QR + barcode |
| `check-in.php` | Online check-in with 48h→2h eligibility window and countdown |
| `cancel-booking.php` | Cancel a confirmed booking |
| `flight-status.php` | Live status by flight number or route (gate, terminal, weather) |
| `login.php` / `register.php` | User authentication (auto-login after registration) |
| `forgot-password.php` / `reset-password.php` | Password recovery flow |
| `verify-email.php` | Email verification after registration |
| `user-dashboard.php` | **Travel Hub** — trip timeline, stats, milestones, saved routes, price watches |
| `my-bookings.php` | All bookings with check-in, cancel, and boarding-pass actions |
| `travel-calendar.php` | Monthly calendar view of upcoming trips |
| `travel-documents.php` | Central access to boarding passes, e-tickets, trip summaries |
| `notifications.php` | In-app notification center |
| `profile.php` | Account profile, avatar, password change, login history, sessions |
| `about.php` / `contact.php` | Company info and support contact form |
| `health.php` | JSON health-check endpoint (`{"status":"ok",...}`) for uptime monitors |

### 🏢 Operations Center (`admin/`)

| Page | What It Does |
|------|-------------|
| `login.php` | Admin sign-in |
| `dashboard.php` | Operations Center — KPI cards, Boarding Soon, Upcoming Departures, alerts |
| `analytics.php` | Airline analytics — revenue, occupancy, top customers, trends |
| `route-analytics.php` | Route profitability, inactive routes, occupancy distribution |
| `reports.php` | CSV exports — bookings, flights, revenue, passengers, routes, contacts |
| `activity-log.php` | Searchable admin audit trail with filters and pagination |
| `diagnostics.php` | System diagnostics + 7 automated data-quality checks |
| `manage-flights.php` | Flight CRUD with search, filters, occupancy bars |
| `add-flight.php` / `edit-flight.php` | Create / update flights |
| `manage-seats.php` | Per-flight seat availability and status updates |
| `manage-bookings.php` | Booking management, status filters, admin cancellation |
| `manage-users.php` | Search, view, and delete user accounts |
| `manage-contacts.php` | Review and manage contact-form submissions |
| `aviation-sync.php` | One-click AviationStack data sync with status and logs |

---

## 🧱 Technology Stack

| Layer | Technology |
|-------|-----------|
| **Frontend** | HTML5, CSS3 (Variables, Flexbox/Grid, Animations), Bootstrap 5.3, Bootstrap Icons, Vanilla JS (ES6+) |
| **Backend** | PHP 8.0+ (Prepared Statements, Sessions, CSRF, Transactions) |
| **Database** | MySQL 8.0 / MariaDB (InnoDB, Foreign Keys, 10 Indexes) |
| **Server** | Apache 2.4+ (mod_rewrite, mod_headers, mod_expires) |
| **Payments** | Simulated demo payment (no real gateway) |
| **Email** | PHPMailer (SMTP, optional) |
| **PDF** | tFPDF (optional, falls back to HTML) |
| **Deployment** | Docker, Shared Hosting, Manual Apache |

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.0+ with `mysqli`, `gd`, `json`, `mbstring`, `fileinfo`, `curl` extensions
- MySQL 8.0 / MariaDB
- Apache 2.4+ with mod_rewrite

### Deploy on Render (recommended)

AeroBook ships a `render.yaml` Blueprint + **all-in-one `Dockerfile`** for one-click deployment on [Render](https://render.com) — **no external database required**:

1. Push this repo to GitHub → **dashboard.render.com/blueprints → New Blueprint Instance**.
2. Select this repository — Render reads `render.yaml` automatically.
3. Click **Apply**. The container boots a bundled MariaDB, creates the `aerobook_db` database, and seeds `database/aerobook.sql` automatically on first boot (see `docker/entrypoint.sh`).
4. Verify `https://aerobook-2snu.onrender.com/health.php` returns `{"status":"ok",...}`.

> ⚠️ **Free-tier note:** Render free instances have an **ephemeral filesystem** — runtime data (users, bookings) resets on every restart/redeploy and the DB is re-seeded fresh. This is ideal for demos and submissions. For persistent data, override `DB_HOST`/`DB_USER`/`DB_PASS`/`DB_NAME` to an external MySQL (see [docs/DEPLOY_RENDER.md](docs/DEPLOY_RENDER.md)).

### Standard Installation

```bash
# Clone the repository
git clone https://github.com/suman2308/airline-reservation-system.git
cd airline-reservation-system

# Create database and import schema
mysql -u root -p -e "CREATE DATABASE aerobook_db"
mysql -u root -p aerobook_db < database/aerobook.sql

# Configure environment (edit .env with your credentials)
cp .env.example .env

# Set directory permissions
chmod -R 775 uploads/ logs/

# Visit http://localhost/airline-reservation-system
```

### Docker Installation

```bash
docker-compose up -d --build
# Visit http://localhost:8080
```

### Default Credentials

| Portal | Username | Password |
|--------|----------|----------|
| **Admin Panel** | `admin` | `admin123` |
| **User Account** | Register at `/register.php` | — |

---

## 🧪 Automated Tests

AeroBook ships a dependency-free smoke test suite (`tests/smoke.php`) — no PHPUnit or Composer required. It verifies page availability, auth guards, admin + user login, registration, flight search, booking validation, and database integrity.

```bash
# Start the dev server first
php -S 127.0.0.1:8080

# Run the suite (pass your server URL)
php tests/smoke.php http://127.0.0.1:8080
```

Exit code `0` = all green, `1` = failures. The suite creates and cleans up its own test user, so it never pollutes your data.

## 📸 Screenshots

Captured from a live local run of the app (PHP 8.5 + MariaDB 11.4) — every major page of the site.

### 🧑‍✈️ Passenger Portal

<p align="center">
  <img src="screenshots/landing.png" alt="AeroBook landing page" width="32%"/>
  <img src="screenshots/search-flights.png" alt="Flight search" width="32%"/>
  <img src="screenshots/fare-results.png" alt="Smart Fare Engine results" width="32%"/>
</p>
<p align="center">
  <img src="screenshots/flight-details.png" alt="Flight details" width="32%"/>
  <img src="screenshots/booking.png" alt="Multi-passenger booking with seat map" width="32%"/>
  <img src="screenshots/booking-confirmation.png" alt="Booking confirmation with QR code" width="32%"/>
</p>
<p align="center">
  <img src="screenshots/e-ticket.png" alt="E-ticket boarding pass" width="32%"/>
  <img src="screenshots/check-in.png" alt="Online check-in" width="32%"/>
  <img src="screenshots/cancel-booking.png" alt="Cancel booking" width="32%"/>
</p>
<p align="center">
  <img src="screenshots/flight-status.png" alt="Flight status tracker" width="32%"/>
  <img src="screenshots/login.png" alt="User login" width="32%"/>
  <img src="screenshots/register.png" alt="Create account" width="32%"/>
</p>
<p align="center">
  <img src="screenshots/forgot-password.png" alt="Forgot password" width="32%"/>
  <img src="screenshots/user-dashboard.png" alt="Travel Hub dashboard" width="32%"/>
  <img src="screenshots/my-bookings.png" alt="My bookings" width="32%"/>
</p>
<p align="center">
  <img src="screenshots/travel-calendar.png" alt="Travel calendar" width="32%"/>
  <img src="screenshots/travel-documents.png" alt="Travel documents" width="32%"/>
  <img src="screenshots/notifications.png" alt="Notifications center" width="32%"/>
</p>
<p align="center">
  <img src="screenshots/profile.png" alt="User profile" width="32%"/>
  <img src="screenshots/contact.png" alt="Contact page" width="32%"/>
  <img src="screenshots/about.png" alt="About page" width="32%"/>
</p>

### 🏢 Operations Center

<p align="center">
  <img src="screenshots/admin-login.png" alt="Admin login" width="32%"/>
  <img src="screenshots/admin-dashboard.png" alt="Admin operations dashboard" width="32%"/>
  <img src="screenshots/admin-analytics.png" alt="Admin airline analytics" width="32%"/>
</p>
<p align="center">
  <img src="screenshots/admin-route-analytics.png" alt="Admin route analytics" width="32%"/>
  <img src="screenshots/admin-reports.png" alt="Admin CSV reports" width="32%"/>
  <img src="screenshots/admin-activity-log.png" alt="Admin activity log" width="32%"/>
</p>
<p align="center">
  <img src="screenshots/admin-diagnostics.png" alt="Admin system diagnostics" width="32%"/>
  <img src="screenshots/admin-manage-flights.png" alt="Admin flight management" width="32%"/>
  <img src="screenshots/admin-add-flight.png" alt="Admin add flight" width="32%"/>
</p>
<p align="center">
  <img src="screenshots/admin-manage-seats.png" alt="Admin seat availability" width="32%"/>
  <img src="screenshots/admin-manage-bookings.png" alt="Admin booking management" width="32%"/>
  <img src="screenshots/admin-manage-users.png" alt="Admin user management" width="32%"/>
</p>
<p align="center">
  <img src="screenshots/admin-manage-contacts.png" alt="Admin support queries" width="32%"/>
  <img src="screenshots/admin-aviation-sync.png" alt="Admin AviationStack sync" width="32%"/>
</p>

---

## 📖 Documentation

| Guide | Description |
|-------|-------------|
| 📦 [Installation Guide](docs/INSTALLATION.md) | Step-by-step setup for all environments |
| ⚙️ [Configuration Guide](docs/CONFIGURATION.md) | All config options explained |
| 🚢 [Deployment Guide](PRODUCTION.md) | Production deployment instructions |
| 🌐 [Render Deployment](docs/DEPLOY_RENDER.md) | One-click Render + MySQL setup |
| 🏗️ [Architecture Overview](ARCHITECTURE.md) | System design, ER diagram, decisions |
| 🤝 [Contributing Guide](CONTRIBUTING.md) | How to contribute to AeroBook |
| 🔒 [Security Policy](SECURITY.md) | Security practices and vulnerability reporting |
| 📋 [Changelog](CHANGELOG.md) | Version history and updates |
| 📧 [SMTP Setup](docs/SETUP_SMTP.md) | Email configuration guide |
| 🐳 [Docker Setup](docs/SETUP_DOCKER.md) | Docker deployment guide |
| 💳 [Payment Setup](docs/SETUP_PAYMENT.md) | Demo payment configuration guide |

---

## 🗺️ Roadmap

- [x] Automated smoke test suite (`tests/smoke.php` — dependency-free)
- [ ] REST API for flight search and booking
- [ ] Multi-language support (i18n)
- [ ] Email price watch alerts
- [ ] Real-time WebSocket flight status updates
- [ ] Mobile app (PWA)
- [x] Admin table pagination for large datasets (`admin/manage-bookings.php`, `manage-contacts.php`, `manage-flights.php`, `manage-users.php`)

---

## 🤝 Contributing

Contributions are welcome! Please read our [Contributing Guide](CONTRIBUTING.md) for details.

This project follows:
- [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards
- Prepared statements for all SQL
- CSRF protection on all forms
- `htmlspecialchars()` for all output

## 🛡️ Security

We take security seriously. See our [Security Policy](SECURITY.md) for vulnerability reporting.

## 📄 License

Distributed under the MIT License. See [LICENSE](LICENSE).

---

## 🙏 Credits

- **Bootstrap 5** • **Bootstrap Icons** • **Google Fonts (Inter) + Helvetica Now Display**
- **Demo Payment System** • **PHPMailer** • **tFPDF**

---

<p align="center">
  <strong>Built with ❤️ for the open-source community.</strong><br>
  <sub>⭐ Star this repository if you find it useful!</sub>
</p>
