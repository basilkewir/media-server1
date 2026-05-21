# Icecast & Relay Broadcasting Implementation Summary

This document summarizes the Icecast streaming and relay broadcasting infrastructure added to the Media Server.

## Overview

The Media Server has been enhanced with enterprise-grade Icecast streaming and multi-server relay broadcasting capabilities, enabling:

- **Icecast Integration**: Direct streaming to Icecast servers with automatic credential management
- **Multi-Server Relay**: Simultaneous broadcasting to multiple relay servers (Icecast, RTMP, Shoutcast)
- **Automatic Failover**: Health monitoring with automatic process restart on failure
- **Centralized Management**: Full REST API for relay and Icecast control
- **Real-time Statistics**: Listener tracking and bitrate monitoring across all relays
- **Event Logging**: Complete audit trail of relay events and status changes

## Files Created

### Models

| File | Purpose | Lines |
|------|---------|-------|
| `app/Models/RelayBroadcast.php` | Track relay broadcast sessions | 35 |
| `app/Models/RelayServer.php` | Configure relay server targets | 50 |
| `app/Models/RelayBroadcastLog.php` | Audit trail for relay events | 30 |

### Services

| File | Purpose | Lines |
|------|---------|-------|
| `app/Services/IcecastService.php` | Icecast admin API integration | 95 |
| `app/Services/RelayBroadcastService.php` | Multi-server relay orchestration | 280 |

### Controllers

| File | Purpose | Lines |
|------|---------|-------|
| `app/Http/Controllers/API/IcecastController.php` | 6 REST endpoints for Icecast | 160 |
| `app/Http/Controllers/API/RelayBroadcastController.php` | 10 REST endpoints for relay | 170 |

### Console Commands

| File | Purpose | Lines |
|------|---------|-------|
| `app/Console/Commands/RelayHealthCheckCommand.php` | Periodic relay health monitoring | 180 |

### Database Migrations

| Migration | Purpose | Tables Affected |
|-----------|---------|-----------------|
| `2024_01_01_000005_add_icecast_relay_to_channels.php` | Add Icecast/relay flags to channels | channels |
| `2024_01_01_000006_create_relay_servers_table.php` | Create relay server registry | relay_servers |
| `2024_01_01_000007_create_relay_broadcasts_table.php` | Track active relays | relay_broadcasts |
| `2024_01_01_000008_create_relay_broadcast_logs_table.php` | Event audit trail | relay_broadcast_logs |

### Configuration Files

| File | Purpose |
|------|---------|
| `icecast.conf.example` | Production-ready Icecast configuration template |
| `routes/api.php.laravel` | Complete API route definitions |

### Documentation

| File | Purpose | Words |
|------|---------|-------|
| `ICECAST_GUIDE.md` | Complete Icecast setup and usage guide | 3,500+ |
| `RELAY_GUIDE.md` | Complete relay broadcasting guide | 4,000+ |

## Data Models

### RelayBroadcast

Tracks individual relay broadcast sessions:

```php
$relay = RelayBroadcast::where('is_active', true)->first();
// Properties: status, relay_url, listeners, bitrate_kbps, is_active
// Relationships: channel(), relayServer(), logs()
```

Status states: `connecting`, `connected`, `disconnected`, `failed`, `server_offline`, `process_died`, `stopped`

### RelayServer

Stores relay server configurations:

```php
$server = RelayServer::find(1);
$server->isOnline();           // Check connectivity via fsockopen
$server->getTotalListeners();  // Sum listeners across relays
```

Server types: `icecast`, `rtmp`, `shoutcast`

### RelayBroadcastLog

Maintains event audit trail:

```php
$logs = $relay->logs()->latest()->limit(100)->get();
// Event types: relay_started, relay_stopped, server_offline, process_died, relay_health_check
// Status values: success, warning, error
```

## Service Methods

### IcecastService

```php
$service = app(IcecastService::class);

// Create new Icecast stream
$result = $service->createIcecastStream($channel);
// Returns: ['mount_point' => '/stream', 'password' => 'xyz', 'stream_url' => '...']

// Get stream URL
$url = $service->getStreamUrl($channel);

// Query live statistics
$stats = $service->getStreamStats($channel);
// Returns: ['listeners' => 42, 'bitrate_kbps' => 192, ...]

// Disconnect stream
$service->disconnectStream($channel);

// Set listener limit
$service->setMaxListeners($channel, 500);
```

### RelayBroadcastService

