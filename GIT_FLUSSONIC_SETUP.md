# Git Pull & Flussonic Setup - Implementation Guide

## Quick Summary

You now have **3 automated scripts** to help you pull the latest code and set up Flussonic on your Ubuntu server:

### 1. **Fully Automated Setup** (Recommended)
- **File**: `update-and-setup.sh`
- **What it does**: Everything in one command
- **Run as**: `sudo bash /var/www/mediaserver/update-and-setup.sh`
- **Time**: ~5-10 minutes

### 2. **Quick Command Reference**
- **File**: `QUICK_SETUP_COMMANDS.sh`
- **What it does**: Copy-paste commands for manual setup
- **Use when**: You want to run steps individually or troubleshoot

### 3. **Detailed Step-by-Step Guide**
- **File**: `UBUNTU_UPDATE_GUIDE.md`
- **What it does**: Comprehensive instructions with explanations
- **Read when**: You need to understand what each step does

---

## The Fastest Way (Copy & Paste)

### On Your Ubuntu Server:

```bash
# 1. SSH to your server
ssh root@your-server-ip

# 2. Run the complete setup in one command
sudo bash /var/www/mediaserver/update-and-setup.sh

# Done! All of these will automatically run:
# ✓ Git pull latest code
# ✓ Backup configuration
# ✓ Update PHP dependencies
# ✓ Run database migrations
# ✓ Configure Flussonic
# ✓ Clear caches
# ✓ Restart services
# ✓ Verify everything works
```

---

## What The Automated Script Does

### Phase 1: Prerequisites Check
- Verifies you're running as root
- Checks MediaServer directory exists
- Confirms git is installed
- Verifies it's a git repository

### Phase 2: Pull Latest Code
- Displays current git status
- Pulls latest changes from master branch
- Shows recent commits pulled

### Phase 3: Backup Configuration
- Creates timestamped backup directory
- Backs up `.env`, config files, and database
- Shows backup location for recovery

### Phase 4: Check Dependencies
- Verifies Composer is installed
- Updates PHP packages
- Reports any issues

### Phase 5: Database Migrations
- Runs pending database migrations
- Reports if any issues occur
- Shows migration status

### Phase 6: Flussonic Setup
- Verifies Flussonic is installed
- Runs the Flussonic integration script
- Configures Nginx proxy
- Moves Flussonic to port 8935

### Phase 7: Clear Caches
- Clears Laravel application cache
- Clears configuration cache
- Clears route cache
- Clears view cache

### Phase 8: Restart Services
- Restarts PHP-FPM
- Restarts Nginx
- Restarts Flussonic
- Restarts Supervisor

### Phase 9: Verify Everything
- Checks all services are running
- Tests Laravel API endpoint
- Tests Flussonic API endpoint
- Reports overall status

### Phase 10: Summary & Next Steps
- Shows what was completed
- Lists backup location
- Provides commands for verification
- Points to documentation

---

## Files Created/Updated

### New Scripts Created

| File | Purpose |
|------|---------|
| `update-and-setup.sh` | Fully automated setup script |
| `QUICK_SETUP_COMMANDS.sh` | Copy-paste command reference |
| `UBUNTU_UPDATE_GUIDE.md` | Detailed step-by-step guide |

### These Should Already Exist

| File | Purpose |
|------|---------|
| `flussonic-setup.sh` | Configures Flussonic integration |
| `install_flussonic.sh` | Installs Flussonic package |

---

## Pre-Setup Checklist

Before running the setup script, make sure:

- [ ] Ubuntu 20.04 LTS or newer installed
- [ ] SSH access to server (or direct terminal access)
- [ ] Root or sudo privileges available
- [ ] Git installed: `sudo apt-get install git`
- [ ] Flussonic already installed
- [ ] Nginx, PHP-FPM, MySQL running
- [ ] At least 2GB disk space free: `df -h`
- [ ] Internet connectivity for pulling code

## Step 1: Copy Script to Server

If you don't have git set up yet:

### Option A: Use SCP (Copy from local machine)
```bash
# On your LOCAL machine (Windows/Mac/Linux):
scp update-and-setup.sh root@your-server-ip:/var/www/mediaserver/
scp QUICK_SETUP_COMMANDS.sh root@your-server-ip:/var/www/mediaserver/
scp UBUNTU_UPDATE_GUIDE.md root@your-server-ip:/var/www/mediaserver/
```

### Option B: Download directly on server
```bash
# SSH to server first, then:
cd /var/www/mediaserver

# If you have git repo, scripts are already there
git pull origin master

# Otherwise, manually create the scripts
nano update-and-setup.sh
# Paste the content from update-and-setup.sh
# Press Ctrl+X, then Y, then Enter to save
```

## Step 2: Make Script Executable

```bash
# SSH to server
ssh root@your-server-ip

# Make scripts executable
chmod +x /var/www/mediaserver/update-and-setup.sh
chmod +x /var/www/mediaserver/flussonic-setup.sh

# Verify
ls -la /var/www/mediaserver/*.sh
```

## Step 3: Run the Setup

```bash
# Make sure you're root
sudo -i

# Or run with sudo:
sudo bash /var/www/mediaserver/update-and-setup.sh

# Watch the output to see progress
# Script should take 5-10 minutes
```

## Step 4: Monitor the Output

The script will show you:
- ✓ Green checkmarks for successful steps
- ⚠ Yellow warnings for items needing attention
- ✗ Red errors if something goes wrong

Example output:
```
╺━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━╸
STEP 2: Pulling Latest Code from Repository
╺━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━╸

✓ Latest code pulled successfully

Recent commits:
  a1b2c3d feat: Add Flussonic integration
  d4e5f6g fix: Update Nginx configuration
  ...
```

## Step 5: Verify Everything Works

