# Relay Broadcasting Guide

This guide covers setting up and using the relay broadcasting system for multi-server distribution.

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Relay Server Setup](#relay-server-setup)
4. [API Endpoints](#api-endpoints)
5. [Creating Relays](#creating-relays)
6. [Monitoring Relays](#monitoring-relays)
7. [Advanced Configuration](#advanced-configuration)
8. [Troubleshooting](#troubleshooting)

## Overview

Relay broadcasting allows you to:

- Distribute streams to multiple servers simultaneously
- Support multiple streaming protocols (Icecast, RTMP, Shoutcast)
- Automatically failover if a relay server goes offline
- Monitor relay health and listener counts
- Scale beyond single-server limitations

## Architecture

### Components

```
┌─────────────────────────────────────────────────────────┐
│           Media Server (Central Control)                │
│  - Channel Management                                   │
│  - RelayBroadcastService                               │
│  - Health Monitoring                                    │
└─────────────────────────────────────────────────────────┘
           ↓          ↓          ↓
    ┌──────────┐ ┌──────────┐ ┌──────────┐
    │ Icecast  │ │   RTMP   │ │ Shoutcast│
    │ Server 1 │ │ Server 2 │ │ Server 3 │
    │ (8000)   │ │ (1935)   │ │ (8002)   │
    └──────────┘ └──────────┘ └──────────┘
```

### Data Flow

1. **Stream Source** → Media Server (RTMP/Icecast push)
2. **Media Server** → FFmpeg Process (relay encoder)
3. **FFmpeg** → **Relay Servers** (simultaneous broadcast)
4. **Relay Servers** → **Listeners** (distributed playback)

## Relay Server Setup

### 1. Register Relay Server

Define a relay target server in the database.

**Endpoint:** `POST /api/relay/servers`

**Request:**
```json
{
  "name": "NYC Icecast 1",
  "hostname": "nyc-icecast.example.com",
  "port": 8000,
  "username": "source",
  "password": "secure-password-123",
  "server_type": "icecast",
  "max_listeners": 1000,
  "location": "New York, USA"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Relay server added successfully",
  "data": {
    "id": 1,
    "name": "NYC Icecast 1",
    "hostname": "nyc-icecast.example.com",
    "port": 8000,
    "server_type": "icecast",
    "is_active": true
  }
}
```

### 2. Supported Server Types

#### Icecast

```json
{
  "name": "Icecast EU",
  "hostname": "eu.streaming.example.com",
  "port": 8000,
  "server_type": "icecast",
  "username": "source",
  "password": "password123"
}
```

#### RTMP

```json
{
  "name": "RTMP China",
  "hostname": "rtmp-cn.example.com",
  "port": 1935,
  "server_type": "rtmp",
  "username": "live",
  "password": "secret789"
}
```

#### Shoutcast

```json
{
  "name": "Shoutcast Backup",
  "hostname": "backup.shoutcast.example.com",
  "port": 8002,
  "server_type": "shoutcast",
  "username": "dj",
  "password": "djpass456"
}
```

## API Endpoints

### 1. List Relay Servers

Get all active relay servers.

**Endpoint:** `GET /api/relay/servers`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "NYC Icecast 1",
      "hostname": "nyc-icecast.example.com",
      "port": 8000,
      "server_type": "icecast",
      "max_listeners": 1000,
      "location": "New York, USA"
    }
  ]
}
```

### 2. Add Relay Server

Register a new relay server.

**Endpoint:** `POST /api/relay/servers`

See [Relay Server Setup](#relay-server-setup) section above.

### 3. Start Relay

Initiate relay broadcasting to a server.

**Endpoint:** `POST /api/relay/{channel}/start`

**Request:**
```json
{
  "relay_server_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Relay broadcast started",
  "data": {
    "relay_id": 5,
    "status": "connecting",
    "relay_url": "icecast://source:password@nyc-icecast.example.com:8000/channel-name"
  }
}
```

### 4. Stop Relay

Terminate relay broadcasting.

**Endpoint:** `POST /api/relay/{relay}/stop`

**Response:**
```json
{
  "success": true,
  "message": "Relay broadcast stopped"
}
```

### 5. Get Relay Status

Check real-time relay health and statistics.

**Endpoint:** `GET /api/relay/{relay}/status`

**Response:**
```json
{
  "success": true,
  "data": {
    "relay_id": 5,
    "channel_id": 1,
    "status": "connected",
    "relay_url": "icecast://source:***@nyc-icecast.example.com:8000/channel",
    "listeners": 42,
    "bitrate_kbps": 192,
    "uptime_seconds": 3600,
    "process_pid": 12345,
    "server": {
      "id": 1,
      "name": "NYC Icecast 1",
      "is_online": true
    }
  }
}
```

### 6. Get Channel Relays

List all active relays for a specific channel.

**Endpoint:** `GET /api/relay/{channel}/broadcasts`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "relay_id": 5,
      "server_name": "NYC Icecast 1",
      "status": "connected",
      "listeners": 42
    },
    {
      "relay_id": 6,
      "server_name": "RTMP China",
      "status": "connected",
      "listeners": 128
    }
  ]
}
```

### 7. Get Relay Event Logs

Retrieve relay history and events.

**Endpoint:** `GET /api/relay/{relay}/logs?limit=50`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "event_type": "relay_started",
      "message": "Relay broadcast started",
      "status": "success",
      "listeners_count": 0,
      "bitrate_kbps": 192,
      "created_at": "2024-01-15 10:30:00"
    },
    {
      "id": 2,
      "event_type": "relay_health_check",
      "message": "Health check successful",
      "status": "success",
      "listeners_count": 42,
      "bitrate_kbps": 192,
      "created_at": "2024-01-15 10:35:00"
    }
  ]
}
```

### 8. Enable/Disable Relay Feature

Toggle relay broadcasting for a channel.

**Endpoint:** `POST /api/relay/{channel}/enable`

**Response:**
```json
{
  "success": true,
  "message": "Relay enabled for channel"
}
```

**Endpoint:** `POST /api/relay/{channel}/disable`

**Response:**
```json
{
  "success": true,
  "message": "Relay disabled for channel"
}
```

## Creating Relays

### Step-by-Step: Multi-Server Setup

#### 1. Register Relay Servers

```bash
# Register Icecast relay in NYC
curl -X POST http://media-server.com/api/relay/servers \
  -H "Content-Type: application/json" \
  -d '{
    "name": "NYC Icecast",
    "hostname": "nyc.example.com",
    "port": 8000,
    "username": "source",
    "password": "nyc-secret-123",
    "server_type": "icecast",
    "max_listeners": 1000,
    "location": "New York, USA"
  }'

