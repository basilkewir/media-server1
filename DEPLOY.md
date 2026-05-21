# Deploy MediaServer to Ubuntu VPS

## Step 1: Push to Your GitHub

Run these commands **on your local machine** (in the `media-server` folder):

```bash
# 1. Create a new private repo on GitHub first:
#    https://github.com/new  (name it: mediaserver)

# 2. Add your GitHub repo as remote and push
git remote add origin https://github.com/YOUR_USERNAME/mediaserver.git
git branch -M main
git push -u origin main
```

## Step 2: Deploy on Your VPS

SSH into your Ubuntu VPS and run:

```bash
# SSH into your server (replace with your actual IP/user)
# ssh root@5.180.182.232

# Download and run the installer
curl -fsSL https://raw.githubusercontent.com/YOUR_USERNAME/mediaserver/main/deploy-remote.sh -o deploy-remote.sh
bash deploy-remote.sh
```

Or do it manually:

```bash
# 1. Install dependencies
apt update && apt install -y git nginx mysql-server redis-server ffmpeg php8.3-fpm php8.3-mysql php8.3-redis php8.3-curl php8.3-mbstring php8.3-xml php8.3-zip composer supervisor

# 2. Clone your repo
cd /var/www
git clone https://github.com/YOUR_USERNAME/mediaserver.git mediaserver
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
mysql -e "CREATE DATABASE media_server; CREATE USER 'media_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD'; GRANT ALL ON media_server.* TO 'media_user'@'localhost'; FLUSH PRIVILEGES;"

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

## Step 3: Post-Deploy

| Task | Command |
|------|---------|
| SSL Certificate | `certbot --nginx -d yourdomain.com` |
| View logs | `tail -f /var/www/mediaserver/storage/logs/laravel.log` |
| Restart queue | `supervisorctl restart mediaserver-queue:*` |
| Restart monitor | `supervisorctl restart mediaserver-monitor` |
| Health check | `curl http://yourdomain.com/api/health` |

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
