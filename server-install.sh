#!/bin/bash
# ============================================================
#  MediaServer - Ubuntu Server Install Script
#  Clones from GitHub via HTTPS — no git account needed
#
#  Run on your Ubuntu server as root:
#    bash <(curl -fsSL https://raw.githubusercontent.com/basilkewir/media-server1/main/server-install.sh)
#
#  Or with a private repo token:
#    GITHUB_TOKEN=ghp_xxx bash server-install.sh
# ============================================================

set -euo pipefail

# ── Config ────────────────────────────────────────────────────────────────────
REPO_URL="https://github.com/basilkewir/media-server1.git"
APP_DIR="/var/www/mediaserver"
APP_USER="www-data"
PHP_VER="8.3"
DB_NAME="media_server"
DB_USER="media_user"
BRANCH="${BRANCH:-main}"

# If repo is private, set GITHUB_TOKEN env var before running
# GITHUB_TOKEN is never stored on disk
if [ -n "${GITHUB_TOKEN:-}" ]; then
    CLONE_URL="https://${GITHUB_TOKEN}@github.com/basilkewir/media-server1.git"
else
    CLONE_URL="$REPO_URL"
fi

# Generate secure passwords
DB_PASS=$(openssl rand -base64 24 | tr -dc 'a-zA-Z0-9' | head -c 24)
ICECAST_PASS=$(openssl rand -base64 16 | tr -dc 'a-zA-Z0-9' | head -c 16)
ADMIN_PASS=$(openssl rand -base64 16 | tr -dc 'a-zA-Z0-9' | head -c 16)

# Detect server IP / domain
SERVER_IP=$(curl -s ifconfig.me 2>/dev/null || hostname -I | awk '{print $1}')
DOMAIN="${DOMAIN:-$SERVER_IP}"

# ── Colors ────────────────────────────────────────────────────────────────────
G='\033[0;32m'; Y='\033[1;33m'; R='\033[0;31m'; C='\033[0;36m'; N='\033[0m'
step() { echo -e "\n${C}━━━ $1 ━━━${N}"; }
ok()   { echo -e "${G}  ✓ $1${N}"; }
warn() { echo -e "${Y}  ⚠ $1${N}"; }
die()  { echo -e "${R}  ✗ $1${N}"; exit 1; }

[ "$EUID" -ne 0 ] && die "Run as root: sudo bash server-install.sh"

echo -e "${G}"
echo "  ╔══════════════════════════════════════════╗"
echo "  ║     MediaServer — Ubuntu Installer       ║"
echo "  ╚══════════════════════════════════════════╝"
echo -e "${N}"
echo "  Server:  $DOMAIN"
echo "  App dir: $APP_DIR"
echo "  Branch:  $BRANCH"
echo ""

# ── 1. System packages ────────────────────────────────────────────────────────
step "1/11 System packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get upgrade -y -qq
apt-get install -y -qq \
    curl wget git unzip zip software-properties-common \
    supervisor redis-server ffmpeg \
    ufw fail2ban gnupg lsb-release ca-certificates
ok "Base packages installed"

# ── 2. PHP 8.3 ───────────────────────────────────────────────────────────────
step "2/11 PHP $PHP_VER"
add-apt-repository -y ppa:ondrej/php 2>/dev/null || true
apt-get update -qq
apt-get install -y -qq \
    php${PHP_VER}-fpm php${PHP_VER}-cli php${PHP_VER}-common \
    php${PHP_VER}-mysql php${PHP_VER}-redis php${PHP_VER}-curl \
    php${PHP_VER}-mbstring php${PHP_VER}-xml php${PHP_VER}-zip \
    php${PHP_VER}-bcmath php${PHP_VER}-intl php${PHP_VER}-gd \
    php${PHP_VER}-tokenizer php${PHP_VER}-posix
ok "PHP $PHP_VER installed"

# ── 3. MySQL ──────────────────────────────────────────────────────────────────
step "3/11 MySQL"
apt-get install -y -qq mysql-server
systemctl enable --now mysql

mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"
ok "MySQL configured — DB: $DB_NAME, User: $DB_USER"

