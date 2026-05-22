# Quick Streaming Setup - SRT/RTMP Push

## 🚀 Quick Start URLs

### SRT Streaming (Recommended)
```
srt://5.180.182.232:8000?streamid=compassiontv
srt://5.180.182.232:8001?streamid=sudfmtv
```

### RTMP Streaming (Alternative)
```
rtmp://5.180.182.232:1935/live/compassiontv
rtmp://5.180.182.232:1935/live/sudfmtv
```

---

## 📺 vMix Configuration

### SRT Method (Preferred)
1. **Output** → **Streaming**
2. Choose **SRT**
3. Enter URL: `srt://5.180.182.232:8000?streamid=compassiontv`
4. **SRT Latency**: 1000 ms
5. Click **Start**

### RTMP Method (Fallback)
1. **Output** → **Streaming**
2. Choose **RTMP**
3. Enter URL: `rtmp://5.180.182.232:1935/live/compassiontv`
4. **Bitrate**: 3000-5000 kbps
5. Click **Start**

---

## 🎥 OBS Configuration

### SRT Method (OBS 28.0+)
1. **Settings** → **Stream**
2. **Service**: Custom
3. **Server**: `srt://5.180.182.232:8000?streamid=compassiontv`
4. **Stream Key**: (leave empty)
5. Click **Start Streaming**

### RTMP Method
1. **Settings** → **Stream**
2. **Service**: Custom
3. **Server**: `rtmp://5.180.182.232:1935/live`
4. **Stream Key**: `compassiontv`
5. Click **Start Streaming**

---

## ✅ Verification

After starting your stream:

```bash
# Check stream status
curl http://5.180.182.232:8080/api/health

# View in admin panel
http://5.180.182.232:8080/
# Login: admin@mediaserver.local / admin123
```

---

## 🔧 Recommended Settings

| Setting | Value |
|---------|-------|
| Video Resolution | 1920x1080 or 1280x720 |
| Frame Rate | 30 fps |
| Video Bitrate | 3000-5000 kbps |
| Audio Bitrate | 128 kbps |
| SRT Latency | 1000 ms |
| Keyframe Interval | 2 seconds |

---

## 🆘 Troubleshooting

### "Cannot Connect" Error
- ✅ Ports are open (8000, 8001 UDP and 1935 TCP)
- ✅ Flussonic is running
- Check internet connectivity: `ping 5.180.182.232`
- Try RTMP as fallback if SRT fails

### "Connection Timeout"
- Check firewall on your local network
- Try reducing SRT latency to 500 ms
- Ensure correct port numbers (8000 or 8001 for SRT)

### "No Data Being Received"
- Verify encoder settings match your bitrate
- Check CPU usage isn't maxed out
- Monitor server with: `ssh root@5.180.182.232 "top"`

---

## 📊 Monitor Streams

**Admin Panel**: http://5.180.182.232:8080/
- Live stream status
- Bitrate graphs
- Viewer count
- Stream events

---

**Server**: 5.180.182.232  
**Status**: ✅ Ready for Streaming  
**Streams**: compassiontv, sudfmtv
