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
if [ -d "${APP_DIR}" ] && [ ! -d "${APP_DIR}/.git" ]; then
    error "App directory ${APP_DIR} exists but is not a git repository. Please remove it manually or set a different APP_DIR."
fi

# ── 3. BACKUP CRITICAL FILES ─────────────────────────────────────────────────
BACKUP_DIR="/tmp/mediaserver-backup-$(date +%Y%m%d_%H%M%S)"
mkdir -p "${BACKUP_DIR}"

if [ -d "${APP_DIR}" ]; then
    info "Backing up critical files before deletion..."

    # Backup .env
    if [ -f "${APP_DIR}/.env" ]; then
        cp "${APP_DIR}/.env" "${BACKUP_DIR}/.env"
        info "  → .env backed up"
    fi

    # Backup storage directory (logs, HLS segments, uploads)
    if [ -d "${APP_DIR}/storage" ]; then
        cp -a "${APP_DIR}/storage" "${BACKUP_DIR}/storage"
        info "  → storage/ backed up"
    fi

    # Backup any custom nginx config if present in the app dir
    if [ -f "${APP_DIR}/nginx.conf" ]; then
        cp "${APP_DIR}/nginx.conf" "${BACKUP_DIR}/nginx.conf"
        info "  → nginx.conf backed up"
    fi
fi

# ── 4. DELETE EXISTING FOLDER AND CLONE FRESH ────────────────────────────────
if [ -d "${APP_DIR}" ]; then
    info "Deleting existing app directory: ${APP_DIR}"
    rm -rf "${APP_DIR}"
fi

info "Cloning fresh copy from ${REPO_URL}..."
if [[ "${REPO_URL}" == git@github.com:* ]]; then
    if [ -f "${DEPLOY_KEY}" ]; then
        GIT_SSH_COMMAND="ssh -i ${DEPLOY_KEY} -o IdentitiesOnly=yes" git clone "${REPO_URL}" "${APP_DIR}"
    else
        git clone "${REPO_URL}" "${APP_DIR}"
    fi
else
    git clone "${REPO_URL}" "${APP_DIR}"
fi

cd "${APP_DIR}"

# ── 6. RESTORE BACKED-UP FILES ───────────────────────────────────────────────
if [ -f "${BACKUP_DIR}/.env" ]; then
    info "Restoring .env..."
    cp "${BACKUP_DIR}/.env" "${APP_DIR}/.env"
fi

if [ -d "${BACKUP_DIR}/storage" ]; then
    info "Restoring storage/..."
    rm -rf "${APP_DIR}/storage"
    cp -a "${BACKUP_DIR}/storage" "${APP_DIR}/storage"
fi

if [ -f "${BACKUP_DIR}/nginx.conf" ]; then
    info "Restoring nginx.conf..."
    cp "${BACKUP_DIR}/nginx.conf" "${APP_DIR}/nginx.conf"
fi

# ── 7. INSTALL PHP DEPENDENCIES ──────────────────────────────────────────────
info "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# ── 8. RUN MIGRATIONS ────────────────────────────────────────────────────────
info "Running database migrations..."
php artisan migrate --force

# ── 9. CLEAR AND REBUILD CACHES ──────────────────────────────────────────────
info "Clearing caches..."
php artisan cache:clear --no-interaction
php artisan config:clear --no-interaction
php artisan route:clear --no-interaction
php artisan view:clear --no-interaction

info "Rebuilding caches..."
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

# ── 10. FIX PERMISSIONS ──────────────────────────────────────────────────────
info "Setting permissions..."
chown -R www-data:www-data "${APP_DIR}"
chmod -R 755 "${APP_DIR}"
chmod -R 775 "${APP_DIR}/storage"
chmod -R 775 "${APP_DIR}/bootstrap/cache"

# ── 11. RESTART WORKERS ──────────────────────────────────────────────────────
info "Restarting supervisor workers..."
supervisorctl reread || true
supervisorctl update || true
supervisorctl restart all || true

# ── 12. HEALTH CHECK ─────────────────────────────────────────────────────────
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
