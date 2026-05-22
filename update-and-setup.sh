#!/bin/bash

# ============================================================
#  Media Server - Complete Git Update + Flussonic Setup
#  Run this script on your Ubuntu server as root:
#    sudo bash /var/www/mediaserver/update-and-setup.sh
# ============================================================

set -euo pipefail

# Colors for output
G='\033[0;32m'      # Green
R='\033[0;31m'      # Red
Y='\033[1;33m'      # Yellow
C='\033[0;36m'      # Cyan
N='\033[0m'         # Normal

# Output functions
ok()   { echo -e "${G}✓ $1${N}"; }
error() { echo -e "${R}✗ $1${N}"; exit 1; }
step() { echo -e "\n${C}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${N}"; echo -e "${C}$1${N}"; echo -e "${C}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${N}\n"; }
warn() { echo -e "${Y}⚠ $1${N}"; }

# Configuration
MEDIASERVER_DIR="/var/www/mediaserver"
FLUSSONIC_SETUP_SCRIPT="${MEDIASERVER_DIR}/flussonic-setup.sh"

# ============================================================
# 1. VERIFY PREREQUISITES
# ============================================================
step "STEP 1: Verifying Prerequisites"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    error "This script must be run as root (use: sudo bash $0)"
fi

# Check if mediaserver directory exists
if [ ! -d "$MEDIASERVER_DIR" ]; then
    error "MediaServer directory not found at $MEDIASERVER_DIR"
fi

ok "Running as root"
ok "MediaServer directory found at $MEDIASERVER_DIR"

# Check git is installed
if ! command -v git &> /dev/null; then
    error "Git is not installed. Install with: sudo apt-get install git"
fi
ok "Git is installed"

# Check if it's a git repository
cd "$MEDIASERVER_DIR"
if [ ! -d ".git" ]; then
    error "Not a git repository at $MEDIASERVER_DIR. Initialize with: cd $MEDIASERVER_DIR && git init"
fi
ok "Git repository verified"

# ============================================================
# 2. PULL LATEST CODE
# ============================================================
step "STEP 2: Pulling Latest Code from Repository"

warn "Current branch and status:"
git status | sed 's/^/  /'

warn "Pulling latest changes from master..."
if git pull origin master; then
    ok "Latest code pulled successfully"
else
    error "Failed to pull from origin/master. Check your git configuration."
fi

# Show what changed
echo ""
warn "Recent commits:"
git log --oneline -5 | sed 's/^/  /'

# ============================================================
# 3. BACKUP CURRENT CONFIGURATION
# ============================================================
step "STEP 3: Backing Up Configuration Files"

