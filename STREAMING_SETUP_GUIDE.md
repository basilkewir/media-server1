# Streaming Setup Guide - SRT/RTMP Push from vMix/OBS

## Server Configuration

Your media server is configured with the following streaming inputs:

### Stream Details

| Stream Name | Type | Input Method | RTMP URL | SRT URL | Status |
|------------|------|--------------|----------|---------|--------|
| **compassiontv** | Live | RTMP / SRT | `rtmp://5.180.182.232:1935/live/compassiontv` | `srt://5.180.182.232:8000` | ✅ Active |
| **sudfmtv** | Live | RTMP / SRT | `rtmp://5.180.182.232:1935/live/sudfmtv` | `srt://5.180.182.232:8001` | ✅ Active |

### Server Details

- **Server IP**: 5.180.182.232
- **RTMP Port**: 1935 (TCP) - ✅ Open
- **SRT Port (compassiontv)**: 8000 (UDP) - ✅ Open
- **SRT Port (sudfmtv)**: 8001 (UDP) - ✅ Open

---

## Option 1: SRT Push from vMix

### Step 1: Setup in vMix

1. Open **vMix**
2. Go to **Output** → **Streaming** (or **External Links**)
3. Choose **SRT** protocol
4. Enter the streaming URL:
   ```
   srt://5.180.182.232:8000?streamid=compassiontv
   ```
   (Or use `8001` for `sudfmtv`)

5. **SRT Settings** (if available):
   - **Latency**: 1000-2000 ms (adjust as needed)
   - **Bandwidth**: Keep as auto
   - **Encryption**: None (unless configured on server)
   - **Bitrate**: Adjust based on your connection quality

6. Click **Start** to begin streaming

### Step 2: Verify Connection

Once streaming starts from vMix, verify on the server:

```bash
ssh root@5.180.182.232
curl -s http://localhost:8080/api/health
```

You should see active streams if the push is successful.

---

## Option 2: SRT Push from OBS

### Step 1: Install SRT Plugin (if needed)

1. **Check if OBS has native SRT support**:
   - OBS 28.0+ has native SRT support
   - If using older version, install SRT plugin

2. **Configuration steps**:
   - Open **OBS Settings** → **Stream**
   - Select **Custom...** from Service dropdown
   - Change **Server** to:
     ```
     srt://5.180.182.232:8000?streamid=compassiontv
     ```
   - Leave **Stream Key** empty (SRT handles this via streamid)

3. **Optional: Advanced Settings**
   - **SRT Latency**: 1000 ms
   - **SRT Encryption**: Off (unless configured)
   - **Bitrate**: Match your encoder settings (2000-5000 kbps recommended)

### Step 2: Start Streaming

1. Click **Start Streaming** in OBS
2. Monitor the status indicator - should show green/active
3. Check bitrate and frame drops in the status area

### Step 3: Verify on Server

```bash
ssh root@5.180.182.232
curl -s http://localhost:8080/api/health
```

---

## Option 3: RTMP Push (Alternative - More Compatible)

If SRT isn't working, RTMP is more universally supported.

### vMix Configuration

1. Go to **Output** → **Streaming**
2. Set **URL**:
   ```
   rtmp://5.180.182.232:1935/live/compassiontv
   ```
3. Configure:
   - **Bitrate**: 3000-5000 kbps recommended
   - **Resolution**: 1920x1080 or 1280x720
   - **Framerate**: 30 or 60 fps

4. Click **Start**

### OBS Configuration

1. **Settings** → **Stream**
2. Set **Server**:
   ```
   rtmp://5.180.182.232:1935/live
   ```
3. Set **Stream Key**:
   ```
   compassiontv
   ```
4. **Audio/Video Bitrate**:
   - Video: 3000-5000 kbps
   - Audio: 128 kbps
5. Click **Start Streaming**

---

## Troubleshooting

### SRT Connection Issues

#### Problem: "Connection Timeout" or "Cannot Connect"

**Check 1: Verify port is open**
```bash
ssh root@5.180.182.232 "sudo ss -tlnp | grep 8000"
```
Expected output should show SRT listening on 8000 (UDP).

**Check 2: Verify Flussonic is running**
```bash
ssh root@5.180.182.232 "sudo systemctl status flussonic"
```

**Check 3: Check Flussonic logs**
```bash
ssh root@5.180.182.232 "sudo tail -f /var/log/flussonic/flussonic.log"
```

