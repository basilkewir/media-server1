# SRT Stream VOD Standby Playlist

## Overview

The **VOD Standby Playlist** feature ensures your SRT stream channels **never go off-air**. When a live SRT stream disconnects (encoder stops pushing), the system automatically switches to playing your VOD (Video-on-Demand) playlist, keeping the channel streaming 24/7.

## How It Works

```
Live SRT Stream Active
    ↓
[Stream is live and healthy]
    ↓
Encoder Disconnects / SRT Stream Goes Down
    ↓
System Detects Disconnection (every 30 seconds)
    ↓
VOD Fallback Triggered
    ↓
VOD Playlist Plays on Repeat
    ↓
Encoder Comes Back Online
    ↓
System Detects Reconnection
    ↓
Switches Back to Live Stream
```

## Setup Instructions

### Step 1: Link SRT Stream to a Channel

1. Go to **Admin → SRT Streams**
2. Click the **Edit** button on your SRT stream (e.g., "Compassion TV SRT")
3. Under **"VOD Standby Playlist (Never Off-Air)"** section:
   - Select a **Channel** from the dropdown (this links the SRT receiver to a playback channel)
   - Channels available: any channel in your system (e.g., "main", "compassiontv", etc.)
4. Click **Save Changes**

### Step 2: Upload VOD Files

1. After linking a channel, click the **📁 Manage VOD Files** button in the same edit form
   - Or navigate to **Admin → Channels → [Channel Name] → VOD Library**
2. **Upload video files** (MP4, MKV, MOV, AVI, TS, etc.)
   - Max 10 GB per file
   - Supported formats: MP4, MKV, MOV, AVI, TS, M2TS, FLV, WebM
3. **Reorder files** if desired (drag to reorder)
   - Files play in order, then loop
4. A **VOD playlist URL** is auto-generated and saved to the channel

### Step 3: Enable VOD Fallback

1. Return to the SRT Stream edit form
2. Under **"VOD Standby Playlist"**:
   - Toggle **"Enable VOD Fallback"** to **ON** ✓
3. Click **Save Changes**

✅ **Setup complete!** Your channel will now automatically switch to VOD when the live stream goes offline.

## Configuration Fields

| Field | Purpose | Required? |
|-------|---------|-----------|
| **Link to Channel** | Associates the SRT stream with a channel for VOD playback | Yes (for VOD fallback) |
| **Enable VOD Fallback** | Activates automatic fallback when SRT disconnects | Yes (toggle) |

## Understanding the Flow

### Real-time Monitoring

The dashboard monitors the SRT port every 30 seconds:
- If the port is listening → Stream is **connected** ✓
- If the port stops listening → Stream is **disconnected** ✗ → VOD fallback triggered

### Automatic Failover

When a disconnection is detected:
1. Status changes to `disconnected` in the UI
2. System checks if VOD fallback is enabled for this stream
3. If enabled and a linked channel + VOD files exist:
   - System switches the channel's input to the VOD playlist URL
   - VOD playlist loops continuously
4. Dashboard shows the stream is now in **fallback mode**

### Recovery

When the encoder reconnects:
1. Status changes back to `connected`
2. System automatically resumes live streaming
3. Channel playback switches back to the live RTMP feed

## API Endpoints (for testing)

### Get SRT Stream Status

```bash
curl -H "Authorization: Bearer <token>" \
  http://5.180.182.232:8080/api/admin/srt-streams/api/status
```

Response:
```json
{
  "streams": [
    {
      "id": 1,
      "name": "Compassion TV SRT",
      "stream_id": "compassiontv",
      "port": 9000,
      "status": "connected",
      "enabled": true,
      "listening": true,
      "last_connected_at": "2026-05-22T21:30:00Z"
    }
  ],
  "timestamp": "2026-05-22T21:30:15Z"
}
```

### Get Stream Details (with VOD info)

```bash
curl -H "Authorization: Bearer <token>" \
  http://5.180.182.232:8080/api/admin/srt-streams/api/{id}/details
```

