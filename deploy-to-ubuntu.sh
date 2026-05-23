#!/bin/bash
set -euo pipefail

# =============================================================================
# MediaServer1 — Ubuntu 22.04/24.04 Production Deployment Script
# Repo: https://github.com/basilkewir/media-server1.git
# =============================================================================

REPO_URL="https://github.com/basilkewir/media-server1.git"
APP_DIR="/var/www/mediaserver"
DOMAIN="${DOMAIN:-$(curl -s ifconfig.me || echo 'your-server-ip')}"
MYSQL_PASS="${MYSQL_PASS:-$(openssl rand -base64 32 | tr -d '=+/')}"
APP_KEY="${APP_KEY:-$(openssl rand -base64 32)}"

echo "========================================"
echo "  MediaServer1 Deployment"
echo "  Domain: ${DOMAIN}"
echo "========================================"
echo ""

# ── 1. REQUIRE ROOT ──────────────────────────────────────────────────────────
if [ "$EUID" -ne 0 ]; then
    echo "❌ Please run as root: sudo bash deploy-to-ubuntu.sh"
    exit 1
fi

# ── 2. SYSTEM UPDATE ─────────────────────────────────────────────────────────
echo "[1/12] Updating system packages..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get upgrade -y -qq

# ── 3. INSTALL DEPENDENCIES ──────────────────────────────────────────────────
echo "[2/12] Installing PHP 8.3, Nginx, MySQL, Redis, FFmpeg, Supervisor, Icecast2..."
add-apt-repository -y ppa:ondrej/php 2>/dev/null || true
apt-get update -qq

# Preseed Icecast2 debconf answers (non-interactive)
echo "icecast2 icecast2/icecast-setup boolean true" | debconf-set-selections
echo "icecast2 icecast2/hostname string localhost" | debconf-set-selections
echo "icecast2 icecast2/sourcepassword password ${MYSQL_PASS}" | debconf-set-selections
echo "icecast2 icecast2/relaypassword password ${MYSQL_PASS}" | debconf-set-selections
echo "icecast2 icecast2/adminpassword password ${MYSQL_PASS}" | debconf-set-selections

apt-get install -y -qq \
    php8.3-fpm php8.3-cli php8.3-mysql php8.3-redis php8.3-curl \
    php8.3-mbstring php8.3-xml php8.3-zip php8.3-bcmath php8.3-intl \
    php8.3-gd php8.3-tokenizer \
    nginx mysql-server redis-server ffmpeg supervisor git unzip curl icecast2 \
    certbot python3-certbot-nginx ufw openssl

# Configure Icecast2
mkdir -p /etc/icecast2/mounts
chown icecast:icecast /etc/icecast2/mounts 2>/dev/null || chown root:root /etc/icecast2/mounts

# Patch icecast.xml to include per-channel mount configs
if ! grep -q "mounts" /etc/icecast2/icecast.xml 2>/dev/null; then
    sed -i 's|</icecast>|    <!-- Per-channel mount configs -->\n    <include>/etc/icecast2/mounts/*.xml</include>\n</icecast>|' /etc/icecast2/icecast.xml 2>/dev/null || true
fi

systemctl enable icecast2 2>/dev/null || true
systemctl restart icecast2 2>/dev/null || true

# ── 4. MYSQL SETUP ───────────────────────────────────────────────────────────
echo "[3/12] Configuring MySQL..."
mysql -e "CREATE DATABASE IF NOT EXISTS media_server CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || true
mysql -e "CREATE USER IF NOT EXISTS 'media_user'@'localhost' IDENTIFIED BY '${MYSQL_PASS}';" 2>/dev/null || true
mysql -e "GRANT ALL PRIVILEGES ON media_server.* TO 'media_user'@'localhost';" 2>/dev/null || true
mysql -e "FLUSH PRIVILEGES;" 2>/dev/null || true

# ── 5. CLONE REPO ────────────────────────────────────────────────────────────
echo "[4/12] Cloning from GitHub..."
if [ -d "${APP_DIR}/.git" ]; then
    cd "${APP_DIR}" && git pull