# Register RTMP relay in EU
curl -X POST http://media-server.com/api/relay/servers \
  -H "Content-Type: application/json" \
  -d '{
    "name": "EU RTMP",
    "hostname": "eu.example.com",
    "port": 1935,
    "username": "live",
    "password": "eu-secret-789",
    "server_type": "rtmp",
    "max_listeners": 2000,
    "location": "Frankfurt, Germany"
  }'
```

#### 2. Create Channel with Relay Enabled

```bash
curl -X POST http://media-server.com/api/channels \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Global News Live",
    "slug": "global-news-live",
    "is_relay_enabled": true,
    "description": "24/7 news broadcast"
  }'
```

#### 3. Start Relays

```bash
# Start relay to NYC Icecast
RELAY_RESPONSE=$(curl -s -X POST http://media-server.com/api/relay/1/start \
  -H "Content-Type: application/json" \
  -d '{"relay_server_id": 1}')
echo $RELAY_RESPONSE | jq '.data.relay_id'

# Start relay to EU RTMP
curl -X POST http://media-server.com/api/relay/1/start \
  -H "Content-Type: application/json" \
  -d '{"relay_server_id": 2}'
```

#### 4. Monitor Relays

```bash
# Check all relays for the channel
curl http://media-server.com/api/relay/1/broadcasts | jq '.data'

# Get specific relay status
curl http://media-server.com/api/relay/5/status | jq '.data'
```

## Monitoring Relays

### Health Check Command

The Media Server runs automatic health checks every 30 seconds:

```bash
php artisan relay:health-check --interval=30
```

This command:
- Checks relay server connectivity
- Verifies FFmpeg processes are running
- Updates listener counts
- Logs all status changes
- Automatically restarts failed relays

### Monitor Relay Health

```bash
# Get live relay status
watch -n 5 'curl -s http://media-server.com/api/relay/5/status | jq .'

