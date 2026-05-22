# SRT VOD Fallback Setup - Quick Checklist

## Your SRT Streams

- ✓ **Compassion TV** (SRT port 9000)
- ✓ **SUDFM TV** (SRT port 9001)

Both are currently live and receiving streams. This checklist shows how to set up VOD fallback for each.

---

## Setup for Compassion TV

### ☐ Step 1: Link to Channel
1. Go to **Admin → SRT Streams**
2. Click **Edit** on "Compassion TV"
3. Under **"VOD Standby Playlist (Never Off-Air)"**
4. Select channel: **"main"** (or your Compassion TV channel)
5. Click **Save Changes**

### ☐ Step 2: Upload VOD Content
1. Click **"📁 Manage VOD Files"** button (or go to **Admin → Channels → main → VOD Library**)
2. Upload video files (MP4, MKV, MOV, etc.)
   - Example: `compassion_tv_intro.mp4`, `compassion_tv_loop_1h.mp4`, `compassion_tv_bumper.mp4`
3. Reorder files if desired (top to bottom = play order)

### ☐ Step 3: Enable Fallback
1. Return to SRT edit form
2. Toggle **"Enable VOD Fallback"** to **ON** ✓
3. Click **Save Changes**

### ✅ Compassion TV is now protected!

---

## Setup for SUDFM TV

### ☐ Step 1: Link to Channel
1. Go to **Admin → SRT Streams**
2. Click **Edit** on "SUDFM TV"
3. Under **"VOD Standby Playlist (Never Off-Air)"**
4. Select channel: **"sudfmtv"** (or your SUDFM TV channel)
5. Click **Save Changes**

### ☐ Step 2: Upload VOD Content
1. Click **"📁 Manage VOD Files"** button
2. Upload video files for SUDFM (MP4, MKV, MOV, etc.)
   - Example: `sudfm_jingles.mp4`, `sudfm_music_loop_2h.mp4`
3. Reorder files if desired

### ☐ Step 3: Enable Fallback
1. Return to SRT edit form
2. Toggle **"Enable VOD Fallback"** to **ON** ✓
3. Click **Save Changes**

### ✅ SUDFM TV is now protected!

---

## Testing Each Stream

### Compassion TV Test
1. **Verify live stream is active**
   - Dashboard shows: `Status: Connected ✓`
   - Bitrate: ~1200+ kbps
2. **Stop encoder** (pause the SRT push)
3. **Wait 30-60 seconds** (health monitor detects disconnection)
4. **Check dashboard**
   - Status should show: `Status: Disconnected ✗`
   - Channel playback should show: VOD content looping
5. **Restart encoder**
6. **Wait 30-60 seconds** (health monitor detects reconnection)
7. **Verify playback switches back to live**

### SUDFM TV Test
1. Repeat same steps for SUDFM TV (port 9001)

---

## Dashboard Status Reference

| Status | Meaning | Action |
|--------|---------|--------|
| 🟢 **Connected** | Live SRT stream is active | All good! |
| 🟡 **Pending** | Stream hasn't been tested yet | No action needed |
| 🔴 **Disconnected** | Encoder is offline; VOD fallback active | Check encoder |
| 🔴 **Error** | Stream has encountered an error | Check logs |

---

## Monitor & Verify

### Via Dashboard
1. Go to **Admin → SRT Streams**
2. Check each stream's:
   - Status badge (color)
   - Bitrate (kbps)
   - Last connected time

### Via Logs (If Issues)
1. SSH into server: `ssh root@5.180.182.232`
2. Check Laravel log: `tail -f /var/www/mediaserver/storage/logs/laravel-2026-05-22.log`
3. Look for messages like:
   - `"SRT stream disconnected; switched to VOD fallback"`
   - `"VOD fallback FFmpeg died, restarting"` (normal if briefly offline)

### Via API
```bash
# Get current status
curl -s http://5.180.182.232:8080/api/admin/srt-streams/api/status | jq .
```

---

## Troubleshooting

### "VOD Fallback disabled" toggle is grayed out?
- **Solution:** You must select a **Channel** first in the dropdown above

### VOD files uploaded but no playlist URL appears?
- **Solution:** At least one file must be marked **Active** (toggle it in the VOD Library)

### Channel keeps switching between live & VOD?
- **Cause:** Encoder has unstable network; disconnecting frequently
- **Solution:** Fix encoder connection or reduce monitor interval

### Status shows "Disconnected" but encoder is still pushing?
- **Cause:** Port might be blocked by firewall or wrong port configured
- **Solution:** Verify port 9000/9001 are open and encoder is pushing correctly

---

## Next Steps (Optional)

### Real-time Bitrate Monitoring
- Bitrate updates every time dashboard refreshes
- Current implementation parses `/var/www/mediaserver/storage/logs/srt-server.log`
- If you want **truly instantaneous** bitrate, you can:
  - Add a dedicated `/api/srt-streams/{id}/live-bitrate` endpoint
  - Poll it every 2-5 seconds for near-real-time updates

### Advanced: Custom Monitor Interval
- Edit `app/Console/Kernel.php` to change the health check frequency
- Default: every 30 seconds

---

## Support

📖 **Full Guide:** `SRT_VOD_STANDBY_GUIDE.md` (in repo root)

📊 **Dashboard:** `http://5.180.182.232:8080/admin/srt-streams`

🔧 **Logs:** `/var/www/mediaserver/storage/logs/laravel-*.log`

---

**Checklist Version:** 1.0  
**Last Updated:** May 22, 2026  
**Status:** ✅ Ready to Use
