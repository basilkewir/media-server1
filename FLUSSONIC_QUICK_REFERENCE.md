# Flussonic Quick Reference

Quick command reference for common Flussonic operations.

## Installation

### Quick Install

```bash
# Download and extract
wget https://your-repo.com/flussonic_24.02_unlimited.zip
unzip flussonic_24.02_unlimited.zip -d /opt/flussonic/

# Or use the automated script
sudo bash install_flussonic.sh /path/to/flussonic_24.02_unlimited.zip

# Then verify
sudo systemctl status flussonic
```

## Service Management

### Start/Stop/Restart

```bash
# Start Flussonic
sudo systemctl start flussonic

# Stop Flussonic
sudo systemctl stop flussonic

# Restart Flussonic
sudo systemctl restart flussonic

# Check status
sudo systemctl status flussonic

# View logs
sudo journalctl -u flussonic -n 50
sudo tail -f /var/log/flussonic/flussonic.log
```

### Enable/Disable Auto-start

```bash
# Enable auto-start on boot
sudo systemctl enable flussonic

# Disable auto-start
sudo systemctl disable flussonic
```

## Configuration

### Edit Configuration

```bash
# Edit main config
sudo nano /etc/flussonic/flussonic.conf

# Reload without restart
sudo systemctl reload flussonic

# Restart after changes
sudo systemctl restart flussonic
```

### Basic Settings

```ini
# Change admin password
admin_password your_new_password

# Set listening ports
port 8080           # Admin panel
http_port 80        # HTTP streaming
rtmp_port 1935      # RTMP push/pull

# Set API token
api_password your_api_token

# Enable/disable features
dvr enabled
http enabled
hls enabled
dash enabled
cors enabled
```

## API Commands

### Authentication

```bash
# Using API token
curl -H "X-Auth-Token: your_api_token" \
  http://localhost:8080/api/v1/streams

# Using basic auth (if configured)
curl -u admin:password \
  http://localhost:8080/api/v1/streams
```

### Get Server Info

```bash
# Get Flussonic version and info
curl -H "X-Auth-Token: token" \
  http://localhost:8080/api/v1/info
```

### List All Streams

```bash
curl -H "X-Auth-Token: token" \
  http://localhost:8080/api/v1/streams
```

### Get Stream Stats

```bash
# Get statistics for specific stream
curl -H "X-Auth-Token: token" \
  http://localhost:8080/api/v1/streams/my_stream/stats
```

### Create Stream

```bash
curl -X POST -H "X-Auth-Token: token" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "new_stream",
    "input": "rtmp://encoder:1935/live/stream",
    "outputs": ["hls", "dash"]
  }' \
  http://localhost:8080/api/v1/streams
```

### Delete Stream

```bash
curl -X DELETE -H "X-Auth-Token: token" \
  http://localhost:8080/api/v1/streams/stream_name
```

## Streaming URLs

### Get Stream URLs

```bash
# HLS (HTTP Live Streaming)
http://your-server/stream_name/index.m3u8

# DASH (Dynamic Adaptive Streaming)
http://your-server/stream_name/manifest.mpd

# RTMP (Real Time Messaging Protocol)
rtmp://your-server:1935/live/stream_name
```

### Test Playback

```bash
# Test HLS with ffplay
ffplay http://localhost/stream_name/index.m3u8

# Test RTMP
ffplay rtmp://localhost:1935/live/stream_name

# Test DASH
ffplay http://localhost/stream_name/manifest.mpd
```

## Push Stream to Flussonic

### Using FFmpeg

```bash
# Push local file
ffmpeg -re -i input.mp4 \
  -c:v copy -c:a copy \
  -f flv rtmp://localhost:1935/live/my_stream

# Push from camera/encoder
ffmpeg -f dshow -i video="Camera" \
  -c:v libx264 -b:v 2000k \
  -c:a aac -b:a 128k \
  -f flv rtmp://localhost:1935/live/my_stream

# Push with transcoding
ffmpeg -re -i input.mp4 \
  -c:v libx264 -b:v 1500k -maxrate 1500k -bufsize 3000k \
  -c:a aac -b:a 128k \
  -f flv rtmp://localhost:1935/live/my_stream
```

