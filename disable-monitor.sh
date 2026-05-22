#!/bin/bash

# Disable VOD Fallback Stream Monitor
# This script disables the background stream health monitoring that
# was causing "Too many open files" errors

echo "🛠️  Disabling VOD Fallback Stream Monitor..."

cd /var/www/mediaserver

# Remove Supervisor configuration for monitor
echo "Removing Supervisor monitor configuration..."
sudo rm -f /etc/supervisor/conf.d/mediaserver-monitor.conf
sudo systemctl reload supervisor 2>/dev/null || true

# Remove from crontab if it exists  
echo "Removing from crontab..."
sudo crontab -l 2>/dev/null | grep -v "stream:monitor" | sudo crontab - 2>/dev/null || true

# Stop any running monitor processes
echo "Stopping any running monitor processes..."
sudo pkill -f "php artisan.*stream:monitor" || true
sudo pkill -f "php artisan.*relay:monitor" || true
sudo pkill -f ffmpeg || true

# Update .env to disable health checks
echo "Updating .env configuration..."
sed -i 's/STREAM_VOD_FALLBACK_ENABLED=true/STREAM_VOD_FALLBACK_ENABLED=false/' .env
sed -i 's/STREAM_HEALTH_CHECK_INTERVAL=.*/STREAM_HEALTH_CHECK_INTERVAL=0/' .env

# Clear Laravel cache
echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear

# Restart services
echo "Restarting services..."
sudo systemctl restart php-fpm.service php8.3-fpm.service 2>/dev/null || true
sudo systemctl restart nginx

echo "✅ Stream Monitor disabled successfully!"
echo ""
echo "What was disabled:"
echo "  ✗ VOD Fallback FFmpeg processes"
echo "  ✗ Background stream health monitoring"
echo "  ✗ Relay broadcast monitoring"
echo ""
echo "This will allow:"
echo "  ✓ Login page to load (no more 500 errors)"
echo "  ✓ Admin panel to work"
echo "  ✓ REST API to function properly"
echo ""
echo "Note: You can re-enable this later by:"
echo "  1. Setting STREAM_VOD_FALLBACK_ENABLED=true in .env"
echo "  2. Running 'sudo systemctl restart supervisor'"
echo ""
