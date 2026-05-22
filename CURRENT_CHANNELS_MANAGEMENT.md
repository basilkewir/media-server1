# Current SRT Channels Management Guide

## Overview

This guide explains how to manage the current SRT receiving channels (Compassion TV and SUDFM TV) through the media server admin panel.

## Current Channels Status

Your media server is currently receiving SRT streams from two channels:

### 1. Compassion TV
- **Stream ID**: `compassiontv`
- **SRT Port**: 9000
- **RTMP Stream**: `compassiontv`
- **Status**: Active
- **SRT URL**: `srt://your-server:9000?streamid=compassiontv`

### 2. SUDFM TV
- **Stream ID**: `sudfmtv`
- **SRT Port**: 9001
- **RTMP Stream**: `sudfmtv`
- **Status**: Active
- **SRT URL**: `srt://your-server:9001?streamid=sudfmtv`

## Step 1: Import Existing Channels to Database

The first step is to import these existing channels into the media server database so they can be managed through the admin panel.

### Option A: Using Artisan Command (Recommended)

```bash
cd /var/www/mediaserver

# Run the import command
php artisan srt:import-existing-channels
```

**Expected Output:**
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
You can now manage these channels from the admin panel:
  http://your-server/admin/srt-streams
```

### Option B: Using Database Seeder

```bash
cd /var/www/mediaserver

# Run Laravel seeder
php artisan db:seed --class=SrtStreamSeeder
```

## Step 2: Access the SRT Management Dashboard

1. **Log in to the admin panel:**
   - Navigate to: `http://your-server-ip/admin/`
   - Enter your admin credentials

2. **Go to SRT Streams Management:**
   - Click on **"📡 SRT Streams Management"** in the sidebar
   - Or navigate to: `http://your-server-ip/admin/srt-streams`

## Step 3: Verify Channel Status

The dashboard displays:

### Statistics Cards (Top Section)
- **Total Streams**: 2 (Compassion TV + SUDFM TV)
- **Active Streams**: 2 (both enabled)
- **Listening Ports**: 2 (ports 9000 and 9001)
- **Inactive**: 0

### Streams Table

| Column | Description |
|--------|-------------|
| **Stream Name** | Channel name and stream ID |
| **SRT Port** | Port number (9000, 9001) |
| **RTMP Stream** | RTMP stream name at Flussonic |
| **Status** | Current stream status |
| **Bitrate** | Bitrate in kbps |
| **Last Connected** | When encoder last connected |
| **Actions** | View, Edit, Delete buttons |

## Step 4: Manage Channels

### View Stream Details

Click the **👁️ (Eye)** icon to view detailed information:
- Basic stream configuration
- SRT, RTMP, HLS, and DASH URLs
- Current status and statistics
- Last connection time

### View Stream Logs

Click the **📋 (List)** icon to see real-time logs:
- Connection events
- Stream start/stop events
- Error messages
- Bandwidth information

### Edit Stream Configuration

Click the **✏️ (Edit)** icon to modify:
- Stream name and description
- Bitrate and resolution
- Video codec (h264, h265, vp9)
- Audio codec (aac, mp3, flac)
- Enable/disable status

**Note**: Changes take effect immediately without restarting services.

### Enable/Disable Streams

Use the toggle switch in the status column to:
- **Enable**: Turn on SRT listener and RTMP relay
- **Disable**: Stop receiving SRT but keep configuration

### Delete Stream

Click the **🗑️ (Delete)** icon to:
- Remove stream from database
- Close firewall port
- Delete Flussonic configuration

**Warning**: This is permanent. Make sure the channel isn't in use.

## Step 5: Monitor Stream Health

The dashboard automatically refreshes every 30 seconds to show:

### Real-Time Indicators
- 🟢 **Active**: Stream is receiving data
- 🟡 **Pending**: Stream is configured but waiting for data
- 🔴 **Error**: Stream has connection issues

### Listening Ports
Shows how many SRT ports are actively listening for connections.

### Last Connected
Shows the last time each encoder connected to the SRT listener.

## Encoder Configuration

### vMix SRT Push Configuration

1. **In vMix**, go to **Output** → **SRT**
2. Configure:
   ```
   Address: srt://your-server-ip:9000
   Mode: Caller (vMix initiates connection)
   Latency: 120ms
   Stream ID: compassiontv
   ```

3. **For SUDFM TV**, use port 9001:
   ```
   Address: srt://your-server-ip:9001
   Stream ID: sudfmtv
   ```

### OBS SRT Configuration

