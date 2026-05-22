# SRT Encoder Configuration Guide

**Last Updated:** May 22, 2026  
**Status:** ✅ SRT Server Running on Port 9000  
**Server:** 5.180.182.232:9000

---

## Overview

The Media Server now supports **SRT (Secure Reliable Transport)** ingest directly from vMix, OBS, FFmpeg, and other professional encoders. The SRT server listens on port 9000 and automatically relays incoming streams to Flussonic for HLS/DASH/DVR distribution.

### Architecture

```
vMix/OBS (Encoder)
    ↓
SRT Push: srt://5.180.182.232:9000?streamid=compassiontv
    ↓
SRT Server (Port 9000) - FFmpeg-based
    ↓
Laravel Webhook: POST /api/srt/connect?streamid=compassiontv
    ↓
StreamingService (Stream Lifecycle Management)
    ↓
Flussonic RTMP Relay: rtmp://127.0.0.1:1935/live/compassiontv
    ↓
Flussonic Distribution:
  • HLS: http://5.180.182.232:80/compassiontv/index.m3u8
  • DASH: http://5.180.182.232:80/compassiontv/manifest.mpd
  • DVR: Local recording at /var/cache/flussonic/recordings/
```

---

## vMix Configuration (Recommended)

### Prerequisite

- vMix Pro or vMix Enterprise (streaming features required)
- Network access to 5.180.182.232:9000
- Channel configured in admin panel with slug: `compassiontv`

### Step 1: Open Streaming Settings

1. In vMix, go to **Settings** → **Streaming**
2. Click **Add Output** (or enable if already added)

### Step 2: Configure SRT Protocol

| Setting | Value |
|---------|-------|
| **Service** | SRT |
| **URL** | `srt://5.180.182.232:9000?streamid=compassiontv` |
| **Connection** | Direct |
| **Latency** | 1000-2000 ms (recommended: 1000) |
| **Encryption** | None (optional - disable for simplicity) |
| **Bitrate** | 3000-5000 kbps |
| **Keyframe Interval** | 2 seconds |

### Step 3: Video Settings

| Setting | Value |
|---------|-------|
| **Resolution** | 1920x1080 |
| **Frame Rate** | 30fps or 60fps |
| **Bitrate** | 3000-5000 kbps (adjust for quality) |
| **Codec** | H.264 |

### Step 4: Audio Settings

| Setting | Value |
|---------|-------|
| **Codec** | AAC |
| **Bitrate** | 128 kbps |
| **Sample Rate** | 48 kHz |

### Step 5: Test Connection

1. Click **Test Connection** button
2. Monitor the **Preview** pane for video
3. Check Admin Panel: http://5.180.182.232:8080/
4. Should see stream status "LIVE" for `compassiontv` channel

### Troubleshooting vMix SRT

**Issue:** "Connection timed out"
- Verify network connectivity: `ping 5.180.182.232`
- Check firewall on Media Server: `ufw status | grep 9000`
- Verify SRT server running: `supervisorctl status srt-server`

**Issue:** "Timeout waiting for SRT connection"
- Increase **Latency** setting to 2000-3000 ms
- Verify correct **streamid** parameter matches channel slug

**Issue:** Stream connected but no video
- Check vMix video preview is showing output
- Verify bitrate isn't too low (minimum 1000 kbps)
- Check Admin Panel → Streams for connection status

**Monitor Live Logs:**
```bash
# From server terminal
tail -f /var/www/mediaserver/storage/logs/srt-server.log
tail -f /var/www/mediaserver/storage/logs/laravel.log
```

---

## OBS Configuration (Open Broadcaster Software)

### Prerequisite

- OBS Studio v28.0 or later (with SRT support)
- Network access to 5.180.182.232:9000
- Channel configured in admin panel with slug: `compassiontv`

### Step 1: Open Output Settings

1. In OBS, go to **Settings** → **Stream**
2. Set **Service** dropdown to **Custom**

### Step 2: Configure SRT URL

In the **Server** field, enter:
```
srt://5.180.182.232:9000?streamid=compassiontv&latency=1000
```

| Setting | Value |
|---------|-------|
| **Service** | Custom |
| **Server** | `srt://5.180.182.232:9000` |
| **Stream Key** | `streamid=compassiontv&latency=1000` |

