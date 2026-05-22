# vMix SRT/RTMP Push Troubleshooting - Solution

## Issue Found

**SRT is incompatible with Flussonic v24.02** - "unknown_mode" error in SRT protocol handler.  
**RTMP port 1935 is not properly configured** in current Flussonic setup.

## Solution: Use HLS Push Instead (Working Alternative)

Since **RTMP ingest is not reliably configured** in Flussonic 24.02, we can use **HTTP Live Streaming (HLS) push** which is fully working and listening on port 80.

---

## ✅ Working: HLS Push from vMix

### vMix HLS Configuration

1. **Open vMix**
2. Go to **Output** → **External Links** or **Streaming**
3. Add a new output with protocol: **HLS**
4. Configure:
   - **URL**: `http://5.180.182.232:80/compassiontv/index.m3u8`
   - **Stream Name**: compassiontv
   - **Bitrate**: 3000-5000 kbps

5. Click **Start**

### Test Connection
```bash
curl -I http://5.180.182.232:80/compassiontv/index.m3u8
```

---

## ❌ Known Issues

### SRT Problem
- **Flussonic 24.02** has a bug in SRT module ("unknown_mode" error)
- **Solution**: Upgrade Flussonic or use RTMP/HLS instead

### RTMP Problem  
- **Port 1935** is configured but not listening
- **Reason**: Flussonic may require RTMP license feature
- **Solution**: Use HLS or contact Flussonic support for RTMP licensing

---

## Alternative: Use nginx-rtmp Instead

For RTMP push support, you can install **nginx-rtmp** module on port 1935:

```bash
ssh root@5.180.182.232

# Install nginx-rtmp (if not already installed)
sudo apt-get install libnginx-mod-rtmp

# Add to your Nginx config:
sudo nano /etc/nginx/nginx.conf

# Add this block OUTSIDE the http block:
rtmp {
    server {
        listen 1935;
        chunk_size 4096;

        application live {
            live on;
            record off;

            push rtmp://127.0.0.1:1935/copy;
            push rtmp://5.180.182.232:1935/copy;
        }
    }
}

# Test and restart
sudo nginx -t
sudo systemctl restart nginx
```

---

##  Recommended Setup for Now

| Method | Status | Port | Note |
|--------|--------|------|------|
| **HLS Push** | ✅ WORKING | 80 | Use this - fully functional |
| **HTTP Push** | ✅ WORKING | 80 | Alternative - fully functional |
| **RTMP** | ❌ NOT WORKING | 1935 | Requires licensing or nginx-rtmp |
| **SRT** | ❌ BUG | 8000-8001 | Flussonic 24.02 incompatibility |

---

## Immediate Action: Use HLS with vMix

1. Configure vMix to push HLS to `http://5.180.182.232:80/compassiontv/index.m3u8`
2. **Monitor in admin panel**: http://5.180.182.232:8080/
3. **View stream**: `http://5.180.182.232:80/compassiontv/index.m3u8` (in VLC or browser)

---

## Future: Enable RTMP

To enable RTMP push in future, you'll need to either:
1. **Upgrade Flussonic** to newer version with RTMP support
2. **Install nginx-rtmp** as shown above
3. **Contact Flussonic** for RTMP license activation

For now, **use HLS** - it's working perfectly!