else
    rm -rf "${APP_DIR}"
    git clone "${REPO_URL}" "${APP_DIR}"
fi

cd "${APP_DIR}"

# ── 6. COMPOSER DEPENDENCIES ─────────────────────────────────────────────────
echo "[5/12] Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# ── 7. ENVIRONMENT FILE ──────────────────────────────────────────────────────
echo "[6/12] Writing .env configuration..."
cat > "${APP_DIR}/.env" <<ENV
APP_NAME="MediaServer"
APP_ENV=production
APP_KEY=base64:${APP_KEY}
APP_DEBUG=false
APP_URL=http://${DOMAIN}
APP_VERSION=1.1.0

LOG_CHANNEL=daily
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=media_server
DB_USERNAME=media_user
DB_PASSWORD=${MYSQL_PASS}

CACHE_DRIVER=redis
CACHE_PREFIX=mediaserver

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

STREAM_SOURCE_TIMEOUT=5
STREAM_VOD_FALLBACK_ENABLED=true
STREAM_HEALTH_CHECK_INTERVAL=5
STREAM_PIPE_ENABLED=true

ICECAST_HOST=localhost
ICECAST_PORT=8000
ICECAST_ADMIN_USER=admin
ICECAST_ADMIN_PASSWORD=${MYSQL_PASS}
ICECAST_SOURCE_PASSWORD=${MYSQL_PASS}
ICECAST_RELAY_PASSWORD=${MYSQL_PASS}
ICECAST_MAX_LISTENERS_PER_STREAM=1000
ICECAST_CONF_DIR=/etc/icecast2/mounts

RTMP_HOST=localhost
RTMP_PORT=1935

API_RATE_LIMIT_PER_MINUTE=60
API_RATE_LIMIT_PER_MINUTE_AUTHENTICATED=120
ENV

# ── 8. PERMISSIONS ───────────────────────────────────────────────────────────
echo "[7/12] Setting file permissions..."
chown -R www-data:www-data "${APP_DIR}"
chmod -R 755 "${APP_DIR}"
chmod -R 775 "${APP_DIR}/storage"
chmod -R 775 "${APP_DIR}/bootstrap/cache"

# ── 9. LARAVEL SETUP ─────────────────────────────────────────────────────────
echo "[8/12] Running Laravel setup commands..."
php artisan key:generate --no-interaction
php artisan storage:link --no-interaction
php artisan migrate --force
php artisan db:seed --force

