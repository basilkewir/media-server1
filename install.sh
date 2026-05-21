#!/bin/bash
# MediaServer - Full Installation Script for Ubuntu 22.04 / 24.04
# Run as root: sudo bash install.sh

set -euo pipefail

APP_DIR="/var/www/media-server"
APP_USER="www-data"
PHP_VER="8.2"
DB_NAME="media_server"
DB_USER="media_user"
DB_PASS=$(openssl rand -base64 24 | tr -dc 'a-zA-Z0-9' | head -c 20)

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

[ "$EUID" -ne 0 ] && error "Run as root: sudo bash install.sh"

info "=== MediaServer Installation ==="
info "Ubuntu: $(lsb_release -ds)"

# ── System packages ──────────────────────────────────────────────────────────
info "Updating system..."
apt-get update -qq
apt-get upgrade -y -qq

info "Installing base dependencies..."
apt-get install -y -qq \
    curl wget git zip unzip build-essential \
    software-properties-common supervisor \
    redis-server ffmpeg \
    libavformat-dev libavcodec-dev libavdevice-dev libswscale-dev \
    pkg-config gnupg lsb-release ca-certificates

# ── PHP 8.2 ──────────────────────────────────────────────────────────────────
info "Installing PHP ${PHP_VER}..."
add-apt-repository -y ppa:ondrej/php
apt-get update -qq
apt-get install -y -qq \
    php${PHP_VER} php${PHP_VER}-fpm php${PHP_VER}-cli php${PHP_VER}-common \
    php${PHP_VER}-curl php${PHP_VER}-gd php${PHP_VER}-mbstring \
    php${PHP_VER}-mysql php${PHP_VER}-redis php${PHP_VER}-xml \
    php${PHP_VER}-zip php${PHP_VER}-bcmath php${PHP_VER}-intl \
    php${PHP_VER}-pcov php${PHP_VER}-posix

# ── MySQL ─────────────────────────────────────────────────────────────────────
info "Installing MySQL..."
apt-get install -y -qq mysql-server

systemctl enable mysql
systemctl start mysql

mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"
info "MySQL database '${DB_NAME}' created, user '${DB_USER}'"

# ── Nginx + nginx-rtmp ────────────────────────────────────────────────────────
info "Installing Nginx with RTMP module..."
apt-get install -y -qq nginx libnginx-mod-rtmp

# ── Icecast2 ──────────────────────────────────────────────────────────────────
info "Installing Icecast2..."
# Pre-answer debconf questions to avoid interactive prompt
ICECAST_PASS=$(openssl rand -base64 12 | tr -dc 'a-zA-Z0-9' | head -c 16)
echo "icecast2 icecast2/icecast-setup boolean true"          | debconf-set-selections
echo "icecast2 icecast2/hostname string localhost"            | debconf-set-selections
echo "icecast2 icecast2/sourcepassword password ${ICECAST_PASS}" | debconf-set-selections
echo "icecast2 icecast2/relaypassword password ${ICECAST_PASS}"  | debconf-set-selections
echo "icecast2 icecast2/adminpassword password ${ICECAST_PASS}"  | debconf-set-selections
DEBIAN_FRONTEND=noninteractive apt-get install -y -qq icecast2

# Create mounts directory for per-channel configs
mkdir -p /etc/icecast2/mounts
chown icecast:icecast /etc/icecast2/mounts

# Patch icecast.xml to include mounts directory
if ! grep -q "mounts" /etc/icecast2/icecast.xml 2>/dev/null; then
    sed -i 's|</icecast>|    <!-- Per-channel mount configs -->\n    <include>/etc/icecast2/mounts/*.xml</include>\n</icecast>|' /etc/icecast2/icecast.xml
fi

systemctl enable icecast2
systemctl start icecast2

# ── Composer ──────────────────────────────────────────────────────────────────
info "Installing Composer..."
if ! command -v composer &>/dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# ── Application setup ─────────────────────────────────────────────────────────
info "Setting up application at ${APP_DIR}..."
mkdir -p "${APP_DIR}"

# Copy application files (assumes script is run from project root)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [ "${SCRIPT_DIR}" != "${APP_DIR}" ]; then
    rsync -a --exclude='.git' --exclude='vendor' --exclude='node_modules' \
        "${SCRIPT_DIR}/" "${APP_DIR}/"
fi

cd "${APP_DIR}"

# Set permissions before composer
chown -R "${APP_USER}:${APP_USER}" "${APP_DIR}"
chmod -R 755 "${APP_DIR}"

# Install PHP dependencies
info "Installing PHP dependencies..."
sudo -u "${APP_USER}" composer install --no-dev --optimize-autoloader --no-interaction

# Environment file
if [ ! -f "${APP_DIR}/.env" ]; then
    cp "${APP_DIR}/.env.example" "${APP_DIR}/.env"
fi

# Inject generated credentials into .env
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|"   "${APP_DIR}/.env"
sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USER}|"   "${APP_DIR}/.env"
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|"   "${APP_DIR}/.env"
sed -i "s|^ICECAST_ADMIN_PASSWORD=.*|ICECAST_ADMIN_PASSWORD=${ICECAST_PASS}|" "${APP_DIR}/.env" 2>/dev/null || \
    echo "ICECAST_ADMIN_PASSWORD=${ICECAST_PASS}" >> "${APP_DIR}/.env"
