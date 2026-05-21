#!/bin/bash
set -euo pipefail

# MediaServer Remote VPS Deployment Script
# Run this on your Ubuntu VPS after cloning from GitHub

REPO_URL="${REPO_URL:-https://github.com/basilkewir/media-server1.git}"
DOMAIN="${DOMAIN:-5.180.182.232}"
MYSQL_PASS="${MYSQL_PASS:-$(openssl rand -base64 32 | tr -d '=+/')}"
APP_KEY="${APP_KEY:-$(openssl rand -base64 32)}"

echo "========================================"
echo "  MediaServer Remote Deployment"
echo "========================================"
echo ""

# ── Check root ──────────────────────────────────────────────────────────────
if [ "$EUID" -ne 0 ]; then
    echo "Please run as root (sudo bash deploy-remote.sh)"
    exit 1
fi

# ── System Update ───────────────────────────────────────────────────────────
echo "[1/10] Updating system..."
apt-get update -qq
apt-get upgrade -y -qq

# ── Install Dependencies ────────────────────────────────────────────────────
echo "[2/10] Installing PHP, Nginx, MySQL, Redis, FFmpeg..."
add-apt-repository -y ppa:ondrej/php 2>/dev/null || true
apt-get update -qq

apt-get install -y -qq \
    php8.3-fpm php8.3-cli php8.3-mysql php8.3-redis php8.3-curl \
    php8.3-mbstring php8.3-xml php8.3-zip php8.3-bcmath php8.3-intl \
    nginx mysql-server redis-server ffmpeg supervisor git unzip curl \
    certbot python3-certbot-nginx ufw

# ── MySQL Setup ─────────────────────────────────────────────────────────────
echo "[3/10] Setting up MySQL..."
mysql -e "CREATE DATABASE IF NOT EXISTS media_server CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" || true
mysql -e "CREATE USER IF NOT EXISTS 'media_user'@'localhost' IDENTIFIED BY '${MYSQL_PASS}';" || true
mysql -e "GRANT ALL PRIVILEGES ON media_server.* TO 'media_user'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# ── Clone / Update Application ──────────────────────────────────────────────
echo "[4/10] Cloning application..."
mkdir -p /var/www/mediaserver

if [ -d "/var/www/mediaserver/.git" ]; then
    cd /var/www/mediaserver && git pull
else
    git clone "${REPO_URL}" /var/www/mediaserver
fi

cd /var/www/mediaserver

# ── Install Dependencies ────────────────────────────────────────────────────
echo "[5/10] Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# ── Environment ─────────────────────────────────────────────────────────────
echo "[6/10] Configuring environment..."
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

# ── Permissions ─────────────────────────────────────────────────────────────
echo "[7/10] Setting permissions..."
chown -R www-data:www-data /var/www/mediaserver
chmod -R 755 /var/www/mediaserver
chmod -R 775 /var/www/mediaserver/storage
chmod -R 775 /var/www/mediaserver/bootstrap/cache

# ── Migrations & Seed ───────────────────────────────────────────────────────
echo "[8/10] Running migrations..."
php artisan migrate --force
php artisan db:seed --force

# ── Nginx Config ────────────────────────────────────────────────────────────
echo "[9/10] Configuring Nginx..."
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
    }

    location /streams/ {
        add_header Cache-Control "no-cache, no-store, must-revalidate";
        add_header Access-Control-Allow-Origin "*";
    }

    location ~ /\.(?!well-known).* { deny all; }
}
NGINX

rm -f /etc/nginx/sites-enabled/default
ln -sf /etc/nginx/sites-available/mediaserver /etc/nginx/sites-enabled/mediaserver
nginx -t && systemctl reload nginx

# ── Supervisor ──────────────────────────────────────────────────────────────
echo "[10/10] Configuring Supervisor..."
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
command=php artisan queue:work redis --sleep=3 --tries=3 --timeout=120
directory=/var/www/mediaserver
user=www-data
autostart=true
autorestart=true
numprocs=2
process_name=%(program_name)s_%(process_num)02d

[program:mediaserver-scheduler]
command=php artisan schedule:work
directory=/var/www/mediaserver
user=www-data
autostart=true
autorestart=true
SUPERVISOR

supervisorctl reread && supervisorctl update && supervisorctl start all

# ── Firewall ────────────────────────────────────────────────────────────────
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 1935/tcp
ufw allow 8000/tcp
ufw --force enable

# ── Done ────────────────────────────────────────────────────────────────────
echo ""
echo "========================================"
echo "  Deployment Complete!"
echo "========================================"
echo ""
echo "URL:        http://${DOMAIN}"
echo "Admin:      http://${DOMAIN}/admin/access-codes/create"
echo "Health:     http://${DOMAIN}/api/health"
echo "MySQL Pass: ${MYSQL_PASS}"
echo ""
echo "Generate API token:"
echo "  cd /var/www/mediaserver && php artisan api:token:generate 'Production'"
echo ""
echo "Set up SSL:"
echo "  certbot --nginx -d ${DOMAIN}"
echo ""
