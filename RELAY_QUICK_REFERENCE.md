# Icecast & Relay Quick Reference

Quick command reference for Icecast streaming and relay broadcasting features.

## Icecast Management

### Create Icecast Stream

```bash
curl -X POST http://localhost:8000/api/icecast/1/create \
  -H "Content-Type: application/json" \
  -d '{
    "bitrate": 192,
    "sample_rate": 44100,
    "channels": 2
  }'
```

### Get Stream URL (for encoders)

```bash
curl http://localhost:8000/api/icecast/1/stream-url
# Returns: icecast://source:password@localhost:8000/mount-point
```

### Get Live Statistics

```bash
curl http://localhost:8000/api/icecast/1/stats | jq '.data'
# Returns: {listeners: 42, bitrate_kbps: 192, ...}
```

### Disconnect Stream

```bash
curl -X POST http://localhost:8000/api/icecast/1/disconnect
```

### Set Listener Limit

```bash
curl -X POST http://localhost:8000/api/icecast/1/max-listeners \
  -H "Content-Type: application/json" \
  -d '{"max_listeners": 500}'
```

### Enable/Disable Icecast

```bash
# Enable
curl -X POST http://localhost:8000/api/icecast/1/enable

# Disable
curl -X POST http://localhost:8000/api/icecast/1/disable
```

## Relay Server Management

### List Relay Servers

```bash
curl http://localhost:8000/api/relay/servers | jq '.data'
```

### Add Relay Server (Icecast)

```bash
curl -X POST http://localhost:8000/api/relay/servers \
  -H "Content-Type: application/json" \
  -d '{
    "name": "NYC Icecast",
    "hostname": "nyc.example.com",
    "port": 8000,
    "username": "source",
    "password": "secret123",
    "server_type": "icecast",
    "max_listeners": 1000,
    "location": "New York"
  }'
```

### Add Relay Server (RTMP)

```bash
curl -X POST http://localhost:8000/api/relay/servers \
  -H "Content-Type: application/json" \
  -d '{
    "name": "EU RTMP",
    "hostname": "eu.example.com",
    "port": 1935,
    "username": "live",
    "password": "rtmp-secret",
    "server_type": "rtmp",
    "max_listeners": 2000,
    "location": "Frankfurt"
  }'
```

### Add Relay Server (Shoutcast)

```bash
curl -X POST http://localhost:8000/api/relay/servers \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Shoutcast Backup",
    "hostname": "shout.example.com",
    "port": 8002,
    "username": "dj",
    "password": "dj-password",
    "server_type": "shoutcast",
    "max_listeners": 500,
    "location": "Backup"
  }'
```

## Relay Control

### Start Relay to Server

```bash
# Start relay for channel 1 to relay server 1
curl -X POST http://localhost:8000/api/relay/1/start \
  -H "Content-Type: application/json" \
  -d '{"relay_server_id": 1}'

# Returns: {relay_id: 5, status: "connecting", relay_url: "..."}
```

### Stop Relay

```bash
curl -X POST http://localhost:8000/api/relay/5/stop
```

### Get Relay Status

```bash
curl http://localhost:8000/api/relay/5/status | jq '.data'
# Returns: {relay_id: 5, status: "connected", listeners: 42, ...}
```

### Monitor Relay (watch)

```bash
watch -n 5 'curl -s http://localhost:8000/api/relay/5/status | jq ".data | {status, listeners, bitrate_kbps, uptime_seconds}"'
```

### Get Channel Relays

```bash
curl http://localhost:8000/api/relay/1/broadcasts | jq '.data'
# Returns array of all active relays for channel
```

### Get Relay Event Logs

```bash
# Get last 50 events
curl 'http://localhost:8000/api/relay/5/logs?limit=50' | jq '.data'

# Get last 100 events
curl 'http://localhost:8000/api/relay/5/logs?limit=100' | jq '.data'

# Filter by event type
curl 'http://localhost:8000/api/relay/5/logs?limit=50' | jq '.data[] | select(.event_type == "server_offline")'
```

## Relay Feature Toggles

### Enable Relay for Channel

```bash
curl -X POST http://localhost:8000/api/relay/1/enable
```

### Disable Relay (stops all relays)

```bash
curl -X POST http://localhost:8000/api/relay/1/disable
```

## Health Monitoring

### Run Health Check (manual)

```bash
php artisan relay:health-check --interval=30
```

### Check Supervisor Status

```bash
sudo supervisorctl status media-server-relay-monitor
```

### View Health Check Logs

```bash
tail -f /var/log/supervisor/media-server-relay-monitor.log
```

### View Recent Health Events

```bash
curl 'http://localhost:8000/api/relay/5/logs?limit=20' | jq '.data[] | select(.event_type == "relay_health_check")'
```

## Complete Workflow: Multi-Server Relay

### 1. Register Relay Servers

```bash
# NYC Icecast
RELAY_NYC=$(curl -s -X POST http://localhost:8000/api/relay/servers \
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
  }' | jq -r '.data.id')

echo "NYC Relay Server ID: $RELAY_NYC"

# EU RTMP
RELAY_EU=$(curl -s -X POST http://localhost:8000/api/relay/servers \
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
  }' | jq -r '.data.id')

echo "EU Relay Server ID: $RELAY_EU"
```

### 2. Create Channel with Relay Enabled

```bash
CHANNEL_ID=$(curl -s -X POST http://localhost:8000/api/channels \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Global News Live",
    "slug": "global-news",
    "description": "24/7 news broadcast",
    "is_relay_enabled": true
  }' | jq -r '.data.id')

echo "Channel ID: $CHANNEL_ID"
```

### 3. Start Stream to Main Server

```bash
# Push RTMP stream to media server
ffmpeg -f alsa -i default -c:a libmp3lame -b:a 192k \
  -f flv rtmp://localhost/live/global-news
```

