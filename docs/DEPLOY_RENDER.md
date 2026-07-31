# AeroBook — Render Deployment Guide

Deploy AeroBook to [Render](https://render.com) as a Docker web service. The repo already ships a `Dockerfile` (`php:8.2-apache` with `mysqli` + `gd`), a `render.yaml` Blueprint, and a `/health.php` health endpoint — so the heavy lifting is done.

> **Time:** ~30 minutes · **Cost:** Free (web service) + external MySQL

---

## 0. The one thing to know up front

**Render does not offer managed MySQL** — only PostgreSQL and Redis. AeroBook is a `mysqli` app, so you must host MySQL yourself. Two options:

**Option A (free, recommended): external MySQL provider**
- **Aiven** (aiven.io) — MySQL free tier: create service → **Enable "require_ssl" = OFF** (AeroBook's plain `mysqli` connection doesn't do TLS).
- **TiDB Cloud** serverless — MySQL-compatible, free tier (port `4000`).
- **Clever Cloud**, **DigitalOcean** (paid), etc.
- Put the host **including port** into `DB_HOST` (e.g. `mysql-abc.aivencloud.com:12345`) — `mysqli_connect` accepts `host:port`.

**Option B (paid, all-in-Render): MySQL as a second Render service**
- Create a second web service from the `mysql:8.0` Docker image with a **persistent disk** mounted at `/var/lib/mysql`, seeded via `database/aerobook.sql`. Requires a paid instance — not viable on free (ephemeral disk + spin-down would wipe your data).

---

## 1. Prepare the database

1. Create the database (see above) and note: **host, port, database name, user, password**.
2. Import the schema + seed data. The seed now ships **August 2–8, 2026** flights (upcoming):
   ```bash
   mysql -h <HOST> -P <PORT> -u <USER> -p <DBNAME> < database/aerobook.sql
   ```
   (Or via your provider's web console.) Creates all tables, indexes, FKs, the default admin, and the flight schedule. You may also import `database/aviationstack.sql` after it for the aviation tables.

---

## 2. Option 1 — Blueprint deploy (one click, recommended)

1. Push this repo to GitHub (`main` branch) — the `render.yaml` at the root is the Blueprint.
2. Go to **https://dashboard.render.com/blueprints** → **New Blueprint Instance**.
3. Connect your GitHub account → select `suman2308/airline-reservation-system`.
4. Render detects `render.yaml` and shows the `aerobook` web service. Click **Apply**.
5. During creation, fill the env vars marked `sync: false`:

   | Variable | Value |
   |---|---|
   | `DB_HOST` | `host:port` from your MySQL provider |
   | `DB_USER` | MySQL user |
   | `DB_PASS` | MySQL password |
   | `DB_NAME` | MySQL database name |
   | `BASE_URL` | **leave empty** — auto-detects `https://…onrender.com/` behind Render's TLS proxy |
   | `AVIATIONSTACK_API_KEY` | *(optional)* your key |

6. Wait for the build + deploy. Verify `https://aerobook.onrender.com/health.php` → `{"status":"ok",...}`.

---

## 3. Option 2 — Manual web service (if you skip render.yaml)

Dashboard → **New + → Web Service** → **Connect your GitHub repo** → select it:

| Field | Value |
|---|---|
| **Name** | `aerobook` |
| **Region** | Same region as your MySQL (e.g. Singapore) |
| **Branch** | `main` |
| **Runtime / Environment** | **Docker** (Render auto-detects the `Dockerfile`) |
| **Instance Type** | **Free** (spins down after ~15 min idle, cold start ~1 min) or **Starter** $7/mo (always-on) |
| **Health Check Path** | `/health.php` |
| **Port** | `80` (matches `EXPOSE 80` in the Dockerfile) |

**Advanced → Environment Variables** — same table as step 5 above. **Do NOT set `IS_DOCKER=true`** (that would flip the app into dev mode; `IS_PRODUCTION` is derived from the hostname, which on Render is non-localhost → production mode is automatic).

---

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
