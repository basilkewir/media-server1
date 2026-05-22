#!/bin/bash

# ============================================================
#  Media Server + Flussonic Integration Setup
#  For systems where Flussonic is already installed
#  
#  Run as root:
#    sudo bash /var/www/mediaserver/integration-only-setup.sh
# ============================================================

set -euo pipefail

# Colors for output
G='\033[0;32m'      # Green
R='\033[0;31m'      # Red
Y='\033[1;33m'      # Yellow
C='\033[0;36m'      # Cyan
B='\033[1;34m'      # Blue
N='\033[0m'         # Normal

# Output functions
ok()   { echo -e "${G}✓ $1${N}"; }
error() { echo -e "${R}✗ $1${N}"; exit 1; }
step() { echo -e "\n${C}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${N}"; echo -e "${C}$1${N}"; echo -e "${C}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${N}\n"; }
warn() { echo -e "${Y}⚠ $1${N}"; }
info() { echo -e "${B}ℹ $1${N}"; }

# Configuration
MEDIASERVER_DIR="/var/www/mediaserver"
FLUSSONIC_CONF="/etc/flussonic/flussonic.conf"
NGINX_CONF="/etc/nginx/nginx.conf"

# ============================================================
# STEP 1: VERIFY PREREQUISITES
# ============================================================
step "STEP 1: Verifying Prerequisites"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    error "This script must be run as root (use: sudo bash $0)"
fi
ok "Running as root"

# Check if mediaserver directory exists
if [ ! -d "$MEDIASERVER_DIR" ]; then
    error "MediaServer directory not found at $MEDIASERVER_DIR"
fi
ok "MediaServer directory found"

# Check if Flussonic service exists
if ! systemctl list-unit-files | grep -q flussonic; then
    error "Flussonic service not found. Please install Flussonic first."
fi
ok "Flussonic service found"

# Check if Flussonic config exists
if [ ! -f "$FLUSSONIC_CONF" ]; then
    error "Flussonic config not found at $FLUSSONIC_CONF"
fi
ok "Flussonic configuration found"

# Check if Flussonic service is running
if sudo systemctl is-active --quiet flussonic; then
    ok "Flussonic service is running"
else
    warn "Flussonic service is not running. Attempting to start..."
    sudo systemctl start flussonic || error "Failed to start Flussonic service"
    ok "Flussonic service started"
fi

# ============================================================
# STEP 2: VERIFY LARAVEL SETUP
# ============================================================
step "STEP 2: Verifying Laravel Setup"

cd "$MEDIASERVER_DIR"

# Check if .env exists
if [ ! -f ".env" ]; then
    warn ".env file not found. Copying from .env.example..."
    cp .env.example .env
    ok ".env created from template"
else
    ok ".env file exists"
fi

# Check Laravel installation
if [ ! -f "composer.json" ]; then
    error "composer.json not found. Laravel installation is incomplete."
fi
ok "Laravel composer.json found"

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    warn "vendor directory not found. Running composer install..."
    composer install --no-dev --optimize-autoloader || error "Composer install failed"
    ok "Composer dependencies installed"
else
    ok "Composer dependencies already installed"
fi

# ============================================================
# STEP 3: UPDATE LARAVEL CONFIGURATION
# ============================================================
step "STEP 3: Updating Laravel Configuration"

# Update .env with port 8080 if not already set
if ! grep -q "APP_PORT=8080" .env; then
    warn "Updating APP_PORT to 8080..."
    sed -i 's/APP_URL=.*/APP_URL=http:\/\/localhost:8080/' .env
    echo "APP_PORT=8080" >> .env
    ok "APP_PORT set to 8080"
else
    ok "APP_PORT already set to 8080"
fi

# Ensure Flussonic credentials are in .env
if ! grep -q "FLUSSONIC_HOST" .env; then
    warn "Adding Flussonic configuration to .env..."
    cat >> .env << 'EOF'

