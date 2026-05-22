# Flussonic Installation Guide for Ubuntu

This guide covers installing and configuring Flussonic 24.02 on your Ubuntu server, alongside the Media Server application.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Installation Steps](#installation-steps)
3. [Configuration](#configuration)
4. [Integration with Media Server](#integration-with-media-server)
5. [Verification](#verification)
6. [Troubleshooting](#troubleshooting)

## Prerequisites

### System Requirements

- **OS:** Ubuntu 20.04 LTS or higher (22.04 LTS recommended)
- **CPU:** 2+ cores recommended
- **RAM:** 4GB minimum, 8GB+ recommended
- **Storage:** 50GB+ for streaming cache and VOD
- **Network:** 100 Mbps+ internet connection

### Required Software

```bash
# Update system
sudo apt-get update && sudo apt-get upgrade -y

# Install dependencies
sudo apt-get install -y \
    curl \
    wget \
    unzip \
    nano \
    htop \
    net-tools \
    ffmpeg \
    supervisor \
    nginx \
    mysql-server \
    redis-server
```

### Network Requirements

- **Port 80/443:** Web interface and HLS streaming
- **Port 1935:** RTMP streaming protocol
- **Port 8080:** Flussonic API and admin panel
- **Ports 8000+:** Additional streaming ports

Firewall rules:
```bash
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw allow 1935/tcp  # RTMP
sudo ufw allow 8080/tcp  # Flussonic
sudo ufw enable
```

## Installation Steps

### Step 1: Download Flussonic

First, ensure you have the `flussonic_24.02_unlimited.zip` file:

```bash
# Create directory for Flussonic
sudo mkdir -p /opt/flussonic
cd /opt/flussonic

# If you have the ZIP file locally, transfer it
# Using scp from your local machine:
# scp flussonic_24.02_unlimited.zip user@your-server:/opt/flussonic/

# Or download if you have a download URL
# wget https://your-repo.com/flussonic_24.02_unlimited.zip
```

### Step 2: Extract Flussonic

```bash
# Extract the ZIP file
cd /opt/flussonic
sudo unzip flussonic_24.02_unlimited.zip

# List contents to understand structure
ls -la

# Expected output should show:
# - flussonic (binary)
# - flussonic.conf (default config)
# - start_flussonic.sh (startup script)
# - README or INSTALL files
```

### Step 3: Verify Extraction

```bash
# Check Flussonic binary
file /opt/flussonic/flussonic

# Make binary executable
sudo chmod +x /opt/flussonic/flussonic

# Test Flussonic version
/opt/flussonic/flussonic --version
```

### Step 4: Create System User

```bash
# Create dedicated user for Flussonic
sudo useradd -r -m -s /usr/sbin/nologin flussonic

# Set ownership
sudo chown -R flussonic:flussonic /opt/flussonic

# Create necessary directories
sudo mkdir -p /var/log/flussonic
sudo mkdir -p /var/cache/flussonic
sudo mkdir -p /etc/flussonic
sudo mkdir -p /var/run/flussonic

# Set permissions
sudo chown -R flussonic:flussonic /var/log/flussonic
sudo chown -R flussonic:flussonic /var/cache/flussonic
sudo chown -R flussonic:flussonic /var/run/flussonic
```

### Step 5: Configuration

Create initial Flussonic configuration:

```bash
sudo nano /etc/flussonic/flussonic.conf
```

Add the following configuration:

```ini
# Flussonic Basic Configuration

# Server settings
port 8080
http_port 80
rtmp_port 1935

# Administrator credentials
admin admin
admin_password your_secure_password

# Logging
log_level info
logfile /var/log/flussonic/flussonic.log

# Cache directory (for VOD)
vod_dir /var/cache/flussonic/vod

# Storage for recordings
rec_dir /var/cache/flussonic/recordings

# Maximum bandwidth (in Mbps, 0 = unlimited)
bandwidth_limit 0

# Performance tuning
max_connections 5000
buffer_size 1024

# Enable DVR
dvr enabled

# Enable HTTP streaming
http enabled

# CORS settings
cors enabled

# API settings
api_port 8080
api_enabled yes
api_password your_api_password
```

### Step 6: Create Systemd Service

```bash
sudo nano /etc/systemd/system/flussonic.service
```

Add the following:

```ini
[Unit]
Description=Flussonic Media Server
After=network.target

[Service]
Type=simple
User=flussonic
Group=flussonic
WorkingDirectory=/opt/flussonic
ExecStart=/opt/flussonic/flussonic -c /etc/flussonic/flussonic.conf
ExecReload=/bin/kill -HUP $MAINPID
KillMode=process
Restart=always
RestartSec=10

# Resource limits
LimitNOFILE=65536
LimitNPROC=65536

# Security
PrivateTmp=yes
NoNewPrivileges=yes

[Install]
WantedBy=multi-user.target
```

Enable and start the service:

```bash
sudo systemctl daemon-reload
sudo systemctl enable flussonic
sudo systemctl start flussonic

# Verify it's running
sudo systemctl status flussonic
```

### Step 7: Verify Installation

```bash
# Check if Flussonic is listening
sudo netstat -tlnp | grep flussonic

# Expected output:
# tcp  0  0 0.0.0.0:8080  0.0.0.0:*  LISTEN  1234/flussonic
# tcp  0  0 0.0.0.0:1935  0.0.0.0:*  LISTEN  1234/flussonic
# tcp  0  0 0.0.0.0:80    0.0.0.0:*  LISTEN  1234/flussonic

# Check logs
sudo tail -f /var/log/flussonic/flussonic.log

# Test web interface
curl -u admin:your_secure_password http://localhost:8080/
```

## Configuration

### Basic Settings

Edit configuration file:

```bash
sudo nano /etc/flussonic/flussonic.conf
```

### Configure Live Streaming Sources

```ini
# RTMP input from encoder
stream my_channel {
  input rtmp://localhost/live/my_stream;
  output hls://localhost/my_channel.m3u8;
  output dash://localhost/my_channel.mpd;
  dvr on;
}

# VOD playlist
stream vod_channel {
  input http://localhost:8000/vod_fallback.m3u8;
  output hls://localhost/vod_channel.m3u8;
}

# Relay from upstream
stream relay_channel {
  input rtmp://upstream.example.com/live/channel;
  output hls://localhost/relay_channel.m3u8;
  output rtmp://localhost/live/relay_channel;
}
```

### Enable Authentication

```ini
# Basic authentication for streams
auth_type basic
auth_user broadcaster
auth_password secret123

# Token-based authentication for viewers
auth_type token
token_lifetime 3600
```

### Configure DVR (Recording)

```ini
# Enable DVR for all streams
dvr_default on
dvr_segment_duration 10
dvr_cleanup_interval 86400
dvr_max_size 1TB

# Per-stream DVR settings
stream live_channel {
  input rtmp://localhost/live;
  dvr on;
  dvr_duration 24h;
}
```

### Configure HLS/DASH Output

```ini
# HLS settings
hls_segment_duration 4
hls_segments_in_playlist 12
hls_version 3

# DASH settings
dash_segment_duration 4
dash_segments_in_playlist 10
```

## Integration with Media Server

### Update Media Server Configuration

Update your Media Server `.env` file to include Flussonic settings:

```bash
# Add to .env
FLUSSONIC_ENABLED=true
FLUSSONIC_HOST=localhost
FLUSSONIC_PORT=8080
FLUSSONIC_RTMP_PORT=1935
FLUSSONIC_HTTP_PORT=80
FLUSSONIC_ADMIN_USER=admin
FLUSSONIC_ADMIN_PASSWORD=your_secure_password
FLUSSONIC_API_URL=http://localhost:8080/api
FLUSSONIC_API_TOKEN=your_api_password
FLUSSONIC_STORAGE_PATH=/var/cache/flussonic
```

### Create Flussonic Service Class

Create `app/Services/FlussonicService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class FlussonicService
{
    protected string $apiUrl;
    protected string $apiToken;

    public function __construct()
    {
        $this->apiUrl = config('services.flussonic.api_url');
        $this->apiToken = config('services.flussonic.api_token');
    }

    /**
     * Get list of streams
     */
    public function getStreams(): array
    {
        $response = Http::get("{$this->apiUrl}/v1/streams");
        return $response->json()['streams'] ?? [];
    }

    /**
     * Create new stream
     */
    public function createStream(string $name, string $input): array
    {
        $response = Http::post("{$this->apiUrl}/v1/streams", [
            'name' => $name,
            'input' => $input,
        ]);
        return $response->json();
    }

    /**
     * Get stream stats
     */
    public function getStreamStats(string $streamName): array
    {
        $response = Http::get("{$this->apiUrl}/v1/streams/{$streamName}/stats");
        return $response->json();
    }

    /**
     * Start recording
     */
    public function startRecording(string $streamName): array
    {
        $response = Http::post("{$this->apiUrl}/v1/streams/{$streamName}/dvr/start");
        return $response->json();
    }

    /**
     * Stop recording
     */
    public function stopRecording(string $streamName): array
    {
        $response = Http::post("{$this->apiUrl}/v1/streams/{$streamName}/dvr/stop");
        return $response->json();
    }

    /**
     * Get DVR recordings
     */
    public function getRecordings(string $streamName): array
    {
        $response = Http::get("{$this->apiUrl}/v1/streams/{$streamName}/dvr");
        return $response->json();
    }
}
```

### Create Flussonic Controller

Create `app/Http/Controllers/API/FlussonicController.php`:

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\FlussonicService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FlussonicController extends Controller
{
    protected FlussonicService $flussonicService;

    public function __construct(FlussonicService $flussonicService)
    {
        $this->flussonicService = $flussonicService;
    }

    /**
     * Get all streams
     */
    public function getStreams(): JsonResponse
    {
        try {
            $streams = $this->flussonicService->getStreams();
            return response()->json([
                'success' => true,
                'data' => $streams,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get stream statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        $streamName = $request->route('stream');

        try {
            $stats = $this->flussonicService->getStreamStats($streamName);
            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recordings
     */
    public function getRecordings(Request $request): JsonResponse
    {
        $streamName = $request->route('stream');

        try {
            $recordings = $this->flussonicService->getRecordings($streamName);
            return response()->json([
                'success' => true,
                'data' => $recordings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
```

## Verification

### Test Installation

```bash
# 1. Check service status
sudo systemctl status flussonic

# 2. Check listening ports
sudo netstat -tlnp | grep flussonic

# 3. Check logs
sudo tail -20 /var/log/flussonic/flussonic.log

# 4. Test admin panel (via browser or curl)
curl -u admin:your_secure_password http://your-server-ip:8080/

# 5. Test API
curl -H "X-Auth-Token: your_api_password" \
  http://your-server-ip:8080/api/v1/streams

# 6. Test RTMP push
ffmpeg -re -i input.mp4 \
  -c:v libx264 -c:a aac \
  -f flv rtmp://your-server-ip:1935/live/test_stream
```

### Performance Monitoring

```bash
# Monitor Flussonic process
watch -n 2 'ps aux | grep flussonic'

# Check CPU and memory usage
top -p $(pgrep flussonic)

# Check network I/O
iftop

# Check disk usage
du -sh /var/cache/flussonic/*
```

## Troubleshooting

### Service Won't Start

```bash
# Check if port is already in use
sudo lsof -i :8080
sudo lsof -i :1935

# Kill conflicting process if needed
sudo kill -9 PID

# Check logs for errors
sudo journalctl -u flussonic -n 50

# Try starting manually for debugging
sudo -u flussonic /opt/flussonic/flussonic -c /etc/flussonic/flussonic.conf -d
```

### RTMP Connection Issues

```bash
# Verify RTMP port is listening
sudo netstat -tlnp | grep 1935

# Test connectivity
telnet localhost 1935

# Check firewall
sudo iptables -L | grep 1935

# Restart service
sudo systemctl restart flussonic
```

### High CPU Usage

```bash
# Check for stuck processes
ps aux | grep flussonic

# Check streaming connections
curl -u admin:password http://localhost:8080/api/v1/streams

# Reduce number of concurrent streams
# Edit /etc/flussonic/flussonic.conf and reduce max_connections

# Restart
sudo systemctl restart flussonic
```

### Storage Issues

```bash
# Check disk space
df -h /var/cache/flussonic

# Check DVR size
du -sh /var/cache/flussonic/dvr/

# Clean old DVR files
find /var/cache/flussonic/dvr -mtime +7 -delete

# Increase cleanup frequency in config
dvr_cleanup_interval 3600  # Clean every hour
```

### API Connection Failed

```bash
# Verify API is enabled in config
grep -i "api" /etc/flussonic/flussonic.conf

# Test API directly
curl http://localhost:8080/api/v1/version

# Check authentication
curl -H "X-Auth-Token: your_api_password" \
  http://localhost:8080/api/v1/streams
```

## Backup and Recovery

### Backup Configuration

```bash
# Backup Flussonic config
sudo tar -czf flussonic_backup_$(date +%Y%m%d).tar.gz \
  /etc/flussonic \
  /opt/flussonic/flussonic.conf

# Backup DVR/VOD storage
sudo tar -czf flussonic_storage_$(date +%Y%m%d).tar.gz \
  /var/cache/flussonic
```

### Restore Configuration

```bash
# Restore config
sudo tar -xzf flussonic_backup_20240515.tar.gz -C /

# Restore DVR storage
sudo tar -xzf flussonic_storage_20240515.tar.gz -C /

# Fix permissions
sudo chown -R flussonic:flussonic /etc/flussonic
sudo chown -R flussonic:flussonic /var/cache/flussonic

# Restart service
sudo systemctl restart flussonic
```

## Advanced Configuration

### Load Balancing

Multiple Flussonic instances for high availability:

```ini
# Master Flussonic (primary)
listen 0.0.0.0:8080

# Slave Flussonic (backup)
# Configure as relay from master

stream relay_master {
  input rtmp://master-flussonic:1935/live;
  output hls://localhost/live.m3u8;
}
```

### CDN Integration

```ini
# Configure origin server
origin_mode true
origin_url http://primary-flussonic.example.com:8080

# CDN will pull from origin
stream cdn_stream {
  input rtmp://origin/live/stream;
  output hls://localhost/stream.m3u8;
}
```

### Statistics and Monitoring

```bash
# Create Grafana dashboard for metrics
# Add Prometheus exporter
# Monitor via Zabbix or Nagios

# Log all streams to Elasticsearch
# Enable detailed logging in config
log_format json
logfile_format elasticsearch
```

## Security Hardening

```bash
# 1. Change default credentials
# Edit /etc/flussonic/flussonic.conf
admin_user secure_username
admin_password $(openssl rand -base64 32)

# 2. Enable HTTPS
ssl_cert /etc/ssl/certs/flussonic.crt
ssl_key /etc/ssl/private/flussonic.key

# 3. Restrict API access
api_ip_whitelist 192.168.1.0/24

# 4. Enable authentication for streams
auth_type token

# 5. Set resource limits
max_connections 5000
bandwidth_limit 100  # Mbps per client
```

## Next Steps

1. Access Flussonic admin panel: `http://your-server-ip:8080`
2. Configure your streams
3. Enable DVR and recording
4. Set up monitoring and alerts
5. Configure relay streams as needed
6. Integrate with Media Server API

## Support

- Flussonic Documentation: https://flussonic.com/doc/
- Check logs: `sudo tail -f /var/log/flussonic/flussonic.log`
- API Reference: `http://your-server-ip:8080/api/v1/docs`

---

**Installation Status:** ✅ Ready for Configuration
**Last Updated:** May 2026