### Using OBS

1. Go to **Settings** → **Stream**
2. Set:
   - **Service:** Custom RTMP Server
   - **Server:** `rtmp://your-server-ip:1935/live`
   - **Stream Key:** `my_stream`
3. Click **Start Streaming**

## DVR & Recording

### Start DVR Recording

```bash
# Via API
curl -X POST -H "X-Auth-Token: token" \
  http://localhost:8080/api/v1/streams/stream_name/dvr/start

# Or enable in stream config
stream my_stream {
  dvr on;
  dvr_duration 24h;  # Keep 24 hours
}
```

### Stop DVR Recording

```bash
curl -X POST -H "X-Auth-Token: token" \
  http://localhost:8080/api/v1/streams/stream_name/dvr/stop
```

### List DVR Recordings

```bash
curl -H "X-Auth-Token: token" \
  http://localhost:8080/api/v1/streams/stream_name/dvr
```

### Configure DVR Storage

```ini
# In flussonic.conf
rec_dir /var/cache/flussonic/recordings
dvr_segment_duration 10        # 10-second segments
dvr_cleanup_interval 86400     # Cleanup daily
dvr_max_size 100GB             # Max storage
```

## Relay & Distribution

### Configure Relay Input

```bash
# Get stream from upstream
stream relay_channel {
  input rtmp://upstream:1935/live/channel;
  output hls://localhost/relay_channel.m3u8;
  output dash://localhost/relay_channel.mpd;
}
```

### Configure Relay Output

```bash
# Push to another Flussonic
stream push_relay {
  input rtmp://localhost:1935/live/source;
  output rtmp://remote-flussonic:1935/live/channel;
}
```

## Monitoring & Logging

### Check System Resources

```bash
# Monitor Flussonic process
ps aux | grep flussonic

# Check CPU/Memory
top -p $(pgrep flussonic)

# Check network connections
netstat -tlnp | grep flussonic

# Monitor bandwidth
iftop
```

### View Real-time Logs

```bash
# Follow Flussonic logs
sudo tail -f /var/log/flussonic/flussonic.log

# Filter for errors
sudo grep ERROR /var/log/flussonic/flussonic.log

# Filter for specific stream
sudo grep "my_stream" /var/log/flussonic/flussonic.log
```

### Get Stream Metrics

```bash
# Get live metrics for all streams
watch -n 2 "curl -s -H 'X-Auth-Token: token' \
  http://localhost:8080/api/v1/streams | jq '.streams[] | {name, viewers: .stats.viewers, bitrate: .stats.bitrate}'"

# Get specific stream metrics
curl -s -H "X-Auth-Token: token" \
  http://localhost:8080/api/v1/streams/my_stream/stats | jq .
```

## Troubleshooting

### Stream Won't Start

```bash
# Check if Flussonic is running
sudo systemctl status flussonic

# Check if port is in use
sudo lsof -i :1935

# Check logs for errors
sudo tail -f /var/log/flussonic/flussonic.log

# Restart service
sudo systemctl restart flussonic
```

### High CPU Usage

```bash
# Check number of streams
curl -s -H "X-Auth-Token: token" \
  http://localhost:8080/api/v1/streams | jq '.streams | length'

# Check bitrates
curl -s -H "X-Auth-Token: token" \
  http://localhost:8080/api/v1/streams | \
  jq '.streams[] | {name, bitrate: .stats.bitrate}'

# Reduce transcoding quality
# Edit stream config and reduce bitrate
```

### Storage Running Out

```bash
# Check disk usage
df -h /var/cache/flussonic/

# Check DVR size
du -sh /var/cache/flussonic/recordings/

# Clean old recordings
find /var/cache/flussonic/recordings -mtime +7 -delete

# Set cleanup in config
dvr_max_size 50GB
dvr_cleanup_interval 3600
```

