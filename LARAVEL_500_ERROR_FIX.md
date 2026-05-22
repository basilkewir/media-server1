# Laravel Admin Panel - 500 Error Fix

## Problem

When accessing `http://5.180.182.232:8080/login`, you're getting a **500 Internal Server Error**.

## Root Cause

**"Too many open files"** error from the VOD Fallback Stream Monitor background process.

The Laravel application spawns FFmpeg processes to continuously monitor streams and enable VOD fallback. On a fresh system with default file descriptor limits (1024), this quickly exhausts available file handles, causing PHP-FPM to crash.

**Errors in logs:**
```
Error: proc_open(): Unable to create pipe Too many open files
```

## Solution

### Option 1: Quick Fix (Disable Stream Monitor)

Run this script to disable the problematic background monitor:

```bash
ssh root@5.180.182.232
cd /var/www/mediaserver
bash disable-monitor.sh
```

This will:
- Disable VOD fallback stream monitoring
- Stop FFmpeg processes
- Clear application caches
- Restart services

Then test:
```bash
curl http://localhost:8080/login
```

### Option 2: System Fix (Permanent)

Increase the system file descriptor limits:

```bash
# Add to /etc/security/limits.conf
sudo tee -a /etc/security/limits.conf << 'EOF'
* soft nofile 65536
* hard nofile 65536
* soft nproc 32768
* hard nproc 32768
www-data soft nofile 65536
www-data hard nofile 65536
php-fpm soft nofile 65536
php-fpm hard nofile 65536
EOF
```

Then reboot for changes to take effect:
```bash
sudo reboot
```

Or reload with a new login shell:
```bash
su - www-data
ulimit -n  # Should now show 65536
```

## Verify Fix

### Test 1: API Health Check

```bash
curl http://5.180.182.232:8080/api/health
```

Expected response:
```json
{"status":"ok","service":"MediaServer",...}
```

### Test 2: Login Page

```bash
curl -I http://5.180.182.232:8080/login
```

Expected: `HTTP/1.1 200 OK` (or 302 if already logged in)

### Test 3: Admin Panel

Open in browser:
```
http://5.180.182.232:8080/
```

Should see login form (not 500 error).

## Access Admin Panel

Once fixed, access at:
- **URL**: `http://5.180.182.232:8080/`
- **Default User**: admin
- **Default Password**: password

Or create a new user:
```bash
ssh root@5.180.182.232
cd /var/www/mediaserver
php artisan tinker

User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('your-password'),
]);
```

## Re-enable Stream Monitor (Later)

If you disabled the monitor and want to re-enable it later:

```bash
# Edit .env
sed -i 's/STREAM_VOD_FALLBACK_ENABLED=false/STREAM_VOD_FALLBACK_ENABLED=true/' .env

# Restart services
sudo systemctl restart supervisor php-fpm nginx
```

## Performance Tuning

If you want to keep the stream monitor but reduce resource usage:

```bash
# Edit .env
STREAM_VOD_FALLBACK_ENABLED=true
STREAM_HEALTH_CHECK_INTERVAL=30    # Check every 30 seconds instead of 5
STREAM_PIPE_ENABLED=false          # Disable FFmpeg piping if not needed
```

## Architecture Context

The VOD Fallback feature:
1. Monitors active streams every 5 seconds
2. If RTMP push is disconnected, automatically switches to VOD playlist
3. Requires spawning FFmpeg processes frequently
4. Works best with higher file descriptor limits

This is a **deliberate trade-off**:
- **With Monitor**: More resource usage but automatic VOD fallback
- **Without Monitor**: Less resource usage but no automatic fallback

## Status After Fix

| Component | Status | Notes |
|-----------|--------|-------|
| Laravel API | ✅ Working | Responds on port 8080 |
| Login Page | ✅ Working | No more 500 errors |
| Admin Panel | ✅ Working | Full functionality |
| Flussonic | ✅ Working | Streams on port 80 |
| VOD Fallback | ⏸️ Disabled | Can be re-enabled later |

## Next Steps

1. ✅ Access admin panel at `http://5.180.182.232:8080/`
2. ✅ Create your first stream
3. ✅ Configure Flussonic streams
4. ✅ Setup relay servers if needed
5. ⏸️ Consider re-enabling stream monitor after increasing system limits

## Troubleshooting

### Still Getting 500 Errors?

Check logs:
```bash
# Laravel logs
tail -f /var/www/mediaserver/storage/logs/laravel-2026-05-22.log

# Nginx logs
tail -f /var/log/nginx/mediaserver_error.log

# PHP-FPM logs
journalctl -u php-fpm.service -f
```

### Port 8080 Not Responding?

```bash
# Check Nginx
sudo systemctl status nginx

# Check PHP-FPM
sudo systemctl status php8.3-fpm.service

# Verify port listening
sudo ss -tlnp | grep 8080
```

### Still Getting "Too Many Open Files"?

```bash
# Check current limits
ulimit -n

# If still 1024, apply system limits manually:
ulimit -n 65536

# Or restart services after limits.conf changes
sudo systemctl restart php-fpm.service nginx
```

---

**Status:** ✅ **RESOLVED**  
**Last Updated:** May 22, 2026  
**Issue:** Too many open files (VOD monitor)  
**Solution:** Disable monitor / Increase system limits  
