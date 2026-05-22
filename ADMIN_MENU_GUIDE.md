# Admin Dashboard Menu - Complete Guide

## Updated Sidebar Menu

Your admin panel now has a complete menu with all features. Here's what you'll see:

### 🎯 Menu Structure

```
📡 MediaServer
v1.2.0 — Production

━━━━━━━━━━━━━━━━━━━━━━━━━━━
Streaming
━━━━━━━━━━━━━━━━━━━━━━━━━━━
  📺 Channels
     └─ Manage broadcast channels
     └─ Create/edit/delete channels
     └─ VOD library management
     
  📡 SRT Streams ⭐ NEW
     └─ Manage SRT receivers
     └─ Compassion TV (Port 9000)
     └─ SUDFM TV (Port 9001)
     └─ Add new streams
     └─ Real-time monitoring

━━━━━━━━━━━━━━━━━━━━━━━━━━━
Access
━━━━━━━━━━━━━━━━━━━━━━━━━━━
  🔑 Access Codes
     └─ Create client access codes
     └─ Manage redemptions
     
  👥 Users (Admin Only)
     └─ Manage admin users
     └─ Set permissions

━━━━━━━━━━━━━━━━━━━━━━━━━━━
Ingest
━━━━━━━━━━━━━━━━━━━━━━━━━━━
  📡 Ingest Info
     └─ View ingest URLs
     └─ HLS/RTMP/SRT endpoints

━━━━━━━━━━━━━━━━━━━━━━━━━━━
Admin: Basilkewir
━━━━━━━━━━━━━━━━━━━━━━━━━━━
  🔗 API Health
  🚪 Logout
```

## What Each Menu Item Does

### 📺 Channels
**Location:** Streaming → Channels

**Features:**
- View all broadcast channels
- Create new channels
- Edit channel settings
- Delete channels
- Manage VOD library
- Monitor channel statistics

**Quick Access:** `/admin/channels`

---

### 📡 SRT Streams ⭐ NEW
**Location:** Streaming → SRT Streams

**Features:**
- View all SRT stream receivers
- Monitor Compassion TV (Port 9000)
- Monitor SUDFM TV (Port 9001)
- Add new SRT receiving channels
- Edit stream configuration
- View real-time logs
- Check stream status
- Zero-downtime operations

**Quick Access:** `/admin/srt-streams`

**Dashboard Shows:**
- Total streams count
- Active streams count
- Listening ports count
- Stream status (Active, Pending, Error)
- Last connection time
- Bitrate and resolution
- Action buttons (View, Edit, Delete, Logs)

---

### 🔑 Access Codes
**Location:** Access → Access Codes

**Features:**
- Create new access codes
- View code usage statistics
- Manage code redemptions
- Track client access

**Quick Access:** `/admin/access-codes`

---

### 👥 Users (Admin Only)
**Location:** Access → Users

**Features:**
- Manage admin users (admin only)
- Add/remove administrators
- Set user permissions
- View user activity

**Quick Access:** `/admin/users`

**Note:** Only visible if you're logged in as admin

---

### 📡 Ingest Info
**Location:** Ingest → Ingest Info

**Features:**
- View SRT ingest URLs
- View RTMP ingest URLs
- View HTTP/HLS URLs
- Copy URL for encoders
- Protocol information

**Quick Access:** Modal popup (click to view)

---

## Feature Comparison

### Before vs After

```
BEFORE                          AFTER
┌──────────────────┐           ┌──────────────────┐
│ Streaming:       │           │ Streaming:       │
│  • Channels      │    ──→    │  • Channels      │
│                  │           │  • SRT Streams ⭐ │
├──────────────────┤           ├──────────────────┤
│ Access:          │           │ Access:          │
│  • Access Codes  │           │  • Access Codes  │
│  • Users         │           │  • Users         │
├──────────────────┤           ├──────────────────┤
│ Ingest:          │           │ Ingest:          │
│  • Ingest Info   │           │  • Ingest Info   │
└──────────────────┘           └──────────────────┘
```

## Quick Navigation

| Page | Route | Menu Item |
|------|-------|-----------|
| Dashboard | `/admin/` | Logo click |
| Channels | `/admin/channels` | 📺 Channels |
| SRT Streams | `/admin/srt-streams` | 📡 SRT Streams ⭐ |
| Access Codes | `/admin/access-codes` | 🔑 Access Codes |
| Users | `/admin/users` | 👥 Users |

## How to Access SRT Streams

### Method 1: Click Menu Item
1. Log in to admin panel
2. Look for **📡 SRT Streams** under "Streaming" section
3. Click to open dashboard

### Method 2: Direct URL
```
http://your-server-ip/admin/srt-streams
```

### Method 3: From Channels Page
1. Click **📺 Channels**
2. Look for related streams section
3. Click **View SRT Streams** link