# Get relay event history
curl http://media-server.com/api/relay/5/logs | jq '.data | .[] | {event_type, status, listeners_count}'
```

### Check Supervisor Status

```bash
sudo supervisorctl status media-server-relay-monitor
```

## Advanced Configuration

### Custom Bitrate per Relay

```bash
# Adjust FFmpeg encoding for different networks
# Edit RelayBroadcastService.php startRelayProcess() method to support per-relay bitrate
```

### Failover Strategy

The relay system automatically:
1. Monitors server connectivity
2. Detects process failures
3. Restarts failed relays
4. Logs all failures for analysis

### Bandwidth Management

```bash
# Set max bitrate per relay server
curl -X POST http://media-server.com/api/relay/servers/1 \
  -H "Content-Type: application/json" \
  -d '{
    "bitrate_kbps": 512,
    "max_listeners": 500
  }'
```

## Troubleshooting

### Relay Won't Connect

**Problem:** `status: connecting` after several seconds

**Solutions:**
1. Verify relay server is online: `curl -I http://relay-server.com:8000`
2. Check credentials are correct
3. Verify firewall allows outbound connections
4. Check FFmpeg is installed: `ffmpeg -version`

### High CPU Usage

**Problem:** FFmpeg processes consuming excessive CPU

**Solutions:**
1. Reduce relay count
2. Lower bitrate in channel configuration
3. Run relays on separate machine
4. Monitor: `ps aux | grep ffmpeg`

### Listeners Not Counting

**Problem:** Listeners show 0 on relay

**Solutions:**
1. Verify relay is connected (status = "connected")
2. Check relay server admin API is accessible
3. Wait for listeners to connect
4. Check relay logs: `/api/relay/{relay}/logs`

### Relay Server Offline

**Problem:** `status: server_offline`

**Solutions:**
1. Check server is running: `ssh relay-server 'systemctl status icecast2'`
2. Check network connectivity: `ping relay-server.com`
3. Verify firewall: `telnet relay-server.com 8000`
4. Review relay logs for details

### FFmpeg Process Crashed

**Problem:** `status: process_died`

**Solutions:**
1. Check system resources: `free -h`, `df -h`
2. Review FFmpeg logs in `/tmp/relay-*.log`
3. Manually restart relay: `POST /api/relay/1/stop` then `POST /api/relay/1/start`
4. Check relay server can accept new connections

### Memory Leak

**Problem:** FFmpeg process memory grows over time

**Solutions:**
1. Restart relays periodically: Set up cron job to stop/start
2. Monitor memory: `watch 'ps aux | grep ffmpeg'`
3. Reduce stream duration before restart
4. Update FFmpeg to latest version

### Relay Logs Growing Large

**Problem:** Disk space consumed by relay logs

**Solutions:**
1. Purge old logs: `DELETE FROM relay_broadcast_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);`
2. Set up log rotation in logrotate
3. Archive logs to external storage
4. Reduce logging verbosity

## Performance Tips

1. **Use direct connections:** Minimize network hops between servers
2. **Monitor bandwidth:** Track relay bandwidth consumption
3. **Batch relays:** Group relays to same geographic region
4. **Load balance:** Distribute relays across multiple encoders
5. **Cache streams:** Use CDN for cached stream segments

## Security Best Practices

1. **Use strong passwords:** Generate unique passwords for each relay server
2. **Restrict network:** Use IP whitelist on relay servers
3. **Encrypt in transit:** Use HTTPS/SSL between relay servers
4. **Audit logs:** Review relay logs regularly for anomalies
5. **Monitor access:** Track relay connections and disconnections

## Integration with VOD Fallback

When a stream source disconnects:
1. VOD fallback activates on main channel
2. Relays can continue from VOD source
3. Or relays automatically stop (configurable)

See [DEVELOPMENT.md](./DEVELOPMENT.md) for fallback configuration.

## Next Steps

- [ICECAST_GUIDE.md](./ICECAST_GUIDE.md) - Configure Icecast servers as relay targets
- [DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md) - Deploy relay system to production
- [QUICK_REFERENCE.md](./QUICK_REFERENCE.md) - Quick command reference
