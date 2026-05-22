# 🚀 Quick Ubuntu Setup - 3 Minutes to Dashboard

## Step 1: Install Git (30 seconds)

SSH to your Ubuntu server and run:

```bash
sudo apt-get update && sudo apt-get install -y git
```

## Step 2: Pull Code (1 minute)

```bash
cd /var/www/mediaserver
git pull origin master
```

## Step 3: Import Channels (1 minute)

```bash
php artisan srt:import-existing-channels
php artisan cache:clear
```

## Done! 🎉

Your dashboard is ready:

**URL:** `http://your-server-ip/admin/srt-streams`

---

## If You Need More Help

- **Full Setup Guide:** See `UBUNTU_GIT_SETUP.md`
- **Dashboard Guide:** See `CURRENT_CHANNELS_MANAGEMENT.md`
- **Troubleshooting:** See `QUICK_REFERENCE_CHANNELS.md`

## Common Issues

### "git: command not found"
→ Run: `sudo apt-get install -y git`

### "Permission denied"
→ Run: `sudo chown -R www-data:www-data /var/www/mediaserver`

### "Cannot connect to database"
→ Run: `php artisan migrate`

---

**Time Required:** 3-5 minutes  
**Status:** ✅ Ready to Deploy