1. **In OBS**, go to **Settings** → **Stream**
2. Configure:
   ```
   Service: Custom
   Server: srt://your-server-ip:9000
   Stream Key: compassiontv
   ```

## Stream URLs for Playback

Once imported and active, streams are available at:

### Compassion TV
- **HLS**: `http://your-server/compassiontv/index.m3u8`
- **DASH**: `http://your-server/compassiontv/manifest.mpd`
- **RTMP**: `rtmp://your-server:1935/live/compassiontv`

### SUDFM TV
- **HLS**: `http://your-server/sudfmtv/index.m3u8`
- **DASH**: `http://your-server/sudfmtv/manifest.mpd`
- **RTMP**: `rtmp://your-server:1935/live/sudfmtv`

## Troubleshooting

### Issue: Channels don't appear in dashboard

**Solution**: Make sure you've run the import command:
```bash
php artisan srt:import-existing-channels
```

### Issue: Stream shows "Pending" status

**Reason**: Encoder hasn't connected yet
- Verify encoder is configured with correct SRT URL
- Check firewall allows port 9000/9001
- Check encoder is actually sending stream

**Solution**:
```bash
# Check if ports are listening
ss -tlnup | grep -E '9000|9001'

# Check SRT daemon logs
tail -f /var/www/mediaserver/storage/logs/srt-server.log
```

### Issue: Stream shows "Error" status

**Solution**: Check the logs:
1. Click 📋 icon to view stream logs
2. Look for error messages
3. Common issues:
   - Firewall blocking port
   - Encoder disconnected
   - RTMP server offline
   - Insufficient disk space

### Issue: Can't connect encoder to SRT

**Solution**:
```bash
# Test SRT port is open
nmap -p 9000,9001 your-server-ip

# Check firewall rules
sudo ufw status | grep 900

# Open ports if needed
sudo ufw allow 9000/udp
sudo ufw allow 9001/udp
```

## API Management

If you prefer programmatic management, use the REST API:

### Get all streams
```bash
curl -H "Authorization: Bearer YOUR_API_TOKEN" \
  http://your-server/api/srt-streams
```

### Get stream details
```bash
curl -H "Authorization: Bearer YOUR_API_TOKEN" \
  http://your-server/api/srt-streams/compassiontv
```

### Get stream statistics
```bash
curl -H "Authorization: Bearer YOUR_API_TOKEN" \
  http://your-server/api/srt-streams/compassiontv/stats
```

See `DYNAMIC_SRT_MANAGEMENT.md` for complete API reference.

## Zero-Downtime Operations

All operations on SRT streams are **zero-downtime**:

- ✅ Enable/disable streams
- ✅ Change bitrate/resolution
- ✅ Update codecs
- ✅ Add new streams
- ✅ Modify stream URLs

**No service restart required**. Other streams continue unaffected.

## Performance Monitoring

### Check SRT daemon status
```bash
# View running processes
ps aux | grep srt-daemon

# Check listening ports
ss -tlnup | grep srt-live-transmit

# Monitor bandwidth per stream
watch -n 2 "ps aux | grep -E 'srt-live-transmit|ffmpeg'"
```

### View system resources
```bash
# Check CPU/Memory usage
top -p $(pgrep srt-daemon)

# Check disk I/O
iostat -x 1 10

# Monitor network bandwidth
iftop
```

## Best Practices

1. **Set Appropriate Bitrates**
   - Compassion TV: 1500-2500 kbps for 720p
   - SUDFM TV: 1500-2500 kbps for 720p
   - Adjust based on available bandwidth

2. **Monitor Logs Regularly**
   - Check for connection drops
   - Look for codec errors
   - Monitor bandwidth fluctuations

3. **Configure Encoder Fallback**
   - Have backup encoder ready
   - Test failover mechanism
   - Document encoder IPs and ports

4. **Backup Configuration**
   - Export stream configuration periodically
   - Keep encoder settings documented
   - Maintain list of all stream URLs

5. **Test Before Going Live**
   - Verify stream quality at various bitrates
   - Test with different encoders
   - Monitor for at least 1 hour

## Related Documentation

- **DYNAMIC_SRT_MANAGEMENT.md** - Full SRT management system documentation
- **SRT_IMPLEMENTATION_QUICK_START.md** - Setup checklist and verification steps
- **FLUSSONIC_QUICK_REFERENCE.md** - Flussonic configuration reference

---

**Last Updated:** May 22, 2026
**Status:** ✅ Active - Current channels can be managed via admin panel
