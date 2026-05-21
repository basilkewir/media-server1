#!/bin/bash
# MediaServer - Fix / Repair Script
# Fixes: DB access denied, missing storage, FATAL supervisor processes
# Run as root: sudo bash fix.sh

set -euo pipefail

APP_DIR="/var/www/mediaserver"
APP_USER="www-data"
PHP_VER="8.2"
DB_NAME="media_server"
DB_USER="media_user"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
info()    { echo -e "${GREEN}[FIX]${NC}  $1"; }
warn()    { echo -e "${YELLOW}[WARN]${NC} $1"; }
ok()      { echo -e "${GREEN}[OK]${NC}   $1"; }
section() { echo -e "\n${CYAN}━━━ $1 ━━━${NC}"; }

[ "$EUID" -ne 0 ] && { echo "Run as root: sudo bash fix.sh"; exit 1; }

# ── 1. Detect app directory ───────────────────────────────────────────────────
section "Locating application"

# Support both /var/www/mediaserver and /var/www/media-server
for candidate in /var/www/mediaserver /var/www/media-server; do
    if [ -f "${candidate}/artisan" ]; then
        APP_DIR="${candidate}"
        break
    fi
done

info "App directory: ${APP_DIR}"
[ ! -f "${APP_DIR}/artisan" ] && { echo "ERROR: artisan not found in ${APP_DIR}"; exit 1; }

# ── 2. Fix storage directories ────────────────────────────────────────────────
section "Storage directories"

for dir in \
    "${APP_DIR}/storage/logs" \
    "${APP_DIR}/storage/app/public" \
    "${APP_DIR}/storage/framework/cache/data" \
    "${APP_DIR}/storage/framework/sessions" \
    "${APP_DIR}/storage/framework/views" \
    "${APP_DIR}/storage/streams" \
    "${APP_DIR}/bootstrap/cache"; do
    mkdir -p "$dir"
    ok "Created: $dir"
done

# Create the log file so tail works immediately
touch "${APP_DIR}/storage/logs/laravel.log"

chown -R "${APP_USER}:${APP_USER}" "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
ok "Permissions set"

# ── 3. Fix MySQL credentials ──────────────────────────────────────────────────
section "MySQL credentials"

# Read current password from .env
CURRENT_PASS=$(grep "^DB_PASSWORD=" "${APP_DIR}/.env" 2>/dev/null | cut -d'=' -f2 | tr -d '"' | tr -d "'")

if [ -z "$CURRENT_PASS" ]; then
    warn "DB_PASSWORD is empty in .env — generating new password"
    NEW_PASS=$(openssl rand -base64 24 | tr -dc 'a-zA-Z0-9' | head -c 20)
else
    NEW_PASS="$CURRENT_PASS"
fi

info "Testing MySQL connection for ${DB_USER}..."

# Test if current credentials work
if mysql -u"${DB_USER}" -p"${NEW_PASS}" -e "SELECT 1;" "${DB_NAME}" &>/dev/null; then
    ok "MySQL credentials are valid"
else
    warn "MySQL credentials invalid — resetting password for ${DB_USER}"

    # Reset via root (no password needed on fresh Ubuntu MySQL)
    mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || true
    mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${NEW_PASS}';" 2>/dev/null || true
    mysql -e "ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${NEW_PASS}';" 2>/dev/null || true
    mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';" 2>/dev/null || true
    mysql -e "FLUSH PRIVILEGES;" 2>/dev/null || true

    # Update .env
    if grep -q "^DB_PASSWORD=" "${APP_DIR}/.env"; then
        sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${NEW_PASS}|" "${APP_DIR}/.env"
    else
        echo "DB_PASSWORD=${NEW_PASS}" >> "${APP_DIR}/.env"
    fi

    # Verify
    if mysql -u"${DB_USER}" -p"${NEW_PASS}" -e "SELECT 1;" "${DB_NAME}" &>/dev/null; then
        ok "MySQL credentials fixed. Password: ${NEW_PASS}"
    else
        echo -e "${RED}ERROR: Could not fix MySQL. Try manually:${NC}"
        echo "  sudo mysql"
        echo "  ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY 'newpassword';"
        echo "  Then update DB_PASSWORD in ${APP_DIR}/.env"
        exit 1
    fi
fi

