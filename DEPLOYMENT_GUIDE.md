# MediaServer - Professional Laravel Media Server

A professional, production-ready Laravel-based media server for Ubuntu Server with live streaming (RTMP/HLS) and automatic VOD fallback capabilities.

## Project Summary

This is a complete media server solution similar to Flussonic but with the key feature of **automatic VOD fallback** - when a live stream push becomes unavailable, the system immediately switches to a pre-configured VOD (Video-on-Demand) playlist, maintaining seamless playback for viewers.

### Key Differentiators

✅ **Automatic VOD Fallback**: Seamless switching when live stream unavailable
✅ **Professional Architecture**: Enterprise-grade Laravel framework
✅ **Stream Health Monitoring**: Real-time health checks and automatic recovery
✅ **Multiple Output Formats**: HLS and DASH support
✅ **REST API**: Complete API for stream management
✅ **Production Ready**: Supervisor, Redis, MySQL, Nginx fully configured
✅ **Docker Support**: Easy deployment with Docker Compose
✅ **Ubuntu Server Optimized**: Bash installation script for Ubuntu 22.04+

## Project Structure

```
media-server/
├── app/
│   ├── Console/Commands/
│   │   └── StreamMonitorCommand.php          # Stream health monitoring command
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── StreamPlayerController.php     # Web playback controller
│   │   │   └── API/
│   │   │       ├── ChannelController.php      # Channel management API
│   │   │       └── StreamController.php       # Stream control API
│   ├── Models/
│   │   ├── Channel.php                        # Channel model
│   │   ├── Stream.php                         # Stream model
│   │   ├── StreamEvent.php                    # Stream event logging
│   │   └── StreamStatistic.php                # Stream statistics
│   └── Services/
│       ├── StreamingService.php               # Core streaming logic
│       └── StreamHealthMonitor.php            # Health monitoring service
├── database/
│   └── migrations/
│       ├── 2024_01_01_000001_create_channels_table.php
│       ├── 2024_01_01_000002_create_streams_table.php
│       ├── 2024_01_01_000003_create_stream_events_table.php
│       └── 2024_01_01_000004_create_stream_statistics_table.php
├── routes/
│   └── api.php                                # API and web routes
├── config/
│   └── app.php                                # Laravel app config
├── .env.example                               # Environment configuration template
├── artisan                                    # Laravel command-line tool
├── composer.json                              # PHP dependencies
├── Dockerfile                                 # Docker image definition
├── docker-compose.yml                         # Docker Compose orchestration
├── install.sh                                 # Ubuntu installation script
├── nginx.conf.example                         # Nginx web server config
├── supervisor.conf.example                    # Process manager config
├── INSTALLATION.md                            # Detailed installation guide
└── README.md                                  # Project documentation
```

## Core Features

### 1. Live Streaming
- **RTMP Input**: Accepts RTMP push streams
- **HLS Output**: HTTP Live Streaming protocol
- **DASH Output**: Dynamic Adaptive Streaming over HTTP
- **Multi-bitrate**: Support for different bitrates and resolutions

### 2. VOD Fallback System
- **Automatic Switching**: Detects live stream failures
- **Seamless Playback**: Switches without interrupting viewers
- **Configurable URL**: Per-channel VOD playlist configuration
- **Status Tracking**: Records all fallback events

### 3. Stream Health Monitoring
- **Real-time Health Checks**: Every 5 seconds (configurable)
- **Source Accessibility**: Verifies input stream availability
- **Process Monitoring**: Checks FFmpeg process status
- **Event Logging**: Records all stream events

### 4. RESTful API
- Complete channel management
- Stream control (start/stop/fallback)
- Status and statistics endpoints
- Event history retrieval

### 5. Web Dashboard
- Channel management interface
- Stream status monitoring
- Event log viewer
- Stream playback testing

## Technology Stack

### Backend
- **Framework**: Laravel 11.0
- **Language**: PHP 8.2+
- **Database**: MySQL 8.0 / MariaDB
- **Cache**: Redis 7.0
- **Queue**: Laravel Queue with Redis

### Streaming
- **FFmpeg**: Video transcoding and streaming
- **RTMP**: Live stream ingestion protocol
- **HLS**: HTTP Live Streaming output
- **DASH**: Dynamic Adaptive Streaming over HTTP

### Infrastructure
- **Web Server**: Nginx 1.24+
- **Process Manager**: Supervisor
- **Containerization**: Docker & Docker Compose
- **OS**: Ubuntu Server 22.04 LTS+

## Installation Methods

### Method 1: Automated Installation (Ubuntu)
```bash
sudo bash install.sh
```

### Method 2: Manual Installation
Follow detailed steps in `INSTALLATION.md`

### Method 3: Docker
```bash
docker-compose up -d
```

## Quick Configuration

1. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

2. **Database Setup**
   ```bash
   php artisan migrate
   ```

3. **Create Channel**
   ```bash
   curl -X POST http://localhost/api/channels \
     -H "Content-Type: application/json" \
     -d '{
       "name": "My Channel",
       "slug": "my-channel",
       "vod_playlist_url": "https://example.com/vod.m3u8"
     }'
   ```

4. **Start Stream**
   ```bash
   curl -X POST http://localhost/api/streams/start \
     -H "Content-Type: application/json" \
     -d '{
       "channel_id": 1,
       "push_url": "rtmp://source/live/stream"
     }'
   ```

5. **Watch Stream**
   - Open browser: `http://localhost/play/my-channel`

## API Endpoints

### Channels
- `GET /api/channels` - List all channels
- `POST /api/channels` - Create channel
- `GET /api/channels/{id}` - Get channel details
- `PUT /api/channels/{id}` - Update channel
- `DELETE /api/channels/{id}` - Delete channel
- `GET /api/channels/{id}/status` - Get current status
- `GET /api/channels/{id}/events` - Get event history

### Streams
- `POST /api/streams/start` - Start stream
- `POST /api/streams/stop` - Stop stream
- `GET /api/streams/{id}/status` - Get stream status
- `POST /api/streams/{id}/fallback` - Switch to VOD
- `GET /api/streams/{id}/statistics` - Get statistics
- `GET /api/streams/{id}/recent` - Get recent streams

### Web
- `GET /play/{slug}` - Watch stream
- `GET /streams/{slug}/playlist.m3u8` - HLS manifest
- `GET /streams/{slug}/manifest.mpd` - DASH manifest

## Configuration Options

### Streaming Parameters
```env
STREAM_RTMP_PORT=1935              # RTMP input port
STREAM_HLS_PORT=8080               # HLS output port
HLS_SEGMENT_DURATION=10            # Segment length in seconds
HLS_SEGMENTS_IN_PLAYLIST=3         # Segments to buffer
RTMP_TIMEOUT=30                    # Connection timeout
```

### Health Monitoring
```env
STREAM_HEALTH_CHECK_ENABLED=true   # Enable monitoring
STREAM_HEALTH_CHECK_INTERVAL=5     # Check every N seconds
VOD_FALLBACK_ENABLED=true          # Enable automatic fallback
VOD_FALLBACK_DELAY=2               # Delay before switching
```

### Performance
```env
MAX_CONCURRENT_STREAMS=100         # Maximum simultaneous streams
FFMPEG_TIMEOUT=0                   # FFmpeg unlimited timeout
BUFFER_SIZE_SECONDS=5              # Playback buffer
```

## Monitoring and Logs

### Service Status
```bash
sudo supervisorctl status
sudo systemctl status nginx php8.2-fpm redis-server
```

### Log Files
```bash
tail -f /var/www/media-server/storage/logs/laravel.log
tail -f /var/log/supervisor/media-server-monitor.log
tail -f /var/log/nginx/media-server-error.log
```

### Stream Processes
```bash
ps aux | grep ffmpeg
```

## Performance Recommendations

### For Small Deployments (< 100 concurrent viewers)
- 2 CPU cores, 4GB RAM
- Standard HLS configuration
- Single server setup

### For Medium Deployments (100-1000 concurrent viewers)
- 4+ CPU cores, 8GB RAM
- Use Redis for caching
- Monitor CPU and memory

### For Large Deployments (1000+ concurrent viewers)
- 8+ CPU cores, 16GB+ RAM
- Use load balancer
- CDN for HLS segments
- Database replication

## Security Features

- SSL/TLS encryption (Let's Encrypt)
- Firewall configuration
- IP whitelisting support
- API rate limiting
- Secure password hashing
- CSRF protection
- SQL injection prevention

## Troubleshooting

### Stream not starting
1. Check FFmpeg installation
2. Verify RTMP source URL
3. Check port 1935 is open
4. Review error logs

### VOD fallback not working
1. Verify VOD URL is accessible
2. Check monitor service is running
3. Review stream events log
4. Test URL with curl

### High CPU usage
1. Reduce FFmpeg thread count
2. Adjust PHP-FPM workers
3. Enable caching
4. Monitor system resources

## Support & Documentation

- **Full Documentation**: See README.md
- **Installation Guide**: See INSTALLATION.md
- **API Reference**: `/api/documentation` (when available)
- **Logs**: `/var/www/media-server/storage/logs/`

## License

MIT License - Open source and free to use

## Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create feature branch
3. Commit changes
4. Push to branch
5. Submit pull request

## Credits

Built with Laravel, FFmpeg, and modern web technologies for professional media streaming on Linux.

---

**Created**: May 2026
**Version**: 1.0.0
**License**: MIT
