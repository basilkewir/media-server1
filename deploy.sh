#!/bin/bash
set -euo pipefail

# MediaServer Ubuntu Deployment Script
# Run on your VPS: bash deploy.sh

SERVER_IP="${SERVER_IP:-5.180.182.232}"
DOMAIN="${DOMAIN:-$SERVER_IP}"
MYSQL_PASS="${MYSQL_PASS:-$(openssl rand -base64 32)}"
APP_KEY="${APP_KEY:-$(openssl rand -base64 32)}"
API_TOKEN="${API_TOKEN:-$(openssl rand -hex 32)}"

echo "========================================"
echo "  MediaServer Deployment Script"
echo "========================================"
echo ""

# ── System Update ───────────────────────────────────────────────────────────
echo "[1/12] Updating system packages..."
apt-get update -qq
apt-get upgrade -y -qq

# ── Install Dependencies ────────────────────────────────────────────────────
echo "[2/12] Installing dependencies (PHP 8.3, Nginx, MySQL, Redis, FFmpeg, Icecast2)..."
add-apt-repository -y ppa:ondrej/php 2>/dev/null || true
apt-get update -qq

apt-get install -y -qq \
    php8.3-fpm php8.3-cli php8.3-mysql php8.3-redis php8.3-curl php8.3-mbstring \
    php8.3-xml php8.3-zip php8.3-bcmath php8.3-tokenizer php8.3-intl php8.3-gd \
    nginx mysql-server redis-server ffmpeg icecast2 supervisor curl git unzip \
    composer certbot python3-certbot-nginx ufw fail2ban

# ── MySQL Setup ─────────────────────────────────────────────────────────────
echo "[3/12] Configuring MySQL..."
mysql -e "CREATE DATABASE IF NOT EXISTS media_server CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || true
mysql -e "CREATE USER IF NOT EXISTS 'media_user'@'localhost' IDENTIFIED BY '${MYSQL_PASS}';" 2>/dev/null || true
mysql -e "GRANT ALL PRIVILEGES ON media_server.* TO 'media_user'@'localhost';" 2>/dev/null || true
mysql -e "FLUSH PRIVILEGES;"

# ── Nginx + RTMP Setup ──────────────────────────────────────────────────────
echo "[4/12] Configuring Nginx..."
mkdir -p /etc/nginx/modules-enabled
cat > /etc/nginx/modules-enabled/rtmp.conf <<'NGINXRTMP'
# RTMP module stub — install nginx-full with libnginx-mod-rtmp for full RTMP support
# For now, HLS/DASH segments are served directly by PHP/Laravel
NGINXRTMP

cat > /etc/nginx/sites-available/mediaserver <<NGINX
server {
    listen 80;
    server_name ${DOMAIN};
    root /var/www/mediaserver/public;
    index index.php;

    client_max_body_size 64M;

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

# ── Firewall ────────────────────────────────────────────────────────────────
echo "[5/12] Configuring firewall..."
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 1935/tcp
ufw allow 8000/tcp
ufw --force enable

# ── PHP-FPM Tuning ──────────────────────────────────────────────────────────
echo "[6/12] Tuning PHP-FPM..."
sed -i 's/^pm.max_children = .*/pm.max_children = 50/' /etc/php/8.3/fpm/pool.d/www.conf
sed -i 's/^pm.start_servers = .*/pm.start_servers = 5/' /etc/php/8.3/fpm/pool.d/www.conf
sed -i 's/^pm.min_spare_servers = .*/pm.min_spare_servers = 5/' /etc/php/8.3/fpm/pool.d/www.conf
sed -i 's/^pm.max_spare_servers = .*/pm.max_spare_servers = 35/' /etc/php/8.3/fpm/pool.d/www.conf
systemctl restart php8.3-fpm

# ── Deploy Application ──────────────────────────────────────────────────────
echo "[7/12] Deploying MediaServer application..."
mkdir -p /var/www/mediaserver

if [ -d "/var/www/mediaserver/.git" ]; then
    cd /var/www/mediaserver && git pull || true
else
    # Copy from local source if available, otherwise clone
    if [ -d "$(dirname "$0")/.git" ]; then
        cp -r "$(dirname "$0")"/. /var/www/mediaserver/
    else
        echo "WARNING: No git repo found. Copy your project to /var/www/mediaserver manually."
    fi
fi

chown -R www-data:www-data /var/www/mediaserver
chmod -R 755 /var/www/mediaserver
chmod -R 775 /var/www/mediaserver/storage
chmod -R 775 /var/www/mediaserver/bootstrap/cache

# ── Install PHP Dependencies ────────────────────────────────────────────────
echo "[8/12] Installing Composer dependencies..."
cd /var/www/mediaserver
composer install --no-dev --optimize-autoloader --no-interaction

# ── Environment & Key ───────────────────────────────────────────────────────
echo "[9/12] Configuring environment..."
cat > /var/www/mediaserver/.env <<ENV
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
ICECAST_MAX_LISTENERS_PER_STREAM=1000
ICECAST_CONF_DIR=/etc/icecast2/mounts

RTMP_HOST=localhost
RTMP_PORT=1935

API_RATE_LIMIT_PER_MINUTE=60
API_RATE_LIMIT_PER_MINUTE_AUTHENTICATED=120
ENV

# ── Migrations & Seed ───────────────────────────────────────────────────────
echo "[10/12] Running migrations and seeders..."
cd /var/www/mediaserver
php artisan migrate --force
php artisan db:seed --force

# ── Generate API Token ──────────────────────────────────────────────────────
echo "[11/12] Generating default API token..."
TOKEN_OUTPUT=$(php artisan api:token:generate "Default Admin" --no-interaction 2>&1 || true)
echo "$TOKEN_OUTPUT"

# ── Supervisor (Queue Workers + Monitors) ───────────────────────────────────
echo "[12/12] Configuring Supervisor..."
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
supervisorctl start all

# ── Done ────────────────────────────────────────────────────────────────────
echo ""
echo "========================================"
echo "  MediaServer Deployment Complete!"
echo "========================================"
echo ""
echo "URL:        http://${DOMAIN}"
echo "Admin:      http://${DOMAIN}/admin/access-codes/create"
echo "Health:     http://${DOMAIN}/api/health"
echo "MySQL Pass: ${MYSQL_PASS}"
echo ""
echo "API Token:  Check above output for token"
echo ""
echo "Next steps:"
echo "  1. Set up SSL: certbot --nginx -d ${DOMAIN}"
echo "  2. Configure Icecast2: /etc/icecast2/icecast.xml"
echo "  3. Tune Redis: /etc/redis/redis.conf"
echo ""
