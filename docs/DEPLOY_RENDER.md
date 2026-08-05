# AeroBook — Render Deployment Guide

Deploy AeroBook to [Render](https://render.com) as a Docker web service. The repo already ships a `Dockerfile` (`php:8.2-apache` with `mysqli` + `gd` **+ bundled MariaDB**), a `render.yaml` Blueprint, and a `/health.php` health endpoint — so the heavy lifting is done.

> **Time:** ~5 minutes (all-in-one) or ~30 minutes (persistent MySQL) · **Cost:** Free

---

## 0. The one thing to know up front

**Render does not offer managed MySQL** — only PostgreSQL and Redis. AeroBook is a `mysqli` app, so it needs MySQL from somewhere. Three options:

**Option 1 (free, zero-setup, DEFAULT): all-in-one container**
- The `Dockerfile` now bundles **MariaDB inside the app container**. `docker/entrypoint.sh` boots it, creates the database/user, and seeds `database/aerobook.sql` automatically on first boot.
- `render.yaml` already points at the bundled DB (`DB_HOST=127.0.0.1`) — **deploy with one click, no database to create anywhere**.
- ⚠️ **Demo caveat:** Render's free tier uses an *ephemeral filesystem* and spins down after ~15 min idle — so **all runtime data (users, bookings) resets on every restart/redeploy**. Flights and search always work (re-seeded each boot). Perfect for a college submission or demo.

**Option 2 (free, persistent data): external MySQL provider**
- **Aiven** (aiven.io) — MySQL free tier: create service → **Enable "require_ssl" = OFF** (AeroBook's plain `mysqli` connection doesn't do TLS).
- **TiDB Cloud** serverless — MySQL-compatible, free tier (port `4000`).
- **Clever Cloud**, **DigitalOcean** (paid), etc.
- Put the host **including port** into `DB_HOST` (e.g. `mysql-abc.aivencloud.com:12345`) — `mysqli_connect` accepts `host:port`. In the Render dashboard, override `DB_HOST`/`DB_USER`/`DB_PASS`/`DB_NAME` and import the schema there.

**Option 3 (paid, all-in-Render): MySQL as a second Render service**
- Create a second web service from the `mysql:8.0` Docker image with a **persistent disk** mounted at `/var/lib/mysql`, seeded via `database/aerobook.sql`. Requires a paid instance — not viable on free (ephemeral disk + spin-down would wipe your data).

---

## 1. Prepare the database

**All-in-one mode (Option 1):** skip this step entirely — the entrypoint seeds `database/aerobook.sql` (+ `database/aviationstack.sql`) on first boot. The seed ships **August 2–8, 2026** flights (upcoming), all tables, indexes, FKs, and the default admin.

**External MySQL (Option 2):** create the database and note **host, port, database name, user, password**, then import:
   ```bash
   mysql -h <HOST> -P <PORT> -u <USER> -p <DBNAME> < database/aerobook.sql
   ```
   (Or via your provider's web console.) You may also import `database/aviationstack.sql` after it for the aviation tables.

---

## 2. Blueprint deploy (one click, recommended)

1. Push this repo to GitHub (`main` branch) — the `render.yaml` at the root is the Blueprint.
2. Go to **https://dashboard.render.com/blueprints** → **New Blueprint Instance**.
3. Connect your GitHub account → select `suman2308/airline-reservation-system`.
4. Render detects `render.yaml` and shows the `aerobook` web service. Click **Apply** — all DB values are pre-filled for all-in-one mode.
5. *(Optional)* To use external persistent MySQL instead, override in the dashboard: `DB_HOST` (`host:port`), `DB_USER`, `DB_PASS`, `DB_NAME`, and set `AVIATIONSTACK_API_KEY` if you have one.
6. Wait for the build + deploy. Verify `https://aerobook.onrender.com/health.php` → `{"status":"ok",...}`.

---

## 3. Manual web service (if you skip render.yaml)

Dashboard → **New + → Web Service** → **Connect your GitHub repo** → select it:

| Field | Value |
|---|---|
| **Name** | `aerobook` |
| **Region** | Closest to your users (e.g. Singapore) |
| **Branch** | `main` |
| **Runtime / Environment** | **Docker** (Render auto-detects the `Dockerfile`) |
| **Instance Type** | **Free** (spins down after ~15 min idle, cold start ~1 min) or **Starter** $7/mo (always-on) |
| **Health Check Path** | `/health.php` |
| **Port** | `80` (matches `EXPOSE 80` in the Dockerfile) |

**Advanced → Environment Variables** — for all-in-one mode set `DB_HOST=127.0.0.1`, `DB_USER=aerobook`, `DB_PASS=aerobook_secret`, `DB_NAME=aerobook_db` (or use external MySQL values). **Do NOT set `IS_DOCKER=true`** (that would flip the app into dev mode; `IS_PRODUCTION` is derived from the hostname, which on Render is non-localhost → production mode is automatic).

---

## 3.5 All-in-one container details

- **Boot order:** `docker/entrypoint.sh` initializes MariaDB if needed → starts it → waits for readiness (up to 60 s) → creates `aerobook` user + `aerobook_db` → seeds `database/aerobook.sql` (+ `aviationstack.sql`) → starts Apache.
- **Data dir:** `/var/lib/mysql` (the Dockerfile clears it at build; the entrypoint re-initializes on boot). On Render free tier the filesystem is ephemeral, so the DB is fresh every boot — by design for demos.
- **Lean config:** `docker/mariadb.cnf` (32M buffer pool, 30 connections, `performance_schema=OFF`) keeps MariaDB under ~150 MB so Apache + PHP + DB fit a 512 MB free instance.
- **External-DB escape hatch:** if you set `DB_HOST` to anything other than `127.0.0.1`, the entrypoint still boots (harmless) but the app connects to your external MySQL — the bundled DB is simply unused.
- **Local test:** `docker build -t aerobook . && docker run -p 8080:80 -e DB_HOST=127.0.0.1 -e DB_USER=aerobook -e DB_PASS=aerobook_secret -e DB_NAME=aerobook_db aerobook`

## 4. Why no code changes were needed (what I fixed in this repo)

Render terminates TLS at its proxy, so PHP never sees `HTTPS=on` — the app's `BASE_URL` auto-detection and cookie `secure` flags would have silently broken (http:// asset links → unstyled page, mixed-content blocks). The repo now:

- **`includes/config.php`** — new `isSecureRequest()` helper that trusts `X-Forwarded-Proto` (set by Render) in addition to `$_SERVER['HTTPS']`/port 443; used for the `BASE_URL` scheme and session cookie params.
- **`includes/Auth.php`** + **`includes/Security.php`** — remember-me cookies and session cookie params use the same helper (was duplicated inline 3×).
- **`database/aerobook.sql`** — seed schedule shifted to Aug 2–8, 2026 so check-in/upcoming trips work on a fresh deploy.
- **`render.yaml`** — Blueprint for one-click deploys.

---

## 5. Verification checklist

| Check | URL |
|---|---|
| App + DB healthy | `https://aerobook.onrender.com/health.php` |
| Site styled correctly | `https://aerobook.onrender.com/` |
| Admin panel | `https://aerobook.onrender.com/admin/login.php` → `admin` / `admin123` (change immediately) |
| Flight search | Homepage → search a route (weekday schedule Aug 2–8) |
| Secure cookies | DevTools → Application → Cookies → `Secure` flag set on session cookie |

---

## 6. Free-tier limitations

- **Spin-down:** the free instance sleeps after ~15 min of inactivity; the first request after sleep takes up to ~1 min (cold start). Paid Starter = always on.
- **Ephemeral disk:** `uploads/avatars`, `logs/`, and PHP file sessions are wiped on restart/redeploy → users get logged out and avatars/PDFs vanish. For persistence, attach a **Render Disk** (paid) to `/var/www/html/uploads` and `/var/www/html/logs`.
- **No cron:** price-watch checks are backend-only (no scheduler on free).
- **Health checks** keep the instance "healthy" but don't prevent free spin-down.

---

## 7. Post-deploy

- **Change the admin password** (there's no admin password UI): run via MySQL:
  ```sql
  UPDATE admins SET password = '<bcrypt hash of new password>' WHERE username = 'admin';
  ```
  Generate the hash with PHP's `password_hash()` or a bcrypt tool.
- **Emails** default to `MAIL_MODE=log` (written to `logs/email.log`) — the verification/reset flow works without SMTP. To send real mail, set `MAIL_MODE=smtp` + `MAIL_HOST/PORT/USER/PASS`.
- **Add flights beyond Aug 8** via Admin → Add Flight (the seed covers one week).
