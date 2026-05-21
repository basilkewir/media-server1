# Quick Reference Guide

## 📋 Installation Commands

### Ubuntu Automated Installation
```bash
cd /var/www/media-server
sudo bash install.sh
```

### Manual Quick Setup
```bash
# 1. Copy and configure
cp .env.example .env
php artisan key:generate

# 2. Setup database
php artisan migrate

# 3. Start services
sudo systemctl start nginx php8.2-fpm redis-server supervisor
```

### Docker Setup
```bash
docker-compose up -d
docker-compose exec app php artisan migrate
```

## 🔌 API Quick Examples

### Create Channel
```bash
curl -X POST http://localhost/api/channels \
  -H "Content-Type: application/json" \
  -d '{
    "name": "News",
    "slug": "news",
    "vod_playlist_url": "https://example.com/news.m3u8"
  }'
```

### Start Stream
```bash
curl -X POST http://localhost/api/streams/start \
  -H "Content-Type: application/json" \
  -d '{
    "channel_id": 1,
    "push_url": "rtmp://source/live/news"
  }'
```

### Check Status
```bash
curl http://localhost/api/streams/1/status
```

### Trigger VOD Fallback
```bash
curl -X POST http://localhost/api/streams/fallback \
  -H "Content-Type: application/json" \
  -d '{"channel_id": 1}'
```

### Watch Stream
```
http://localhost/play/news
```

## 🛠️ Common Commands

### Service Management
```bash
# Check status
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status redis-server

# Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
sudo systemctl restart redis-server

# Monitor processes
sudo supervisorctl status
sudo supervisorctl restart media-server-monitor
```

### Database
```bash
# Migrate
php artisan migrate

# Rollback
php artisan migrate:rollback

# Fresh
php artisan migrate:fresh

# Seed
php artisan db:seed
```

### Cache
```bash
# Clear all
php artisan cache:clear

# Clear config
php artisan config:clear

# Clear routes
php artisan route:clear
```

### Streaming
```bash
# Start monitor
php artisan stream:monitor --interval=5

# Check FFmpeg
ps aux | grep ffmpeg

# Check stream files
ls -la storage/streams/
```

## 📊 Log Files

```bash
# Application
tail -f storage/logs/laravel.log

# Stream monitor
tail -f /var/log/supervisor/media-server-monitor.log

# Queue worker
tail -f /var/log/supervisor/media-server-queue.log

# Nginx error
tail -f /var/log/nginx/media-server-error.log

# Nginx access
tail -f /var/log/nginx/media-server-access.log
```

## 🔍 Debugging

### Check Service Status
```bash
sudo systemctl status nginx
sudo supervisorctl status media-server-monitor
redis-cli ping
mysql -u media_user -p media_server
```

### Test Connectivity
```bash
# Test RTMP port
nc -zv localhost 1935

# Test HTTP
curl -I http://localhost

# Test Redis
redis-cli
> PING
> QUIT
```

### View Processes
```bash
# FFmpeg processes
ps aux | grep ffmpeg

# PHP-FPM
ps aux | grep php-fpm

# Nginx
ps aux | grep nginx
```

## ⚙️ Configuration Files

```bash
# Application
/var/www/media-server/.env

# Nginx
/etc/nginx/sites-available/media-server

# PHP-FPM
/etc/php/8.2/fpm/pool.d/www.conf

# Supervisor
/etc/supervisor/conf.d/media-server.conf

# MySQL
/etc/mysql/mysql.conf.d/mysqld.cnf

# Redis
/etc/redis/redis.conf
```

## 📱 Useful URLs

- **Dashboard**: `http://your-domain`
- **API Docs**: `http://your-domain/api`
- **Stream Player**: `http://your-domain/play/{slug}`
- **HLS Manifest**: `http://your-domain/streams/{slug}/playlist.m3u8`
- **DASH Manifest**: `http://your-domain/streams/{slug}/manifest.mpd`

## 🚨 Troubleshooting Quick Fixes

### Stream not starting
```bash
# Check FFmpeg installed
which ffmpeg
ffmpeg -version

# Check port 1935 open
sudo ufw allow 1935

# Check RTMP source
curl -I rtmp://source/live/stream
```

### High CPU usage
```bash
# Check processes
top -p $(pidof php-fpm)
ps aux | grep ffmpeg | wc -l

# Reduce FFmpeg processes
# Edit .env: FFMPEG_TIMEOUT=300
```

### Database errors
```bash
# Check MySQL
sudo systemctl status mysql

# Test connection
mysql -u media_user -p media_server -e "SELECT 1;"
```

### Redis errors
```bash
# Check Redis
redis-cli ping

# Restart
sudo systemctl restart redis-server
```

## 📈 Performance Monitoring

```bash
# System resources
free -h
df -h
top

# FFmpeg status
ps aux | grep ffmpeg

# Stream count
ps aux | grep ffmpeg | wc -l

# Database connections
mysql -e "SHOW PROCESSLIST;"

# Redis info
redis-cli INFO stats
```

## 🔐 Security Checks

```bash
# Check firewall
sudo ufw status

# Check permissions
ls -la /var/www/media-server/storage

# Check SSL
sudo certbot certificates

# View security logs
tail -f /var/log/auth.log
```

## 📦 Backup & Restore

```bash
# Backup database
mysqldump -u media_user -p media_server > backup.sql

# Restore database
mysql -u media_user -p media_server < backup.sql

# Backup configuration
tar -czf config-backup.tar.gz /var/www/media-server/.env

# Backup streams
tar -czf streams-backup.tar.gz /var/www/media-server/storage/streams/
```

## 🎯 Performance Settings

### For < 100 viewers
```env
MAX_CONCURRENT_STREAMS=50
HLS_SEGMENT_DURATION=10
STREAM_HEALTH_CHECK_INTERVAL=10
```

### For 100-1000 viewers
```env
MAX_CONCURRENT_STREAMS=200
HLS_SEGMENT_DURATION=6
STREAM_HEALTH_CHECK_INTERVAL=5
```

### For > 1000 viewers
```env
MAX_CONCURRENT_STREAMS=500
HLS_SEGMENT_DURATION=4
STREAM_HEALTH_CHECK_INTERVAL=3
```

## 📞 Support Resources

- **Documentation**: `/var/www/media-server/README.md`
- **Installation**: `/var/www/media-server/INSTALLATION.md`
- **Development**: `/var/www/media-server/DEVELOPMENT.md`
- **Deployment**: `/var/www/media-server/DEPLOYMENT_GUIDE.md`

---

**Last Updated**: May 2026
**For detailed info**: See full documentation in project root
