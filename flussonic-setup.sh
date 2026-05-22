#!/bin/bash
# ============================================================
#  MediaServer + Flussonic Integration Setup
#  Run on server as root:
#    bash /var/www/mediaserver/flussonic-setup.sh
# ============================================================
set -euo pipefail

APP_DIR="/var/www/mediaserver"
FLUSSONIC_CONF="/etc/flussonic/flussonic.conf"
FLUSSONIC_HTTP_PORT=8935
FLUSSONIC_RTMP_PORT=1935
FLUSSONIC_USER="flussonic"
FLUSSONIC_PASS="letmein!"

G='\033[0;32m'; Y='\033[1;33m'; C='\033[0;36m'; N='\033[0m'
ok()   { echo -e "${G}  ✓ $1${N}"; }
step() { echo -e "\n${C}━━━ $1 ━━━${N}"; }
warn() { echo -e "${Y}  ⚠ $1${N}"; }

# ── 1. Move Flussonic off port 80 → 8935 ─────────────────────────────────────
step "1. Move Flussonic HTTP from port 80 to ${FLUSSONIC_HTTP_PORT}"

if grep -q "^http 80;" "$FLUSSONIC_CONF" 2>/dev/null; then
    sed -i "s/^http 80;/http ${FLUSSONIC_HTTP_PORT};/" "$FLUSSONIC_CONF"
    ok "Flussonic HTTP port changed to ${FLUSSONIC_HTTP_PORT}"
elif grep -q "^http ${FLUSSONIC_HTTP_PORT};" "$FLUSSONIC_CONF" 2>/dev/null; then
    ok "Flussonic already on port ${FLUSSONIC_HTTP_PORT}"
else
    warn "Could not find http port line — adding it"
    sed -i "1s/^/http ${FLUSSONIC_HTTP_PORT};\n/" "$FLUSSONIC_CONF"
fi

# Ensure RTMP is on 1935
if ! grep -q "^rtmp" "$FLUSSONIC_CONF"; then
    echo "rtmp ${FLUSSONIC_RTMP_PORT};" >> "$FLUSSONIC_CONF"
fi

# Show final config
echo ""
echo "  /etc/flussonic/flussonic.conf:"
grep -E "^http|^rtmp|^edit_auth" "$FLUSSONIC_CONF" | sed 's/^/    /'

# Restart Flussonic
systemctl restart flussonic
sleep 4

# Verify
if systemctl is-active --quiet flussonic; then
    ok "Flussonic restarted successfully"
else
    echo "ERROR: Flussonic failed to start. Check: journalctl -u flussonic -n 30"
    exit 1
fi

# ── 2. Test Flussonic API ─────────────────────────────────────────────────────
step "2. Testing Flussonic API on port ${FLUSSONIC_HTTP_PORT}"

sleep 2
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" \
    -u "${FLUSSONIC_USER}:${FLUSSONIC_PASS}" \
    "http://localhost:${FLUSSONIC_HTTP_PORT}/streamer/api/v3/server" 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ]; then
    ok "Flussonic API responding (HTTP 200)"
    curl -s -u "${FLUSSONIC_USER}:${FLUSSONIC_PASS}" \
        "http://localhost:${FLUSSONIC_HTTP_PORT}/streamer/api/v3/server" \
        | python3 -m json.tool 2>/dev/null | grep -E '"version"|"uptime"' | sed 's/^/    /' || true
else
    warn "Flussonic API returned HTTP ${HTTP_CODE} — may still be starting up"
fi

# ── 3. Update Nginx — proxy /flussonic/ to port 8935 ─────────────────────────
step "3. Configuring Nginx proxy for Flussonic"

NGINX_CONF="/etc/nginx/sites-available/mediaserver"

# Backup
cp "$NGINX_CONF" "${NGINX_CONF}.bak.$(date +%s)"

# Write complete Nginx config
cat > "$NGINX_CONF" <<NGINX
server {
    listen 80 default_server;
    server_name _;
    root ${APP_DIR}/public;
    index index.php;

    client_max_body_size 128M;

    # CORS for HLS/DASH players
    add_header Access-Control-Allow-Origin  "*" always;
    add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" always;
    add_header Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With" always;
    if (\$request_method = OPTIONS) { return 204; }

    # ── Laravel app ──────────────────────────────────────────────────────────
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
    }

    # ── HLS/DASH segments served directly ────────────────────────────────────
    location /storage/streams/ {
        alias ${APP_DIR}/storage/streams/;
        expires -1;
        add_header Cache-Control "no-cache, no-store, must-revalidate";
        add_header Access-Control-Allow-Origin "*";
        types {
            application/vnd.apple.mpegurl  m3u8;
            video/MP2T                     ts;
            application/dash+xml           mpd;
        }
    }

    # ── Flussonic UI + API proxy (port 8935) ──────────────────────────────────
    # Access Flussonic at: http://YOUR_IP/flussonic/
    location /flussonic/ {
        proxy_pass         http://127.0.0.1:${FLUSSONIC_HTTP_PORT}/;
        proxy_http_version 1.1;
        proxy_set_header   Host              \$host;
        proxy_set_header   X-Real-IP         \$remote_addr;
        proxy_set_header   X-Forwarded-For   \$proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto \$scheme;
        proxy_set_header   Upgrade           \$http_upgrade;
        proxy_set_header   Connection        "upgrade";
        proxy_read_timeout 3600;
        proxy_buffering    off;
    }

    # ── Flussonic HLS streams via Nginx proxy ─────────────────────────────────
    # Viewers can access Flussonic HLS at: http://YOUR_IP/live/<slug>/index.m3u8
    location /live/ {
        proxy_pass         http://127.0.0.1:${FLUSSONIC_HTTP_PORT}/;
        proxy_http_version 1.1;
        proxy_set_header   Host \$host;
        proxy_buffering    off;
        add_header         Access-Control-Allow-Origin "*";
        add_header         Cache-Control "no-cache";
    }

    location ~ /\.(?!well-known).* { deny all; }
}
NGINX

