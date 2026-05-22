# Quick Integration Setup (Flussonic Already Installed)

## Overview

This guide is for systems where **Flussonic is already installed**. We'll integrate the Laravel media server without reinstalling Flussonic.

## Prerequisites

✓ Flussonic already installed and running
✓ Ubuntu 20.04 LTS or 22.04 LTS
✓ Root or sudo access
✓ Git installed

## One-Command Setup

```bash
ssh root@your-server-ip
cd /var/www/mediaserver
sudo bash integration-only-setup.sh
```

This script will:

1. ✅ Verify Flussonic is installed and running
2. ✅ Update Laravel configuration for port 8080
3. ✅ Run database migrations
4. ✅ Configure Nginx for port 8080
5. ✅ Verify Flussonic is on port 80
6. ✅ Restart all services
7. ✅ Clear caches
8. ✅ Test connectivity
9. ✅ Display access points

## What Gets Configured

### Port Allocation

| Service | Port | Purpose |
|---------|------|---------|
| **Flussonic** | 80 | HTTP streaming (HLS/DASH) |
| **RTMP Ingest** | 1935 | Stream pushing |
| **Laravel API** | 8080 | Admin & API |
| **Flussonic Admin** | 8935 | Flussonic web panel |

### Files Modified

1. `.env` - Updated to port 8080
2. `/etc/nginx/nginx.conf` - Configured for Laravel on 8080
3. Database migrations - Applied to MySQL
4. Caches - Cleared

### Services Restarted

- PHP-FPM
- Nginx
- Flussonic

## Step-by-Step Manual Setup (If Needed)

If you prefer to do it manually:

### 1. Pull Latest Code

```bash
cd /var/www/mediaserver
git stash
git pull
```

### 2. Update Configuration

```bash
# Copy and edit .env
cp .env.example .env
nano .env

# Update:
APP_URL=http://localhost:8080
APP_PORT=8080
```

### 3. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 4. Run Migrations

```bash
php artisan migrate --force
```

### 5. Update Nginx

```bash
# Backup current config
cp /etc/nginx/nginx.conf /etc/nginx/nginx.conf.backup

# Copy new config with port 8080
cp nginx.conf.example /etc/nginx/nginx.conf

# Test and reload
nginx -t
systemctl reload nginx
```

### 6. Verify Services

```bash
systemctl status php-fpm.service
systemctl status nginx
systemctl status flussonic

# Check ports
netstat -tlnp | grep -E ':(80|1935|8080|8935)'
```

### 7. Test APIs

```bash
# Laravel API
curl http://localhost:8080/api/health

# Flussonic API
curl http://localhost:8935/streamer/api/v3/server
```

## Verify Everything Works

### Check Services Status

```bash
sudo systemctl status php-fpm.service
sudo systemctl status nginx
sudo systemctl status flussonic
```

### Check Listening Ports

```bash
sudo netstat -tlnp | grep -E ':(80|1935|8080|8935)'
```

Or with `ss`:

```bash
sudo ss -tlnp | grep -E ':(80|1935|8080|8935)'
```

### Test Laravel API

```bash
curl -v http://your-server:8080/api/health
```

Expected response:
```json
{
  "status": "ok",
  "timestamp": "2026-05-22T12:00:00Z"
}
```

### Test Flussonic API

```bash
curl -v http://your-server:8935/streamer/api/v3/server
```

### Test HLS Stream

```bash
# List available streams
curl http://your-server/your-stream-name/index.m3u8

# Play with ffplay
ffplay http://your-server/your-stream-name/index.m3u8
```

### Test RTMP Push

```bash
# Push a test stream
ffmpeg -f lavfi -i testsrc=size=1280x720:duration=10 \
  -f lavfi -i sine=frequency=440:duration=10 \
  -c:v libx264 -b:v 1500k \
  -c:a aac -b:a 128k \
  -f flv rtmp://your-server:1935/live/test_stream
```

## Troubleshooting

### Port 80 Already in Use

```bash
# Find what's using port 80
sudo lsof -i :80

# If it's not Flussonic, stop it
sudo systemctl stop <service-name>
```

### Port 8080 Already in Use

```bash
# Find what's using port 8080
sudo lsof -i :8080

# Kill the process if it's not needed
sudo kill -9 <PID>
```

### Laravel API Not Responding

```bash
# Check PHP-FPM status
sudo systemctl status php-fpm.service

# Check Nginx error logs
sudo tail -f /var/log/nginx/error.log

# Check Laravel logs
tail -f /var/www/mediaserver/storage/logs/laravel.log
```

### Flussonic Not Responding

```bash
# Check Flussonic status
sudo systemctl status flussonic

# Check Flussonic logs
sudo tail -f /var/log/flussonic/flussonic.log

# Check if ports are listening
sudo netstat -tlnp | grep flussonic
```

### Nginx Won't Start

```bash
# Test configuration
sudo nginx -t

# If there are errors, check the config file
sudo cat /etc/nginx/nginx.conf

# View detailed error
sudo journalctl -u nginx -n 50
```

## Access Your Services

### Admin Dashboard

- **URL**: `http://your-server:8080`
- **Default credentials**: (Set in Laravel admin setup)

### REST API

- **Base URL**: `http://your-server:8080/api`
- **Health Check**: `curl http://your-server:8080/api/health`

### Flussonic Admin

- **URL**: `http://your-server:8935`
- **Default credentials**: Check Flussonic installation

### Stream Playback

**HLS**:
```
http://your-server/channel_name/index.m3u8
```

**DASH**:
```
http://your-server/channel_name/manifest.mpd
```

**RTMP**:
```
rtmp://your-server:1935/live/channel_name
```

## What's Next?

1. **Configure Channels**: Create channels in the Laravel admin panel
2. **Setup Streams**: Add stream sources in Flussonic
3. **Enable Relays**: Configure multi-server relay broadcasting
4. **Setup Recording**: Enable DVR/recording in Flussonic
5. **Configure SSL**: Add HTTPS certificates
6. **Monitor**: Set up logging and monitoring

## Documentation

- `PORT_CONFIGURATION.md` - Detailed port configuration
- `FLUSSONIC_QUICK_REFERENCE.md` - Flussonic commands
- `DEPLOYMENT_GUIDE.md` - Full deployment guide
- `API.md` - REST API documentation

## Support

If you encounter issues:

1. Check the troubleshooting section above
2. Review log files (see paths above)
3. Verify port configuration with `netstat` or `ss`
4. Test APIs with `curl`
5. Check service status with `systemctl`

---

**Last Updated:** May 2026
**Status:** ✅ Production Ready
