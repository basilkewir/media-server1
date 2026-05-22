#!/bin/bash

################################################################################
# SRT to RTMP Relay Server
#
# This script uses Flussonic's built-in SRT support to listen on port 9000
# and relay to RTMP. Since Flussonic's SRT has bugs, we use srt-live-transmit
# as a fallback SRT listener that relays to FFmpeg which pushes to RTMP.
#
# Alternatively, we can use FFmpeg's SRT output support with a simple approach:
# - FFmpeg listens for SRT input on port 9000
# - Encoder (vMix/OBS) pushes SRT to FFmpeg
# - FFmpeg outputs to Flussonic RTMP
################################################################################

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

LOG_FILE="/var/www/mediaserver/storage/logs/srt-server.log"
mkdir -p "$(dirname "$LOG_FILE")"

echo "[$(date)] ======================================="
echo "[$(date)] SRT to RTMP Relay Starting"
echo "[$(date)] =======================================" | tee -a "$LOG_FILE"

STREAM_ID="${1:-compassiontv}"
LISTEN_PORT="${2:-9000}"
RTMP_HOST="${3:-127.0.0.1}"
RTMP_PORT="${4:-1935}"

echo "[$(date)] Stream ID: $STREAM_ID"
echo "[$(date)] Listening on: 0.0.0.0:$LISTEN_PORT (SRT)"
echo "[$(date)] Relaying to: rtmp://$RTMP_HOST:$RTMP_PORT/live/$STREAM_ID" | tee -a "$LOG_FILE"

# Use srt-live-transmit if available (from libsrt)
if command -v srt-live-transmit &>/dev/null; then
    echo "[$(date)] Using srt-live-transmit" | tee -a "$LOG_FILE"
    srt-live-transmit "srt://:$LISTEN_PORT?mode=listener&streamid=$STREAM_ID" \
        "rtmp://$RTMP_HOST:$RTMP_PORT/live/$STREAM_ID" \
        2>&1 | tee -a "$LOG_FILE"
else
    # Fallback: Use FFmpeg with passthrough mode
    # FFmpeg will act as SRT listener and relay to RTMP
    echo "[$(date)] Using FFmpeg SRT relay" | tee -a "$LOG_FILE"
    
    ffmpeg \
        -hide_banner \
        -protocol_whitelist file,http,https,tcp,tls,srt,crypto,rtp,udp \
        -listen 1 \
        -i "srt://0.0.0.0:$LISTEN_PORT?mode=listener" \
        -c:v copy \
        -c:a copy \
        -f flv \
        -flvflags no_duration_filesize \
        "rtmp://$RTMP_HOST:$RTMP_PORT/live/$STREAM_ID" \
        2>&1 | tee -a "$LOG_FILE"
fi

echo "[$(date)] Relay stopped" | tee -a "$LOG_FILE"
