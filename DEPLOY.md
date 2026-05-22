# Deploy MediaServer to Ubuntu VPS

This guide covers deploying the MediaServer Laravel app to an Ubuntu server using a **git push/pull workflow**. The Ubuntu server does **not** need its own GitHub account.

---

## Step 1: Push to GitHub (Local Machine)

Run these commands **on your local machine** inside the `media-server` folder:

```bash
# 1. Create a new private repo on GitHub:
#    https://github.com/new  (name it: media-server)

# 2. Add your GitHub repo as remote and push
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO.git
git branch -M main
git push -u origin main
```

---

## Step 2: Give the Server Access to the Repo (No Git Account Needed)

The server needs to pull code but should **not** use a personal GitHub account. Choose one method:

### Method A: GitHub Deploy Key (Recommended — Most Secure)

A deploy key is an SSH key attached to a single repo. The server holds the private key; GitHub holds the public key.

**On your Ubuntu server:**
```bash
ssh-keygen -t ed25519 -C "mediaserver-deploy" -f /root/.ssh/mediaserver_deploy -N ""
cat /root/.ssh/mediaserver_deploy.pub
```

**On GitHub:**
1. Go to **Repo Settings → Deploy keys → Add deploy key**
2. Paste the public key (`mediaserver_deploy.pub`)
3. ✅ **Allow write access** ONLY if you plan to push from the server (usually not needed)
4. Click **Add key**

**Back on the server, test and clone:**
```bash
# Add GitHub to known hosts
ssh-keyscan -t ed25519 github.com >> /root/.ssh/known_hosts

# Clone using the deploy key
GIT_SSH_COMMAND='ssh -i /root/.ssh/mediaserver_deploy -o IdentitiesOnly=yes' \
  git clone git@github.com:YOUR_USERNAME/YOUR_REPO.git /var/www/mediaserver
```

For future pulls:
```bash
cd /var/www/mediaserver
GIT_SSH_COMMAND='ssh -i /root/.ssh/mediaserver_deploy -o IdentitiesOnly=yes' git pull
```

---

### Method B: HTTPS with Fine-Grained Personal Access Token (PAT)

If you prefer not to use SSH, create a token on GitHub and embed it in the clone URL.

**On GitHub:**
1. Go to **Settings → Developer settings → Personal access tokens → Fine-grained tokens**
2. Generate a new token with **Contents: Read-only** access to this repo.

**On your Ubuntu server:**
```bash
# Replace TOKEN with your actual token
git clone https://TOKEN@github.com/YOUR_USERNAME/YOUR_REPO.git /var/www/mediaserver
```

> ⚠️ The token will be visible in the server's bash history. To avoid this, set it as an env var first:
> ```bash
> read -s GITHUB_TOKEN
> # (paste token, press Enter)
> git clone https://${GITHUB_TOKEN}@github.com/YOUR_USERNAME/YOUR_REPO.git /var/www/mediaserver
> unset GITHUB_TOKEN
> ```

---

## Step 3: Run the Automated Deploy Script

Copy `deploy-to-ubuntu.sh` to your server and run it as root:

```bash
# From your local machine
scp deploy-to-ubuntu.sh root@YOUR_SERVER_IP:/root/

# SSH into the server
ssh root@YOUR_SERVER_IP

# Set your repo URL (use SSH or HTTPS depending on method above)
export REPO_URL="git@github.com:YOUR_USERNAME/YOUR_REPO.git"
# OR for HTTPS:
# export REPO_URL="https://TOKEN@github.com/YOUR_USERNAME/YOUR_REPO.git"

bash deploy-to-ubuntu.sh
```

The script will:
1. Update system packages
2. Install PHP 8.3, Nginx, MySQL, Redis, FFmpeg, Supervisor
3. Clone/pull your repo
4. Install PHP dependencies
5. Create `.env`, run migrations, and seed demo data
6. Configure Nginx and Supervisor
7. Open firewall ports (22, 80, 443, 1935, 8000)

