# Ubuntu Server: Git Pull & Flussonic Setup Guide

This guide explains how to pull the latest code and run the Flussonic integration setup on your Ubuntu server.

## Prerequisites

Before running the setup, ensure you have:

- ✅ Ubuntu 20.04 LTS or newer
- ✅ Git installed: `sudo apt-get install git`
- ✅ Flussonic installed
- ✅ Nginx, PHP-FPM, and MySQL running
- ✅ SSH access to the server
- ✅ Root or sudo privileges

## Quick Start (3 Steps)

### Step 1: SSH into Your Ubuntu Server

```bash
ssh root@your-server-ip
# or if using a different user:
ssh user@your-server-ip
```

### Step 2: Download and Run the Setup Script

```bash
# Navigate to the media server directory
cd /var/www/mediaserver

# Pull the latest code
git pull origin master

# Run the complete update & setup script
sudo bash update-and-setup.sh
```

### Step 3: Verify the Installation

```bash
# Check service status
sudo systemctl status nginx php-fpm flussonic

# Test the API endpoints
curl http://localhost/api/health
curl -u flussonic:letmein! http://localhost:8935/streamer/api/v3/server
```

## Step-by-Step: Manual Setup (if you prefer)

If you want to run commands individually, follow this sequence:

### 1. Setup Git Repository

```bash
# Navigate to media server directory
cd /var/www/mediaserver

# Initialize git (if not already initialized)
git init

# Add remote (replace with your repository URL)
git remote add origin https://github.com/your-username/media-server.git

# Fetch the latest code
git fetch origin

# Switch to master branch
git checkout master
```

### 2. Pull Latest Code

```bash
cd /var/www/mediaserver

# View current status
git status

# Pull latest changes
git pull origin master

# View recent commits
git log --oneline -10
```

### 3. Create Backups

```bash
# Create backup directory
sudo mkdir -p /var/backups/mediaserver/$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/mediaserver/$(date +%Y%m%d_%H%M%S)"

# Backup configuration
sudo cp /var/www/mediaserver/.env "$BACKUP_DIR/env.backup"
sudo cp /var/www/mediaserver/config/*.php "$BACKUP_DIR/"

# Backup database
sudo mysqldump -u root media_server > "$BACKUP_DIR/database.sql"

echo "Backups saved to: $BACKUP_DIR"
```

### 4. Update PHP Dependencies

```bash
cd /var/www/mediaserver

# Install or update Composer
sudo php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
sudo php -r "unlink('composer-setup.php');"

# Install dependencies
sudo composer install --no-interaction --optimize-autoloader
```

### 5. Run Database Migrations

```bash
cd /var/www/mediaserver

# Check migration status
php artisan migrate:status

# Run pending migrations
sudo php artisan migrate --force

# Seed database (if needed)
sudo php artisan db:seed
```

### 6. Run Flussonic Setup

```bash
# Make setup script executable
sudo chmod +x /var/www/mediaserver/flussonic-setup.sh

# Run the setup
sudo bash /var/www/mediaserver/flussonic-setup.sh
```

### 7. Clear Application Caches

```bash
cd /var/www/mediaserver

sudo php artisan cache:clear
sudo php artisan config:clear
sudo php artisan route:clear
sudo php artisan view:clear
```

### 8. Fix File Permissions

```bash
# Set proper ownership
sudo chown -R www-data:www-data /var/www/mediaserver

# Set directory permissions
sudo chmod -R 755 /var/www/mediaserver
sudo chmod -R 775 /var/www/mediaserver/storage
sudo chmod -R 775 /var/www/mediaserver/bootstrap/cache

# Fix Flussonic permissions
sudo chown -R flussonic:flussonic /var/log/flussonic
sudo chown -R flussonic:flussonic /var/cache/flussonic
```

### 9. Restart Services

```bash
# Restart PHP-FPM
sudo systemctl restart php-fpm

# Restart Nginx
sudo systemctl restart nginx

# Restart Flussonic
sudo systemctl restart flussonic

# Restart Supervisor (for queue workers)
sudo systemctl restart supervisor

# Verify all services
sudo systemctl status php-fpm nginx flussonic supervisor
```

### 10. Verify Installation

```bash
# Check if services are running
sudo systemctl is-active php-fpm nginx flussonic

# Test API endpoints
curl http://localhost/api/health

# Test Flussonic API
curl -u flussonic:letmein! http://localhost:8935/streamer/api/v3/server

# Check logs for errors
sudo tail -f /var/log/nginx/error.log &
sudo tail -f /var/log/php-fpm.log &
sudo journalctl -u flussonic -f
```

## Troubleshooting

### Git Pull Fails

**Problem:** `fatal: 'origin' does not appear to be a 'git' repository`

**Solution:**
```bash
# Check git remote
git remote -v

# If empty, add remote
git remote add origin https://github.com/your-username/media-server.git

# Try pull again
git pull origin master
```

