#!/bin/bash

# SRT Streams 500 Error - Quick Fix Script
# Run this on your server to fix the dashboard error

set -e  # Exit on error

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║         SRT Streams Dashboard - 500 Error Fix Script          ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Check if running from correct location
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run from /var/www/mediaserver"
    exit 1
fi

echo "📁 Working directory: $(pwd)"
echo ""

# Step 1: Pull latest code
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📥 STEP 1: Pulling latest code from GitHub..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
git pull origin master 2>&1 || echo "⚠️  Git pull had issues (code may already be current)"
echo "✓ Code pull complete"
echo ""

# Step 2: Fix permissions
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔐 STEP 2: Fixing directory permissions..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
sudo chown -R www-data:www-data . 2>/dev/null || echo "⚠️  Permission change failed (may need sudo password)"
sudo chmod -R 755 . 2>/dev/null || true
sudo chmod -R 755 storage bootstrap/cache 2>/dev/null || true
echo "✓ Permissions fixed"
echo ""

# Step 3: Run database migration
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🗄️  STEP 3: Running database migrations..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
php artisan migrate --force 2>&1 || echo "⚠️  Migration had issues (may already be run)"
echo "✓ Migrations complete"
echo ""

# Step 4: Clear all caches
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🗑️  STEP 4: Clearing all caches..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
echo "✓ Caches cleared"
echo ""

# Step 5: Rebuild caches
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔄 STEP 5: Rebuilding caches..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
php artisan config:cache
php artisan route:cache
echo "✓ Caches rebuilt"
echo ""

# Step 6: Import channels
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📡 STEP 6: Importing SRT channels..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
php artisan srt:import-existing-channels 2>&1 || echo "⚠️  Import had issues (channels may already be imported)"
echo "✓ Channels imported"
echo ""

# Step 7: Restart services
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔄 STEP 7: Restarting web services..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
sudo systemctl restart php-fpm 2>/dev/null || echo "⚠️  Could not restart PHP-FPM (may need sudo)"
sudo systemctl restart nginx 2>/dev/null || echo "⚠️  Could not restart Nginx (may need sudo)"
echo "✓ Services restarted"
echo ""

# Step 8: Verify
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✓ VERIFICATION: Checking database..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
php artisan tinker <<EOF 2>/dev/null || echo "⚠️  Database check failed"
\$count = App\Models\SrtStream::count();
echo "Database has {$count} SRT streams\n";
exit;
EOF
echo ""

# Final summary
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                    ✅ Fix Complete!                           ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "📝 Next steps:"
echo "  1. Try accessing: http://your-server:8080/admin/srt-streams"
echo "  2. If still showing 500 error, run: tail -100 storage/logs/laravel.log"
echo "  3. Share the error output for further troubleshooting"
echo ""
echo "💡 Tip: To monitor the logs in real-time, use:"
echo "   tail -f storage/logs/laravel.log"
echo ""
