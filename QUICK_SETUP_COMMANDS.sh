#!/bin/bash
# ============================================================
#  QUICK COMMAND REFERENCE - Git Pull & Flussonic Setup
#  Copy and paste commands as needed
# ============================================================

# ============================================================
# OPTION 1: Fully Automated (Recommended)
# ============================================================

# Run everything in one command:
sudo bash /var/www/mediaserver/update-and-setup.sh


# ============================================================
# OPTION 2: Step-by-Step Manual
# ============================================================

# 1. SSH to server
ssh root@your-server-ip

# 2. Navigate to directory
cd /var/www/mediaserver

# 3. Check git status
git status

# 4. Pull latest code
git pull origin master

# 5. Backup configuration
sudo mkdir -p /var/backups/mediaserver/$(date +%Y%m%d_%H%M%S)
sudo mysqldump -u root media_server > /var/backups/mediaserver/$(date +%Y%m%d_%H%M%S)/database.sql

# 6. Update dependencies
sudo composer install --no-interaction --optimize-autoloader

# 7. Run migrations
sudo php artisan migrate --force

# 8. Run Flussonic setup
sudo bash flussonic-setup.sh

# 9. Clear caches
sudo php artisan cache:clear
sudo php artisan config:clear
sudo php artisan route:clear

# 10. Restart services
sudo systemctl restart php-fpm nginx flussonic supervisor


# ============================================================
# OPTION 3: Individual Commands (if troubleshooting)
# ============================================================

# Check git remote
git remote -v

# Add git remote (if needed)
git remote add origin https://github.com/your-username/media-server.git

# Fetch code (without merging)
git fetch origin

# View branches
git branch -a

# View recent commits
git log --oneline -10

# Pull specific branch
git pull origin master

# Check for uncommitted changes
git status

# Stash uncommitted changes (if blocking pull)
git stash

# Reset to latest remote version
git reset --hard origin/master


# ============================================================
# VERIFICATION COMMANDS
# ============================================================

# Check service status
sudo systemctl status php-fpm
sudo systemctl status nginx
sudo systemctl status flussonic
sudo systemctl status supervisor

# Test Laravel API
curl http://localhost/api/health

# Test Flussonic API
curl -u flussonic:letmein! http://localhost:8935/streamer/api/v3/server

# View nginx errors
sudo tail -f /var/log/nginx/error.log

# View PHP-FPM errors
sudo tail -f /var/log/php-fpm.log

# View Flussonic logs
sudo journalctl -u flussonic -f

# Check file permissions
ls -la /var/www/mediaserver/storage

# Check database connection
php artisan tinker
# Type: exit

# View Laravel logs
tail -f /var/www/mediaserver/storage/logs/laravel.log


# ============================================================
# TROUBLESHOOTING COMMANDS
# ============================================================

# Restart all services
sudo systemctl restart php-fpm nginx flussonic supervisor

# Restart individual services
sudo systemctl restart nginx
sudo systemctl restart php-fpm
sudo systemctl restart flussonic

# Stop service
sudo systemctl stop flussonic

# Start service
sudo systemctl start flussonic

# Check if service is active
sudo systemctl is-active flussonic

# Enable service on boot
sudo systemctl enable flussonic

# Disable service on boot
sudo systemctl disable flussonic

# View service unit file
sudo systemctl cat flussonic

# Check listening ports
sudo netstat -tlnp | grep nginx
sudo netstat -tlnp | grep php-fpm
sudo netstat -tlnp | grep flussonic

# Kill process by port
sudo lsof -ti :80 | xargs kill -9
sudo lsof -ti :1935 | xargs kill -9
sudo lsof -ti :8935 | xargs kill -9

# Check disk space
df -h

# Check memory usage
free -h

# Check CPU usage
top -b -n 1 | head -20


# ============================================================
# BACKUP & RESTORE COMMANDS
# ============================================================

# Create full backup
sudo tar -czf /var/backups/mediaserver_full_$(date +%Y%m%d_%H%M%S).tar.gz \
  /var/www/mediaserver \
  /etc/flussonic \
  /var/log/flussonic

# List backups
ls -lah /var/backups/mediaserver*/

# Backup database only
sudo mysqldump -u root --all-databases > /var/backups/all_databases_$(date +%Y%m%d).sql

# Restore database
sudo mysql -u root < /var/backups/all_databases_20240515.sql

# Find backup by date
find /var/backups -name "*20240515*" -type f


# ============================================================
# LOG VIEWING COMMANDS
# ============================================================

# Show last 50 lines of Flussonic log
sudo journalctl -u flussonic -n 50

# Follow Flussonic logs in real-time
sudo journalctl -u flussonic -f

# Show errors only
sudo journalctl -u flussonic -p err

# Show logs from last hour
sudo journalctl -u flussonic --since "1 hour ago"

# View system logs
sudo dmesg | tail -20

# View auth logs
sudo tail -f /var/log/auth.log


# ============================================================
# GIT ADVANCED COMMANDS
# ============================================================

# Show diff between local and remote
git diff origin/master

# Show what commits are ahead/behind
git log --oneline origin/master..HEAD

# Revert to specific commit
git reset --hard abc123def

# Create new branch
git checkout -b feature/my-feature

# List all branches
git branch -a

# Delete local branch
git branch -d branch-name

# Delete remote branch
git push origin --delete branch-name

# View commit history
git log --oneline --all --graph

# Show who changed what
git blame app/Http/Controllers/StreamController.php


# ============================================================
# COMPOSER COMMANDS
# ============================================================

