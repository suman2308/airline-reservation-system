#!/bin/bash
# ============================================================
# AeroBook – All-in-One Container Entrypoint
# ------------------------------------------------------------
# Boots the bundled MariaDB, creates the app database + user,
# seeds database/aerobook.sql (+ aviationstack.sql) on first
# boot, then starts Apache in the foreground.
#
# Works with an ephemeral filesystem (Render free tier): the DB
# is re-seeded fresh on every boot. On a persistent disk the
# "flights table empty" guard prevents wiping existing data.
# ============================================================
set -e

DB_NAME="${DB_NAME:-aerobook_db}"
DB_USER="${DB_USER:-aerobook}"
DB_PASS="${DB_PASS:-aerobook_secret}"
DB_HOST="${DB_HOST:-127.0.0.1}"
SQL_DIR="/var/www/html/database"

echo "[aerobook] Starting entrypoint (DB_HOST=$DB_HOST, DB_NAME=$DB_NAME)…"

# If an external database is configured (docker-compose, managed MySQL), the
# bundled MariaDB is not needed — skip straight to Apache.
if [ "$DB_HOST" != "127.0.0.1" ] && [ "$DB_HOST" != "localhost" ]; then
    echo "[aerobook] External DB_HOST=$DB_HOST detected — skipping bundled MariaDB."
    exec apache2-foreground
fi

# ─── 1. Prepare MariaDB data directory ───
if [ ! -d /var/lib/mysql/mysql ]; then
    echo "[aerobook] Initializing MariaDB data directory…"
    mariadb-install-db --user=mysql --datadir=/var/lib/mysql >/dev/null 2>&1 \
        || mysql_install_db --user=mysql --datadir=/var/lib/mysql >/dev/null 2>&1
fi

mkdir -p /run/mysqld
chown -R mysql:mysql /run/mysqld /var/lib/mysql 2>/dev/null || true

# ─── 2. Start MariaDB ───
echo "[aerobook] Starting MariaDB…"
if command -v mysqld_safe >/dev/null 2>&1; then
    mysqld_safe --user=mysql >/dev/null 2>&1 &
else
    # Fallback for slim images without mysqld_safe
    mariadbd --user=mysql >/dev/null 2>&1 &
fi

# Wait until MariaDB accepts connections (max ~60s)
for i in $(seq 1 60); do
    if mariadb-admin ping --silent 2>/dev/null; then
        echo "[aerobook] MariaDB is up."
        break
    fi
    if [ "$i" = "60" ]; then
        echo "[aerobook] ERROR: MariaDB did not become ready." >&2
        exit 1
    fi
    sleep 1
done

# ─── 3. Create database + app user ───
mariadb -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mariadb -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
mariadb -e "CREATE USER IF NOT EXISTS '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';"
mariadb -e "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';"
mariadb -e "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'127.0.0.1';"
mariadb -e "FLUSH PRIVILEGES;"

# ─── 4. Seed schema + data if the database is empty ───
FLIGHT_TABLES=$(mariadb -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name='flights';")
if [ "$FLIGHT_TABLES" = "0" ]; then
    echo "[aerobook] Seeding schema from $SQL_DIR/aerobook.sql…"
    mariadb "$DB_NAME" < "$SQL_DIR/aerobook.sql"
    if [ -f "$SQL_DIR/aviationstack.sql" ]; then
        echo "[aerobook] Seeding aviation tables…"
        # Optional import — a failure here must NOT block Apache boot.
        mariadb "$DB_NAME" < "$SQL_DIR/aviationstack.sql" \
            || echo "[aerobook] WARNING: aviation tables seed skipped (optional)"
    fi
    echo "[aerobook] Seed complete."
else
    FLIGHT_COUNT=$(mariadb -N -e "SELECT COUNT(*) FROM \`$DB_NAME\`.flights;")
    if [ "$FLIGHT_COUNT" = "0" ]; then
        echo "[aerobook] flights table empty — re-seeding…"
        mariadb "$DB_NAME" < "$SQL_DIR/aerobook.sql"
    fi
fi

# ─── 5. Graceful shutdown of MariaDB on container stop ───
cleanup() {
    echo "[aerobook] Shutting down MariaDB…"
    mariadb-admin shutdown >/dev/null 2>&1 || true
}
trap cleanup TERM INT

# ─── 6. Start Apache in the foreground (keeps container alive) ───
echo "[aerobook] Starting Apache…"
exec apache2-foreground