**Solution**: 
- Ensure UFW firewall allows UDP 8000-8001:
  ```bash
  ssh root@5.180.182.232 "sudo ufw allow 8000:8001/udp"
  ```

#### Problem: "Stream Started but No Data Received"

1. Check network connectivity between encoder and server
2. Try reducing SRT latency in encoder settings (start with 1000ms)
3. Check firewall rules on your local network
4. Try RTMP as fallback (more stable over some networks)

#### Problem: "High Latency" or "Packet Loss"

1. Reduce **SRT Latency** setting to 500-1000ms
2. Check network conditions:
   ```bash
   ping 5.180.182.232  # Check latency
   ```
3. Reduce encoder bitrate to improve stability
4. Check CPU usage on server:
   ```bash
   ssh root@5.180.182.232 "top -bn1 | head -20"
   ```

### RTMP Connection Issues

#### Problem: "Connection Refused"

1. Verify RTMP port 1935 is open:
   ```bash
   ssh root@5.180.182.232 "sudo ss -tlnp | grep 1935"
   ```

2. Check if nginx-rtmp is running (if used):
   ```bash
   ssh root@5.180.182.232 "ps aux | grep rtmp"
   ```

#### Problem: "Authentication Failed" (RTMP)

- Ensure correct stream name is used (compassiontv or sudfmtv)
- No password is required for publish on this server
- Check if RTMP is enabled in Flussonic

---

## Testing Your Setup

### Test SRT Connection

```bash
# From your local machine, test SRT connectivity
ffmpeg -f lavfi -i testsrc=s=1280x720:d=10 -f lavfi -i sine=f=1000:d=10 \
  -c:v libx264 -c:a aac -f mpegts srt://5.180.182.232:8000?streamid=compassiontv
```

### Test RTMP Connection

```bash
# Test RTMP stream push
ffmpeg -f lavfi -i testsrc=s=1280x720:d=10 -f lavfi -i sine=f=1000:d=10 \
  -c:v libx264 -c:a aac \
  -f flv rtmp://5.180.182.232:1935/live/compassiontv
```

### Verify Stream is Active

```bash
# Check if stream is receiving data
ssh root@5.180.182.232 "curl -s http://localhost:8080/api/health"
```

---

## Recommended Settings

### For Lower Latency (Live Events)

- **Video Bitrate**: 3000-4000 kbps
- **Resolution**: 1280x720 @ 30fps
- **SRT Latency**: 500-1000ms
- **Audio Bitrate**: 128 kbps

### For Better Quality (Streaming)

- **Video Bitrate**: 5000-8000 kbps
- **Resolution**: 1920x1080 @ 30fps
- **SRT Latency**: 1000-2000ms
- **Audio Bitrate**: 192 kbps

### For Mobile/Unstable Networks

- **Video Bitrate**: 1500-2500 kbps
- **Resolution**: 1280x720 @ 24fps
- **SRT Latency**: 2000-3000ms
- **Audio Bitrate**: 96 kbps

---

## Admin Dashboard Access

Once streaming is active, monitor your streams in the admin panel:

- **Admin URL**: http://5.180.182.232:8080/
- **Username**: admin@mediaserver.local
- **Password**: admin123

From the dashboard you can:
- View active streams and connection status
- Monitor bitrate and viewer count
- Start/stop streams
- Configure output targets
- Access stream statistics

---

## Support URLs

| Service | URL | Port |
|---------|-----|------|
| **Admin Panel** | http://5.180.182.232:8080/ | 8080 |
| **API Health** | http://5.180.182.232:8080/api/health | 8080 |
| **Flussonic** | http://5.180.182.232:80/admin | 80 |
| **RTMP Push** | rtmp://5.180.182.232:1935/live/ | 1935 |
| **SRT Push (compassiontv)** | srt://5.180.182.232:8000 | 8000 |
| **SRT Push (sudfmtv)** | srt://5.180.182.232:8001 | 8001 |

---

## Next Steps

1. ✅ Ensure SRT/RTMP ports are open (done: 8000, 8001 UDP and 1935 TCP)
2. 📝 Configure your encoder (vMix/OBS) with URLs above
3. ▶️ Start streaming from your encoder
4. 📊 Monitor in admin panel at :8080
5. 🔧 Adjust bitrate/latency based on performance

---

**Last Updated**: May 22, 2026  
**Status**: ✅ Ready for Streaming  
**Configured Streams**: compassiontv, sudfmtv