## Testing the Feature

### Test 1: Normal Operation (Live Stream Active)

1. Start your SRT encoder pushing to the stream
2. Go to **Admin → SRT Streams**
3. Verify status shows **"Connected"** ✓
4. Check that channel playback shows live stream (low latency)

### Test 2: Fallback Trigger (Stop the Encoder)

1. Stop your SRT encoder (stop pushing)
2. Wait 30-60 seconds for the health monitor to detect disconnection
3. Refresh the dashboard
4. Verify status changed to **"Disconnected"** ✗
5. Channel should now be playing VOD files on loop
6. Confirm viewers see VOD content (no black screen)

### Test 3: Recovery (Restart Encoder)

1. Restart your SRT encoder
2. Push to the SRT stream again
3. Wait 30-60 seconds
4. Dashboard status should return to **"Connected"** ✓
5. Playback should switch back to live stream automatically

## Troubleshooting

### VOD Fallback Not Triggering

**Symptom:** Stream disconnects but channel doesn't play VOD

**Solutions:**
1. Verify **"Enable VOD Fallback"** is toggled ON in the edit form
2. Verify a **Channel is linked** to the SRT stream
3. Verify **VOD files exist** for the channel (check VOD Library)
4. Check Laravel logs: `storage/logs/laravel-*.log`
   - Look for messages like: `"SRT stream disconnected; switched to VOD fallback"`
5. Restart the stream health monitor service if needed

### VOD Playlist Not Generating

**Symptom:** "Manage VOD Files" button appears but no playlist URL

**Solutions:**
1. Upload at least one VOD file first
2. Wait 5-10 seconds after upload
3. Refresh the page
4. Check that the channel's VOD files are marked as **Active** (toggle in VOD Library)

### Stream Switches Between Live & VOD Too Frequently

**Symptom:** Channel keeps switching between live and VOD

**Causes:**
- Network instability: encoder connection dropping intermittently
- Port check timing issues

**Solutions:**
1. Verify encoder has stable network connection
2. Check logs for port listening state changes
3. Consider increasing the monitor check interval (in config)

## Configuration (Advanced)

### Disable VOD Fallback Globally

Edit `config/services.php`:
```php
'services' => [
    'stream' => [
        'vod_fallback_enabled' => false,  // Set to false to disable all VOD fallback
    ],
],
```

### Adjust Health Monitor Frequency

The health monitor checks SRT stream status every 30 seconds by default. This is controlled by the scheduler in `app/Console/Kernel.php`.

## Best Practices

1. **Upload diverse VOD content**: Mix of full shows, clips, and commercials
2. **Order VOD files logically**: Most important content first
3. **Test fallback**: Simulate encoder failure during off-peak hours
4. **Monitor logs**: Watch `storage/logs/laravel-*.log` for fallback transitions
5. **Backup VOD files**: Store copies of important VOD content elsewhere

## Example: Complete Setup Flow

**Goal:** Make "Compassion TV" SRT stream never go off-air

1. ✅ SRT Stream "Compassion TV" already receiving from encoder on port 9000
2. ✅ Channel "compassiontv" created in the system
3. ✅ Navigate to **Admin → SRT Streams → Compassion TV SRT → Edit**
4. ✅ Link to channel: Select **"compassiontv"**
5. ✅ Click **"📁 Manage VOD Files"**
6. ✅ Upload 3 videos: morning show, afternoon show, evening news
7. ✅ Return to SRT edit form
8. ✅ Toggle **"Enable VOD Fallback"** ON
9. ✅ Click **Save Changes**
10. ✅ Test: Stop encoder → VOD plays → Restart encoder → Back to live

## Support & Logs

For debugging, check:
- **Laravel error log**: `storage/logs/laravel-YYYY-MM-DD.log`
- **SRT runtime log**: `storage/logs/srt-server.log`
- **Admin dashboard**: Status badges show real-time stream state

---

**Last Updated:** May 22, 2026  
**Feature Status:** ✅ Production Ready
