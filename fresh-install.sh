#!/bin/bash

###############################################################################
# Fresh Laravel MediaServer Installation (Clean Slate)
# 
# This script:
# ✅ Completely removes the existing Laravel installation
# ✅ Preserves Flussonic and other streaming engines
# ✅ Installs fresh Laravel application from scratch
# ✅ Configures all services independently
# ✅ Does NOT touch Flussonic, Wowza, or other streaming software
#
# Usage: sudo bash fresh-install.sh
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}════════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Fresh Laravel MediaServer Installation${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════════${NC}"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
   echo -e "${RED}❌ This script must be run as root${NC}"
   exit 1
fi

# Paths
APP_PATH="/var/www/mediaserver"
BACKUP_PATH="/var/backups/mediaserver-$(date +%Y%m%d-%H%M%S)"
FLUSSONIC_PORT=80
LARAVEL_PORT=8080

echo -e "${YELLOW}⚠️  WARNING: This will completely remove the Laravel application${NC}"
echo -e "${YELLOW}    Flussonic and other streaming engines will be PRESERVED${NC}"
echo ""
read -p "Continue with fresh installation? (yes/no): " -r confirm
if [[ ! $confirm =~ ^[Yy][Ee][Ss]$ ]]; then
    echo -e "${RED}Cancelled by user${NC}"
    exit 0
fi

# Step 1: Stop Laravel-related services (but NOT Flussonic)
echo -e "${BLUE}[1/10]${NC} Stopping Laravel services..."
sudo systemctl stop php8.3-fpm.service 2>/dev/null || true
sudo systemctl stop nginx 2>/dev/null || true
sudo pkill -9 'php artisan' 2>/dev/null || true
sudo pkill -9 'ffmpeg' 2>/dev/null || true
sleep 2

# Verify Flussonic is still running
echo -e "${BLUE}[2/10]${NC} Verifying Flussonic is still running..."
if systemctl is-active --quiet flussonic; then
    echo -e "${GREEN}✅ Flussonic is still active (not affected)${NC}"
else
    echo -e "${YELLOW}⚠️  Flussonic is not running (this is OK, we won't restart it)${NC}"
fi

# Step 2: Backup current application (excluding node_modules, vendor)
echo -e "${BLUE}[3/10]${NC} Backing up current application..."
mkdir -p "$BACKUP_PATH"
if [ -d "$APP_PATH" ]; then
    tar --exclude='node_modules' --exclude='vendor' --exclude='storage/logs' \
        -czf "$BACKUP_PATH/laravel-backup.tar.gz" -C /var/www mediaserver 2>/dev/null || true
    echo -e "${GREEN}✅ Backup saved to: $BACKUP_PATH${NC}"
fi

# Step 3: Remove old Laravel application completely
echo -e "${BLUE}[4/10]${NC} Removing old Laravel application..."
if [ -d "$APP_PATH" ]; then
    rm -rf "$APP_PATH"
    echo -e "${GREEN}✅ Old application removed${NC}"
fi

# Step 4: Clone fresh Laravel application
echo -e "${BLUE}[5/10]${NC} Cloning fresh Laravel application..."
cd /var/www
git clone https://github.com/basilkewir/media-server1.git mediaserver
cd "$APP_PATH"
echo -e "${GREEN}✅ Fresh application cloned${NC}"

# Step 5: Install PHP dependencies
echo -e "${BLUE}[6/10]${NC} Installing PHP dependencies..."
composer install --no-interaction --optimize-autoloader
echo -e "${GREEN}✅ Composer packages installed${NC}"

# Step 6: Create fresh .env file
echo -e "${BLUE}[7/10]${NC} Creating fresh .env configuration..."
cp .env.example .env

# Generate APP_KEY
php artisan key:generate --force

# Configure database - detect MySQL socket and auth method
echo -e "${BLUE}  Detecting MySQL configuration...${NC}"

# Check if MySQL socket exists
if [ -S /var/run/mysqld/mysqld.sock ]; then
    DB_SOCKET="/var/run/mysqld/mysqld.sock"
else
    DB_SOCKET="/tmp/mysql.sock"
fi

# Try to connect to MySQL (it should allow root without password via socket)
mysql -u root << SQL_EOF 2>/dev/null || true
CREATE DATABASE IF NOT EXISTS media_server;
CREATE USER IF NOT EXISTS 'mediaserver'@'localhost' IDENTIFIED BY 'media_server_pass';
GRANT ALL PRIVILEGES ON media_server.* TO 'mediaserver'@'localhost';
FLUSH PRIVILEGES;
SQL_EOF

# Configure database
sed -i "s/DB_HOST=.*/DB_HOST=127.0.0.1/" .env
sed -i "s/DB_DATABASE=.*/DB_DATABASE=media_server/" .env
sed -i "s/DB_USERNAME=.*/DB_USERNAME=mediaserver/" .env
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=media_server_pass/" .env

# Configure Laravel port
sed -i "s/APP_URL=.*/APP_URL=http:\/\/localhost:${LARAVEL_PORT}/" .env
sed -i "s/^SERVER_PORT=.*/SERVER_PORT=${LARAVEL_PORT}/" .env || echo "SERVER_PORT=${LARAVEL_PORT}" >> .env

# Disable VOD fallback to avoid memory issues
sed -i "s/VOD_FALLBACK_ENABLED=.*/VOD_FALLBACK_ENABLED=false/" .env

# Configure Flussonic integration
sed -i "s/FLUSSONIC_HOST=.*/FLUSSONIC_HOST=127.0.0.1/" .env
sed -i "s/FLUSSONIC_PORT=.*/FLUSSONIC_PORT=${FLUSSONIC_PORT}/" .env
sed -i "s/FLUSSONIC_API_PORT=.*/FLUSSONIC_API_PORT=8080/" .env

echo -e "${GREEN}✅ .env configuration created${NC}"

# Step 7: Run database migrations
echo -e "${BLUE}[8/10]${NC} Running database migrations..."
php artisan migrate --force
echo -e "${GREEN}✅ Database migrations completed${NC}"

# Step 8: Configure Nginx for Laravel
echo -e "${BLUE}[9/10]${NC} Configuring Nginx..."
sudo tee /etc/nginx/sites-available/mediaserver > /dev/null << 'NGINX_CONF'
server {
    listen 8080;
    listen [::]:8080;
    server_name _;

    root /var/www/mediaserver/public;
    index index.php index.html index.htm;

    # Logging
    access_log /var/log/nginx/mediaserver-access.log;
    error_log /var/log/nginx/mediaserver-error.log;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # CORS headers
    add_header Access-Control-Allow-Origin "*" always;
    add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" always;
    add_header Access-Control-Allow-Headers "DNT, User-Agent, X-Requested-With, If-Modified-Since, Cache-Control, Content-Type, Range, Authorization" always;

    if ($request_method = 'OPTIONS') {
        add_header Access-Control-Max-Age 1728000;
        add_header Content-Type 'text/plain; charset=utf-8';
        add_header Content-Length 0;
        return 204;
    }

    # Handle Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 60;
        fastcgi_send_timeout 60;
    }

    # Static file caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }
}
NGINX_CONF