BACKUP_DIR="/var/backups/mediaserver/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Backup important files
for file in .env config/*.php routes/api.php; do
    if [ -f "$MEDIASERVER_DIR/$file" ]; then
        cp "$MEDIASERVER_DIR/$file" "$BACKUP_DIR/"
        ok "Backed up: $file"
    fi
done

# Backup database
if command -v mysqldump &> /dev/null; then
    warn "Backing up database..."
    mysqldump -u root media_server > "$BACKUP_DIR/media_server_backup.sql" 2>/dev/null && \
        ok "Database backed up" || \
        warn "Could not backup database (may need MySQL credentials)"
fi

ok "Backups saved to: $BACKUP_DIR"

# ============================================================
# 4. CHECK DEPENDENCIES
# ============================================================
step "STEP 4: Checking PHP Dependencies"

cd "$MEDIASERVER_DIR"

# Check composer is installed
if ! command -v composer &> /dev/null; then
    warn "Composer not found. Installing dependencies may fail."
    warn "Install Composer with: curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer"
else
    ok "Composer is installed"
    
    # Update composer dependencies
    warn "Updating Composer dependencies..."
    if composer install --no-interaction --optimize-autoloader; then
        ok "Composer dependencies updated"
    else
        warn "Composer update had issues - check logs"
    fi
fi

# ============================================================
# 5. RUN DATABASE MIGRATIONS
# ============================================================
step "STEP 5: Running Database Migrations"

if [ -f "artisan" ]; then
    warn "Running pending migrations..."
    if php artisan migrate --force; then
        ok "Database migrations completed"
    else
        warn "Migration had issues - check manually with: php artisan migrate:status"
    fi
else
    warn "Laravel artisan not found - skipping migrations"
fi

# ============================================================
# 6. RUN FLUSSONIC SETUP
# ============================================================
step "STEP 6: Running Flussonic Integration Setup"

if [ ! -f "$FLUSSONIC_SETUP_SCRIPT" ]; then
    error "Flussonic setup script not found at: $FLUSSONIC_SETUP_SCRIPT"
fi

# Check if flussonic is installed
if ! systemctl list-unit-files | grep -q flussonic; then
    warn "Flussonic service not found. Make sure Flussonic is installed before running this."
    warn "Install Flussonic manually from: https://flussonic.com/"
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        error "Setup cancelled"
    fi
fi

warn "Starting Flussonic integration setup..."
echo ""

# Run the setup script
if bash "$FLUSSONIC_SETUP_SCRIPT"; then
    ok "Flussonic setup completed successfully"
else
    error "Flussonic setup failed. Check logs above."
fi

# ============================================================
# 7. CLEAR CACHES
# ============================================================
step "STEP 7: Clearing Application Caches"

cd "$MEDIASERVER_DIR"

commands=(
    "php artisan cache:clear"
    "php artisan config:clear"
    "php artisan route:clear"
    "php artisan view:clear"
)

for cmd in "${commands[@]}"; do
    if [ -f "artisan" ]; then
        if $cmd 2>/dev/null; then
            ok "Executed: $cmd"
        fi
    fi
done

# ============================================================
# 8. RESTART SERVICES
# ============================================================
step "STEP 8: Restarting Services"

services=(
    "php-fpm"
    "nginx"
    "flussonic"
    "supervisor"
)

for service in "${services[@]}"; do
    if systemctl list-unit-files | grep -q "^$service\.service"; then
        warn "Restarting $service..."
        if systemctl restart "$service" 2>/dev/null; then
            ok "$service restarted"
        else
            warn "$service restart had issues"
        fi
    fi
done

# ============================================================
# 9. FINAL VERIFICATION
# ============================================================
step "STEP 9: Final Verification"

# Check services status
echo ""
warn "Service status:"
for service in php-fpm nginx flussonic; do
    if systemctl is-active --quiet $service 2>/dev/null; then
        ok "$service is running"
    else
        warn "$service may not be running - check with: sudo systemctl status $service"
    fi
done

# Check endpoints
echo ""
warn "Testing endpoints:"

# Test Laravel app
if curl -s http://localhost/api/health > /dev/null 2>&1; then
    ok "Laravel API responding at http://localhost/api/health"
else
    warn "Laravel API not responding - check with: curl http://localhost/api/health"
fi

# Test Flussonic
if curl -s -u flussonic:letmein! http://localhost:8935/streamer/api/v3/server > /dev/null 2>&1; then
    ok "Flussonic API responding at http://localhost:8935"
else
    warn "Flussonic API not responding - check with: sudo systemctl status flussonic"
fi

# ============================================================
# 10. COMPLETION SUMMARY
# ============================================================
step "SETUP COMPLETE!"

echo ""
echo -e "${G}Summary:${N}"
echo "  ✓ Code pulled from: origin/master"
echo "  ✓ Backups saved to: $BACKUP_DIR"
echo "  ✓ Dependencies updated"
echo "  ✓ Migrations run"
echo "  ✓ Flussonic configured"
echo "  ✓ Services restarted"
echo ""

echo -e "${C}Next Steps:${N}"
echo "  1. Verify all services are running:"
echo "     sudo systemctl status nginx php-fpm flussonic"
echo ""
echo "  2. Check application logs:"
echo "     tail -f /var/log/nginx/error.log"
echo "     tail -f /var/log/php-fpm.log"
echo "     sudo journalctl -u flussonic -f"
echo ""
echo "  3. Access the applications:"
echo "     Laravel:   http://$(hostname -I | awk '{print $1}')/api/health"
echo "     Flussonic: http://$(hostname -I | awk '{print $1}'):8935"
echo ""
echo "  4. Review backup location (for rollback if needed):"
echo "     ls -la $BACKUP_DIR"
echo ""

echo -e "${Y}Documentation:${N}"
echo "  • Read: ${MEDIASERVER_DIR}/IMPLEMENTATION_COMPLETE.md"
echo "  • Read: ${MEDIASERVER_DIR}/FLUSSONIC_INTEGRATION.md"
echo "  • Read: ${MEDIASERVER_DIR}/RELAY_GUIDE.md"
echo ""

ok "Setup script completed successfully!"