### Step 3: Output Settings

Go to **Settings** → **Output**

| Setting | Value |
|---------|-------|
| **Output Mode** | Simple or Advanced |
| **Video Bitrate** | 3000-5000 kbps |
| **Audio Bitrate** | 128 kbps |
| **Encoder** | Hardware (NVIDIA/AMD) if available, else x264 |

### Step 4: Video Settings

Go to **Settings** → **Video**

| Setting | Value |
|---------|-------|
| **Base Resolution** | 1920x1080 |
| **Output Resolution** | 1920x1080 |
| **FPS** | 30 or 60 |

### Step 5: Test Streaming

1. Add your sources to OBS (camera, screen share, etc.)
2. Click **Start Streaming** button
3. Wait 3-5 seconds for SRT connection to establish
4. Monitor connection status in OBS (should show green "Streaming")
5. Check Admin Panel: http://5.180.182.232:8080/

### Advanced OBS Configuration (FFmpeg SRT)

For more control over SRT parameters, use **Advanced Output Mode**:

1. **Settings** → **Output** → Switch to **Advanced**
2. **Streaming Tab:**
   - **Type:** Custom FFMPEG Output
   - **FFmpeg Output Type:** Output to URL
   - **URL:** `srt://5.180.182.232:9000?streamid=compassiontv`
   - **Container Format:** flv
   - **Video Encoder:** libx264
   - **Video Bitrate:** 3000 kbps
   - **Audio Encoder:** aac
   - **Audio Bitrate:** 128 kbps

### Troubleshooting OBS SRT

**Issue:** "Connection refused"
- Verify **Server** URL is correct: `srt://5.180.182.232:9000`
- Verify **Stream Key** includes streamid parameter
- Check network connectivity and firewall

**Issue:** Frequent disconnects
- Reduce **Video Bitrate** to 2000-3000 kbps
- Add **latency** parameter: `srt://5.180.182.232:9000?streamid=compassiontv&latency=2000`
- Check network stability: `ping -c 20 5.180.182.232`

**Issue:** Stream starts but stops after 10-30 seconds
- Check SRT server logs: `tail -f /var/www/mediaserver/storage/logs/srt-server.log`
- Verify Flussonic RTMP is running: `curl http://127.0.0.1:80/api/v1/streams/list`
- Restart SRT server: `sudo supervisorctl restart srt-server`

---

## FFmpeg Command Line

### Direct SRT Push

```bash
ffmpeg -re -i input.mp4 \
  -c:v h264 -b:v 3000k -c:a aac -b:a 128k \
  -f flv "srt://5.180.182.232:9000?streamid=compassiontv&latency=1000"
```

### Screen Capture + SRT (macOS/Linux)

```bash
ffmpeg -f gdigrab -i desktop \
  -c:v h264 -b:v 3000k -c:a aac \
  -f flv "srt://5.180.182.232:9000?streamid=compassiontv&latency=1000"
```

### Real-time Camera Encoding

```bash
ffmpeg -f dshow -i video="Camera Device" \
  -c:v h264 -b:v 2500k \
  -f flv "srt://5.180.182.232:9000?streamid=compassiontv"
```

---

## Admin Panel Monitoring

### View Active Streams

1. Open: http://5.180.182.232:8080/
2. Login with: `admin@mediaserver.local` / `admin123`
3. Navigate to: **Streams** or **Channels**
4. Should see `compassiontv` channel with status:
   - **Protocol:** SRT
   - **Status:** LIVE
   - **Bitrate:** Shows current throughput
   - **Duration:** Stream uptime
   - **Viewers:** Number of people watching HLS/DASH

### Test Playback

#### HLS Playback
```
http://5.180.182.232:80/compassiontv/index.m3u8
```
- Test in VLC: **File** → **Open Network Stream** → Paste URL
- Test in Web Browser (if player added)

#### DASH Playback
```
http://5.180.182.232:80/compassiontv/manifest.mpd
```

#### DVR Recording
- Automatically recorded when stream is live
- Accessible via admin panel → **Recordings**
- Or direct file access: `/var/cache/flussonic/recordings/`

---

## SRT Server Health Check

### Manual Status Check

