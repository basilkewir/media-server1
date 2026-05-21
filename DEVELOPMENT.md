# Development Guide

## Local Development Setup

### Prerequisites
- PHP 8.2+
- Composer
- Docker & Docker Compose (optional)
- Git

### Setup

```bash
# Clone repository
git clone https://github.com/your-org/media-server.git
cd media-server

# Install dependencies
composer install

# Create environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Create symbolic link to storage
php artisan storage:link
```

### Using Docker for Development

```bash
# Start services
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# Seed database
docker-compose exec app php artisan db:seed

# Access application
# http://localhost
```

### Using Docker with Specific Port

```bash
# Modify docker-compose.yml to map different ports
# Then run:
docker-compose up -d
```

## Development Commands

### Database Management

```bash
# Create tables
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Seed with test data
php artisan db:seed

# Fresh migration (careful!)
php artisan migrate:fresh --seed
```

### Stream Testing

```bash
# Create test channel
php artisan tinker
> Channel::create(['name' => 'Test', 'slug' => 'test'])

# Test stream monitor
php artisan stream:monitor

# Test queue worker
php artisan queue:work
```

### Cache Management

```bash
# Clear all cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear
```

## Testing

### Run Tests

```bash
# All tests
php artisan test

# Specific test
php artisan test --filter=StreamingServiceTest

# With coverage
php artisan test --coverage
```

### Manual Testing

#### 1. Test Channel Creation

```bash
# Via API
curl -X POST http://localhost/api/channels \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Channel",
    "slug": "test",
    "vod_playlist_url": "https://example.com/test.m3u8"
  }'
```

#### 2. Test Stream Start/Stop

```bash
# Start stream
curl -X POST http://localhost/api/streams/start \
  -H "Content-Type: application/json" \
  -d '{
    "channel_id": 1,
    "push_url": "rtmp://source/live/test"
  }'

# Check status
curl http://localhost/api/streams/1/status

# Stop stream
curl -X POST http://localhost/api/streams/stop \
  -H "Content-Type: application/json" \
  -d '{"channel_id": 1}'
```

#### 3. Test VOD Fallback

```bash
# Trigger fallback
curl -X POST http://localhost/api/streams/fallback \
  -H "Content-Type: application/json" \
  -d '{"channel_id": 1}'

# Check stream events
curl http://localhost/api/channels/1/events
```

## Code Style

### PHP Code Style

Follow PSR-12 standards:

```bash
# Format code
php artisan pint

# Check code style
./vendor/bin/pint --test
```

### Database Naming Conventions

- Table names: snake_case, plural
- Column names: snake_case
- Foreign keys: {table}_id
- Indexes: idx_{table}_{column}

### Model Conventions

- Model names: PascalCase, singular
- File location: app/Models/
- Include relationships, scopes, and helpers

## Debugging

### Using Laravel Debugbar

```bash
# Install debugbar (optional)
composer require barryvdh/laravel-debugbar --dev

# It will appear in local environment
```

### Using Tinker (Laravel REPL)

```bash
php artisan tinker

# Try some commands:
> Channel::all()
> Channel::where('slug', 'test')->first()
> Stream::latest()->first()
> StreamEvent::recent()->get()
```

### Viewing Logs

```bash
# Real-time log
tail -f storage/logs/laravel.log

# Recent logs
head -100 storage/logs/laravel.log

# Error logs
grep -i error storage/logs/laravel.log
```

## Performance Optimization

### Database
```bash
# Check query count and time
php artisan debugbar:enable

# Optimize database
php artisan optimize
```

### Caching
```bash
# Enable query caching in development
# Add to config/database.php

'query_cache' => true
```

### Asset Compilation
```bash
# Compile assets (if using Vite)
npm run build
npm run dev  # For development with hot reload
```

## Common Issues

### Permissions Error

```bash
# Fix storage permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www-data:www-data storage
```

### Database Connection Error

```bash
# Verify .env database settings
# Test connection:
php artisan tinker
> DB::connection()->getPdo();
```

### Artisan Command Not Found

```bash
# Make sure you're in the project root
pwd  # Should show media-server directory

# Or run with full path
/usr/bin/php artisan list
```

### Redis Connection Error

```bash
# Check Redis is running
redis-cli ping

# Restart Redis
sudo systemctl restart redis-server
```

## Git Workflow

### Branching Strategy

```bash
# Feature branch
git checkout -b feature/new-feature
git add .
git commit -m "Add new feature"
git push origin feature/new-feature

# Create Pull Request
# After review and merge:
git checkout main
git pull origin main
```

### Commit Messages

Follow conventional commits:

```
feat: add automatic VOD fallback
fix: resolve stream health check timeout
docs: update installation guide
test: add streaming service tests
```

## Building for Production

```bash
# Optimize autoloader
composer install --no-dev --optimize-autoloader

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Compile assets
npm run build
```

## Deployment Checklist

- [ ] All tests passing
- [ ] Code reviewed
- [ ] Environment variables configured
- [ ] Database migrations run
- [ ] Cache cleared
- [ ] Assets compiled
- [ ] Logs configured
- [ ] Backups taken
- [ ] SSL certificates valid
- [ ] Firewall rules applied

## Resources

- Laravel Documentation: https://laravel.com/docs
- FFmpeg Documentation: https://ffmpeg.org/documentation.html
- HLS Specification: https://tools.ietf.org/html/rfc8216
- DASH Specification: https://dashif.org/

## Support

For development questions:
- Check existing issues
- Create new issue with details
- Contact development team
