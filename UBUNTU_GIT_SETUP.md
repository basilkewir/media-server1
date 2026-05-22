# Ubuntu Server Setup Guide - Git Installation & Dashboard Deployment

## Quick Setup (Copy-Paste Ready)

### Step 1: SSH to Your Ubuntu Server

```bash
ssh root@5.180.182.232
```

### Step 2: Install Git (One Command)

```bash
sudo apt-get update && sudo apt-get install -y git
```

**Expected output:**
```
Get:1 http://security.ubuntu.com/ubuntu focal-security InRelease [114 kB]
...
Setting up git (1:2.25.1-1ubuntu3.11) ...
Processing triggers for man-db (2.9.1-1) ...
```

### Step 3: Verify Git Installation

```bash
git --version
```

**Expected output:**
```
git version 2.25.1 (or higher)
```

## Full Installation Walkthrough

### 1. Update Ubuntu Packages

```bash
# Update package list
sudo apt-get update

# Optional: Upgrade all packages
sudo apt-get upgrade -y
```

**This may take 1-2 minutes on first run.**

### 2. Install Git

```bash
# Install git package
sudo apt-get install -y git

# Verify installation
git --version
```

### 3. Configure Git (Recommended)

```bash
# Set your name
git config --global user.name "Media Server Admin"

# Set your email
git config --global user.email "admin@mediaserver.local"

# Verify configuration
git config --global --list
```

### 4. Navigate to Media Server Directory

```bash
# Go to media server directory
cd /var/www/mediaserver

# Check if directory exists
ls -la
```

### 5. Clone or Pull Repository

**Option A: If directory is empty, clone the repository:**

```bash
cd /var/www
git clone https://github.com/basilkewir/media-server1.git mediaserver
cd mediaserver
```

**Option B: If directory already has code, pull latest:**

```bash
cd /var/www/mediaserver
git pull origin master
```

**Expected output:**
```
remote: Enumerating objects: 25, done.
remote: Counting objects: 100% (25/25), done.
remote: Compressing objects: 100% (15/15), done.
remote: Total 25 (delta 11), reused 0 (delta 0), pack-reused 0
Unpacking objects: 100% (25/25), done.
From https://github.com/basilkewir/media-server1
   cf366c2..a3c3591  master     -> origin/master
Already up to date.
```

### 6. Deploy the Dashboard

```bash
# Navigate to media server
cd /var/www/mediaserver

# Run the import command
php artisan srt:import-existing-channels

# Clear Laravel cache
php artisan cache:clear
php artisan view:clear
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

## One-Liner Setup (Copy-Paste)

If you want to do everything in one go:

```bash
sudo apt-get update && sudo apt-get install -y git && cd /var/www/mediaserver && git pull origin master && php artisan srt:import-existing-channels && php artisan cache:clear && php artisan view:clear && echo "✅ Setup complete!"
```

## Troubleshooting

### Issue: "git: command not found"

**Solution:** Git is not installed. Run:
```bash
sudo apt-get install -y git
```

### Issue: "fatal: not a git repository"

**Solution:** You're not in the media server directory. Run:
```bash
cd /var/www/mediaserver
git status
```

### Issue: "fatal: Unable to read current working directory"

**Solution:** Directory doesn't exist. Create it:
```bash
sudo mkdir -p /var/www/mediaserver
sudo chown -R www-data:www-data /var/www/mediaserver
cd /var/www/mediaserver
```

### Issue: "Permission denied (publickey)"

**Solution:** You need to set up SSH keys. For now, use HTTPS:
```bash
git clone https://github.com/basilkewir/media-server1.git .
```

### Issue: "fatal: destination path '.' already exists and is not an empty directory"

**Solution:** Clone into the media-server directory:
```bash
cd /var/www
rm -rf mediaserver
git clone https://github.com/basilkewir/media-server1.git mediaserver
cd mediaserver
```

## Verify Everything Works

### Check Git Status

```bash
cd /var/www/mediaserver
git status
```

**Expected output:**
```
On branch master
Your branch is up to date with 'origin/master'.

nothing to commit, working tree clean
```

### Check Laravel Installation

```bash
php artisan --version
```

**Expected output:**
```
Laravel Framework 10.x.x
```

### Check SRT Streams in Database

```bash
php artisan tinker
>>> App\Models\SrtStream::all();
```

**Expected output:**
```
=> Illuminate\Database\Eloquent\Collection {#3572
     all: [
       App\Models\SrtStream {#3573
         id: 1,
         name: "Compassion TV",
         stream_id: "compassiontv",
         srt_port: 9000,
         ...
       },
       App\Models\SrtStream {#3574
         id: 2,
         name: "SUDFM TV",
         stream_id: "sudfmtv",
         srt_port: 9001,
         ...
       },
     ],
   }
>>> exit
```

## Access the Dashboard

Once setup is complete, access the dashboard:

**URL:** `http://your-server-ip/admin/srt-streams`

You should see:
- ✅ Compassion TV (Port 9000)
- ✅ SUDFM TV (Port 9001)
- Real-time statistics
- Stream status and logs

## Common Git Commands

```bash
# Check current branch
git branch

# View recent commits
git log --oneline -10

# Check for updates
git fetch origin

# Pull latest changes
git pull origin master

# Check status
git status

# View differences
git diff
```

## Next Steps

1. **Verify Setup:**
   ```bash
   git status
   php artisan tinker
   >>> App\Models\SrtStream::count();
   ```

2. **Access Dashboard:**
   - Open: `http://your-server-ip/admin/srt-streams`
   - Both channels should be visible

3. **Monitor Streams:**
   - Click on each stream for details
   - View logs in real-time
   - Check status and bitrate

4. **Manage Streams:**
   - Edit configuration
   - Enable/disable streams
   - Delete if needed

## Performance Optimization (Optional)

After deploying, optimize Laravel for production:

```bash
# Optimize autoloader
composer install --no-dev --optimize-autoloader

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Restart web server
sudo systemctl restart php-fpm
sudo systemctl restart nginx
```

## Update in Future

To pull the latest updates in the future:

```bash
cd /var/www/mediaserver
git pull origin master
php artisan cache:clear
```

---

## Quick Reference

| Task | Command |
|------|---------|
| Install Git | `sudo apt-get install -y git` |
| Clone Repo | `git clone https://github.com/basilkewir/media-server1.git .` |
| Pull Latest | `git pull origin master` |
| Import Channels | `php artisan srt:import-existing-channels` |
| Clear Cache | `php artisan cache:clear` |
| Check Status | `git status` |
| View Logs | `git log --oneline -10` |
| Access Dashboard | `http://your-server-ip/admin/srt-streams` |

---

**Created:** May 22, 2026  
**Status:** ✅ Ready to Use  
**Deployment Time:** 5 minutes