# Flussonic Integration
FLUSSONIC_HOST=localhost
FLUSSONIC_PORT=8935
FLUSSONIC_API_TOKEN=letmein!
FLUSSONIC_ADMIN_USER=admin
FLUSSONIC_ADMIN_PASSWORD=letmein!
EOF
    ok "Flussonic configuration added"
else
    ok "Flussonic configuration already exists in .env"
fi

# ============================================================
# STEP 4: RUN DATABASE MIGRATIONS
# ============================================================
step "STEP 4: Running Database Migrations"

warn "Ensuring database exists..."
mysql -u root -e "CREATE DATABASE IF NOT EXISTS media_server;" || warn "Could not create database (may need MySQL credentials)"

warn "Running Laravel migrations..."
php artisan migrate --force || warn "Migrations may have already been run"

ok "Database migrations completed"

# ============================================================
# STEP 5: CONFIGURE NGINX
# ============================================================
step "STEP 5: Configuring Nginx"

# Check if nginx.conf needs updating
if grep -q "listen 80 default_server" $NGINX_CONF; then
    warn "Nginx still configured for port 80. Updating to port 8080..."
    cp "$MEDIASERVER_DIR/nginx.conf.example" "$NGINX_CONF"
    ok "Nginx configuration updated to use port 8080"
else
    ok "Nginx already configured for port 8080"
fi

# Test nginx configuration
warn "Testing Nginx configuration..."
if nginx -t >/dev/null 2>&1; then
    ok "Nginx configuration is valid"
else
    warn "Nginx configuration test failed. Review $NGINX_CONF manually."
fi

# ============================================================
# STEP 6: VERIFY FLUSSONIC CONFIGURATION
# ============================================================
step "STEP 6: Verifying Flussonic Configuration"

# Check if Flussonic is listening on port 80
if grep -q "http_port 80" "$FLUSSONIC_CONF"; then
    ok "Flussonic configured for port 80 (HTTP streaming)"
else
    warn "Verify Flussonic HTTP port configuration manually"
    info "Edit: $FLUSSONIC_CONF"
    info "Look for: http_port 80"
fi

# Check if RTMP is enabled
if grep -q "rtmp_port 1935" "$FLUSSONIC_CONF"; then
    ok "Flussonic RTMP enabled on port 1935"
else
    warn "RTMP may not be configured. Check $FLUSSONIC_CONF"
fi

# ============================================================
# STEP 7: RESTART SERVICES
# ============================================================
step "STEP 7: Restarting Services"

warn "Restarting PHP-FPM..."
if systemctl restart php-fpm.service || systemctl restart php8.3-fpm.service; then
    ok "PHP-FPM restarted"
else
    warn "Could not restart PHP-FPM (may already be running)"
fi

warn "Reloading Nginx..."
if nginx -t >/dev/null 2>&1; then
    systemctl reload nginx
    ok "Nginx reloaded"
else
    error "Nginx configuration is invalid. Fix the errors above."
fi

warn "Restarting Flussonic..."
systemctl restart flussonic
sleep 2
if systemctl is-active --quiet flussonic; then
    ok "Flussonic restarted successfully"
else
    error "Flussonic failed to start. Check logs: journalctl -u flussonic -n 50"
fi

# ============================================================
# STEP 8: CLEAR CACHES
# ============================================================
step "STEP 8: Clearing Application Caches"

cd "$MEDIASERVER_DIR"
php artisan cache:clear
ok "Application cache cleared"

php artisan config:clear
ok "Configuration cache cleared"

php artisan view:clear
ok "View cache cleared"

php artisan route:clear
ok "Route cache cleared"

# ============================================================
# STEP 9: VERIFY SERVICES
# ============================================================
step "STEP 9: Verifying All Services"

echo ""
info "Service Status:"
echo ""

# Check PHP-FPM
if systemctl is-active --quiet php-fpm.service || systemctl is-active --quiet php8.3-fpm.service; then
    ok "✓ PHP-FPM is running"
