#!/bin/bash

# Flussonic 24.02 Installation Script for Ubuntu
# Usage: sudo bash install_flussonic.sh /path/to/flussonic_24.02_unlimited.zip

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}This script must be run as root${NC}"
   exit 1
fi

# Check if ZIP file provided
if [ -z "$1" ]; then
    echo -e "${RED}Usage: sudo bash install_flussonic.sh /path/to/flussonic_24.02_unlimited.zip${NC}"
    exit 1
fi

ZIP_FILE="$1"

if [ ! -f "$ZIP_FILE" ]; then
    echo -e "${RED}ZIP file not found: $ZIP_FILE${NC}"
    exit 1
fi

echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}Flussonic 24.02 Installation Script${NC}"
echo -e "${YELLOW}========================================${NC}"

# Step 1: Update system
echo -e "${YELLOW}[1/8] Updating system packages...${NC}"
apt-get update
apt-get upgrade -y

# Step 2: Install dependencies
echo -e "${YELLOW}[2/8] Installing dependencies...${NC}"
apt-get install -y \
    curl \
    wget \
    unzip \
    nano \
    htop \
    net-tools \
    ffmpeg \
    supervisor \
    nginx \
    mysql-server \
    redis-server

# Step 3: Create directories
echo -e "${YELLOW}[3/8] Creating directories...${NC}"
mkdir -p /opt/flussonic
mkdir -p /var/log/flussonic
mkdir -p /var/cache/flussonic
mkdir -p /etc/flussonic
mkdir -p /var/run/flussonic

# Step 4: Extract Flussonic
echo -e "${YELLOW}[4/8] Extracting Flussonic...${NC}"
unzip -o "$ZIP_FILE" -d /opt/flussonic/
chmod +x /opt/flussonic/flussonic

# Verify extraction
if [ ! -f /opt/flussonic/flussonic ]; then
    echo -e "${RED}Failed to extract Flussonic binary${NC}"
    exit 1
fi

echo -e "${GREEN}Flussonic version: $(/opt/flussonic/flussonic --version)${NC}"

# Step 5: Create system user
echo -e "${YELLOW}[5/8] Creating system user...${NC}"
useradd -r -m -s /usr/sbin/nologin flussonic 2>/dev/null || true
chown -R flussonic:flussonic /opt/flussonic
chown -R flussonic:flussonic /var/log/flussonic
chown -R flussonic:flussonic /var/cache/flussonic
chown -R flussonic:flussonic /var/run/flussonic

# Step 6: Create configuration
echo -e "${YELLOW}[6/8] Creating Flussonic configuration...${NC}"
cat > /etc/flussonic/flussonic.conf << 'EOF'
# Flussonic 24.02 Configuration

# Server settings
port 8080
http_port 80
rtmp_port 1935

# Administrator
admin admin
admin_password $(echo $RANDOM | md5sum | cut -c1-12)

# Logging
log_level info
logfile /var/log/flussonic/flussonic.log

# Directories
vod_dir /var/cache/flussonic/vod
rec_dir /var/cache/flussonic/recordings

# Performance
max_connections 5000
buffer_size 1024

# Features
dvr enabled
http enabled
cors enabled

# API
api_port 8080
api_enabled yes

# HLS/DASH settings
hls_segment_duration 4
hls_segments_in_playlist 12
dash_segment_duration 4
dash_segments_in_playlist 10
EOF

echo -e "${GREEN}Configuration created${NC}"

# Step 7: Create systemd service
echo -e "${YELLOW}[7/8] Creating systemd service...${NC}"
cat > /etc/systemd/system/flussonic.service << 'EOF'
[Unit]
Description=Flussonic Media Server
After=network.target

[Service]
Type=simple
User=flussonic
Group=flussonic
WorkingDirectory=/opt/flussonic
ExecStart=/opt/flussonic/flussonic -c /etc/flussonic/flussonic.conf
ExecReload=/bin/kill -HUP $MAINPID
KillMode=process
Restart=always
RestartSec=10
LimitNOFILE=65536
LimitNPROC=65536
PrivateTmp=yes
NoNewPrivileges=yes

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable flussonic

# Step 8: Configure firewall
echo -e "${YELLOW}[8/8] Configuring firewall...${NC}"
ufw allow 22/tcp 2>/dev/null || true
ufw allow 80/tcp 2>/dev/null || true
ufw allow 443/tcp 2>/dev/null || true
ufw allow 1935/tcp 2>/dev/null || true
ufw allow 8080/tcp 2>/dev/null || true
ufw --force enable 2>/dev/null || true

# Start service
echo -e "${YELLOW}Starting Flussonic service...${NC}"
systemctl start flussonic

# Verify installation
sleep 2

if systemctl is-active --quiet flussonic; then
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}✓ Flussonic installed successfully!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
    echo -e "${YELLOW}Access Information:${NC}"
    echo -e "  Admin Panel: ${GREEN}http://$(hostname -I | awk '{print $1}'):8080${NC}"
    echo -e "  Default User: ${GREEN}admin${NC}"
    echo -e "  RTMP: ${GREEN}rtmp://$(hostname -I | awk '{print $1}'):1935${NC}"
    echo -e "  HLS: ${GREEN}http://$(hostname -I | awk '{print $1}'):80${NC}"
    echo ""
    echo -e "${YELLOW}Quick Commands:${NC}"
    echo -e "  View logs: ${GREEN}sudo tail -f /var/log/flussonic/flussonic.log${NC}"
    echo -e "  Restart: ${GREEN}sudo systemctl restart flussonic${NC}"
    echo -e "  Status: ${GREEN}sudo systemctl status flussonic${NC}"
    echo -e "  Config: ${GREEN}sudo nano /etc/flussonic/flussonic.conf${NC}"
    echo ""
else
    echo -e "${RED}✗ Failed to start Flussonic${NC}"
    echo -e "${YELLOW}Check logs: sudo journalctl -u flussonic -n 50${NC}"
    exit 1
fi

echo -e "${YELLOW}Next Steps:${NC}"
echo "1. Access admin panel at http://your-server-ip:8080"
echo "2. Configure your streams"
echo "3. Set up DVR recording if needed"
echo "4. Configure relay streams"
echo "5. Integrate with Media Server"
echo ""
echo -e "${YELLOW}For more information, see:${NC}"
echo "  - FLUSSONIC_INSTALLATION.md"
echo "  - FLUSSONIC_INTEGRATION.md"
echo "  - Flussonic docs: https://flussonic.com/doc/"