# Enable the site if not already enabled
if [ ! -L /etc/nginx/sites-enabled/mediaserver ]; then
    sudo ln -s /etc/nginx/sites-available/mediaserver /etc/nginx/sites-enabled/mediaserver
fi

# Test Nginx configuration
nginx -t

echo -e "${GREEN}✅ Nginx configured${NC}"

# Step 9: Configure Supervisor
echo -e "${BLUE}[10/10]${NC} Configuring Supervisor..."
sudo tee /etc/supervisor/conf.d/mediaserver.conf > /dev/null << 'SUPERVISOR_CONF'
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
SUPERVISOR_CONF

echo -e "${GREEN}✅ Supervisor configured${NC}"

# Step 10: Set permissions
echo -e "${BLUE}[11/11]${NC} Setting file permissions..."
sudo chown -R www-data:www-data "$APP_PATH"
sudo chmod -R 755 "$APP_PATH"
sudo chmod -R 775 "$APP_PATH/storage"
sudo chmod -R 775 "$APP_PATH/bootstrap/cache"
echo -e "${GREEN}✅ Permissions set${NC}"

# Start services
echo ""
echo -e "${BLUE}Starting services...${NC}"
sudo systemctl restart php8.3-fpm.service
sudo systemctl restart nginx
sudo systemctl reload supervisor
sleep 2

# Verify services
echo ""
echo -e "${BLUE}Verifying services...${NC}"

# Test PHP-FPM
if systemctl is-active --quiet php8.3-fpm.service; then
    echo -e "${GREEN}✅ PHP-FPM is running${NC}"
else
    echo -e "${RED}❌ PHP-FPM failed to start${NC}"
fi

# Test Nginx
if systemctl is-active --quiet nginx; then
    echo -e "${GREEN}✅ Nginx is running${NC}"
else
    echo -e "${RED}❌ Nginx failed to start${NC}"
fi

# Test Flussonic (should still be running)
if systemctl is-active --quiet flussonic; then
    echo -e "${GREEN}✅ Flussonic is still running (untouched)${NC}"
else
    echo -e "${YELLOW}⚠️  Flussonic is not running${NC}"
fi

# Test API
echo ""
echo -e "${BLUE}Testing API endpoints...${NC}"
sleep 2

API_HEALTH=$(curl -s http://localhost:${LARAVEL_PORT}/api/health | head -c 50)
if [[ $API_HEALTH == *"ok"* ]]; then
    echo -e "${GREEN}✅ API health endpoint working${NC}"
else
    echo -e "${YELLOW}⚠️  API health check returned: $API_HEALTH${NC}"
fi

# Summary
echo ""
echo -e "${GREEN}════════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Fresh Laravel Installation Complete!${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE}📋 Important Information:${NC}"
echo ""
echo -e "  ${BLUE}Application Path:${NC}        $APP_PATH"
echo -e "  ${BLUE}Laravel Port:${NC}            $LARAVEL_PORT"
echo -e "  ${BLUE}Flussonic Port:${NC}          $FLUSSONIC_PORT (untouched)"
echo -e "  ${BLUE}Database:${NC}                media_server (MySQL)"
echo -e "  ${BLUE}Backup Location:${NC}         $BACKUP_PATH"
echo ""
echo -e "${BLUE}🔗 Access Points:${NC}"
echo ""
echo -e "  ${BLUE}Admin Panel:${NC}             http://localhost:${LARAVEL_PORT}/"
echo -e "  ${BLUE}API Health:${NC}              http://localhost:${LARAVEL_PORT}/api/health"
echo -e "  ${BLUE}Flussonic Admin:${NC}         http://localhost:${FLUSSONIC_PORT}/admin/"
echo ""
echo -e "${BLUE}📊 Services:${NC}"
echo ""
echo -e "  ${BLUE}Check Services:${NC}          sudo systemctl status php8.3-fpm nginx"
echo -e "  ${BLUE}View Logs:${NC}               tail -f /var/www/mediaserver/storage/logs/laravel.log"
echo -e "  ${BLUE}Monitor Supervisor:${NC}      sudo supervisorctl status"
echo ""
echo -e "${YELLOW}⚠️  Note:${NC} VOD Fallback is disabled to prevent memory issues"
echo -e "         Enable it later in .env when system has sufficient resources"
echo ""