# ── 10. NGINX CONFIG ─────────────────────────────────────────────────────────
echo "[9/12] Configuring Nginx..."
cat > /etc/nginx/sites-available/mediaserver <<NGINX
server {
    listen 80;
    server_name ${DOMAIN};
    root ${APP_DIR}/public;
    index index.php;
    client_max_body_size 2100M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_read_timeout 3600;
        fastcgi_send_timeout 3600;
    }

    location /streams/ {
        add_header Cache-Control "no-cache, no-store, must-revalidate";
        add_header Access-Control-Allow-Origin "*";
        try_files \$uri \$uri/ =404;
    }

    location ~* \.(ts|m3u8|mpd)$ {
        add_header Access-Control-Allow-Origin "*";
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

rm -f /etc/nginx/sites-enabled/default
ln -sf /etc/nginx/sites-available/mediaserver /etc/nginx/sites-enabled/mediaserver
nginx -t && systemctl reload nginx

# ── 11. PHP-FPM TUNING ───────────────────────────────────────────────────────
echo "[10/12] Tuning PHP-FPM..."
sed -i 's/^pm.max_children = .*/pm.max_children = 50/' /etc/php/8.3/fpm/pool.d/www.conf 2>/dev/null || true
sed -i 's/^pm.start_servers = .*/pm.start_servers = 5/' /etc/php/8.3/fpm/pool.d/www.conf 2>/dev/null || true
sed -i 's/^pm.min_spare_servers = .*/pm.min_spare_servers = 5/' /etc/php/8.3/fpm/pool.d/www.conf 2>/dev/null || true
sed -i 's/^pm.max_spare_servers = .*/pm.max_spare_servers = 35/' /etc/php/8.3/fpm/pool.d/www.conf 2>/dev/null || true
# Upload limits for VOD files (2 GB)
echo 'php_admin_value[upload_max_filesize] = 2048M' >> /etc/php/8.3/fpm/pool.d/www.conf
echo 'php_admin_value[post_max_size] = 2100M'       >> /etc/php/8.3/fpm/pool.d/www.conf
echo 'php_admin_value[max_execution_time] = 3600'   >> /etc/php/8.3/fpm/pool.d/www.conf
echo 'php_admin_value[max_input_time] = 3600'       >> /etc/php/8.3/fpm/pool.d/www.conf
echo 'php_admin_value[memory_limit] = 512M'         >> /etc/php/8.3/fpm/pool.d/www.conf
systemctl restart php8.3-fpm

# ── yt-dlp (YouTube VOD support) ─────────────────────────────────────────────
echo "[10b] Installing yt-dlp for YouTube VOD fallback..."
curl -fsSL https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp
chmod +x /usr/local/bin/yt-dlp

# ── 12. SUPERVISOR (Queue + Monitors + Scheduler) ────────────────────────────
echo "[11/12] Configuring Supervisor..."
cat > /etc/supervisor/conf.d/mediaserver.conf <<'SUPERVISOR'
[program:mediaserver-monitor]
command=php artisan stream:monitor --interval=5
directory=/var/www/mediaserver
user=www-data
autostart=true
autorestart=true
stdout_logfile=/var/www/mediaserver/storage/logs/monitor.log
stderr_logfile=/var/www/mediaserver/storage/logs/monitor-error.log

[program:mediaserver-queue]
command=php artisan queue:work redis --sleep=3 --tries=3 --timeout=120 --max-time=3600
directory=/var/www/mediaserver
user=www-data
autostart=true
autorestart=true
numprocs=2
process_name=%(program_name)s_%(process_num)02d
stdout_logfile=/var/www/mediaserver/storage/logs/queue.log
stderr_logfile=/var/www/mediaserver/storage/logs/queue-error.log

[program:mediaserver-relay-monitor]
command=php artisan relay:health-check --interval=30
directory=/var/www/mediaserver
user=www-data
autostart=true
autorestart=true
stdout_logfile=/var/www/mediaserver/storage/logs/relay-monitor.log
stderr_logfile=/var/www/mediaserver/storage/logs/relay-monitor-error.log

[program:mediaserver-scheduler]
command=php artisan schedule:work
directory=/var/www/mediaserver
user=www-data
autostart=true
autorestart=true
stdout_logfile=/var/www/mediaserver/storage/logs/scheduler.log
stderr_logfile=/var/www/mediaserver/storage/logs/scheduler-error.log
SUPERVISOR

supervisorctl reread
supervisorctl update
supervisorctl start all || true

# ── 13. FIREWALL ─────────────────────────────────────────────────────────────
echo "[12/12] Configuring UFW firewall..."
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 1935/tcp
ufw allow 8000/tcp
ufw --force enable

# ── DONE ─────────────────────────────────────────────────────────────────────
echo ""
echo "========================================"
echo "  ✅ MediaServer1 Deployed!"
echo "========================================"
echo ""
echo "🌐 URL:        http://${DOMAIN}"
echo "🔑 MySQL Pass: ${MYSQL_PASS}"
echo ""
echo "Generate an API token:"
echo "  cd ${APP_DIR} && php artisan api:token:generate 'Production App'"
echo ""
echo "Enable SSL (replace with your domain):"
echo "  certbot --nginx -d yourdomain.com"
echo ""
echo "Useful commands:"
echo "  supervisorctl status          # Check workers"
echo "  tail -f ${APP_DIR}/storage/logs/laravel.log"
echo "  nginx -t && systemctl reload nginx"
echo ""