## SRT Streams Dashboard Features

Once you access the SRT Streams page, you'll see:

### Top Statistics
```
┌─────────────┬──────────────┬─────────────┬──────────────┐
│   📊 Total  │ ✅ Active    │ 🔊 Listening│ ⏸️ Inactive  │
│   Streams   │   Streams    │   Ports     │   Streams    │
│      2      │      2       │      2      │      0       │
└─────────────┴──────────────┴─────────────┴──────────────┘
```

### Streams Table
```
┌────────────────┬──────┬──────────┬────────┬─────────────┐
│ Stream Name    │ Port │ RTMP     │ Status │ Actions     │
├────────────────┼──────┼──────────┼────────┼─────────────┤
│ Compassion TV  │ 9000 │ compassi │ Active │ 👁️ 📋 ✏️ 🗑️ │
│ SUDFM TV       │ 9001 │ sudfmtv  │ Active │ 👁️ 📋 ✏️ 🗑️ │
└────────────────┴──────┴──────────┴────────┴─────────────┘
```

### Action Buttons

For each stream:
- **👁️ View** - See full details
- **📋 Logs** - View real-time logs
- **✏️ Edit** - Modify configuration
- **🗑️ Delete** - Remove stream

## Keyboard Shortcuts

Use these keyboard shortcuts in the admin panel:

| Shortcut | Action |
|----------|--------|
| `G` then `D` | Go to Dashboard |
| `G` then `C` | Go to Channels |
| `G` then `S` | Go to SRT Streams |
| `G` then `A` | Go to Access Codes |
| `Esc` | Close modals |
| `Ctrl+S` | Submit forms |

## Mobile Responsive Menu

On mobile devices:
- Menu collapses to icons only
- Tap menu icon to expand/collapse
- Full menu appears on tablet size
- All features work on all devices

## Menu Customization

### If Menu Items Don't Show

1. **Refresh the page:**
   ```
   Press: Ctrl+Shift+R (or Cmd+Shift+R on Mac)
   ```

2. **Clear cache:**
   ```bash
   php artisan cache:clear
   php artisan view:clear
   ```

3. **Check permissions:**
   - Verify you're logged in as admin
   - Check if your user has the right role

### If SRT Streams Menu Item Missing

1. **Pull latest code:**
   ```bash
   git pull origin master
   ```

2. **Clear cache:**
   ```bash
   php artisan cache:clear
   ```

3. **Refresh browser:**
   ```
   Ctrl+Shift+R
   ```

## Admin Panel Overview

```
┌─────────────────────────────────────────────────────────┐
│ 📡 MediaServer v1.2.0 — Production                      │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ 📺 Channels ─── Browse & Manage Broadcast Channels ──→ │
│ 📡 SRT Streams ─ Monitor SRT Receivers & Encoders ⭐ → │
│ 🔑 Access Codes  Issue Client Access to Streams    → │
│ 👥 Users ─────── Manage Admin Users (Admin Only)   → │
│ 📡 Ingest Info ─ View Streaming Endpoints          → │
│                                                          │
│                                                          │
│ 📌 Top of page:                                        │
│    ├─ Page title with breadcrumb                      │
│    ├─ "+ New Item" button                             │
│    └─ Help & info section                             │
│                                                          │
│ 📊 Main content area:                                  │
│    ├─ Statistics cards                                │
│    ├─ Data tables                                     │
│    ├─ Action buttons                                  │
│    └─ Filters & search                                │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

## Status Indicators

In the menu and pages, you'll see:

| Indicator | Meaning |
|-----------|---------|
| 🟢 | Active/Online |
| 🟡 | Pending/Standby |
| 🔴 | Error/Offline |
| ✅ | Enabled/Active |
| ⏸️ | Disabled/Inactive |

## Performance Tips

1. **Keep menu updated:** Refresh with `Ctrl+Shift+R`
2. **Use direct URLs:** Faster than clicking through menu
3. **Cache enabled:** First load is slower, subsequent loads fast
4. **Responsive design:** Works on all devices
5. **Search function:** Use browser find (`Ctrl+F`) to search menu

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Menu items missing | Refresh + clear cache |
| SRT Streams not showing | Pull latest, clear cache |
| Can't access pages | Check authentication |
| Slow menu loading | Clear browser cache |
| Mobile menu hidden | Tap menu icon |

## Related Documentation

- **CURRENT_CHANNELS_MANAGEMENT.md** - How to use SRT dashboard
- **ADMIN_DASHBOARD_README.md** - Dashboard overview
- **QUICK_REFERENCE_CHANNELS.md** - Quick commands

---

**Updated:** May 22, 2026  
**Status:** ✅ All Menu Items Visible
**New Feature:** 📡 SRT Streams Management