### Permission Denied on Setup Script

**Problem:** `Permission denied` when running script

**Solution:**
```bash
# Make script executable
sudo chmod +x /var/www/mediaserver/update-and-setup.sh
sudo chmod +x /var/www/mediaserver/flussonic-setup.sh

# Try again
sudo bash /var/www/mediaserver/update-and-setup.sh
```

### Composer Dependency Errors

**Problem:** `Your requirements could not be resolved to an installable set of packages`

**Solution:**
```bash
# Clear composer cache
sudo composer clear-cache

# Update composer
sudo composer self-update

# Try install again
sudo composer install --no-interaction --no-dev --optimize-autoloader
```

### Database Migration Fails

**Problem:** `SQLSTATE[42S02]: Table not found`

**Solution:**
```bash
# Check migration status
php artisan migrate:status

# Rollback to start
php artisan migrate:reset

# Run migrations fresh
php artisan migrate:fresh

# Or just run specific migration
php artisan migrate --path=/database/migrations/2024_01_01_000005_add_icecast_relay_to_channels.php
```

### Flussonic Service Won't Start

**Problem:** `Job for flussonic.service failed`

**Solution:**
```bash
# Check Flussonic logs
sudo journalctl -u flussonic -n 50

# Check if port is in use
sudo lsof -i :1935
sudo lsof -i :8935

# Kill process if needed
sudo lsof -ti :1935 | xargs kill -9

# Try to start Flussonic
sudo systemctl start flussonic

# Check status
sudo systemctl status flussonic
```

### Nginx Returns 502 Bad Gateway

**Problem:** Nginx can't connect to PHP-FPM

**Solution:**
```bash
# Check PHP-FPM is running
sudo systemctl status php-fpm

# Check PHP-FPM socket
sudo ls -la /run/php/php-fpm.sock

# Restart PHP-FPM
sudo systemctl restart php-fpm

# Restart Nginx
sudo systemctl restart nginx
```

## Post-Setup Checklist

After running the setup script, verify:

- [ ] All services are running: `sudo systemctl status nginx php-fpm flussonic`
- [ ] Laravel API responding: `curl http://localhost/api/health`
- [ ] Flussonic API responding: `curl -u flussonic:letmein! http://localhost:8935/streamer/api/v3/server`
- [ ] No errors in logs: `tail -f /var/log/nginx/error.log`
- [ ] Database migrations applied: `php artisan migrate:status`
- [ ] File permissions correct: `ls -la /var/www/mediaserver/storage`
- [ ] Backups created: `ls -la /var/backups/mediaserver/`

## Rollback (if something goes wrong)

If you need to rollback to the previous version:

```bash
# Go to backup directory
cd /var/backups/mediaserver/

# List available backups
ls -la

# Choose the most recent backup and restore
BACKUP_DIR="/var/backups/mediaserver/20240515_143022"

# Restore configuration files
sudo cp "$BACKUP_DIR/.env" /var/www/mediaserver/
sudo cp "$BACKUP_DIR"/*.php /var/www/mediaserver/config/

# Restore database
sudo mysql -u root media_server < "$BACKUP_DIR/database.sql"

# Revert git changes
cd /var/www/mediaserver
git reset --hard HEAD~1

# Restart services
sudo systemctl restart php-fpm nginx flussonic
```

## Automatic Updates (Optional)

To automatically pull and update every day at 2 AM:

```bash
# Edit crontab
sudo crontab -e

# Add this line:
0 2 * * * cd /var/www/mediaserver && git pull origin master && bash update-and-setup.sh >> /var/log/mediaserver-update.log 2>&1

# Save and exit
```

## Production Best Practices

1. **Always backup before updates:**
   ```bash
   sudo mysqldump -u root media_server > /var/backups/pre-update-backup.sql
   ```

2. **Run updates during maintenance window:**
   - Put maintenance message in place
   - Pull code
   - Run migrations
   - Restart services

3. **Monitor logs after update:**
   ```bash
   sudo tail -f /var/log/nginx/error.log /var/log/php-fpm.log
   ```

4. **Test critical functionality:**
   - Create a test stream
   - Verify relay works
   - Check VOD fallback

## Need Help?

Check these documentation files:

- `INSTALLATION.md` - Complete installation guide
- `FLUSSONIC_INTEGRATION.md` - Flussonic integration details
- `DEVELOPMENT.md` - Development environment setup
- `DEPLOYMENT_GUIDE.md` - Production deployment guide
- `RELAY_GUIDE.md` - Relay broadcasting guide

Or check logs:

```bash
# Application logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/php-fpm.log
sudo tail -f /var/www/mediaserver/storage/logs/laravel.log

# System logs
sudo journalctl -u php-fpm -f
sudo journalctl -u nginx -f
sudo journalctl -u flussonic -f
```

---

**Last Updated:** May 2026  
**Script Version:** 1.0  
**Status:** ✅ Production Ready