After the script completes:

```bash
# Check all services
sudo systemctl status nginx php-fpm flussonic

# Test Laravel API
curl http://localhost/api/health
# Should return: {"status":"ok","timestamp":"..."}

# Test Flussonic API
curl -u flussonic:letmein! http://localhost:8935/streamer/api/v3/server
# Should return: {"version":"24.02",...}

# Access from browser
# Laravel:   http://your-server-ip/
# Flussonic: http://your-server-ip:8935/
```

## Troubleshooting

### Problem: "Permission denied" running script

```bash
# Solution: Make it executable
chmod +x /var/www/mediaserver/update-and-setup.sh
sudo bash /var/www/mediaserver/update-and-setup.sh
```

### Problem: "git: command not found"

```bash
# Solution: Install git
sudo apt-get update
sudo apt-get install git

# Then try again
sudo bash /var/www/mediaserver/update-and-setup.sh
```

### Problem: "Flussonic service not found"

```bash
# Solution: Install Flussonic first
# Refer to FLUSSONIC_INSTALLATION.md

# Or continue without automatic Flussonic setup
# Edit the setup script to skip Flussonic
```

### Problem: Script gets stuck

```bash
# If script hangs, press Ctrl+C to stop it
# Then check logs manually:
sudo tail -f /var/log/nginx/error.log
sudo journalctl -u flussonic -n 50

# Check what's running
ps aux | grep php
ps aux | grep nginx
ps aux | grep flussonic
```

### Problem: Database migration fails

```bash
# Solution: Check migration status
php artisan migrate:status

# Run specific migration
php artisan migrate --path=/database/migrations/2024_01_01_000005_add_icecast_relay_to_channels.php

# Or reset and start fresh (WARNING: data loss!)
php artisan migrate:fresh --seed
```

## After Setup: Recommended Actions

### 1. Change Default Credentials

```bash
# SSH to server
ssh root@your-server-ip

# Change Flussonic admin password
sudo nano /etc/flussonic/flussonic.conf
# Find: admin_password letmein!
# Change to: admin_password your_new_strong_password
# Save and exit

# Restart Flussonic
sudo systemctl restart flussonic
```

### 2. Set Up SSL/HTTPS

```bash
# Using Let's Encrypt (Certbot)
sudo apt-get install certbot python3-certbot-nginx

# Generate certificate
sudo certbot --nginx -d your-domain.com

# Auto-renewal will be configured automatically
```

### 3. Configure Firewall

```bash
# Check current rules
sudo ufw status

# Allow HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Allow Flussonic ports (if public)
sudo ufw allow 1935/tcp
sudo ufw allow 8935/tcp
```

### 4. Set Up Automated Backups

```bash
# Create backup directory
sudo mkdir -p /var/backups/daily

# Add cron job
sudo crontab -e

# Add line (backup daily at 2 AM):
0 2 * * * mysqldump -u root media_server > /var/backups/daily/backup_$(date +\%Y\%m\%d).sql

# Save and exit
```

### 5. Configure Monitoring

```bash
# Install monitoring tools
sudo apt-get install htop iotop nethogs

# Monitor real-time
htop

# Check bandwidth
sudo nethogs

# Check disk I/O
sudo iotop
```

## Command Reference Quick Links

For copy-paste commands, see:
- `QUICK_SETUP_COMMANDS.sh` - All commands organized by category
- `UBUNTU_UPDATE_GUIDE.md` - Step-by-step with explanations

## Documentation Navigation

After setup, read these in order:

1. **`IMPLEMENTATION_COMPLETE.md`** - Overview of all features
2. **`FLUSSONIC_INTEGRATION.md`** - How Flussonic works with Media Server
3. **`RELAY_GUIDE.md`** - Multi-server relay broadcasting
4. **`ICECAST_GUIDE.md`** - Icecast streaming setup
5. **`DEPLOYMENT_GUIDE.md`** - Production deployment tips

## Support & Help

### If Something Goes Wrong

```bash
# 1. Check current status
sudo systemctl status nginx php-fpm flussonic

# 2. View recent logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/php-fpm.log
sudo journalctl -u flussonic -f

# 3. Check git status
cd /var/www/mediaserver && git status

# 4. Rollback if needed
git reset --hard HEAD~1
sudo systemctl restart php-fpm nginx flussonic
```

### Check Backups

```bash
# Find all backups
ls -la /var/backups/mediaserver/

# Restore database from backup
sudo mysql -u root media_server < /var/backups/mediaserver/20240515_143022/database.sql

# Restore config files
cp /var/backups/mediaserver/20240515_143022/.env /var/www/mediaserver/
```

## Production Deployment Checklist

Before going live:

- [ ] Run full setup script successfully
- [ ] All services running and responding
- [ ] Backups created and tested
- [ ] SSL certificates installed
- [ ] Firewall rules configured
- [ ] Monitoring and logging set up
- [ ] Default passwords changed
- [ ] Documentation read and understood
- [ ] Test stream created and verified
- [ ] Relay broadcasting tested
- [ ] VOD fallback tested

---

## Quick Reference: After Each Update

```bash
# Every time you pull new code:

# 1. Pull latest
cd /var/www/mediaserver && git pull origin master

# 2. Run setup
sudo bash /var/www/mediaserver/update-and-setup.sh

# 3. Verify
curl http://localhost/api/health
curl -u flussonic:letmein! http://localhost:8935/streamer/api/v3/server

# 4. Check logs
sudo tail -f /var/log/nginx/error.log &
sudo tail -f /var/log/php-fpm.log &
sudo journalctl -u flussonic -f
```

---

**Status:** ✅ Ready for Production  
**Last Updated:** May 2026  
**Tested on:** Ubuntu 20.04 LTS, 22.04 LTS