# ── 4. Nginx + RTMP ───────────────────────────────────────────────────────────
step "4/11 Nginx"
apt-get install -y -qq nginx libnginx-mod-rtmp
systemctl enable nginx

cat > /etc/nginx/sites-available/mediaserver <<NGINX
server {
    listen 80 default_server;
    server_name ${DOMAIN};
    root ${APP_DIR}/public;
    index index.php;

    client_max_body_size 128M;
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    # CORS for HLS/DASH players
    add_header Access-Control-Allow-Origin "*" always;
    add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" always;
    add_header Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With" always;

    if (\$request_method = OPTIONS) { return 204; }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php${PHP_VER}-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
    }

    # Serve HLS/DASH segments directly — bypass PHP for speed
    location /storage/streams/ {
        alias ${APP_DIR}/storage/streams/;
        expires -1;
        add_header Cache-Control "no-cache, no-store, must-revalidate";
        add_header Access-Control-Allow-Origin "*";
        types {
            application/vnd.apple.mpegurl  m3u8;
            video/MP2T                     ts;
            application/dash+xml           mpd;
        }
    }

    location ~ /\.(?!well-known).* { deny all; }
}
NGINX

# RTMP ingest block
cat > /etc/nginx/conf.d/rtmp.conf <<'RTMP'
rtmp {
    server {
        listen 1935;
        chunk_size 4096;
        max_connections 1000;

        application live {
            live on;
            record off;
            allow publish all;
            allow play all;

            # Notify MediaServer API when encoder connects/disconnects
            on_publish      http://127.0.0.1/api/streams/rtmp-publish;
            on_publish_done http://127.0.0.1/api/streams/rtmp-done;
        }
    }
}
RTMP

rm -f /etc/nginx/sites-enabled/default
ln -sf /etc/nginx/sites-available/mediaserver /etc/nginx/sites-enabled/mediaserver
nginx -t && systemctl reload nginx
ok "Nginx configured with RTMP"

# ── 5. Icecast2 ───────────────────────────────────────────────────────────────
step "5/11 Icecast2"
echo "icecast2 icecast2/icecast-setup boolean true"              | debconf-set-selections
echo "icecast2 icecast2/hostname string ${DOMAIN}"               | debconf-set-selections
echo "icecast2 icecast2/sourcepassword password ${ICECAST_PASS}" | debconf-set-selections
echo "icecast2 icecast2/relaypassword password ${ICECAST_PASS}"  | debconf-set-selections
echo "icecast2 icecast2/adminpassword password ${ICECAST_PASS}"  | debconf-set-selections
apt-get install -y -qq icecast2

mkdir -p /etc/icecast2/mounts
chown icecast:icecast /etc/icecast2/mounts 2>/dev/null || true

# Include per-channel mount configs
if ! grep -q "mounts" /etc/icecast2/icecast.xml 2>/dev/null; then
    sed -i 's|</icecast>|    <include>/etc/icecast2/mounts/*.xml</include>\n</icecast>|' \
        /etc/icecast2/icecast.xml 2>/dev/null || true
fi

systemctl enable --now icecast2 2>/dev/null || true
ok "Icecast2 installed (password: $ICECAST_PASS)"

# ── 6. Composer ───────────────────────────────────────────────────────────────
step "6/11 Composer"
if ! command -v composer &>/dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    chmod +x /usr/local/bin/composer
fi
ok "Composer $(composer --version --no-ansi 2>/dev/null | awk '{print $3}')"

# ── 7. Clone application ──────────────────────────────────────────────────────
step "7/11 Clone application from GitHub"
mkdir -p "$APP_DIR"

if [ -d "${APP_DIR}/.git" ]; then
    warn "Repo already exists — pulling latest changes"
    cd "$APP_DIR"
    # Use token if provided, otherwise plain pull
    if [ -n "${GITHUB_TOKEN:-}" ]; then
        git remote set-url origin "$CLONE_URL"
    fi
    git fetch origin
    git reset --hard "origin/${BRANCH}"
    git clean -fd
else
    git clone --depth=1 --branch "$BRANCH" "$CLONE_URL" "$APP_DIR"
