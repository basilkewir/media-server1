# SRT Streams 500 Error - Troubleshooting Guide

## ⚠️ Error: 500 Server Error

When accessing `http://your-server:8080/admin/srt-streams`, you get a 500 error.

## 🔍 Common Causes & Fixes

### Issue 1: Database Table Not Created

**Symptom:** 500 error when accessing the page

**Fix:**
```bash
# SSH to your server
ssh root@your-server-ip

# Navigate to media server
cd /var/www/mediaserver

# Run database migration
php artisan migrate

# Expected output:
# Migration table created successfully.
# Migrating: 2024_05_22_000000_create_srt_streams_table
# Migrated:  2024_05_22_000000_create_srt_streams_table (0.23s)
```

### Issue 2: Missing Route or Controller

**Symptom:** 500 error on SRT Streams page

**Fix:**
```bash
# Pull latest code
cd /var/www/mediaserver
git pull origin master

# Clear cache
php artisan cache:clear
php artisan view:clear
php artisan route:cache

# Clear config
php artisan config:cache
```

### Issue 3: Cache Issues

**Symptom:** Page worked before, now shows 500

**Fix:**
```bash
# Clear all Laravel caches
php artisan cache:clear
php artisan view:clear
php artisan route:cache
php artisan config:cache

# Restart PHP
sudo systemctl restart php-fpm

# Restart web server
sudo systemctl restart nginx
```

### Issue 4: Permissions Problem

**Symptom:** 500 error, permission denied in logs

**Fix:**
```bash
# Fix directory permissions
sudo chown -R www-data:www-data /var/www/mediaserver
sudo chmod -R 755 /var/www/mediaserver
sudo chmod -R 755 /var/www/mediaserver/storage
sudo chmod -R 755 /var/www/mediaserver/bootstrap/cache
```

### Issue 5: Missing Dependencies

**Symptom:** 500 error mentioning missing class

**Fix:**
```bash
# Install/update Composer dependencies
cd /var/www/mediaserver
composer install --no-dev --optimize-autoloader

# Clear cache
php artisan cache:clear
```

## 🛠️ Step-by-Step Troubleshooting

### Step 1: Check Error Logs

```bash
# SSH to server
ssh root@your-server-ip

# View latest errors
tail -100 /var/www/mediaserver/storage/logs/laravel.log

# Or specific error
grep -i "srt\|streams" /var/www/mediaserver/storage/logs/laravel.log | tail -50
```

### Step 2: Verify Database

```bash
# Connect to database
mysql -u root -p mediaserver

# Check if table exists
SHOW TABLES LIKE 'srt_streams';

# If not, run migration
exit
cd /var/www/mediaserver
php artisan migrate
```

### Step 3: Test artisan Command

```bash
# Test the import command
php artisan srt:import-existing-channels

# If error, it shows the problem
```

### Step 4: Check PHP Errors

```bash
# View PHP errors
sudo tail -100 /var/log/php-fpm/error.log

# Or Nginx errors
sudo tail -100 /var/log/nginx/error.log
```

### Step 5: Clear Everything and Restart

```bash
# Complete reset
cd /var/www/mediaserver

# 1. Clear caches
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear

# 2. Rebuild caches
php artisan config:cache
php artisan route:cache

# 3. Restart services
sudo systemctl restart php-fpm
sudo systemctl restart nginx

# 4. Try accessing page
```

## 🚨 Complete Fix Script

Run this entire script on your server:

```bash
#!/bin/bash

echo "🔧 Troubleshooting SRT Streams 500 Error..."
echo ""

# Navigate to media server
cd /var/www/mediaserver

# 1. Pull latest code
echo "📥 Pulling latest code..."
git pull origin master

# 2. Fix permissions
echo "🔐 Fixing permissions..."
sudo chown -R www-data:www-data /var/www/mediaserver
sudo chmod -R 755 /var/www/mediaserver
sudo chmod -R 755 /var/www/mediaserver/storage
sudo chmod -R 755 /var/www/mediaserver/bootstrap/cache

# 3. Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

# 4. Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate

# 5. Import channels
echo "📡 Importing SRT channels..."
php artisan srt:import-existing-channels

# 6. Clear caches
echo "🗑️ Clearing caches..."
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear

# 7. Rebuild caches
echo "🔄 Rebuilding caches..."
php artisan config:cache
php artisan route:cache

# 8. Restart services
echo "🔄 Restarting services..."
sudo systemctl restart php-fpm
sudo systemctl restart nginx

echo ""
echo "✅ Troubleshooting complete!"
echo "Try accessing: http://your-server:8080/admin/srt-streams"
```

## 📋 Checklist Before Troubleshooting

Before trying fixes, verify:

- [ ] You've pulled the latest code: `git pull origin master`
- [ ] Database migration ran: `php artisan migrate`
- [ ] You're logged in as admin
- [ ] Routes are cached properly: `php artisan route:cache`
- [ ] Storage permissions are correct
- [ ] PHP-FPM is running: `sudo systemctl status php-fpm`
- [ ] Nginx is running: `sudo systemctl status nginx`

## 🎯 Quick Fix (Try This First)

```bash
cd /var/www/mediaserver

# Pull latest + run migrations + clear cache
git pull origin master && \
php artisan migrate && \
php artisan cache:clear && \
php artisan route:cache && \
sudo systemctl restart php-fpm nginx

# Then try the URL again
echo "✅ Done! Try accessing the page now."
```

## 📝 Diagnostic Commands

Use these to gather error information:

```bash
# Get latest error
tail -50 /var/www/mediaserver/storage/logs/laravel.log

# Check if table exists
mysql -u root -p mediaserver -e "SHOW TABLES LIKE 'srt_streams';"

# Test artisan
php artisan srt:import-existing-channels

# Check permissions
ls -la /var/www/mediaserver/storage/logs/

# Verify PHP
php -v
php artisan tinker
>>> App\Models\SrtStream::count();
```

## 🆘 If Nothing Works

Try the nuclear option:

```bash
# WARNING: This clears everything
cd /var/www/mediaserver

# 1. Remove cache files
rm -rf storage/framework/views/*
rm -rf storage/framework/cache/*
rm -rf bootstrap/cache/*

# 2. Migrate fresh (destructive!)
php artisan migrate:fresh

# 3. Reseed data
php artisan db:seed --class=SrtStreamSeeder
php artisan srt:import-existing-channels

# 4. Rebuild caches
php artisan config:cache
php artisan route:cache

# 5. Restart
sudo systemctl restart php-fpm nginx
```

**⚠️ WARNING:** `migrate:fresh` deletes all data. Only use if necessary!

## 📞 Getting Help

If you still see 500 error:

1. **Share error log:**
   ```bash
   tail -100 /var/www/mediaserver/storage/logs/laravel.log
   ```

2. **Share Nginx error:**
   ```bash
   sudo tail -50 /var/log/nginx/error.log
   ```

3. **Share PHP error:**
   ```bash
   sudo tail -50 /var/log/php-fpm/error.log
   ```

4. **Run diagnostic:**
   ```bash
   php artisan srt:import-existing-channels
   php artisan tinker
   >>> App\Models\SrtStream::all();
   ```

## Related Guides

- CURRENT_CHANNELS_MANAGEMENT.md - Usage guide
- DEPLOYMENT_CURRENT_CHANNELS.md - Deployment steps
- ADMIN_MENU_GUIDE.md - Menu reference

---

**Updated:** May 22, 2026  
**Status:** Troubleshooting Guide Ready
