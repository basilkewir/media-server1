# SRT Multi-Stream Configuration Guide

## Overview

The Media Server now supports **SRT streaming for multiple streams** simultaneously. Each stream has its own dedicated SRT port and can be pushed independently.

## Stream Configuration

### Active Streams

| Stream Name | SRT Port | StreamID | Flussonic | Status |
|---|---|---|---|---|
| **compassiontv** | `9000` | `compassiontv` | `rtmp://127.0.0.1:1935/compassiontv` | ✅ Active |
| **sudfmtv** | `9001` | `sudfmtv` | `rtmp://127.0.0.1:1935/sudfmtv` | ✅ Active |

## vMix Configuration

### For Streaming to compassiontv

1. **Open vMix Streaming Settings**
   - Go to: **Streaming** → **SRT**

2. **Configure SRT Output**
   - **Server:** `5.180.182.232`
   - **Port:** `9000`
   - **Stream ID:** `compassiontv`
   - **Mode:** `Caller` (vMix is the client)
   - **Latency:** `1000ms` (1 second)

3. **Click Connect/Start**

### For Streaming to sudfmtv

1. **Open vMix Streaming Settings**
   - Go to: **Streaming** → **SRT**

2. **Configure SRT Output**
   - **Server:** `5.180.182.232`
   - **Port:** `9001`
   - **Stream ID:** `sudfmtv`
   - **Mode:** `Caller` (vMix is the client)
   - **Latency:** `1000ms` (1 second)

3. **Click Connect/Start**

## Playback URLs

### HLS Streaming

```
http://5.180.182.232/compassiontv/index.m3u8
http://5.180.182.232/sudfmtv/index.m3u8
```

### DASH Streaming

```
http://5.180.182.232/compassiontv/manifest.mpd
http://5.180.182.232/sudfmtv/manifest.mpd
```

### Direct RTMP

```
rtmp://5.180.182.232:1935/compassiontv
rtmp://5.180.182.232:1935/sudfmtv
```

## Firewall Configuration

Both SRT ports are open in the server firewall:

```bash
# compassiontv SRT port
ufw allow 9000/udp

# sudfmtv SRT port
ufw allow 9001/udp
```

**Status:**
```bash
ss -tlnup | grep -E '9000|9001'
```

Should show both ports listening for UDP connections.

## Testing SRT Connection

### Test compassiontv

```bash
# From encoder/client
srt-live-transmit "srt://5.180.182.232:9000?streamid=compassiontv&mode=caller" \
  file://con
```

### Test sudfmtv

```bash
# From encoder/client
srt-live-transmit "srt://5.180.182.232:9001?streamid=sudfmtv&mode=caller" \
  file://con
```

## Monitoring

### Check SRT Server Status

```bash
# SSH to server
ssh root@5.180.182.232

# Check process
supervisorctl status srt-server

# View logs
tail -f /var/www/mediaserver/storage/logs/srt-server.log
```

### Monitor Specific Stream

```bash
# Filter for compassiontv
tail -f /var/www/mediaserver/storage/logs/srt-server.log | grep compassiontv

# Filter for sudfmtv
tail -f /var/www/mediaserver/storage/logs/srt-server.log | grep sudfmtv
```

### Check Listening Ports

```bash
# Verify both SRT ports are listening
ss -tlnup | grep srt-live-transmit
```

Expected output:
```
udp   UNCONN 0  0  0.0.0.0:9000  0.0.0.0:*  users:(("srt-live-transmit",pid=XXXXX,fd=4))
udp   UNCONN 0  0  0.0.0.0:9001  0.0.0.0:*  users:(("srt-live-transmit",pid=XXXXX,fd=4))
```

## Stream Architecture

### Data Flow

```
vMix (encoder)
    ↓
    └─→ SRT://server:9000 (compassiontv)
            ↓
            srt-live-transmit
            ↓
            UDP://127.0.0.1:5000
            ↓
            FFmpeg (relay)
            ↓
            RTMP://127.0.0.1:1935/compassiontv
            ↓
            Flussonic
            ├─→ HLS (HTTP Live Streaming)
            ├─→ DASH (Dynamic Adaptive Streaming)
            └─→ DVR (Recording)

vMix (encoder)
    ↓
    └─→ SRT://server:9001 (sudfmtv)
            ↓
            srt-live-transmit
            ↓
            UDP://127.0.0.1:5001
            ↓
            FFmpeg (relay)
            ↓
            RTMP://127.0.0.1:1935/sudfmtv
            ↓
            Flussonic
            ├─→ HLS (HTTP Live Streaming)
            ├─→ DASH (Dynamic Adaptive Streaming)
            └─→ DVR (Recording)
```

### Component Details

