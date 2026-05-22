# Flussonic Installation & Integration - Complete Guide

## Overview

Flussonic 24.02 is a professional media server for live streaming, VOD, and relay broadcasting. This complete package includes everything you need to install, configure, and integrate Flussonic with your Media Server.

## What's Included

### 📋 Documentation (4 files)

1. **FLUSSONIC_INSTALLATION.md** (8,000+ words)
   - Complete system setup guide
   - Step-by-step installation procedure
   - Configuration examples
   - Troubleshooting guide

2. **FLUSSONIC_INTEGRATION.md** (6,000+ words)
   - Laravel Media Server integration
   - Service and controller implementation
   - API endpoint examples
   - Stream management workflows

3. **FLUSSONIC_QUICK_REFERENCE.md** (3,000+ words)
   - Common commands reference
   - API curl examples
   - Bash functions
   - Performance tuning tips

4. **FLUSSONIC_SETUP_GUIDE.md** (this file)
   - Quick start instructions
   - Integration overview
   - File locations
   - Next steps

### 🔧 Installation Script

**install_flussonic.sh** - Automated installation script
- Checks prerequisites
- Extracts Flussonic binary
- Creates system user
- Configures systemd service
- Sets up firewall rules
- Starts Flussonic automatically

## Quick Start (5 Minutes)

### Step 1: Transfer Flussonic ZIP

```bash
# On your local machine
scp flussonic_24.02_unlimited.zip user@your-server:/tmp/

# Or download to server if you have URL
ssh user@your-server
wget https://your-repo/flussonic_24.02_unlimited.zip
```

### Step 2: Run Installation Script

```bash
# Make script executable
chmod +x install_flussonic.sh

# Run as root
sudo bash install_flussonic.sh /tmp/flussonic_24.02_unlimited.zip
```

### Step 3: Verify Installation

```bash
# Check service status
sudo systemctl status flussonic

# Test web interface
curl http://localhost:8080

# Expected output: HTML response (admin panel)
```

### Step 4: Access Admin Panel

Open in browser:
```
http://your-server-ip:8080
Username: admin
Password: (check in /etc/flussonic/flussonic.conf)
```

## System Requirements

| Component | Requirement |
|-----------|-------------|
| OS | Ubuntu 20.04 LTS or newer |
| CPU | 2+ cores (4+ recommended for transcoding) |
| RAM | 4GB minimum, 8GB+ recommended |
| Storage | 50GB+ for streaming cache and VOD |
| Network | 100 Mbps+ internet connection |
| Ports | 80, 443, 1935, 8080 |

## Installation Overview

```
┌────────────────────────────────────────────────┐
│  Flussonic Installation Workflow                │
├────────────────────────────────────────────────┤
│ 1. Transfer flussonic_24.02_unlimited.zip       │
│ 2. Run install_flussonic.sh script              │
│    ├─ Update system packages                    │
│    ├─ Extract Flussonic binary                  │
│    ├─ Create flussonic system user              │
│    ├─ Create config file                        │
│    ├─ Create systemd service                    │
│    └─ Configure firewall                        │
│ 3. Service automatically starts                 │
│ 4. Access admin panel at :8080                  │
│ 5. Configure streams                            │
└────────────────────────────────────────────────┘
```

## File Locations

### Flussonic Files

```
/opt/flussonic/
├── flussonic              # Main binary
├── flussonic.conf         # Default config (reference)
└── README                 # Documentation

/etc/flussonic/
└── flussonic.conf         # Active configuration

/var/log/flussonic/
└── flussonic.log          # Application logs

/var/cache/flussonic/
├── vod/                   # VOD files
├── recordings/            # DVR recordings
└── cache/                 # Streaming cache

/etc/systemd/system/
└── flussonic.service      # Systemd service file
```

### Media Server Integration Files

```
app/Services/
└── FlussonicService.php   # Service class (create)

app/Http/Controllers/API/
└── FlussonicController.php # Controller (create)

config/
└── services.php           # Flussonic config (update)

routes/
└── api.php                # API routes (update)

.env
└── FLUSSONIC_* variables  # Configuration (update)
```

## Key Configuration

### 1. Update .env File

```bash
# Add these to your .env
FLUSSONIC_ENABLED=true
FLUSSONIC_HOST=your-server-ip
FLUSSONIC_PORT=8080
FLUSSONIC_RTMP_PORT=1935
FLUSSONIC_HTTP_PORT=80
FLUSSONIC_ADMIN_USER=admin
FLUSSONIC_ADMIN_PASSWORD=your_secure_password
FLUSSONIC_API_URL=http://your-server-ip:8080/api/v1
FLUSSONIC_API_TOKEN=your_api_token
FLUSSONIC_STORAGE_PATH=/var/cache/flussonic
FLUSSONIC_STREAM_PREFIX=stream_
```

### 2. Update Flussonic Config

