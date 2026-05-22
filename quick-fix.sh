#!/bin/bash

# Quick fix script for SRT Streams 500 error
# Run this directly on the Ubuntu server

set -e

cd /var/www/mediaserver

echo "=========================================="
echo "  SRT Streams - Quick Fix Script"
echo "=========================================="
echo ""

# Step 1: Check PHP version
echo "📋 Step 1: Checking PHP services..."
echo ""
sudo systemctl list-unit-files | grep -i php || echo "No php services found in systemctl"
echo ""

# Step 2: Check which PHP is running
echo "📋 Step 2: Checking running PHP processes..."
ps aux | grep php-fpm || echo "No PHP-FPM processes found"
echo ""

# Step 3: Find available PHP services
echo "📋 Step 3: Available PHP versions..."
apt list --installed 2>/dev/null | grep php-fpm || echo "No PHP-FPM packages found"
echo ""

# Step 4: Try to restart PHP and Nginx
echo "🔄 Step 4: Attempting to restart services..."
echo ""

# Try PHP 8.3
if sudo systemctl list-unit-files | grep -q php8.3-fpm; then
  echo "✓ Found PHP 8.3-FPM, restarting..."
  sudo systemctl restart php8.3-fpm
else
  echo "✗ PHP 8.3-FPM not found"
fi

# Try PHP 8.2
if sudo systemctl list-unit-files | grep -q php8.2-fpm; then
  echo "✓ Found PHP 8.2-FPM, restarting..."
  sudo systemctl restart php8.2-fpm
else
  echo "✗ PHP 8.2-FPM not found"
fi

# Try PHP 8.1
if sudo systemctl list-unit-files | grep -q php8.1-fpm; then
  echo "✓ Found PHP 8.1-FPM, restarting..."
  sudo systemctl restart php8.1-fpm
else
  echo "✗ PHP 8.1-FPM not found"
fi

# Try PHP 8.0
if sudo systemctl list-unit-files | grep -q php8.0-fpm; then
  echo "✓ Found PHP 8.0-FPM, restarting..."
  sudo systemctl restart php8.0-fpm
else
  echo "✗ PHP 8.0-FPM not found"
fi

# Restart Nginx
echo "🔄 Restarting Nginx..."
sudo systemctl restart nginx

echo ""
echo "=========================================="
echo "  ✅ Fix Complete!"
echo "=========================================="
echo ""
echo "📝 Next steps:"
echo "  1. Open: http://5.180.182.232:8080/admin/srt-streams"
echo "  2. Check if dashboard loads"
echo "  3. If 500 error still shows, run:"
echo "     tail -50 /var/www/mediaserver/storage/logs/laravel.log"
echo ""
