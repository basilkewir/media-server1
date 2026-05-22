# Deployment Guide: Current Channels Management

## Quick Setup (5 minutes)

Follow these steps to activate the SRT channels management interface on your media server.

## Step 1: Pull Latest Code

```bash
# SSH into your server
ssh root@your-server-ip

# Navigate to media server
cd /var/www/mediaserver

# Pull latest changes
git pull origin master
```

## Step 2: Install Dependencies (if needed)

```bash
# Install/update Composer dependencies
composer install --no-dev --optimize-autoloader
```

## Step 3: Database Setup

```bash
# Run Laravel migrations (if not already done)
php artisan migrate

# Import existing channels into database
php artisan srt:import-existing-channels
```

**Expected output:**
```
📡 Importing existing SRT channels...

  ✓ Compassion TV imported successfully
    - Port: 9000, RTMP: compassiontv

  ✓ SUDFM TV imported successfully
    - Port: 9001, RTMP: sudfmtv

Summary:
  Imported: 2
  Skipped:  0

✅ Channels imported successfully!
```

## Step 4: Clear Cache

```bash
# Clear Laravel cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Step 5: Verify Dashboard Access

1. Open browser: `http://your-server-ip/admin/`
2. Log in with your admin credentials
3. Click **"📡 SRT Streams Management"** in sidebar
4. Verify both channels appear:
   - ✅ Compassion TV (Port 9000)
   - ✅ SUDFM TV (Port 9001)

## What Was Added

### New Files Created:
```
✅ app/Console/Commands/SrtImportExistingChannels.php
   - Artisan command to import existing channels

✅ app/Http/Controllers/Admin/SrtDashboardController.php
   - Dashboard API endpoints for stream data

✅ database/seeders/SrtStreamSeeder.php
   - Database seeder for initial channels

✅ resources/views/admin/srt-streams/index.blade.php
   - Dashboard view with real-time monitoring

✅ CURRENT_CHANNELS_MANAGEMENT.md
   - Complete management guide
```

### Modified Files:
```
✅ routes/web.php
   - Added new dashboard routes
```

## What You Can Now Do

### ✅ View All Streams
- See both compassiontv and sudfmtv in one dashboard
- View real-time status and last connection time

### ✅ Monitor Streams
- View stream logs in real-time
- See detailed configuration for each stream
- Monitor bitrate and resolution

### ✅ Manage Streams
- Enable/disable streams without restart
- Edit stream parameters (bitrate, codecs, etc.)
- View stream URLs (SRT, RTMP, HLS, DASH)

### ✅ Zero-Downtime Operations
- All changes take effect immediately
- No service restart required
- Other streams continue unaffected

## Troubleshooting

### Issue: Channels don't appear in dashboard

```bash
# Check if import was successful
php artisan srt:import-existing-channels

# Check database
php artisan tinker
>>> App\Models\SrtStream::all();
```

### Issue: Dashboard shows no data

```bash
# Check Laravel cache
php artisan cache:clear
php artisan view:clear

# Restart web server
sudo systemctl restart php-fpm
sudo systemctl restart nginx
```

### Issue: SRT ports not listening

```bash
# Check if srt-daemon is running
ps aux | grep srt-daemon

# Check ports
ss -tlnup | grep -E '9000|9001'

# Check logs
tail -f /var/www/mediaserver/storage/logs/srt-server.log
```

## Next Steps

1. **Access the Dashboard**: `http://your-server:8080/admin/srt-streams`

2. **Monitor Stream Status**: Dashboard refreshes every 30 seconds

3. **Manage Streams**: Use View, Edit, or Delete buttons as needed

4. **Create New Streams**: Use "Add New Stream" button to add more channels

## Important Notes

- ⚠️ **No Service Restart**: Unlike previous version, changes take effect immediately
- 📡 **Multiple Streams**: Each stream runs independently - one crash doesn't affect others
- 🔒 **Safe Deletions**: Soft delete ensures data isn't lost immediately
- 📊 **Real-time Monitoring**: Dashboard updates every 30 seconds automatically

## API Endpoints Available

```bash
# Get all streams
curl http://your-server/admin/srt-streams/api/list

# Get specific stream details
curl http://your-server/admin/srt-streams/api/{id}/details

# Get stream logs
curl http://your-server/admin/srt-streams/api/{id}/logs

# Get stream status
curl http://your-server/admin/srt-streams/api/status
```

## Documentation

For complete information, see:
- `CURRENT_CHANNELS_MANAGEMENT.md` - Full management guide
- `DYNAMIC_SRT_MANAGEMENT.md` - Complete system documentation
- `FLUSSONIC_QUICK_REFERENCE.md` - Stream configuration reference

---

**Deployment Status:** ✅ Ready to deploy
**Compatibility:** Works with existing channels without migration needed
**Downtime Required:** None - zero-downtime deployment