```bash
# Edit configuration
sudo nano /etc/flussonic/flussonic.conf

# Key settings:
port 8080                      # Admin panel port
http_port 80                   # HTTP streaming port
rtmp_port 1935                 # RTMP push/pull port
admin admin                    # Admin username
admin_password your_password   # Admin password
api_password your_api_token    # API authentication
dvr enabled                    # Enable DVR recording
hls enabled                    # Enable HLS output
dash enabled                   # Enable DASH output
```

### 3. Firewall Configuration

```bash
# Allow necessary ports
sudo ufw allow 22/tcp     # SSH
sudo ufw allow 80/tcp     # HTTP
sudo ufw allow 443/tcp    # HTTPS
sudo ufw allow 1935/tcp   # RTMP
sudo ufw allow 8080/tcp   # Flussonic admin
sudo ufw enable
```

## Streaming URLs

### After Installation

```
HLS:  http://your-server-ip/stream_name/index.m3u8
DASH: http://your-server-ip/stream_name/manifest.mpd
RTMP: rtmp://your-server-ip:1935/live/stream_name
```

### Example RTMP Push

```bash
# Push video using FFmpeg
ffmpeg -re -i input.mp4 \
  -c:v libx264 -b:v 2000k \
  -c:a aac -b:a 128k \
  -f flv rtmp://your-server-ip:1935/live/my_stream
```

### Example HLS Playback

```bash
# Play HLS stream
ffplay http://your-server-ip/my_stream/index.m3u8

# Or in HTML5 video player
# <video src="http://your-server-ip/my_stream/index.m3u8"></video>
```

## Integration with Media Server

### Step 1: Create Service Class

Copy the FlussonicService implementation from `FLUSSONIC_INTEGRATION.md`:

```bash
# This file doesn't exist yet - you need to create it based on the guide
app/Services/FlussonicService.php
```

### Step 2: Create Controller

Create `app/Http/Controllers/API/FlussonicController.php` following the template in `FLUSSONIC_INTEGRATION.md`.

### Step 3: Update Routes

Add to `routes/api.php`:

```php
Route::prefix('flussonic')->group(function () {
    Route::get('/info', [FlussonicController::class, 'serverInfo']);
    Route::get('/streams', [FlussonicController::class, 'listStreams']);
    Route::get('/{channel}/stats', [FlussonicController::class, 'getStats']);
    Route::post('/{channel}/dvr/start', [FlussonicController::class, 'startDVR']);
    Route::post('/{channel}/dvr/stop', [FlussonicController::class, 'stopDVR']);
});
```

### Step 4: Test Integration

```bash
# Test Flussonic API from Media Server
php artisan tinker
> app(FlussonicService::class)->getServerInfo()
> app(FlussonicService::class)->listStreams()
```

## Common Tasks

### Create a New Stream

```bash
# Via API
curl -X POST http://localhost:8080/api/v1/streams \
  -H "X-Auth-Token: your_token" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "my_stream",
    "input": "rtmp://localhost:1935/live/source"
  }'

# Or via Media Server
POST /api/flussonic/streams
{
  "name": "my_stream",
  "input": "rtmp://source"
}
```

### Start DVR Recording

```bash
# Via API
curl -X POST http://localhost:8080/api/v1/streams/my_stream/dvr/start \
  -H "X-Auth-Token: your_token"

# Via Media Server
POST /api/flussonic/my_stream/dvr/start
```

### Get Stream Statistics

```bash
# Via API
curl http://localhost:8080/api/v1/streams/my_stream/stats \
  -H "X-Auth-Token: your_token"

# Via Media Server
GET /api/flussonic/my_stream/stats
```

## Monitoring

### Check Service Status

```bash
# Service status
sudo systemctl status flussonic

# Automatic restart on failure?
sudo systemctl is-enabled flussonic

# View recent logs
sudo tail -50 /var/log/flussonic/flussonic.log
```

### Monitor Performance

```bash
# CPU and memory
top -p $(pgrep flussonic)

# Network connections
netstat -tlnp | grep flussonic

# Disk usage
du -sh /var/cache/flussonic/*

# Real-time metrics
watch -n 2 'curl -s http://localhost:8080/api/v1/streams | jq'
```

## Troubleshooting

### Service Won't Start

```bash
# Check for errors
sudo journalctl -u flussonic -n 50

# Start manually for debugging
sudo -u flussonic /opt/flussonic/flussonic -c /etc/flussonic/flussonic.conf -d

# Check if port is in use
sudo lsof -i :8080
```

### RTMP Connection Refused

```bash
# Verify port is listening
sudo netstat -tlnp | grep 1935

# Check firewall
sudo ufw status | grep 1935

# Test connectivity
telnet localhost 1935
```

### API Authentication Failed

```bash
# Verify API is enabled
grep "api" /etc/flussonic/flussonic.conf

# Test with correct token
curl -H "X-Auth-Token: your_token" \
  http://localhost:8080/api/v1/info

# Check token in config
grep "api_password" /etc/flussonic/flussonic.conf
```

