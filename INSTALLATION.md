# MediaServer - Ubuntu Server Installation and Deployment Guide

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Quick Start](#quick-start)
3. [Manual Installation](#manual-installation)
4. [Docker Installation](#docker-installation)
5. [Configuration](#configuration)
6. [API Usage](#api-usage)
7. [Troubleshooting](#troubleshooting)
8. [Performance Tuning](#performance-tuning)

## Prerequisites

### System Requirements
- Ubuntu Server 22.04 LTS or later
- Minimum 2 CPU cores, 4GB RAM (recommended: 4+ cores, 8GB+ RAM)
- At least 50GB free disk space
- Internet connection for package downloads

### Required Ports
- 80 (HTTP)
- 443 (HTTPS)
- 1935 (RTMP)
- 6379 (Redis - internal only)
- 3306 (MySQL - internal only)

### Network Requirements
- Static IP address recommended
- DNS records configured (A record pointing to server)
- Firewall rules allowing ports 80, 443, and 1935

## Quick Start

### Option 1: Automated Installation (Recommended)

```bash
# Download the installation script
wget https://your-repo/media-server/install.sh
chmod +x install.sh

# Run with sudo
sudo bash install.sh

# After installation, configure your .env file
sudo nano /var/www/media-server/.env

# Start all services
sudo systemctl start nginx php8.2-fpm redis-server supervisor

# Check service status
sudo systemctl status nginx php8.2-fpm redis-server supervisor
```

### Option 2: Docker Installation

```bash
# Clone the repository
git clone https://github.com/your-org/media-server.git
cd media-server

# Create environment file
cp .env.example .env

# Build and start containers
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# Access at http://localhost
```

## Manual Installation

### Step 1: Update System

```bash
sudo apt-get update
sudo apt-get upgrade -y
sudo apt-get install -y curl wget git
```

### Step 2: Install PHP 8.2

```bash
sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get update

sudo apt-get install -y \
    php8.2-fpm \
    php8.2-cli \
    php8.2-common \
    php8.2-curl \
    php8.2-gd \
    php8.2-mbstring \
    php8.2-mysql \
    php8.2-redis \
    php8.2-xml \
    php8.2-zip \
    php8.2-bcmath \
    php8.2-intl \
    php8.2-iconv
```

### Step 3: Install Required Services

```bash
# Nginx
sudo apt-get install -y nginx

# Redis
sudo apt-get install -y redis-server

# MySQL
sudo apt-get install -y mysql-server

# FFmpeg and streaming tools
sudo apt-get install -y \
    ffmpeg \
    vlc \
    libavformat-dev \
    libavcodec-dev \
    libavdevice-dev \
    libswscale-dev \
    pkg-config

# Supervisor for process management
sudo apt-get install -y supervisor
```

### Step 4: Install Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Verify installation
composer --version
```

### Step 5: Setup Application

```bash
# Create application directory
sudo mkdir -p /var/www/media-server
cd /var/www/media-server

# Clone or copy your application
# git clone <your-repo> .

# Set proper permissions
sudo chown -R www-data:www-data /var/www/media-server
sudo chmod -R 775 /var/www/media-server/storage
sudo chmod -R 775 /var/www/media-server/bootstrap/cache

# Install dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# Generate application key
sudo -u www-data php artisan key:generate

# Create storage link
sudo -u www-data php artisan storage:link
```

### Step 6: Configure MySQL Database

```bash
# Secure MySQL installation (optional but recommended)
sudo mysql_secure_installation

# Login to MySQL
sudo mysql -u root -p

# Create database and user
CREATE DATABASE media_server;
CREATE USER 'media_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON media_server.* TO 'media_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Update .env with database credentials
sudo nano /var/www/media-server/.env
```

### Step 7: Configure Environment

```bash
# Copy example environment file
sudo cp /var/www/media-server/.env.example /var/www/media-server/.env

# Edit configuration
sudo nano /var/www/media-server/.env

# Key settings to configure:
# - APP_URL
# - DB_DATABASE, DB_USERNAME, DB_PASSWORD
# - REDIS_HOST, REDIS_PORT
# - STREAM_RTMP_PORT=1935
# - VOD_FALLBACK_ENABLED=true
```

### Step 8: Configure Nginx

```bash
# Copy Nginx configuration
sudo cp /var/www/media-server/nginx.conf.example \
    /etc/nginx/sites-available/media-server

# Enable the site
sudo ln -sf /etc/nginx/sites-available/media-server \
    /etc/nginx/sites-enabled/media-server

# Remove default site
sudo rm -f /etc/nginx/sites-enabled/default

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

### Step 9: Configure Supervisor

```bash
# Copy supervisor configuration
sudo cp /var/www/media-server/supervisor.conf.example \
    /etc/supervisor/conf.d/media-server.conf

# Update permissions
sudo chown root:root /etc/supervisor/conf.d/media-server.conf

# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Check status
sudo supervisorctl status
```

### Step 10: Run Migrations

```bash
# Migrate database
sudo -u www-data php artisan migrate --force

# Seed database (optional)
sudo -u www-data php artisan db:seed --force
```

### Step 11: Start Services

```bash
# Start PHP-FPM
sudo systemctl start php8.2-fpm
sudo systemctl enable php8.2-fpm

# Start Nginx
sudo systemctl start nginx
sudo systemctl enable nginx

# Start Redis
sudo systemctl start redis-server
sudo systemctl enable redis-server

# Start Supervisor
sudo systemctl start supervisor
sudo systemctl enable supervisor

# Check all services
sudo systemctl status php8.2-fpm nginx redis-server supervisor
```

## Configuration

### Environment Variables

Key environment variables to configure in `.env`:

```env
# Application
APP_NAME="MediaServer"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=media_server
DB_USERNAME=media_user
DB_PASSWORD=your_secure_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Streaming
STREAM_RTMP_PORT=1935
STREAM_HLS_PORT=8080
STREAM_HEALTH_CHECK_INTERVAL=5
VOD_FALLBACK_ENABLED=true

# FFmpeg
FFMPEG_PATH=/usr/bin/ffmpeg
FFMPEG_TIMEOUT=0

# HLS Configuration
HLS_SEGMENT_DURATION=10
HLS_SEGMENTS_IN_PLAYLIST=3
```

### PHP-FPM Tuning

Edit `/etc/php/8.2/fpm/pool.d/www.conf`:

```ini
[www]
user = www-data
group = www-data
listen = /run/php/php8.2-fpm.sock

pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

; Timeouts for streaming
request_terminate_timeout = 300
```

### Redis Configuration

Edit `/etc/redis/redis.conf`:

```
maxmemory 512mb
maxmemory-policy allkeys-lru
appendonly yes
save 900 1
```

### MySQL Configuration

Add to `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```ini
[mysqld]
max_connections = 100
innodb_buffer_pool_size = 2G
innodb_log_file_size = 512M
slow_query_log = ON
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

## API Usage

### Authentication

Include Bearer token in authorization header:

```bash
Authorization: Bearer YOUR_API_TOKEN
```

### Create Channel

```bash
curl -X POST https://your-domain/api/channels \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "News Channel",
    "slug": "news",
    "description": "Live news streaming",
    "vod_playlist_url": "https://example.com/vod/news.m3u8"
  }'
```

### Start Live Stream

```bash
curl -X POST https://your-domain/api/streams/start \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "channel_id": 1,
    "push_url": "rtmp://your-server/live/news"
  }'
```

### Get Stream Status

```bash
curl -X GET https://your-domain/api/streams/1/status \
  -H "Authorization: Bearer TOKEN"
```

### Trigger VOD Fallback

```bash
curl -X POST https://your-domain/api/streams/fallback \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "channel_id": 1
  }'
```

### Watch Stream

```
https://your-domain/play/news?format=hls
```

## Troubleshooting

### Stream Not Starting

1. Check FFmpeg installation:
   ```bash
   which ffmpeg
   ffmpeg -version
   ```

2. Check stream process:
   ```bash
   ps aux | grep ffmpeg
   ```

3. Check logs:
   ```bash
   tail -f /var/www/media-server/storage/logs/laravel.log
   tail -f /var/log/nginx/media-server-error.log
   ```

### VOD Fallback Not Triggering

1. Verify VOD URL is accessible:
   ```bash
   curl -I https://example.com/playlist.m3u8
   ```

2. Check stream monitor status:
   ```bash
   sudo supervisorctl status media-server-monitor
   ```

3. Check health monitor logs:
   ```bash
   tail -f /var/log/supervisor/media-server-monitor.log
   ```

### High CPU Usage

1. Check PHP-FPM processes:
   ```bash
   ps aux | grep php-fpm
   top -p $(pidof php-fpm)
   ```

2. Reduce FFmpeg threads:
   ```bash
   # Edit services/StreamingService.php
   # Add -threads 2 to FFmpeg command
   ```

3. Check database queries:
   ```bash
   mysql> SHOW PROCESSLIST;
   ```

### Database Connection Errors

1. Verify MySQL is running:
   ```bash
   sudo systemctl status mysql
   ```

2. Test connection:
   ```bash
   mysql -h localhost -u media_user -p media_server
   ```

3. Check credentials in .env

### Redis Connection Issues

1. Verify Redis is running:
   ```bash
   redis-cli ping
   ```

2. Check Redis logs:
   ```bash
   sudo tail -f /var/log/redis/redis-server.log
   ```

3. Restart Redis:
   ```bash
   sudo systemctl restart redis-server
   ```

## Performance Tuning

### Optimize Streaming

1. Adjust segment duration in `.env`:
   ```
   HLS_SEGMENT_DURATION=6  # Lower for better responsiveness
   HLS_SEGMENTS_IN_PLAYLIST=5  # Increase buffer
   ```

2. Use hardware acceleration if available (GPU):
   ```bash
   # Check for H.264 encoding support
   ffmpeg -encoders | grep h264
   ```

### Monitor Performance

1. Check system resources:
   ```bash
   free -h
   df -h
   top
   ```

2. Monitor streams:
   ```bash
   sudo supervisorctl status
   ps aux | grep ffmpeg | wc -l
   ```

3. Check database performance:
   ```bash
   mysql -e "SHOW STATUS LIKE 'Threads%';"
   ```

### Scaling Considerations

1. Use load balancer for multiple servers
2. Implement video caching layer
3. Use CDN for HLS segment distribution
4. Consider horizontal scaling with Kubernetes

## Security Hardening

1. Enable firewall:
   ```bash
   sudo ufw enable
   sudo ufw allow 22/tcp
   sudo ufw allow 80/tcp
   sudo ufw allow 443/tcp
   sudo ufw allow 1935/tcp
   ```

2. Setup SSL/TLS with Let's Encrypt:
   ```bash
   sudo apt-get install -y certbot python3-certbot-nginx
   sudo certbot certonly --nginx -d your-domain.com
   ```

3. Implement IP whitelisting in .env:
   ```
   ENABLE_IP_WHITELIST=true
   IP_WHITELIST=192.168.1.0/24,10.0.0.0/8
   ```

4. Regular backups:
   ```bash
   # Backup database
   mysqldump -u root -p media_server > backup.sql
   
   # Backup configuration
   tar -czf config-backup.tar.gz /var/www/media-server/.env
   ```

## Support and Updates

For issues, feature requests, or contributions:
- GitHub: https://github.com/your-org/media-server
- Documentation: https://docs.media-server.local
- Issues: https://github.com/your-org/media-server/issues

## License

MIT License - See LICENSE file for details