sed -i "s|^ICECAST_SOURCE_PASSWORD=.*|ICECAST_SOURCE_PASSWORD=${ICECAST_PASS}|" "${APP_DIR}/.env" 2>/dev/null || \
    echo "ICECAST_SOURCE_PASSWORD=${ICECAST_PASS}" >> "${APP_DIR}/.env"

sudo -u "${APP_USER}" php artisan key:generate --force

# Storage directories
mkdir -p "${APP_DIR}/storage/streams"
mkdir -p "${APP_DIR}/storage/logs"
mkdir -p "${APP_DIR}/storage/framework/cache"
mkdir -p "${APP_DIR}/storage/framework/sessions"
mkdir -p "${APP_DIR}/storage/framework/views"
mkdir -p "${APP_DIR}/bootstrap/cache"

chown -R "${APP_USER}:${APP_USER}" "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

# Run migrations
info "Running database migrations..."
sudo -u "${APP_USER}" php artisan migrate --force

# Storage link
sudo -u "${APP_USER}" php artisan storage:link || true

# ── Nginx configuration ───────────────────────────────────────────────────────
info "Configuring Nginx..."
cat > /etc/nginx/sites-available/media-server <<'NGINX'
server {
    listen 80;
    server_name _;
    root /var/www/media-server/public;
    index index.php;

    add_header Access-Control-Allow-Origin *;
    add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS";
    add_header Access-Control-Allow-Headers "Content-Type, Authorization";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # HLS segments - serve directly from storage
    location /streams/ {
        alias /var/www/media-server/storage/streams/;
        add_header Cache-Control "no-cache";
        add_header Access-Control-Allow-Origin *;
        types {
            application/vnd.apple.mpegurl m3u8;
            video/MP2T ts;
            application/dash+xml mpd;
        }
    }

    location ~ /\.ht { deny all; }
}
NGINX

# RTMP block in nginx
cat > /etc/nginx/modules-enabled/rtmp.conf <<'RTMP'
rtmp {
    server {
        listen 1935;
        chunk_size 4096;

        application live {
            live on;
            record off;

            # Push to HLS output handled by FFmpeg via MediaServer
            # Notify MediaServer on publish/unpublish
            on_publish http://127.0.0.1/api/streams/start;
            on_publish_done http://127.0.0.1/api/streams/stop;
        }
    }
}
RTMP

ln -sf /etc/nginx/sites-available/media-server /etc/nginx/sites-enabled/media-server
rm -f /etc/nginx/sites-enabled/default

nginx -t && systemctl enable nginx && systemctl restart nginx

# ── PHP-FPM ───────────────────────────────────────────────────────────────────
systemctl enable php${PHP_VER}-fpm
systemctl restart php${PHP_VER}-fpm

# ── Redis ─────────────────────────────────────────────────────────────────────
systemctl enable redis-server
systemctl start redis-server

# ── Supervisor ────────────────────────────────────────────────────────────────
info "Configuring Supervisor..."
cp "${APP_DIR}/supervisor.conf.example" /etc/supervisor/conf.d/media-server.conf
sed -i "s|/var/www/media-server|${APP_DIR}|g" /etc/supervisor/conf.d/media-server.conf

systemctl enable supervisor
systemctl restart supervisor
supervisorctl reread
supervisorctl update

# ── Log rotation ──────────────────────────────────────────────────────────────
cat > /etc/logrotate.d/media-server <<LOGROTATE
${APP_DIR}/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 ${APP_USER} ${APP_USER}
    sharedscripts
    postrotate
        systemctl reload php${PHP_VER}-fpm > /dev/null 2>&1 || true
    endscript
}
LOGROTATE

# ── Firewall ──────────────────────────────────────────────────────────────────
if command -v ufw &>/dev/null; then
    ufw allow 80/tcp   comment "HTTP"
    ufw allow 443/tcp  comment "HTTPS"
    ufw allow 1935/tcp comment "RTMP"
    ufw allow 8000/tcp comment "Icecast"
fi

# ── Summary ───────────────────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║         MediaServer Installation Complete!           ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════╝${NC}"
echo ""
echo "  App URL:        http://$(hostname -I | awk '{print $1}')"
echo "  API Base:       http://$(hostname -I | awk '{print $1}')/api"
echo "  RTMP Ingest:    rtmp://$(hostname -I | awk '{print $1}'):1935/live/<stream-key>"
echo "  Icecast:        http://$(hostname -I | awk '{print $1}'):8000"
echo ""
echo "  DB Name:        ${DB_NAME}"
echo "  DB User:        ${DB_USER}"
echo "  DB Password:    ${DB_PASS}"
echo "  Icecast Pass:   ${ICECAST_PASS}"
echo ""
echo "  Credentials saved to: ${APP_DIR}/.env"
echo ""
echo "  Quick test:"
echo "    curl http://localhost/api/health"
echo "    curl -X POST http://localhost/api/channels \\"
echo "      -H 'Content-Type: application/json' \\"
echo "      -d '{\"name\":\"Test\",\"slug\":\"test\",\"vod_playlist_url\":\"http://example.com/playlist.m3u8\"}'"
echo ""
