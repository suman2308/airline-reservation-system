# Docker Setup Guide

AeroBook includes a Docker setup for easy local development and production deployment.

## Quick Start

```bash
# Build and start containers
docker-compose up -d --build

# Access the application
open http://localhost:8080

# Access the admin panel
open http://localhost:8080/admin/login.php

# Default admin credentials: admin / admin123
```

## Services

| Service | Container Name | Port | Description |
|---------|---------------|------|-------------|
| **app** | `aerobook-app` | `8080:80` | PHP 8.2 Apache web server |
| **db** | `aerobook-db` | `3307:3306` | MySQL 8.0 database |

## Volumes

| Volume | Mount Point | Purpose |
|--------|-------------|---------|
| `app` → `.` | `/var/www/html` | Application code (live reload) |
| `app` → `./uploads` | `/var/www/html/uploads` | User uploads |
| `app` → `./logs` | `/var/www/html/logs` | Application logs |
| `db-data` | `/var/lib/mysql` | Database data (persistent) |
| Database init | `./database/aerobook.sql` | Auto-imported on first run |

## Environment Variables

The Docker environment sets `IS_DOCKER=true`, which:
- Prevents the production detection (allows localhost-like behavior)
- Uses the database connection details from `docker-compose.yml`

You can also pass additional variables via Docker environment configuration.

## Database

- **Host:** `db` (Docker internal hostname)
- **Port:** `3306` (internal)
- **User:** `aerobook`
- **Password:** `aerobook_secret`
- **Database:** `aerobook_db`
- **Root Password:** `root_secret`

To connect from your host machine:
```bash
mysql -h localhost -P 3307 -u aerobook -p aerobook_db
```

## Logs

```bash
# View application logs
docker-compose logs -f app

# View database logs
docker-compose logs -f db
```

## Useful Commands

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# Rebuild and start
docker-compose up -d --build

# Reset everything (CAUTION: destroys database data)
docker-compose down -v
docker-compose up -d --build

# Access the app container
docker exec -it aerobook-app bash

# Access MySQL
docker exec -it aerobook-db mysql -u aerobook -p aerobook_db
```

## Production Considerations

For production Docker deployment:

1. **Use a reverse proxy** (Nginx, Traefik) with HTTPS termination
2. **Set strong database passwords** in `docker-compose.yml`
3. **Configure SMTP** via environment variables:
   ```yaml
   environment:
     - MAIL_MODE=smtp
     - MAIL_HOST=smtp.gmail.com
     - MAIL_USER=your@email.com
     - MAIL_PASS=your-app-password
   ```
4. **Payment:** AeroBook's Demo Payment system works out of the box — no API keys required
5. **Set up regular database backups** (see PRODUCTION.md)
6. **Enable maintenance mode** during updates:
   ```yaml
   environment:
     - MAINTENANCE_MODE=true
   ```
