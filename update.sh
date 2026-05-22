#!/bin/bash
set -euo pipefail

# =============================================================================
# MediaServer — Safe Update Script
# =============================================================================
# Pulls latest code from git, installs dependencies, runs migrations,
# clears caches, and restarts workers WITHOUT changing existing code
# or configuration files.
#
# USAGE:
#   export REPO_URL="git@github.com:YOUR_USERNAME/YOUR_REPO.git"
#   bash update.sh
# =============================================================================

REPO_URL="${REPO_URL:-https://github.com/basilkewir/media-server1.git}"
APP_DIR="${APP_DIR:-/var/www/mediaserver}"
DEPLOY_KEY="${DEPLOY_KEY:-/root/.ssh/mediaserver_deploy}"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

echo "========================================"
echo "  MediaServer Update"
echo "========================================"
echo ""

# ── 1. REQUIRE ROOT ──────────────────────────────────────────────────────────
if [ "$EUID" -ne 0 ]; then
    error "Please run as root: sudo bash update.sh"
fi

# ── 2. CHECK APP DIRECTORY ───────────────────────────────────────────────────
if [ ! -d "${APP_DIR}/.git" ]; then
    error "App directory ${APP_DIR} is not a git repository. Run deploy-to-ubuntu.sh first."
fi

cd "${APP_DIR}"

# ── 3. BACKUP .ENV (just in case) ────────────────────────────────────────────
if [ -f ".env" ]; then
    info "Backing up .env..."
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
fi

# ── 4. PULL LATEST CODE ──────────────────────────────────────────────────────
info "Pulling latest code from ${REPO_URL}..."

# Stash any local changes (shouldn't be any, but safety first)
git stash push -m "update-script-auto-stash-$(date +%Y%m%d_%H%M%S)" || true

if [[ "${REPO_URL}" == git@github.com:* ]]; then
    if [ -f "${DEPLOY_KEY}" ]; then
        GIT_SSH_COMMAND="ssh -i ${DEPLOY_KEY} -o IdentitiesOnly=yes" git pull origin $(git rev-parse --abbrev-ref HEAD)
    else
        git pull origin $(git rev-parse --abbrev-ref HEAD)
    fi
else
    git pull origin $(git rev-parse --abbrev-ref HEAD)
fi

# Restore stashed changes if any (unlikely in production)
git stash pop 2>/dev/null || true

# ── 5. INSTALL PHP DEPENDENCIES ──────────────────────────────────────────────
info "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# ── 6. RUN MIGRATIONS ────────────────────────────────────────────────────────
info "Running database migrations..."
php artisan migrate --force

# ── 7. CLEAR AND REBUILD CACHES ──────────────────────────────────────────────
info "Clearing caches..."
php artisan cache:clear --no-interaction
php artisan config:clear --no-interaction
php artisan route:clear --no-interaction
php artisan view:clear --no-interaction

info "Rebuilding caches..."
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

# ── 8. FIX PERMISSIONS ───────────────────────────────────────────────────────
info "Setting permissions..."
chown -R www-data:www-data "${APP_DIR}"
chmod -R 755 "${APP_DIR}"
chmod -R 775 "${APP_DIR}/storage"
chmod -R 775 "${APP_DIR}/bootstrap/cache"

# ── 9. RESTART WORKERS ───────────────────────────────────────────────────────
info "Restarting supervisor workers..."
supervisorctl reread || true
supervisorctl update || true
supervisorctl restart all || true

# ── 10. HEALTH CHECK ─────────────────────────────────────────────────────────
info "Running health check..."
sleep 2
HEALTH_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/health || echo "000")

if [ "${HEALTH_STATUS}" = "200" ]; then
    echo ""
    echo "========================================"
    echo "  ✅ Update Successful!"
    echo "========================================"
    echo ""
    echo "Health check: OK (200)"
else
    warn "Health check returned status ${HEALTH_STATUS}"
    echo ""
    echo "========================================"
    echo "  ⚠️  Update Complete (check health)"
    echo "========================================"
fi

echo ""
echo "Useful commands:"
echo "  tail -f ${APP_DIR}/storage/logs/laravel.log"
echo "  supervisorctl status"
echo ""