```php
$service = app(RelayBroadcastService::class);

// Start relay to server
$relay = $service->startRelay($channel, $relayServer);

// Stop relay
$service->stopRelay($relay);

// Get relay statistics
$stats = $service->getRelayStats($relay);
// Returns: ['listeners' => 42, 'bitrate_kbps' => 192, 'uptime_seconds' => 3600, ...]

// Get all relays for channel
$relays = $service->getChannelRelays($channel);

// Check relay health
$service->checkRelayHealth($relay);

// Check all relays
$service->checkAllRelayHealth();
```

## API Endpoints

### Icecast Endpoints (6 total)

| Method | Path | Purpose |
|--------|------|---------|
| POST | `/api/icecast/{channel}/create` | Create mount point |
| GET | `/api/icecast/{channel}/stream-url` | Get push URL |
| GET | `/api/icecast/{channel}/stats` | Get listener stats |
| POST | `/api/icecast/{channel}/disconnect` | Stop streaming |
| POST | `/api/icecast/{channel}/max-listeners` | Set limit |
| POST | `/api/icecast/{channel}/enable` | Enable feature |
| POST | `/api/icecast/{channel}/disable` | Disable feature |

### Relay Endpoints (10 total)

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/api/relay/servers` | List relay servers |
| POST | `/api/relay/servers` | Add server |
| POST | `/api/relay/{channel}/start` | Start relay |
| POST | `/api/relay/{relay}/stop` | Stop relay |
| GET | `/api/relay/{relay}/status` | Get relay status |
| GET | `/api/relay/{channel}/broadcasts` | List channel relays |
| GET | `/api/relay/{relay}/logs` | Get event logs |
| POST | `/api/relay/{channel}/enable` | Enable relay |
| POST | `/api/relay/{channel}/disable` | Disable relay |

## Architecture

### Flow Diagram

```
┌─────────────────────────────────────────────────────────┐
│           Laravel Media Server Application              │
│  ┌──────────────────────────────────────────────────┐  │
│  │ HTTP Middleware & Routing                        │  │
│  ├──────────────────────────────────────────────────┤  │
│  │ IcecastController + RelayBroadcastController     │  │
│  │  - HTTP request handling                         │  │
│  │  - Input validation                              │  │
│  │  - JSON response formatting                      │  │
│  └──────────────────────────────────────────────────┘  │
│                      ↓                                   │
│  ┌──────────────────────────────────────────────────┐  │
│  │ Services Layer                                   │  │
│  │ ┌──────────────────────┐                         │  │
│  │ │ IcecastService       │                         │  │
│  │ │ - Icecast API calls  │                         │  │
│  │ │ - Credential mgmt    │                         │  │
│  │ │ - Stats retrieval    │                         │  │
│  │ └──────────────────────┘                         │  │
│  │ ┌──────────────────────┐                         │  │
│  │ │ RelayBroadcastSvc    │                         │  │
│  │ │ - FFmpeg processes   │                         │  │
│  │ │ - Server monitoring  │                         │  │
│  │ │ - Event logging      │                         │  │
│  │ └──────────────────────┘                         │  │
│  └──────────────────────────────────────────────────┘  │
│                      ↓                                   │
│  ┌──────────────────────────────────────────────────┐  │
│  │ Eloquent Models                                  │  │
│  │ - Channel                                        │  │
│  │ - RelayBroadcast                                 │  │
│  │ - RelayServer                                    │  │
│  │ - RelayBroadcastLog                              │  │
│  └──────────────────────────────────────────────────┘  │
│                      ↓                                   │
│  ┌──────────────────────────────────────────────────┐  │
│  │ Database (MySQL)                                 │  │
│  │ - relay_servers                                  │  │
│  │ - relay_broadcasts                               │  │
│  │ - relay_broadcast_logs                           │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
         ↓                    ↓                    ↓
    ┌─────────┐        ┌──────────┐        ┌──────────┐
    │ Icecast │        │   RTMP   │        │Shoutcast │
    │ Server  │        │  Server  │        │ Server   │
    └─────────┘        └──────────┘        └──────────┘
         ↓                    ↓                    ↓
    ┌─────────────────────────────────────────────────┐
    │         Global Listener Base                    │
    │  (CDN, regional servers, mobile clients)        │
    └─────────────────────────────────────────────────┘