### 4. Start Relays

```bash
# Relay to NYC
RELAY_1=$(curl -s -X POST http://localhost:8000/api/relay/$CHANNEL_ID/start \
  -H "Content-Type: application/json" \
  -d "{\"relay_server_id\": $RELAY_NYC}" | jq -r '.data.relay_id')

echo "Relay 1 (NYC) ID: $RELAY_1"

# Relay to EU
RELAY_2=$(curl -s -X POST http://localhost:8000/api/relay/$CHANNEL_ID/start \
  -H "Content-Type: application/json" \
  -d "{\"relay_server_id\": $RELAY_EU}" | jq -r '.data.relay_id')

echo "Relay 2 (EU) ID: $RELAY_2"
```

### 5. Monitor Relays

```bash
# Check all relays for channel
watch -n 5 "curl -s http://localhost:8000/api/relay/$CHANNEL_ID/broadcasts | jq '.data | .[] | {server_name, status, listeners}'"

# Check specific relay
watch -n 5 "curl -s http://localhost:8000/api/relay/$RELAY_1/status | jq '.data | {status, listeners, bitrate_kbps}'"
```

### 6. Stop Relays

```bash
# Stop relay 1
curl -X POST http://localhost:8000/api/relay/$RELAY_1/stop

# Stop relay 2
curl -X POST http://localhost:8000/api/relay/$RELAY_2/stop

# Or disable relay for entire channel
curl -X POST http://localhost:8000/api/relay/$CHANNEL_ID/disable
```

## Useful Queries

### Get all active relays

```bash
curl http://localhost:8000/api/relay/servers | jq '.data[] | select(.is_active == true)'
```

### Get relay with most listeners

```bash
curl 'http://localhost:8000/api/relay/1/broadcasts' | jq '.data | max_by(.listeners)'
```

### Get failed relays

```bash
curl 'http://localhost:8000/api/relay/5/logs' | jq '.data[] | select(.status == "error")'
```

### Calculate total relay listeners

```bash
curl 'http://localhost:8000/api/relay/1/broadcasts' | jq '[.data[].listeners] | add'
```

## Database Queries

### See all relay servers

```bash
sqlite> SELECT * FROM relay_servers;
```

### See active relays

```bash
sqlite> SELECT id, channel_id, relay_server_id, status, listeners FROM relay_broadcasts WHERE is_active = 1;
```

### See relay events

```bash
sqlite> SELECT event_type, COUNT(*) FROM relay_broadcast_logs GROUP BY event_type;
```

### See failed relays in last hour

```bash
sqlite> SELECT * FROM relay_broadcast_logs WHERE status = 'error' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);
```

## Streaming to Relay with FFmpeg

### Stream to Icecast relay

```bash
ffmpeg -i input.mp3 \
  -c:a libmp3lame -b:a 192k \
  -f mp3 "icecast://source:password@relay-server:8000/mount-point"
```

### Stream to RTMP relay

```bash
ffmpeg -i input.mp4 \
  -c:v libx264 -b:v 5000k \
  -c:a aac -b:a 192k \
  -f flv "rtmp://relay-server:1935/live/channel"
```

## Troubleshooting Commands

### Check Icecast is running

```bash
sudo systemctl status icecast2
curl -I http://localhost:8000/
```

### Check relay process

```bash
ps aux | grep ffmpeg | grep relay
```

### Test relay server connectivity

```bash
telnet relay-server.com 8000
nc -zv relay-server.com 8000
```

### Check relay logs

```bash
tail -f /var/log/supervisor/media-server-relay-monitor.log
```

### Monitor system resources

```bash
watch -n 1 'ps aux | grep -E "icecast|ffmpeg|relay"'
free -h
df -h
```

## Common Issues & Solutions

### Relay not starting
```bash
# Check error logs
curl 'http://localhost:8000/api/relay/5/logs?limit=5' | jq '.data'

# Check relay server is online
telnet relay-server.com 8000

# Check FFmpeg installed
ffmpeg -version
```

### High memory usage
```bash
# Monitor FFmpeg processes
ps aux | grep ffmpeg

# Check uptime
curl 'http://localhost:8000/api/relay/5/status' | jq '.data.uptime_seconds'

# Restart relay if needed
curl -X POST http://localhost:8000/api/relay/5/stop
sleep 2
curl -X POST http://localhost:8000/api/relay/1/start -H "Content-Type: application/json" -d '{"relay_server_id": 1}'
```

### Listeners not showing
```bash
# Check relay status first
curl 'http://localhost:8000/api/relay/5/status' | jq '.data | {status, listeners}'

# Wait for listeners to connect
sleep 10
curl 'http://localhost:8000/api/relay/5/status' | jq '.data.listeners'

# Check relay logs
curl 'http://localhost:8000/api/relay/5/logs?limit=10' | jq '.data'
```

## Performance Monitoring

```bash
# Get relay statistics
RELAY_ID=5
curl "http://localhost:8000/api/relay/$RELAY_ID/status" | jq '.data | {
  status,
  listeners,
  bitrate_kbps,
  uptime_seconds: (.uptime_seconds / 3600 | "\(.)) hours"
}'

# Monitor in real-time
watch -n 5 "curl -s http://localhost:8000/api/relay/$RELAY_ID/status | jq '.data | {status, listeners, bitrate_kbps}'"
```

## Documentation

- **Full Icecast Guide**: `ICECAST_GUIDE.md`
- **Full Relay Guide**: `RELAY_GUIDE.md`
- **Implementation Details**: `ICECAST_RELAY_IMPLEMENTATION.md`
- **Installation**: `INSTALLATION.md`
- **Deployment**: `DEPLOYMENT_GUIDE.md`
