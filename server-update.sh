#!/bin/bash
# ============================================================
#  MediaServer - Update Script
#  Run on server after pushing new code from Windows:
#    ssh root@SERVER_IP "bash /var/www/mediaserver/server-update.sh"
#  Or remotely from Windows push.bat
# ============================================================
set -euo pipefail

APP_DIR="/var/www/mediaserver"
APP_USER="www-data"
BRANCH="${BRANCH:-main}"

G='\033[0;32m'; C='\033[0;36m'; N='\033[0m'
ok() { echo -e "${G}  ✓ $1${N}"; }
step() { echo -e "\n${C}━━━ $1 ━━━${N}"; }

cd "$APP_DIR"

step "Pulling latest code"
git fetch origin
git reset --hard "origin/${BRANCH}"
git clean -fd
ok "Code updated"

step "Installing dependencies"
sudo -u "$APP_USER" composer install --no-dev --optimize-autoloader --no-interaction
ok "Dependencies installed"

step "Running migrations"
sudo -u "$APP_USER" php artisan migrate --force
ok "Migrations done"

step "Clearing caches"
sudo -u "$APP_USER" php artisan config:cache
sudo -u "$APP_USER" php artisan route:cache
sudo -u "$APP_USER" php artisan view:cache
ok "Caches rebuilt"

step "Restarting services"
supervisorctl restart mediaserver:*
systemctl reload nginx
ok "Services restarted"

echo ""
echo -e "${G}  Update complete!${N}"