nginx -t && systemctl reload nginx
ok "Nginx configured — Flussonic proxied at /flussonic/"

# ── 4. Update Laravel .env ────────────────────────────────────────────────────
step "4. Updating Laravel .env"

ENV_FILE="${APP_DIR}/.env"

update_env() {
    local key="$1" val="$2"
    if grep -q "^${key}=" "$ENV_FILE"; then
        sed -i "s|^${key}=.*|${key}=${val}|" "$ENV_FILE"
    else
        echo "${key}=${val}" >> "$ENV_FILE"
    fi
}

update_env "MEDIA_SERVER_DRIVER"   "flussonic"
update_env "FLUSSONIC_URL"         "http://localhost:${FLUSSONIC_HTTP_PORT}"
update_env "FLUSSONIC_USERNAME"    "${FLUSSONIC_USER}"
update_env "FLUSSONIC_PASSWORD"    "${FLUSSONIC_PASS}"

ok ".env updated — driver set to flussonic"

# ── 5. Clear Laravel config cache ────────────────────────────────────────────
step "5. Clearing Laravel config cache"

cd "$APP_DIR"
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan config:cache
ok "Config cache rebuilt"

# ── 6. Open firewall for port 8935 (internal only — not public) ──────────────
step "6. Firewall"

# Port 8935 should NOT be public — only accessible via Nginx proxy
# Block direct external access to 8935
ufw deny 8935/tcp 2>/dev/null || true
ok "Port 8935 blocked externally (access via /flussonic/ proxy only)"

# ── 7. Verify everything ──────────────────────────────────────────────────────
step "7. Final verification"

echo ""
echo "  Port status:"
ss -tlnp | grep -E ':80|:8935|:1935|:8000' | awk '{print "    " $0}'

echo ""
echo "  Flussonic streams:"
curl -s -u "${FLUSSONIC_USER}:${FLUSSONIC_PASS}" \
    "http://localhost:${FLUSSONIC_HTTP_PORT}/streamer/api/v3/streams" \
    2>/dev/null | python3 -m json.tool 2>/dev/null | head -20 || echo "    (no streams yet)"

echo ""
echo "  Laravel API:"
curl -s "http://localhost/api/health" | python3 -m json.tool 2>/dev/null || echo "    (check nginx)"

# ── Summary ───────────────────────────────────────────────────────────────────
SERVER_IP=$(curl -s ifconfig.me 2>/dev/null || hostname -I | awk '{print $1}')

echo ""
echo -e "${G}╔══════════════════════════════════════════════════════════╗${N}"
echo -e "${G}║        Flussonic Integration Complete!                   ║${N}"
echo -e "${G}╚══════════════════════════════════════════════════════════╝${N}"
echo ""
echo "  Laravel Admin:      http://${SERVER_IP}/admin"
echo "  Flussonic UI:       http://${SERVER_IP}/flussonic/"
echo "  Flussonic Direct:   http://${SERVER_IP}:8935  (internal only)"
echo "  RTMP Ingest:        rtmp://${SERVER_IP}:1935/live/<stream-key>"
echo "  HLS Playback:       http://${SERVER_IP}/live/<slug>/index.m3u8"
echo "  Icecast:            http://${SERVER_IP}:8000"
echo ""
echo "  Flussonic login:    ${FLUSSONIC_USER} / ${FLUSSONIC_PASS}"
echo ""
echo "  Active driver:      FLUSSONIC"
echo "  Config:             ${FLUSSONIC_CONF}"
echo ""
echo "  To create a stream via Laravel admin:"
echo "    1. Go to http://${SERVER_IP}/admin/settings"
echo "    2. Confirm driver = Flussonic"
echo "    3. Go to Channels → Create Channel"
echo "    4. Push RTMP to: rtmp://${SERVER_IP}:1935/live/<slug>"
echo ""