## Next Steps

### 1. Complete Installation
- [ ] Transfer Flussonic ZIP to server
- [ ] Run install_flussonic.sh
- [ ] Verify service is running
- [ ] Access admin panel at :8080

### 2. Configure Flussonic
- [ ] Change admin password
- [ ] Set up API token
- [ ] Configure DVR storage
- [ ] Enable HLS/DASH output

### 3. Integrate with Media Server
- [ ] Create FlussonicService.php
- [ ] Create FlussonicController.php
- [ ] Add API routes
- [ ] Update .env with Flussonic settings

### 4. Test Streaming
- [ ] Push RTMP stream
- [ ] Verify HLS playback
- [ ] Check DVR recording
- [ ] Monitor via API

### 5. Production Setup
- [ ] Enable SSL/TLS
- [ ] Set up backups
- [ ] Configure monitoring
- [ ] Optimize for scale

## Documentation Files

| File | Purpose | Length |
|------|---------|--------|
| FLUSSONIC_INSTALLATION.md | Complete setup guide | 8,000+ words |
| FLUSSONIC_INTEGRATION.md | Media Server integration | 6,000+ words |
| FLUSSONIC_QUICK_REFERENCE.md | Command reference | 3,000+ words |
| FLUSSONIC_SETUP_GUIDE.md | This file | 2,000+ words |
| install_flussonic.sh | Automated installer | 150 lines |

## System Architecture

```
┌────────────────────────────────────────────────────────────────┐
│                    Viewers                                      │
│  (Web Browser, Mobile App, Smart TV)                           │
└─────────┬────────────────────────────────────────┬─────────────┘
          │                                        │
          │ HLS/DASH                              │ RTMP
          ↓                                        ↓
┌──────────────────────────────────────────────────────────────┐
│           Flussonic Media Server (Port 80/1935)              │
│  ┌─────────────┐  ┌──────────┐  ┌──────────────────────┐   │
│  │  HLS Output │  │DVR Record│  │ RTMP Relay Output    │   │
│  └─────────────┘  └──────────┘  └──────────────────────┘   │
│                                                               │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Stream Processing (Transcoding, Segmentation)       │   │
│  └──────────────────────────────────────────────────────┘   │
└──────────────────┬──────────────────────────────────────────┘
                   │ HTTP/REST API
                   ↓
┌──────────────────────────────────────────────────────────────┐
│      Laravel Media Server (Port 8000)                        │
│  - Stream Management                                         │
│  - Channel Configuration                                     │
│  - VOD Fallback Control                                      │
│  - Relay Distribution                                        │
└──────────────────┬──────────────────────────────────────────┘
                   │
        ┌──────────┴──────────┐
        ↓                     ↓
   ┌──────────┐         ┌──────────┐
   │ Encoders │         │ Database │
   └──────────┘         └──────────┘
```

## Support & Resources

### Documentation
- Flussonic Docs: https://flussonic.com/doc/
- This guide: See accompanying markdown files
- API Reference: `http://your-server:8080/api/v1/docs`

### Logs
- Service logs: `/var/log/flussonic/flussonic.log`
- System logs: `sudo journalctl -u flussonic`
- Config location: `/etc/flussonic/flussonic.conf`

### Commands
- See `FLUSSONIC_QUICK_REFERENCE.md` for command examples
- Use `--help` flag: `/opt/flussonic/flussonic --help`

## Security Best Practices

```bash
# 1. Change default credentials
sudo nano /etc/flussonic/flussonic.conf
# Change: admin_password and api_password

# 2. Enable firewall
sudo ufw enable

# 3. Restrict admin access
# Only allow from trusted IPs in firewall

# 4. Use HTTPS for web interface
# Set up SSL certificates (see FLUSSONIC_INSTALLATION.md)

# 5. Regular backups
sudo tar -czf flussonic_backup_$(date +%Y%m%d).tar.gz \
  /etc/flussonic /var/cache/flussonic/recordings
```

## Performance Tuning

```ini
# In /etc/flussonic/flussonic.conf

# For high-load streaming
max_connections 10000
buffer_size 2048
hls_segment_duration 2
hls_segments_in_playlist 12

# For VOD
vod_buffer_size 20MB

# For recording
dvr_segment_duration 10
dvr_max_size 500GB
```

---

## Summary

You now have a complete Flussonic installation and integration guide. Follow these steps:

1. **Install**: Run `sudo bash install_flussonic.sh /path/to/flussonic_24.02_unlimited.zip`
2. **Configure**: Update `.env` and `/etc/flussonic/flussonic.conf`
3. **Integrate**: Create Service and Controller in Media Server
4. **Test**: Push a stream and verify playback
5. **Monitor**: Use dashboard and API for monitoring

For detailed information, see the individual guide files included in this package.

**Installation Status:** ✅ Ready for Deployment
**Last Updated:** May 2026