# Ensure DB_DATABASE and DB_USERNAME are set correctly
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" "${APP_DIR}/.env"
sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" "${APP_DIR}/.env"

# ── 4. Clear config cache (picks up new .env) ─────────────────────────────────
section "Clearing caches"

cd "${APP_DIR}"
sudo -u "${APP_USER}" php artisan config:clear
sudo -u "${APP_USER}" php artisan cache:clear
ok "Caches cleared"

# ── 5. Run migrations ─────────────────────────────────────────────────────────
section "Database migrations"

sudo -u "${APP_USER}" php artisan migrate --force
ok "Migrations complete"

# ── 6. Generate APP_KEY if missing ───────────────────────────────────────────
section "Application key"

APP_KEY=$(grep "^APP_KEY=" "${APP_DIR}/.env" | cut -d'=' -f2)
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    sudo -u "${APP_USER}" php artisan key:generate --force
    ok "APP_KEY generated"
else
    ok "APP_KEY already set"
fi

# ── 7. Fix supervisor config path ────────────────────────────────────────────
section "Supervisor configuration"

SUPERVISOR_CONF="/etc/supervisor/conf.d/media-server.conf"

if [ ! -f "$SUPERVISOR_CONF" ]; then
    if [ -f "${APP_DIR}/supervisor.conf.example" ]; then
        cp "${APP_DIR}/supervisor.conf.example" "$SUPERVISOR_CONF"
        # Fix path in case it differs
        sed -i "s|/var/www/media-server|${APP_DIR}|g" "$SUPERVISOR_CONF"
        sed -i "s|/var/www/mediaserver|${APP_DIR}|g" "$SUPERVISOR_CONF"
        ok "Supervisor config installed"
    else
        warn "supervisor.conf.example not found — writing minimal config"
        cat > "$SUPERVISOR_CONF" <<SUPCONF
[program:mediaserver-monitor]
command=/usr/bin/php ${APP_DIR}/artisan stream:monitor --interval=5
directory=${APP_DIR}
user=${APP_USER}
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor/mediaserver-monitor.log

[program:mediaserver-queue]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php ${APP_DIR}/artisan queue:work redis --sleep=3 --tries=3 --timeout=120
directory=${APP_DIR}
user=${APP_USER}
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/mediaserver-queue.log

[program:mediaserver-relay-monitor]
command=/usr/bin/php ${APP_DIR}/artisan relay:health-check --interval=30
directory=${APP_DIR}
user=${APP_USER}
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor/mediaserver-relay.log

[program:mediaserver-scheduler]
command=/usr/bin/php ${APP_DIR}/artisan schedule:work
directory=${APP_DIR}
user=${APP_USER}
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor/mediaserver-scheduler.log
SUPCONF
    fi
else
    # Fix path in existing config
    sed -i "s|/var/www/media-server|${APP_DIR}|g" "$SUPERVISOR_CONF"
    sed -i "s|/var/www/mediaserver|${APP_DIR}|g" "$SUPERVISOR_CONF"
    ok "Supervisor config path updated"
fi

# Reload supervisor
supervisorctl reread
supervisorctl update
supervisorctl restart all 2>/dev/null || supervisorctl start all 2>/dev/null || true
ok "Supervisor reloaded"

# ── 8. Verify services ────────────────────────────────────────────────────────
section "Service status"

sleep 3
supervisorctl status

# ── 9. Test API ───────────────────────────────────────────────────────────────
section "API health check"

sleep 2
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/health 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ]; then
    ok "API responding: HTTP ${HTTP_CODE}"
    curl -s http://localhost/api/health | python3 -m json.tool 2>/dev/null || true
else
    warn "API returned HTTP ${HTTP_CODE} — check nginx: sudo nginx -t && sudo systemctl status nginx"
fi

# ── Summary ───────────────────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║              MediaServer Fix Complete                ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════╝${NC}"
echo ""
echo "  App:      ${APP_DIR}"
echo "  DB User:  ${DB_USER}"
echo "  DB Pass:  ${NEW_PASS}"
echo "  Logs:     tail -f ${APP_DIR}/storage/logs/laravel.log"
echo "  Monitor:  sudo supervisorctl status"
echo ""
echo "  Generate API token:"
echo "    cd ${APP_DIR} && php artisan api:token:generate \"My App\""
echo ""
