#!/bin/bash

################################################################################
# SRT Server for Media Server
# 
# Listens on port 9000 for incoming SRT streams from vMix/OBS encoders
# and relays them to local Flussonic RTMP server for processing
#
# Usage: ./srt-server.sh [streamid]
# Example: ./srt-server.sh compassiontv
#
# Webhook: http://127.0.0.1:8000/api/srt/connect?streamid=compassiontv
################################################################################

set -e

# Configuration
SRT_LISTEN_PORT="${SRT_LISTEN_PORT:-9000}"
RTMP_RELAY_HOST="${RTMP_RELAY_HOST:-127.0.0.1}"
RTMP_RELAY_PORT="${RTMP_RELAY_PORT:-1935}"
WEBHOOK_URL="${WEBHOOK_URL:-http://127.0.0.1:8000/api/srt/connect}"
LOG_FILE="/var/www/mediaserver/storage/logs/srt-server.log"
PID_FILE="/var/run/srt-server.pid"

# Stream ID (from command line or default)
STREAM_ID="${1:-compassiontv}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

################################################################################
# Helper Functions
################################################################################

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $@" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR]${NC} $(date '+%Y-%m-%d %H:%M:%S') $@" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $(date '+%Y-%m-%d %H:%M:%S') $@" | tee -a "$LOG_FILE"
}

info() {
    echo -e "${YELLOW}[INFO]${NC} $(date '+%Y-%m-%d %H:%M:%S') $@" | tee -a "$LOG_FILE"
}

cleanup() {
    log "Cleaning up SRT server..."
    if [ -f "$PID_FILE" ]; then
        kill -9 $(cat "$PID_FILE") 2>/dev/null || true
        rm "$PID_FILE"
    fi
    exit 0
}

################################################################################
# Trap Signals
################################################################################

trap cleanup SIGTERM SIGINT

################################################################################
# Validation
################################################################################

# Check dependencies
check_dependency() {
    if ! command -v "$1" &> /dev/null; then
        error "$1 is not installed. Please install it and try again."
        exit 1
    fi
}

log "Checking dependencies..."
check_dependency ffmpeg
check_dependency curl
success "All dependencies available"

# Check if port is available
info "Checking if port $SRT_LISTEN_PORT is available..."
if netstat -tuln 2>/dev/null | grep -q ":$SRT_LISTEN_PORT "; then
    error "Port $SRT_LISTEN_PORT is already in use. Please choose a different port."
    exit 1
fi
success "Port $SRT_LISTEN_PORT is available"

# Create log directory if needed
mkdir -p "$(dirname "$LOG_FILE")"

################################################################################
# Main SRT Server Loop
################################################################################

log "========== SRT Server Starting =========="
log "Listening on: 0.0.0.0:$SRT_LISTEN_PORT"
log "RTMP Relay: rtmp://$RTMP_RELAY_HOST:$RTMP_RELAY_PORT/live/$STREAM_ID"
log "Webhook: $WEBHOOK_URL?streamid=$STREAM_ID"

# Save PID
echo $$ > "$PID_FILE"

# Start SRT server using FFmpeg
# This listens on the SRT port and relays to Flussonic RTMP
# Format: ffmpeg [INPUT_OPTIONS] -i input [OUTPUT_OPTIONS] output
ffmpeg \
    -hide_banner \
    -protocol_whitelist "file,http,https,tcp,tls,srt,crypto" \
    -f mpegts \
    -listen 1 \
    -i "srt://0.0.0.0:$SRT_LISTEN_PORT" \
    -c:v copy \
    -c:a copy \
    -f flv \
    -flvflags no_duration_filesize \
    "rtmp://$RTMP_RELAY_HOST:$RTMP_RELAY_PORT/live/$STREAM_ID" \
    2>&1 | tee -a "$LOG_FILE" | while read line; do
        # Look for connection indicators
        if echo "$line" | grep -iq "Connection from\|Opening\|Connected\|srt.*started"; then
            info "Encoder connected: $line"
            # Call webhook to notify Laravel
            curl -s "$WEBHOOK_URL?streamid=$STREAM_ID" > /dev/null 2>&1 || true
        fi
        
        if echo "$line" | grep -iq "error"; then
            error "FFmpeg: $line"
        fi
    done

cleanup