# Install dependencies
composer install

# Update dependencies
composer update

# Add new package
composer require package-name

# Remove package
composer remove package-name

# List installed packages
composer show

# Update composer itself
composer self-update

# Clear composer cache
composer clear-cache

# Optimize autoloader
composer dump-autoload --optimize


# ============================================================
# LARAVEL COMMANDS
# ============================================================

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Refresh database (reset + migrate)
php artisan migrate:fresh

# Check migration status
php artisan migrate:status

# Clear application cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Clear view cache
php artisan view:clear

# Optimize application
php artisan optimize

# List all routes
php artisan route:list

# Run tinker REPL
php artisan tinker

# Generate app key
php artisan key:generate

# Seed database
php artisan db:seed

# Make new migration
php artisan make:migration migration_name


# ============================================================
# FILE PERMISSION COMMANDS
# ============================================================

# Fix Laravel permissions
sudo chown -R www-data:www-data /var/www/mediaserver
sudo chmod -R 755 /var/www/mediaserver
sudo chmod -R 775 /var/www/mediaserver/storage
sudo chmod -R 775 /var/www/mediaserver/bootstrap/cache

# Fix Flussonic permissions
sudo chown -R flussonic:flussonic /var/cache/flussonic
sudo chown -R flussonic:flussonic /var/log/flussonic
sudo chmod -R 755 /etc/flussonic

# Check current permissions
ls -la /var/www/mediaserver
ls -la /var/www/mediaserver/storage
ls -la /etc/flussonic


# ============================================================
# NGINX COMMANDS
# ============================================================

# Test Nginx configuration
sudo nginx -t

# Reload Nginx (without dropping connections)
sudo systemctl reload nginx

# Full restart Nginx
sudo systemctl restart nginx

# View Nginx configuration
sudo cat /etc/nginx/sites-available/mediaserver

# Edit Nginx configuration
sudo nano /etc/nginx/sites-available/mediaserver

# View active Nginx processes
ps aux | grep nginx


# ============================================================
# MYSQL COMMANDS
# ============================================================

# Connect to MySQL
mysql -u root -p

# List databases
mysql -u root -p -e "SHOW DATABASES;"

# Show tables in database
mysql -u root -p media_server -e "SHOW TABLES;"

# Backup database
mysqldump -u root -p media_server > backup.sql

# Restore database
mysql -u root -p media_server < backup.sql

# Create database user
mysql -u root -p -e "CREATE USER 'media_user'@'localhost' IDENTIFIED BY 'password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON media_server.* TO 'media_user'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"


# ============================================================
# MONITORING COMMANDS
# ============================================================

# Real-time system monitor
top

# Process monitor with search
ps aux | grep flussonic

# Network connections
netstat -tlnp

# Disk usage
du -sh /var/www/mediaserver
du -sh /var/cache/flussonic

# Memory usage
free -h

# Load average
uptime

# CPU information
lscpu

# Disk information
lsblk


# ============================================================
# DEPLOYMENT COMMANDS
# ============================================================

# Create maintenance file
sudo touch /var/www/mediaserver/storage/framework/down

# Remove maintenance file
sudo rm /var/www/mediaserver/storage/framework/down

# Queue statistics
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Flush queue
php artisan queue:flush

# Monitor queue
watch -n 5 'php artisan queue:failed'


# ============================================================
# DEVELOPMENT COMMANDS
# ============================================================

# Run tests
php artisan test

# Run PHPStan analysis
./vendor/bin/phpstan analyse

# Run Laravel Pint formatter
./vendor/bin/pint

# Generate IDE helper
php artisan ide-helper:generate

# Tinker interactive shell
php artisan tinker


# ============================================================
# PRODUCTION SAFETY CHECKS
# ============================================================

# Before running any updates:

# 1. Create backup
sudo mysqldump -u root media_server > /var/backups/pre_update.sql && echo "Backup created"

# 2. Check git status
git status

# 3. View changes being pulled
git diff origin/master

# 4. Check disk space (need at least 1GB free)
df -h /var/www/mediaserver

# 5. Verify services are running
sudo systemctl is-active php-fpm nginx flussonic && echo "All services running"

# 6. Create snapshot (if using LVM or cloud snapshots)
# Example for AWS: aws ec2 create-snapshot --volume-id vol-xxxxx

# 7. Run pull and setup
sudo bash /var/www/mediaserver/update-and-setup.sh

# 8. Verify everything
curl http://localhost/api/health
curl -u flussonic:letmein! http://localhost:8935/streamer/api/v3/server

# 9. Monitor logs
sudo tail -f /var/log/nginx/error.log &
sudo tail -f /var/log/php-fpm.log &
sudo journalctl -u flussonic -f


# ============================================================
# EMERGENCY COMMANDS
# ============================================================

# If something goes wrong, run these in order:

# 1. Check what's wrong
sudo systemctl status php-fpm
sudo tail -f /var/log/nginx/error.log

# 2. Try restart
sudo systemctl restart php-fpm nginx flussonic

# 3. Check logs again
sudo journalctl -u flussonic -n 50

# 4. If still broken, rollback
cd /var/www/mediaserver
git reset --hard HEAD~1
sudo systemctl restart php-fpm nginx

# 5. Restore database if needed
sudo mysql -u root media_server < /var/backups/pre_update.sql

# 6. Notify users
echo "System recovered - checking everything now..."
curl http://localhost/api/health
curl -u flussonic:letmein! http://localhost:8935/streamer/api/v3/server

# ============================================================
# End of Quick Reference
# ============================================================