```bash
# SSH to server
ssh root@5.180.182.232

# Check if SRT server is running
supervisorctl status srt-server

# View recent logs (last 50 lines)
tail -50 /var/www/mediaserver/storage/logs/srt-server.log

# Check listening ports
ss -tlnup | grep 9000

# Monitor real-time
watch -n 1 'supervisorctl status srt-server && echo "" && ss -tlnup | grep 9000'
```

### Common Issues & Solutions

| Issue | Cause | Solution |
|-------|-------|----------|
| SRT server not listening | Process crashed | `supervisorctl restart srt-server` |
| Connection times out | Firewall blocked | `ufw allow 9000/udp` |
| Stream connects but stops | Flussonic RTMP down | `systemctl status flussonic` |
| High latency/buffering | Network congestion | Reduce encoder bitrate, increase latency |
| No video in player | RTMP relay failed | Check RTMP configuration, restart Flussonic |

### API Health Endpoint

```bash
curl http://5.180.182.232:8080/api/health

# Response:
{
  "status": "ok",
  "service": "MediaServer",
  "version": "1.1.0",
  "timestamp": "2026-05-22T18:09:22Z",
  "environment": "production"
}
```

---

## Supported Stream IDs (Channel Slugs)

The SRT URL must include the channel slug as the `streamid` parameter. Examples:

| Channel | URL |
|---------|-----|
| CompassionTV | `srt://5.180.182.232:9000?streamid=compassiontv` |
| Live Events | `srt://5.180.182.232:9000?streamid=liveevents` |
| Sports Feed | `srt://5.180.182.232:9000?streamid=sportsfeed` |

> **Note:** Channel must be created in admin panel with matching slug and set to "Active" status.

---

## Performance Tuning

### SRT Connection Parameters

```
srt://5.180.182.232:9000?streamid=compassiontv&latency=1000&transtype=live&nakreport=1
```

| Parameter | Default | Recommended | Notes |
|-----------|---------|-------------|-------|
| `latency` | 120 ms | 1000-2000 ms | Higher = more stable, slower |
| `transtype` | live | live | Stream type |
| `nakreport` | 0 | 1 | Enable NAK (negative ACK) reports |
| `timeout` | 0 | 5000000 | Connection timeout in microseconds |

### Encoder Bitrate Guidelines

| Quality | Video Bitrate | Audio Bitrate | Total | Notes |
|---------|---------------|---------------|-------|-------|
| **Low** | 1000-1500 kbps | 64 kbps | ~1.1 Mbps | Mobile/backup |
| **Medium** | 2000-3000 kbps | 128 kbps | ~2.2 Mbps | Recommended |
| **High** | 4000-5000 kbps | 192 kbps | ~4.3 Mbps | Professional |
| **Premium** | 6000-8000 kbps | 256 kbps | ~6.4 Mbps | 4K ready |

---

## Support & Logs

### Real-time Log Monitoring

```bash
# SRT Server Logs
ssh root@5.180.182.232 'tail -f /var/www/mediaserver/storage/logs/srt-server.log'

# Laravel API Logs
ssh root@5.180.182.232 'tail -f /var/www/mediaserver/storage/logs/laravel.log'

# FFmpeg Debug Logs
ssh root@5.180.182.232 'tail -f /var/www/mediaserver/storage/logs/ffmpeg.log'
```

### Emergency Restart

```bash
# Restart SRT Server
ssh root@5.180.182.232 'sudo supervisorctl restart srt-server'

# Restart Flussonic
ssh root@5.180.182.232 'sudo systemctl restart flussonic'

# Restart All Services
ssh root@5.180.182.232 'sudo supervisorctl restart all'
```

---

## Glossary

- **SRT:** Secure Reliable Transport - UDP-based streaming protocol with encryption & error correction
- **RTMP:** Real-Time Messaging Protocol - protocol for streaming media
- **HLS:** HTTP Live Streaming - adaptive bitrate streaming protocol
- **DASH:** Dynamic Adaptive Streaming over HTTP - MPEG standard for adaptive streaming
- **streamid:** Parameter identifying the destination channel/stream
- **Latency:** Delay between encoder output and viewer reception

---

**Version:** 1.0  
**Last Updated:** May 22, 2026  
**Server:** 5.180.182.232  
**Status:** ✅ Production Ready