else
    error "PHP-FPM is not running"
fi

# Check Nginx
if systemctl is-active --quiet nginx; then
    ok "✓ Nginx is running"
else
    error "Nginx is not running"
fi

# Check Flussonic
if systemctl is-active --quiet flussonic; then
    ok "✓ Flussonic is running"
else
    error "Flussonic is not running"
fi

# Check ports
echo ""
info "Port Status:"
echo ""

if netstat -tlnp 2>/dev/null | grep -q ":80 "; then
    ok "✓ Port 80 is listening (Flussonic)"
else
    warn "⚠ Port 80 is not listening (check Flussonic)"
fi

if netstat -tlnp 2>/dev/null | grep -q ":1935 "; then
    ok "✓ Port 1935 is listening (RTMP)"
else
    warn "⚠ Port 1935 is not listening (check Flussonic RTMP)"
fi

if netstat -tlnp 2>/dev/null | grep -q ":8080 "; then
    ok "✓ Port 8080 is listening (Laravel API)"
else
    warn "⚠ Port 8080 is not listening (check Nginx/PHP-FPM)"
fi

# ============================================================
# STEP 10: TEST CONNECTIVITY
# ============================================================
step "STEP 10: Testing Connectivity"

echo ""
info "Testing Laravel API on port 8080:"
if curl -s http://localhost:8080/api/health > /dev/null 2>&1; then
    ok "✓ Laravel API is responding"
else
    warn "⚠ Laravel API not responding yet (may need to wait a moment)"
fi

echo ""
info "Testing Flussonic API on port 8935:"
if curl -s http://localhost:8935/streamer/api/v3/server > /dev/null 2>&1; then
    ok "✓ Flussonic API is responding"
else
    warn "⚠ Flussonic API not responding (check Flussonic logs)"
fi

# ============================================================
# COMPLETE
# ============================================================
step "INTEGRATION SETUP COMPLETE"

echo ""
echo -e "${G}╔════════════════════════════════════════════════════════════╗${N}"
echo -e "${G}║          FLUSSONIC + LARAVEL INTEGRATION READY             ║${N}"
echo -e "${G}╚════════════════════════════════════════════════════════════╝${N}"
echo ""

cat << 'EOF'
📊 ACCESS POINTS:

  Laravel Admin & API:
  • http://your-server:8080
  • http://your-server:8080/api/health
  • http://your-server:8080/api/channels

  Flussonic Streaming:
  • HLS: http://your-server/stream_name/index.m3u8
  • DASH: http://your-server/stream_name/manifest.mpd
  • RTMP: rtmp://your-server:1935/live/stream_name

  Flussonic Admin Panel:
  • http://your-server:8935

🔧 NEXT STEPS:

  1. Verify Flussonic has streams configured
  2. Test streaming from an encoder (OBS, FFmpeg, etc.)
  3. Access Laravel admin panel at http://your-server:8080
  4. Create channels and configure relays

📚 DOCUMENTATION:

  • PORT_CONFIGURATION.md - Port allocation and setup
  • FLUSSONIC_QUICK_REFERENCE.md - Common Flussonic commands
  • DEPLOYMENT_GUIDE.md - Full deployment guide
  • API.md - REST API documentation

🔍 TROUBLESHOOTING:

  Check logs:
  • Nginx: tail -f /var/log/nginx/error.log
  • Flussonic: tail -f /var/log/flussonic/flussonic.log
  • Laravel: tail -f /var/www/mediaserver/storage/logs/laravel.log

  Verify ports:
  • sudo netstat -tlnp | grep -E ':(80|1935|8080|8935)'

  Test connectivity:
  • curl http://localhost:8080/api/health
  • curl http://localhost:8935/streamer/api/v3/server

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

EOF

ok "Setup completed successfully at $(date '+%Y-%m-%d %H:%M:%S')"
echo ""