fi

ok "Application cloned to $APP_DIR"

# ── 8. PHP dependencies ───────────────────────────────────────────────────────
step "8/11 PHP dependencies"
cd "$APP_DIR"
chown -R "${APP_USER}:${APP_USER}" "$APP_DIR"
sudo -u "$APP_USER" composer install --no-dev --optimize-autoloader --no-interaction
ok "Composer dependencies installed"

# ── 9. Environment & storage ──────────────────────────────────────────────────
step "9/11 Environment"

# Create storage directories
for dir in \
    "${APP_DIR}/storage/logs" \
    "${APP_DIR}/storage/streams" \
    "${APP_DIR}/storage/app/public" \
    "${APP_DIR}/storage/framework/cache/data" \
    "${APP_DIR}/storage/framework/sessions" \
    "${APP_DIR}/storage/framework/views" \
    "${APP_DIR}/bootstrap/cache"; do
    mkdir -p "$dir"
done
touch "${APP_DIR}/storage/logs/laravel.log"
chown -R "${APP_USER}:${APP_USER}" "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

# Write .env
cat > "${APP_DIR}/.env" <<ENV
APP_NAME="MediaServer"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://${DOMAIN}
APP_VERSION=1.2.0

LOG_CHANNEL=daily
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

CACHE_DRIVER=redis
CACHE_PREFIX=mediaserver_
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

FFMPEG_PATH=/usr/bin/ffmpeg
FFMPEG_LOG_LEVEL=warning

HLS_SEGMENT_DURATION=2
HLS_SEGMENTS_IN_PLAYLIST=10
STREAM_PIPE_ENABLED=true
STREAM_SOURCE_TIMEOUT=5
STREAM_VOD_FALLBACK_ENABLED=true
STREAM_HEALTH_CHECK_INTERVAL=5

ICECAST_HOST=localhost
ICECAST_PORT=8000
ICECAST_ADMIN_USER=admin
ICECAST_ADMIN_PASSWORD=${ICECAST_PASS}
ICECAST_SOURCE_PASSWORD=${ICECAST_PASS}
ICECAST_RELAY_PASSWORD=${ICECAST_PASS}
ICECAST_MAX_LISTENERS=1000
ICECAST_CONF_DIR=/etc/icecast2/mounts

RTMP_HOST=localhost
RTMP_PORT=1935

ADMIN_PASSWORD=${ADMIN_PASS}
ENV

chown "${APP_USER}:${APP_USER}" "${APP_DIR}/.env"
chmod 640 "${APP_DIR}/.env"

# Generate app key
cd "$APP_DIR"
sudo -u "$APP_USER" php artisan key:generate --force
sudo -u "$APP_USER" php artisan storage:link 2>/dev/null || true
ok "Environment configured"

# ── 10. Database migrations ───────────────────────────────────────────────────
step "10/11 Database"
cd "$APP_DIR"
sudo -u "$APP_USER" php artisan migrate --force
sudo -u "$APP_USER" php artisan db:seed --class=DatabaseSeeder --force 2>/dev/null || true
sudo -u "$APP_USER" php artisan config:cache
sudo -u "$APP_USER" php artisan route:cache
ok "Migrations complete"

# Generate admin API token
TOKEN_LINE=$(sudo -u "$APP_USER" php artisan api:token:generate "Admin" 2>/dev/null | grep -A1 "copy now" | tail -1 | tr -d ' ' || echo "")

# ── 11. Supervisor ────────────────────────────────────────────────────────────
step "11/11 Supervisor"
cat > /etc/supervisor/conf.d/mediaserver.conf <<SUPERVISOR
[program:mediaserver-monitor]
command=/usr/bin/php ${APP_DIR}/artisan stream:monitor --interval=5
directory=${APP_DIR}
user=${APP_USER}
autostart=true
autorestart=true
startsecs=10
startretries=10
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/monitor.log
stdout_logfile_maxbytes=20MB
stdout_logfile_backups=3
environment=HOME="/var/www",USER="${APP_USER}"

