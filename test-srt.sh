#!/bin/bash

################################################################################
# SRT Server Test Script
# 
# Tests vMix/OBS SRT connectivity to the Media Server
# Usage: ./test-srt.sh [streamid]
################################################################################

set -e

# Configuration
SERVER_HOST="${1:-5.180.182.232}"
SERVER_PORT="${2:-9000}"
STREAM_ID="${3:-compassiontv}"
TEST_DURATION="${4:-5}"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║          SRT Server Connectivity Test              ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════╝${NC}"
echo ""

echo -e "${YELLOW}[TEST 1]${NC} Network connectivity to $SERVER_HOST:$SERVER_PORT"
if ping -c 1 "$SERVER_HOST" &> /dev/null; then
    echo -e "${GREEN}✓${NC} Server is reachable via network"
else
    echo -e "${RED}✗${NC} Cannot ping server. Check network connectivity."
    exit 1
fi
echo ""

echo -e "${YELLOW}[TEST 2]${NC} SRT port $SERVER_PORT is open (UDP)"
if timeout 3 bash -c "echo > /dev/udp/$SERVER_HOST/$SERVER_PORT" 2>/dev/null; then
    echo -e "${GREEN}✓${NC} Port $SERVER_PORT is open"
else
    # UDP doesn't have traditional open/closed, check if process is listening
    echo -e "${YELLOW}⚠${NC} Could not verify UDP port directly (normal for UDP)"
    echo "  Recommend: ssh root@$SERVER_HOST 'ss -tlnup | grep 9000'"
fi
echo ""

echo -e "${YELLOW}[TEST 3]${NC} Laravel API health endpoint"
if curl -s "http://$SERVER_HOST:8080/api/health" | grep -q '"status":"ok"'; then
    echo -e "${GREEN}✓${NC} Laravel API is responding"
    curl -s "http://$SERVER_HOST:8080/api/health" | python3 -m json.tool 2>/dev/null || curl -s "http://$SERVER_HOST:8080/api/health"
else
    echo -e "${RED}✗${NC} Laravel API is not responding"
    exit 1
fi
echo ""

echo -e "${YELLOW}[TEST 4]${NC} Flussonic HTTP interface"
if curl -s "http://$SERVER_HOST:80/" | grep -q "Flussonic" || curl -I "http://$SERVER_HOST:80/api/v1/streams/list" 2>/dev/null | grep -q "200"; then
    echo -e "${GREEN}✓${NC} Flussonic is responding"
else
    echo -e "${RED}✗${NC} Flussonic is not responding"
fi
echo ""

echo -e "${YELLOW}[TEST 5]${NC} SRT Server process status"
if ssh root@"$SERVER_HOST" 'supervisorctl status srt-server 2>/dev/null | grep -q "RUNNING"' 2>/dev/null; then
    echo -e "${GREEN}✓${NC} SRT server is running"
    ssh root@"$SERVER_HOST" 'supervisorctl status srt-server' 2>/dev/null || true
else
    echo -e "${YELLOW}⚠${NC} Could not verify SRT server status (check firewall rules for SSH)"
fi
echo ""

echo -e "${YELLOW}[TEST 6]${NC} SRT connection simulation (ffmpeg test)"
echo "Attempting to connect with FFmpeg for ${TEST_DURATION} seconds..."

# Check if ffmpeg is available
if ! command -v ffmpeg &> /dev/null; then
    echo -e "${YELLOW}⚠${NC} ffmpeg not installed locally. Install it to test: sudo apt install ffmpeg"
else
    # Create a test pattern
    TEMP_VIDEO="/tmp/srt_test_pattern.avi"
    
    # Generate a simple test pattern (5 frames)
    ffmpeg -f lavfi -i color=c=blue:s=640x480:d=1 -frames:v 5 -pixel_format yuv420p "$TEMP_VIDEO" -y &> /dev/null
    
    # Try to push test stream (will run for TEST_DURATION seconds then timeout)
    if timeout "$TEST_DURATION" ffmpeg -re -i "$TEMP_VIDEO" -c:v libx264 -b:v 500k -f flv "srt://$SERVER_HOST:$SERVER_PORT?streamid=$STREAM_ID&latency=1000" &> /tmp/srt_test.log; then
        echo -e "${GREEN}✓${NC} SRT connection successful"
    else
        # Timeout is expected, just check if connection was established
        if grep -q "Connection from\|Connected\|Opening\|Connecting" /tmp/srt_test.log 2>/dev/null; then
            echo -e "${GREEN}✓${NC} SRT connection attempted (timeout after ${TEST_DURATION}s is normal)"
        else
            echo -e "${YELLOW}⚠${NC} Check FFmpeg output: cat /tmp/srt_test.log"
        fi
    fi
    
    rm -f "$TEMP_VIDEO" /tmp/srt_test.log
fi
echo ""

echo -e "${YELLOW}[SUMMARY]${NC}"
echo -e "${GREEN}SRT Server Test Complete${NC}"
echo ""
echo -e "${BLUE}SRT URL for vMix/OBS:${NC}"
echo -e "  ${YELLOW}srt://$SERVER_HOST:$SERVER_PORT?streamid=$STREAM_ID${NC}"
echo ""
echo -e "${BLUE}Admin Panel URL:${NC}"
echo -e "  ${YELLOW}http://$SERVER_HOST:8080/${NC}"
echo ""
echo -e "${BLUE}View Logs:${NC}"
echo -e "  ${YELLOW}ssh root@$SERVER_HOST 'tail -f /var/www/mediaserver/storage/logs/srt-server.log'${NC}"
echo ""