| Component | Port | Purpose |
|---|---|---|
| **SRT Listener (compassiontv)** | 9000/UDP | Receives SRT from vMix encoder |
| **SRT Listener (sudfmtv)** | 9001/UDP | Receives SRT from vMix encoder |
| **UDP Relay (compassiontv)** | 5000/UDP | Internal relay from srt-live-transmit to FFmpeg |
| **UDP Relay (sudfmtv)** | 5001/UDP | Internal relay from srt-live-transmit to FFmpeg |
| **Flussonic RTMP** | 1935/TCP | Receives RTMP from FFmpeg relay |
| **Flussonic HTTP** | 80/TCP | Serves HLS/DASH/DVR streams |
| **Laravel Admin** | 8080/TCP | Media Server admin panel |

## Troubleshooting

### Stream Not Connecting

**Check 1: Verify ports are listening**
```bash
ss -tlnup | grep -E '9000|9001'
```

**Check 2: Check firewall**
```bash
sudo ufw status | grep -E '9000|9001'
```

**Check 3: Review SRT logs**
```bash
tail -50 /var/www/mediaserver/storage/logs/srt-server.log | grep -i error
```

### FFmpeg Errors

**Common errors:**
- `Error opening output rtmp://...: Input/output error` - Check RTMP URL format
- `corrupt input packet` - Check network stability, may be packet loss
- `Socket error: Connection refused` - Verify RTMP destination is reachable

**Solution:** Review logs and restart:
```bash
supervisorctl restart srt-server
```

### High Packet Loss

If you see `RCV-DROPPED packets` in logs:
- Check network connection stability
- Increase latency in vMix settings (try 2000ms instead of 1000ms)
- Reduce encoder bitrate

### Stream Not Appearing in Admin Panel

1. Verify FFmpeg relay is running:
   ```bash
   ps aux | grep ffmpeg
   ```

2. Check Flussonic RTMP connection:
   ```bash
   tail -50 /var/log/flussonic/flussonic.log
   ```

3. Restart Flussonic:
   ```bash
   systemctl restart flussonic
   ```

## Configuration Files

### SRT Server Script
**Location:** `/var/www/mediaserver/srt-server.py`

Multi-stream configuration (STREAMS dict):
```python
STREAMS = {
    'compassiontv': {
        'srt_port': 9000,
        'streamid': 'compassiontv',
        'udp_port': 5000,
        'rtmp_stream': 'compassiontv'
    },
    'sudfmtv': {
        'srt_port': 9001,
        'streamid': 'sudfmtv',
        'udp_port': 5001,
        'rtmp_stream': 'sudfmtv'
    }
}
```

### Supervisor Configuration
**Location:** `/etc/supervisor/conf.d/srt-server.conf`

Manages automatic restart of SRT server process.

### Flussonic Configuration
**Location:** `/etc/flussonic/flussonic.conf`

Stream definitions for RTMP ingest:
```ini
stream compassiontv {
  input publish://;
}

stream sudfmtv {
  input publish://;
}
```

## Adding New Streams

To add a new stream to the multi-stream setup:

1. **Edit srt-server.py**
   ```python
   STREAMS = {
       'compassiontv': {...},
       'sudfmtv': {...},
       'newstream': {
           'srt_port': 9002,
           'streamid': 'newstream',
           'udp_port': 5002,
           'rtmp_stream': 'newstream'
       }
   }
   ```

2. **Open firewall port**
   ```bash
   sudo ufw allow 9002/udp
   ```

3. **Add to Flussonic config**
   ```ini
   stream newstream {
     input publish://;
   }
   ```

4. **Commit and deploy**
   ```bash
   git add srt-server.py
   git commit -m "Add newstream SRT support on port 9002"
   git push
   cd /var/www/mediaserver && git pull
   supervisorctl restart srt-server
   systemctl restart flussonic
   ```

## Performance Metrics

### Bandwidth per Stream

- **Video (H.264, 1280x720, 25fps):** ~500-600 kbps
- **Audio (AAC, 48kHz stereo):** ~128-192 kbps
- **Total per stream:** ~700-800 kbps

Two streams simultaneously: ~1.4-1.6 Mbps

### Server Capacity

- **CPU:** Each FFmpeg relay uses ~5-10% CPU
- **Memory:** ~50-100MB per relay process
- **Network:** Upstream bandwidth should support total bitrate

## Related Documentation

- **Full SRT Implementation:** `SRT_IMPLEMENTATION_REPORT.md`
- **Flussonic Quick Reference:** `FLUSSONIC_QUICK_REFERENCE.md`
- **Deployment Guide:** `DEPLOYMENT_GUIDE.md`
- **Troubleshooting:** `VMIX_SRT_RTMP_TROUBLESHOOTING.md`

---

**Last Updated:** May 22, 2026
**Status:** ✅ Production Ready
**Server:** 5.180.182.232
**Admin Panel:** http://5.180.182.232:8080
