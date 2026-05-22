# Port Configuration Guide

## Port Allocation

The media server system now uses the following port allocation to avoid conflicts:

### Media Server (Laravel Application)
- **Port 8080**: Laravel admin panel, API endpoints, web interface
- **URL**: `http://your-server:8080`
- **Services**: 
  - Admin dashboard
  - REST API (`/api/*`)
  - Web interface
  - User management
  - Channel management

### Flussonic Streaming Server
- **Port 80**: HTTP streaming (HLS/DASH/HTTP-Progressive)
- **Port 1935**: RTMP push/pull (broadcasting)
- **Port 8935**: Flussonic admin panel (optional, on remote server)
- **URLs**:
  - HLS: `http://your-server/channel_name/index.m3u8`
  - DASH: `http://your-server/channel_name/manifest.mpd`
  - RTMP: `rtmp://your-server:1935/live/channel_name`

### Other Services
- **Port 6379**: Redis (internal, localhost only)
- **Port 3306**: MySQL (internal, localhost only)
- **Port 8000**: Icecast2 (audio streaming, if enabled)
- **Port 9000**: PHP-FPM (internal, unix socket preferred)

## Why This Configuration?

1. **Port 80 for Flussonic**: Industry standard for streaming. Clients expect streams on port 80
2. **Port 8080 for Laravel API**: Common alternative port for admin/API services
3. **Separation of Concerns**: 
   - Streaming: Flussonic (optimized C code)
   - Management: Laravel API (flexible, feature-rich)

## Configuration Files

### 1. Laravel Environment (`.env`)

```bash
APP_URL=http://your-server:8080
APP_PORT=8080
```

### 2. Nginx Configuration

**File**: `/etc/nginx/nginx.conf`

```nginx
# Laravel API server on port 8080
server {
    listen 8080 default_server;
    server_name _;
    root /var/www/mediaserver/public;
    index index.php;
    
    # Laravel routing...
}

# Flussonic reverse proxy on port 80 (optional, if behind another reverse proxy)
server {
    listen 80;
    server_name _;
    
    # Forward to Flussonic backend
    location / {
        proxy_pass http://localhost:8935;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### 3. Start Laravel on Port 8080

```bash
# Using built-in PHP server (development)
php artisan serve --host=0.0.0.0 --port=8080

# Or with Nginx (production) - already configured in nginx.conf.example
sudo systemctl restart nginx
```

## Updating Your System

### Step 1: Update Configuration Files

```bash
cd /var/www/mediaserver

# Copy example configs
cp .env.example .env
cp nginx.conf.example /etc/nginx/nginx.conf

# Edit .env if needed
nano .env
```

### Step 2: Update Nginx

```bash
# Test Nginx config
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

### Step 3: Restart Services

```bash
# Restart Laravel/PHP-FPM
sudo systemctl restart php-fpm

# Restart Nginx
sudo systemctl restart nginx

# Verify Flussonic is still running
sudo systemctl status flussonic
```

### Step 4: Verify Ports

```bash
# Check listening ports
sudo netstat -tlnp

# Or using ss command
sudo ss -tlnp

# Check specific ports:
sudo lsof -i :80      # Flussonic streaming
sudo lsof -i :1935    # RTMP push
sudo lsof -i :8080    # Laravel API
sudo lsof -i :8000    # Icecast (if enabled)
```

## Testing Connectivity

### Test Laravel API on Port 8080

```bash
# Health check
curl http://your-server:8080/api/health

# List channels
curl http://your-server:8080/api/channels

# API documentation
curl http://your-server:8080/api/docs
```

### Test Flussonic on Port 80

```bash
# Test stream playback
ffplay http://your-server/channel_name/index.m3u8

# Check Flussonic status
curl http://your-server:8935/streamer/api/v3/server
```

### Test RTMP on Port 1935

```bash
# Push test stream
ffmpeg -f lavfi -i testsrc=size=1280x720:duration=10 \
  -f lavfi -i sine=frequency=440:duration=10 \
  -c:v libx264 -b:v 1500k \
  -c:a aac -b:a 128k \
  -f flv rtmp://your-server:1935/live/test
```

## Firewall Configuration

### UFW (Uncomplicated Firewall)

```bash
# Allow HTTP streaming (Port 80)
sudo ufw allow 80/tcp

# Allow RTMP push (Port 1935)
sudo ufw allow 1935/tcp

# Allow Laravel API (Port 8080)
sudo ufw allow 8080/tcp

# Allow SSH (if not already allowed)
sudo ufw allow 22/tcp

# Enable firewall
sudo ufw enable
```

### iptables

```bash
# Allow port 80
sudo iptables -A INPUT -p tcp --dport 80 -j ACCEPT

# Allow port 1935
sudo iptables -A INPUT -p tcp --dport 1935 -j ACCEPT

# Allow port 8080
sudo iptables -A INPUT -p tcp --dport 8080 -j ACCEPT

# Save rules
sudo iptables-save | sudo tee /etc/iptables/rules.v4
```

## Reverse Proxy Setup (Optional)

If you want Flussonic streams on port 80 and Laravel API on the same port with path routing:

```nginx
server {
    listen 80;
    server_name your-server.com;

    # API routes go to Laravel on 8080
    location /api/ {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Admin routes go to Laravel on 8080
    location /admin/ {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Everything else (streams) goes to Flussonic on 8935
    location / {
        proxy_pass http://localhost:8935;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
```

## Troubleshooting

### Port Already in Use

```bash
# Find process using port
sudo lsof -i :80
sudo lsof -i :8080
sudo lsof -i :1935

# Kill process (if needed)
sudo kill -9 <PID>
```

### Nginx Not Starting

```bash
# Test config
sudo nginx -t

# Check error logs
sudo tail -f /var/log/nginx/error.log

# Restart
sudo systemctl restart nginx
```

### Can't Connect to API

```bash
# Verify Laravel is running
sudo systemctl status php-fpm

# Check logs
tail -f /var/log/php8.3-fpm.log
tail -f /var/log/nginx/error.log

# Test locally
curl -v http://localhost:8080/api/health
```

### Flussonic Not Accessible

```bash
# Verify Flussonic is running
sudo systemctl status flussonic

# Check Flussonic logs
sudo tail -f /var/log/flussonic/flussonic.log

# Test locally
curl -v http://localhost:8935/streamer/api/v3/server
```

## Production Recommendations

1. **Use Reverse Proxy**: Place Nginx/HAProxy in front for SSL/TLS
2. **SSL/TLS Certificates**: Install Let's Encrypt certificates for HTTPS
3. **DNS**: Point your domain to your server
4. **Firewall**: Only open necessary ports (80, 443, 1935)
5. **Monitoring**: Set up port monitoring and alerting
6. **Rate Limiting**: Configure rate limiting on API endpoints

## DNS Configuration

Point your domain to your server:

```
example.com         A    your-server-ip
www.example.com     A    your-server-ip
api.example.com     A    your-server-ip
streams.example.com A    your-server-ip
```

## Summary

- **Flussonic**: Port 80 (streaming)
- **Laravel API**: Port 8080 (management)
- **RTMP**: Port 1935 (ingest)
- **Icecast**: Port 8000 (audio, optional)

This configuration allows both systems to run independently without port conflicts.

---

**Last Updated:** May 2026
**Status:** ✅ Production Ready