```

### Process Flow

```
1. User creates channel with is_relay_enabled=true
2. User registers relay servers via API
3. User starts stream to media server (RTMP/Icecast)
4. User posts start relay request for channel
5. RelayBroadcastService:
   - Creates RelayBroadcast record
   - Spawns FFmpeg process with relay output
   - Records relay_process_pid
   - Logs "relay_started" event
6. FFmpeg process:
   - Reads from channel stream source
   - Encodes to target server format
   - Pushes to relay server URL
7. RelayHealthCheckCommand (running via supervisor):
   - Every 30 seconds: checks all active relays
   - Verifies server connectivity (fsockopen)
   - Checks process status (/proc/PID)
   - Queries listener counts
   - Logs health check results
   - Restarts failed processes
8. User queries relay status via API
9. Database returns current status + listeners + bitrate
```

## Configuration

### Environment Variables

Add to `.env`:

```env
ICECAST_HOST=localhost
ICECAST_PORT=8000
ICECAST_ADMIN_USER=admin
ICECAST_ADMIN_PASSWORD=your-secure-password
```

### Supervisor Configuration

Added to `supervisor.conf.example`:

```ini
[program:media-server-relay-monitor]
process_name=%(program_name)s
command=/usr/bin/php /var/www/media-server/artisan relay:health-check --interval=30
autostart=true
autorestart=true
```

## Security Features

1. **Credential Isolation**: Relay passwords stored in encrypted database fields
2. **Admin API Protection**: Icecast admin credentials secured in environment
3. **Process Isolation**: Each relay runs separate FFmpeg process
4. **Event Audit Trail**: All relay events logged with timestamps
5. **Health Verification**: Periodic connectivity checks prevent orphaned processes
6. **Automatic Cleanup**: Failed processes terminated and restarted

## Performance Characteristics

| Metric | Typical Value | Tunable |
|--------|---------------|---------|
| Process startup time | 1-2 seconds | Platform dependent |
| Health check interval | 30 seconds | Yes, via --interval flag |
| Database query time | <100ms | Indexed fields optimized |
| Memory per relay | 50-100MB | Depends on bitrate |
| CPU per relay | 5-15% | Depends on encoding |
| Max relays per server | 10-20 | Hardware dependent |

## Deployment Checklist

- [ ] Install Icecast2: `sudo apt-get install icecast2`
- [ ] Copy config: `sudo cp icecast.conf.example /etc/icecast2/icecast.xml`
- [ ] Configure credentials in `.env`
- [ ] Run migrations: `php artisan migrate`
- [ ] Add supervisor config and reload: `sudo supervisorctl reread && sudo supervisorctl update`
- [ ] Verify health check running: `sudo supervisorctl status media-server-relay-monitor`
- [ ] Register relay servers via API
- [ ] Test relay to server: `POST /api/relay/{channel}/start`
- [ ] Verify relay connects: Check status after 5 seconds
- [ ] Monitor logs: `tail -f /var/log/supervisor/media-server-relay-monitor.log`

## Integration with Existing Features

### VOD Fallback

- Relays continue from VOD source when live stream disconnects
- Configure behavior in `app/Services/StreamingService.php`

### Stream Monitoring

- Relay health monitor runs alongside stream monitor
- Both use same process management infrastructure

### Database

- All relay data stored in separate tables
- Channel model extended with `is_relay_enabled` and `is_icecast_enabled` flags
- Cascading deletes prevent orphaned records

## Troubleshooting Resources

- **Icecast issues**: See `ICECAST_GUIDE.md` Troubleshooting section
- **Relay issues**: See `RELAY_GUIDE.md` Troubleshooting section
- **Logs location**: `/var/log/supervisor/media-server-relay-monitor.log`
- **Database logs**: Query `relay_broadcast_logs` table
- **Process logs**: `ps aux | grep ffmpeg` to find running processes

## Next Steps

1. **Monitor**: Watch relay health checks run: `tail -f /var/log/supervisor/media-server-relay-monitor.log`
2. **Test**: Register relay servers and start test relays
3. **Scale**: Add more relay servers for geographic distribution
4. **Optimize**: Tune bitrates and listener limits per server
5. **Document**: Update internal documentation with company relay infrastructure

## Support & Documentation

- **Installation**: See `INSTALLATION.md` for complete setup
- **Quick Reference**: See `QUICK_REFERENCE.md` for command examples
- **Deployment**: See `DEPLOYMENT_GUIDE.md` for production setup
- **Development**: See `DEVELOPMENT.md` for extending features