---

## Step 4: Manual Deploy (Alternative)

If you prefer to run each step manually:

```bash
# 1. Install dependencies
apt update && apt install -y git nginx mysql-server redis-server ffmpeg \
  php8.3-fpm php8.3-mysql php8.3-redis php8.3-curl php8.3-mbstring \
  php8.3-xml php8.3-zip composer supervisor

# 2. Clone your repo (use your chosen method from Step 2)
cd /var/www
git clone git@github.com:YOUR_USERNAME/YOUR_REPO.git mediaserver
cd mediaserver

# 3. Install PHP dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Set permissions
chown -R www-data:www-data /var/www/mediaserver
chmod -R 775 storage bootstrap/cache

# 5. Create .env
cp .env.example .env
php artisan key:generate

# 6. Create MySQL database
mysql -e "CREATE DATABASE media_server; \
  CREATE USER 'media_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD'; \
  GRANT ALL ON media_server.* TO 'media_user'@'localhost'; \
  FLUSH PRIVILEGES;"

# 7. Update .env DB credentials, then run migrations
nano .env
php artisan migrate --force
php artisan db:seed --force

# 8. Generate API token
php artisan api:token:generate "Production Admin"

# 9. Configure Nginx (see nginx.conf.example)
cp nginx.conf.example /etc/nginx/sites-available/mediaserver
ln -s /etc/nginx/sites-available/mediaserver /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx

# 10. Start workers
supervisorctl reread && supervisorctl update && supervisorctl start all
```

---

## Step 5: Post-Deploy

| Task | Command |
|------|---------|
| SSL Certificate | `certbot --nginx -d yourdomain.com` |
| View logs | `tail -f /var/www/mediaserver/storage/logs/laravel.log` |
| Restart queue | `supervisorctl restart mediaserver-queue:*` |
| Restart monitor | `supervisorctl restart mediaserver-monitor` |
| Health check | `curl http://yourdomain.com/api/health` |

---

## Admin Panel

After deployment, open your browser to:

- **Channels Admin**: `http://yourdomain.com/admin/channels`
- **Access Codes**: `http://yourdomain.com/admin/access-codes`

From the Channels admin you can:
- **Create** multiple streaming channels
- **Edit** channel settings (VOD fallback, RTMP push, resolution, bitrate)
- **Start / Stop** live streams per channel
- **Switch** to VOD fallback manually
- **Recover** back to live streaming
- **View** stream status, HLS/DASH URLs, and event logs

---

## Updating the Server After Code Changes

When you make local changes and push to GitHub:

```bash
# On your local machine
git add .
git commit -m "Your changes"
git push origin main
```

**On the Ubuntu server, use the provided `update.sh` script:**

```bash
cd /var/www/mediaserver

# Set your repo URL (same as during deploy)
export REPO_URL="git@github.com:YOUR_USERNAME/YOUR_REPO.git"

# Run the update
bash update.sh
```

This script will safely:
1. Pull the latest code from git
2. Back up your `.env` file (just in case)
3. Install PHP dependencies (`composer install`)
4. Run database migrations
5. Clear and rebuild Laravel caches
6. Fix file permissions
7. Restart Supervisor workers
8. Run a health check

**It will NOT overwrite your `.env`, configs, or any existing data.**

If you prefer to update manually:

```bash
cd /var/www/mediaserver
# If using deploy key:
GIT_SSH_COMMAND='ssh -i /root/.ssh/mediaserver_deploy -o IdentitiesOnly=yes' git pull
# If using HTTPS:
git pull

composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan cache:clear
php artisan config:cache
supervisorctl restart all
```

---

## API Token

After deployment, generate a token:

```bash
cd /var/www/mediaserver
php artisan api:token:generate "My App"
```

Use the token in all API requests:
```
Authorization: Bearer YOUR_TOKEN_HERE
```
