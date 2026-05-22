# Server Commands to Fix SRT Streams Dashboard

You're already on the server! Run these commands directly:

## Step 1: Check PHP Version

```bash
# List available PHP services
sudo systemctl list-unit-files | grep php

# Or check PHP version
php --version
```

## Step 2: Restart PHP (Try One of These)

```bash
# Try PHP 8.2
sudo systemctl restart php8.2-fpm nginx

# OR PHP 8.1
sudo systemctl restart php8.1-fpm nginx

# OR PHP 8.0
sudo systemctl restart php8.0-fpm nginx

# OR check what's installed
sudo apt list --installed | grep php-fpm
```

## Step 3: After Restarting, Test the Dashboard

```bash
# Clear browser cache and try:
# http://5.180.182.232:8080/admin/srt-streams

# Or test with curl
curl http://localhost:8080/admin/srt-streams 2>&1 | head -50
```

## Step 4: If Still Getting 500 Error

```bash
# Check the error log
tail -50 /var/www/mediaserver/storage/logs/laravel.log

# Check nginx error
sudo tail -50 /var/log/nginx/error.log

# Check which PHP is running
ps aux | grep php-fpm

# Or import the channels
php artisan srt:import-existing-channels
```

## Step 5: Verify Everything Works

```bash
# Check if database has data
php artisan tinker
>>> App\Models\SrtStream::count();
>>> App\Models\SrtStream::all();
exit;
```

---

**Tip:** Copy-paste these commands one at a time and share the output!