[program:mediaserver-queue]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php ${APP_DIR}/artisan queue:work redis --sleep=3 --tries=3 --timeout=120 --max-time=3600
directory=${APP_DIR}
user=${APP_USER}
autostart=true
autorestart=true
numprocs=2
startsecs=5
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/queue.log
stdout_logfile_maxbytes=20MB
stdout_logfile_backups=3
environment=HOME="/var/www",USER="${APP_USER}"

[program:mediaserver-relay-monitor]
command=/usr/bin/php ${APP_DIR}/artisan relay:health-check --interval=30
directory=${APP_DIR}
user=${APP_USER}
autostart=true
autorestart=true
startsecs=10
startretries=10
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/relay-monitor.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3
environment=HOME="/var/www",USER="${APP_USER}"

[program:mediaserver-scheduler]
command=/usr/bin/php ${APP_DIR}/artisan schedule:work
directory=${APP_DIR}
user=${APP_USER}
autostart=true
autorestart=true
startsecs=5
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/scheduler.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3
environment=HOME="/var/www",USER="${APP_USER}"

[group:mediaserver]
programs=mediaserver-monitor,mediaserver-queue,mediaserver-relay-monitor,mediaserver-scheduler
SUPERVISOR

systemctl enable --now supervisor
supervisorctl reread
supervisorctl update
supervisorctl start mediaserver:* 2>/dev/null || true
ok "Supervisor configured"

# ── Firewall ──────────────────────────────────────────────────────────────────
ufw default deny incoming 2>/dev/null || true
ufw default allow outgoing 2>/dev/null || true
ufw allow 22/tcp   2>/dev/null || true
ufw allow 80/tcp   2>/dev/null || true
ufw allow 443/tcp  2>/dev/null || true
ufw allow 1935/tcp 2>/dev/null || true
ufw allow 8000/tcp 2>/dev/null || true
ufw --force enable 2>/dev/null || true

# ── PHP-FPM tuning ────────────────────────────────────────────────────────────
systemctl enable --now php${PHP_VER}-fpm
systemctl restart php${PHP_VER}-fpm

# ── Save credentials ──────────────────────────────────────────────────────────
CREDS_FILE="/root/mediaserver-credentials.txt"
cat > "$CREDS_FILE" <<CREDS
MediaServer Credentials
=======================
Date:           $(date)
Server:         http://${DOMAIN}

Admin Panel:    http://${DOMAIN}/admin
Admin Password: ${ADMIN_PASS}

API Token:      ${TOKEN_LINE}

MySQL DB:       ${DB_NAME}
MySQL User:     ${DB_USER}
MySQL Password: ${DB_PASS}

Icecast Admin:  http://${DOMAIN}:8000/admin
Icecast Pass:   ${ICECAST_PASS}

RTMP Ingest:    rtmp://${DOMAIN}:1935/live/<stream-key>
HLS Playback:   http://${DOMAIN}/streams/<slug>/playlist.m3u8
CREDS
chmod 600 "$CREDS_FILE"

# ── Done ──────────────────────────────────────────────────────────────────────
echo ""
echo -e "${G}╔══════════════════════════════════════════════════════╗${N}"
echo -e "${G}║         MediaServer Installation Complete!           ║${N}"
echo -e "${G}╚══════════════════════════════════════════════════════╝${N}"
echo ""
echo -e "  ${C}Admin Panel:${N}    http://${DOMAIN}/admin"
echo -e "  ${C}Admin Pass:${N}     ${ADMIN_PASS}"
echo -e "  ${C}API Health:${N}     http://${DOMAIN}/api/health"
echo -e "  ${C}RTMP Ingest:${N}    rtmp://${DOMAIN}:1935/live/<key>"
echo -e "  ${C}Icecast:${N}        http://${DOMAIN}:8000"
echo ""
echo -e "  ${Y}All credentials saved to: ${CREDS_FILE}${N}"
echo ""
echo "  To update later:"
echo "    cd ${APP_DIR} && git pull && composer install --no-dev && php artisan migrate --force"
echo ""
echo "  To add SSL:"
echo "    apt-get install -y certbot python3-certbot-nginx"
echo "    certbot --nginx -d your-domain.com"
echo ""
