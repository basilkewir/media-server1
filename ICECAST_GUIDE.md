# Icecast Streaming Guide

This guide covers setting up and using Icecast streaming capabilities in the Media Server.

## Table of Contents

1. [Overview](#overview)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [API Endpoints](#api-endpoints)
5. [Streaming to Icecast](#streaming-to-icecast)
6. [Retrieving Icecast Statistics](#retrieving-icecast-statistics)
7. [Troubleshooting](#troubleshooting)

## Overview

Icecast is a free streaming media server that allows you to:

- Stream live audio to multiple listeners simultaneously
- Host multiple streams on a single server
- Provide statistics on listener counts and bandwidth usage
- Integrate with the Media Server for centralized management

This Media Server integration automatically manages Icecast mount points, credentials, and metadata synchronization.

## Installation

### 1. Install Icecast

On Ubuntu/Debian:

```bash
sudo apt-get update
sudo apt-get install icecast2
```

### 2. Configure Icecast

Copy the example configuration:

```bash
sudo cp /var/www/media-server/icecast.conf.example /etc/icecast2/icecast.xml
```

Edit the configuration file:

```bash
sudo nano /etc/icecast2/icecast.xml
```

Key configuration parameters:

```xml
<hostname>your-server.com</hostname>
<port>8000</port>
<admin>
    <admin-user>admin</admin-user>
    <admin-password>your-secure-password</admin-password>
</admin>
<authentication type="default">
    <source-password>your-source-password</source-password>
</authentication>
```

### 3. Start Icecast Service

```bash
sudo systemctl start icecast2
sudo systemctl enable icecast2
```

### 4. Verify Icecast is Running

```bash
sudo systemctl status icecast2
# Test the admin interface
curl -u admin:your-secure-password http://localhost:8000/admin/stats
```

### 5. Configure Media Server

Update your `.env` file:

```env
ICECAST_HOST=localhost
ICECAST_PORT=8000
ICECAST_ADMIN_USER=admin
ICECAST_ADMIN_PASSWORD=your-secure-password
```

Update `config/services.php`:

```php
'icecast' => [
    'host' => env('ICECAST_HOST', 'localhost'),
    'port' => env('ICECAST_PORT', 8000),
    'admin_user' => env('ICECAST_ADMIN_USER', 'admin'),
    'admin_password' => env('ICECAST_ADMIN_PASSWORD', 'hackme'),
],
```

## Configuration

### Enable Icecast for a Channel

```bash
# Create a new channel with Icecast enabled
curl -X POST http://media-server.com/api/channels \
  -H "Content-Type: application/json" \
  -d '{
    "name": "My Live Stream",
    "slug": "my-live-stream",
    "description": "My live audio stream",
    "is_icecast_enabled": true
  }'
```

### Update Existing Channel

```bash
curl -X PUT http://media-server.com/api/channels/1 \
  -H "Content-Type: application/json" \
  -d '{
    "is_icecast_enabled": true
  }'
```

## API Endpoints

### 1. Create Icecast Mount Point

Generates a new Icecast mount point and credentials for a channel.

**Endpoint:** `POST /api/icecast/{channel}/create`

**Request:**
```json
{
  "bitrate": 192,
  "sample_rate": 44100,
  "channels": 2
}
```

**Response:**
```json
{
  "success": true,
  "message": "Icecast stream created successfully",
  "data": {
    "mount_point": "/my-live-stream",
    "password": "a1b2c3d4e5f6g7h8",
    "stream_url": "icecast://source:a1b2c3d4e5f6g7h8@localhost:8000/my-live-stream"
  }
}
```

### 2. Get Stream URL

Retrieves the Icecast stream URL for pushing content.

**Endpoint:** `GET /api/icecast/{channel}/stream-url`

**Response:**
```json
{
  "success": true,
  "data": {
    "mount_point": "/my-live-stream",
    "push_url": "icecast://source:a1b2c3d4e5f6g7h8@localhost:8000/my-live-stream",
    "listen_url": "http://localhost:8000/my-live-stream"
  }
}
```

### 3. Get Stream Statistics

Retrieves real-time statistics from Icecast.

**Endpoint:** `GET /api/icecast/{channel}/stats`

**Response:**
```json
{
  "success": true,
  "data": {
    "listeners": 42,
    "bitrate_kbps": 192,
    "sample_rate": 44100,
    "channels": 2,
    "is_streaming": true,
    "stream_uptime": 3600,
    "peak_listeners": 150
  }
}
```

### 4. Disconnect Stream

Gracefully disconnects a streaming session.

**Endpoint:** `POST /api/icecast/{channel}/disconnect`

**Response:**
```json
{
  "success": true,
  "message": "Stream disconnected"
}
```

### 5. Set Maximum Listeners

Limits the maximum number of concurrent listeners.

**Endpoint:** `POST /api/icecast/{channel}/max-listeners`

**Request:**
```json
{
  "max_listeners": 500
}
```

**Response:**
```json
{
  "success": true,
  "message": "Max listeners updated to 500"
}
```

### 6. Enable/Disable Icecast

Toggle Icecast streaming for a channel.

**Endpoint:** `POST /api/icecast/{channel}/enable`

**Response:**
```json
{
  "success": true,
  "message": "Icecast enabled for channel"
}
```

## Streaming to Icecast

### Using FFmpeg

```bash
# Get the stream URL first
STREAM_URL=$(curl -s http://media-server.com/api/icecast/1/stream-url | jq -r '.data.push_url')

# Stream live audio
ffmpeg -f alsa -i default -c:a libmp3lame -b:a 192k -f mp3 ${STREAM_URL}
```

### Using OBS (Open Broadcaster Software)

1. Go to **Settings** → **Stream**
2. Select **Custom RTMP Server** (or use Generic)
3. Server: `icecast://source:password@localhost:8000`
4. Stream Key: `/channel-slug`
5. Click **Start Streaming**

### Using liquidsoap

```
# liquidsoap script
stream = input.alsa()
output.icecast(%mp3, 
  host = "localhost", 
  port = 8000, 
  user = "source", 
  password = "your-password", 
  mount = "/my-stream", 
  stream)
```

## Retrieving Icecast Statistics

### Get Real-Time Listener Count

```bash
curl http://media-server.com/api/icecast/1/stats | jq '.data.listeners'
```

### Monitor Stream Health

```bash
watch -n 5 'curl -s http://media-server.com/api/icecast/1/stats | jq .'
```

### Get Peak Listeners

```bash
curl http://media-server.com/api/icecast/1/stats | jq '.data.peak_listeners'
```

## Troubleshooting

### Connection Refused

**Problem:** `Failed to connect to Icecast server`

**Solutions:**
1. Verify Icecast is running: `sudo systemctl status icecast2`
2. Check firewall: `sudo ufw allow 8000`
3. Verify configuration: `sudo netstat -tlnp | grep icecast`

### Authentication Failed

**Problem:** `401 Unauthorized` when connecting

**Solutions:**
1. Verify admin credentials in `.env`
2. Check Icecast admin password: `/etc/icecast2/icecast.xml`
3. Test directly: `curl -u admin:password http://localhost:8000/admin/stats`

### Stream Not Starting

**Problem:** `Failed to create mount point`

**Solutions:**
1. Check mount point doesn't exist: `curl http://localhost:8000/admin/stats`
2. Verify channel is Icecast-enabled
3. Check logs: `sudo tail -f /var/log/icecast2/error.log`

### High CPU Usage

**Problem:** Icecast consuming excessive CPU

**Solutions:**
1. Reduce bitrate in channel configuration
2. Check for clients stuck in IDLE state
3. Monitor with: `top | grep icecast2`

### Listeners Dropping

**Problem:** Frequent listener disconnections

**Solutions:**
1. Check bandwidth limits in `icecast.conf.example`
2. Increase queue size in configuration
3. Verify network stability
4. Check streaming source bitrate stability

### Mount Point Not Found

**Problem:** `404 Not Found` when accessing stream

**Solutions:**
1. Verify mount point name: `/api/icecast/{channel}/stream-url`
2. Ensure channel has active stream
3. Check Icecast admin interface: `http://localhost:8000/admin/`
4. Recreate mount point: `POST /api/icecast/{channel}/create`

## Performance Tips

1. **Use appropriate bitrate:** 128 kbps for speech, 192+ kbps for music
2. **Set listener limits:** Prevent unlimited listeners consuming bandwidth
3. **Monitor regularly:** Use the stats endpoint to track performance
4. **Optimize network:** Use wired connection for streaming source
5. **Separate servers:** Run Icecast on separate machine from main application if handling many listeners

## Security Considerations

1. **Change default passwords:** Update admin and source passwords in `icecast.xml`
2. **Use HTTPS:** Consider running Icecast behind NGINX with SSL/TLS
3. **Firewall rules:** Only expose necessary ports
4. **Monitor access:** Check `/var/log/icecast2/access.log` regularly
5. **Disable unused features:** Disable web interface if not needed

## Integration with Relay Broadcasting

Icecast can be used as a relay target. See [RELAY_GUIDE.md](./RELAY_GUIDE.md) for details on setting up multi-server relay broadcasting.