### API Connection Failed

```bash
# Verify API is enabled
grep -i "api" /etc/flussonic/flussonic.conf

# Test API connectivity
curl http://localhost:8080/api/v1/info

# Check if token is valid
curl -H "X-Auth-Token: token" \
  http://localhost:8080/api/v1/info

# Restart service
sudo systemctl restart flussonic
```

## Performance Tuning

### Optimize for Live Streaming

```ini
# In flussonic.conf
max_connections 5000
buffer_size 1024
cache_size 100MB

# HLS optimization
hls_segment_duration 2
hls_segments_in_playlist 10

# DASH optimization
dash_segment_duration 2
dash_segments_in_playlist 10
```

### Optimize for VOD

```ini
vod_dir /var/cache/flussonic/vod
vod_buffer_size 10MB
hls_dvr_duration 24h
```

### Optimize for Recording

```ini
rec_dir /var/cache/flussonic/recordings
dvr_segment_duration 10
dvr_buffer 50MB
```

## Backup & Restore

### Backup Configuration

```bash
# Backup Flussonic config and DVR
sudo tar -czf flussonic_backup_$(date +%Y%m%d).tar.gz \
  /etc/flussonic \
  /opt/flussonic \
  /var/cache/flussonic/recordings

# Save to safe location
sudo cp flussonic_backup_*.tar.gz /mnt/backup/
```

### Restore Configuration

```bash
# Stop Flussonic
sudo systemctl stop flussonic

# Restore from backup
sudo tar -xzf flussonic_backup_20240515.tar.gz -C /

# Fix permissions
sudo chown -R flussonic:flussonic /etc/flussonic
sudo chown -R flussonic:flussonic /var/cache/flussonic

# Start Flussonic
sudo systemctl start flussonic
```

## Useful Bash Functions

```bash
# Add these to ~/.bashrc

# Check Flussonic status
flussonic_status() {
  sudo systemctl status flussonic
}

# View Flussonic logs
flussonic_logs() {
  sudo tail -f /var/log/flussonic/flussonic.log
}

# Restart Flussonic
flussonic_restart() {
  sudo systemctl restart flussonic && echo "Flussonic restarted"
}

# Get all streams
flussonic_streams() {
  curl -s -H "X-Auth-Token: $FLUSSONIC_TOKEN" \
    http://localhost:8080/api/v1/streams | jq '.streams[].name'
}

# Get stream stats
flussonic_stats() {
  local stream=$1
  curl -s -H "X-Auth-Token: $FLUSSONIC_TOKEN" \
    http://localhost:8080/api/v1/streams/$stream/stats | jq .
}
```

## Environment Variables

```bash
# Set in ~/.bashrc or /etc/environment
export FLUSSONIC_HOST=localhost
export FLUSSONIC_PORT=8080
export FLUSSONIC_TOKEN=your_api_token
export FLUSSONIC_ADMIN=admin
export FLUSSONIC_PASS=password
```

## Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| Port already in use | Change port in config or kill process: `sudo lsof -ti :1935 \| xargs kill -9` |
| DVR not recording | Enable DVR in config: `dvr enabled` |
| High latency | Reduce buffer size and segment duration |
| Dropped frames | Check CPU usage and reduce bitrate |
| Storage full | Clean old recordings or increase limit |
| API auth fails | Verify token and enable API in config |
| Stream stuttering | Increase buffer size or check network |
| Slow transcoding | Reduce output bitrate or quality |

## Documentation

- **Full Installation Guide**: `FLUSSONIC_INSTALLATION.md`
- **Integration Guide**: `FLUSSONIC_INTEGRATION.md`
- **Official Docs**: https://flussonic.com/doc/
- **API Reference**: `http://your-server:8080/api/v1/docs`

---

**Last Updated:** May 2026
**Status:** ✅ Production Ready
