# MediaServer

A professional Laravel 11 media server for Ubuntu with:
- **Live stream ingest** via RTMP (nginx-rtmp)
- **Automatic VOD fallback** — when live push drops, seamlessly switches to a VOD playlist and loops it
- **Icecast2 audio streaming** per channel
- **Relay broadcast** to remote Icecast / RTMP / Shoutcast servers
- **HLS output** for all streams (live and VOD fallback)
- **Health monitoring** via Supervisor-managed background processes

---

## Installation (Ubuntu 22.04 / 24.04)

```bash
git clone <repo> /tmp/media-server
cd /tmp/media-server
sudo bash install.sh
```

The installer sets up: PHP 8.2, MySQL, Redis, Nginx + RTMP module, Icecast2, FFmpeg, Supervisor, and runs migrations.

---

## Supported Ingest Protocols

| Protocol | Example URL | Notes |
|----------|-------------|-------|
| RTMP | `rtmp://server:1935/live/key` | Most common, OBS default |
| RTMPS | `rtmps://server:1935/live/key` | TLS-encrypted RTMP |
| RTSP | `rtsp://camera:554/stream` | IP cameras |
| SRT | `srt://server:9000?streamid=key` | Low-latency, packet loss resilient |
| HLS | `http://server/playlist.m3u8` | Pull from another HLS source |
| DASH | `http://server/manifest.mpd` | Pull from DASH source |
| UDP/MPEG-TS | `udp://239.0.0.1:1234` | Multicast / satellite feeds |
| RTP | `rtp://239.0.0.1:5004` | RTP multicast |
| TCP/MPEG-TS | `tcp://server:1234` | Raw MPEG-TS over TCP |
| HTTP/HTTPS | `https://server/stream` | Generic HTTP stream |
| Local file | `/path/to/video.mp4` | File-based VOD source |

```bash
# Probe a URL before starting (returns protocol + reachability)
curl -X POST http://localhost/api/streams/probe \
  -H 'Content-Type: application/json' \
  -d '{"url": "rtsp://192.168.1.100:554/stream"}'
```

## Multi-Output Broadcasting

Every channel has unlimited **Output Targets** — each an independent FFmpeg process pushing to any protocol/destination simultaneously.

### Output protocols supported

| Protocol | Use case |
|----------|----------|
| `rtmp` / `rtmps` | YouTube Live, Facebook, Twitch, any RTMP server |
| `srt` | Low-latency contribution links, CDN ingest |
| `mpeg_ts_udp` | Satellite uplinks, broadcast encoders, multicast |
| `mpeg_ts_tcp` | Reliable MPEG-TS over TCP |
| `rtp` | RTP multicast, IPTV |
| `hls_push` | Push HLS to CDN origin via HTTP PUT |
| `icecast` / `shoutcast` | Audio-only internet radio |
| `file` | Record to disk |

### Trigger modes

| Trigger | When active |
|---------|-------------|
| `always` | Whenever channel is streaming (live or VOD fallback) |
| `live_only` | Only when live push is active |
| `fallback_only` | Only when VOD fallback is playing |
| `manual` | Only when explicitly started via API |

### Examples

```bash
# Add YouTube Live output (always active)
curl -X POST http://localhost/api/channels/1/outputs \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "YouTube Live",
    "output_url": "rtmp://a.rtmp.youtube.com/live2/YOUR-KEY",
    "output_protocol": "rtmp",
    "trigger": "always",
    "video_codec": "copy",
    "audio_codec": "aac"
  }'

# Add Facebook with transcoding to 720p
curl -X POST http://localhost/api/channels/1/outputs \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Facebook Live",
    "output_url": "rtmps://live-api-s.facebook.com:443/rtmp/YOUR-KEY",
    "output_protocol": "rtmps",
    "trigger": "always",
    "video_codec": "libx264",
    "video_bitrate_kbps": 2500,
    "resolution": "1280x720",
    "audio_codec": "aac",
    "audio_bitrate_kbps": 128
  }'

# Add SRT output to CDN
curl -X POST http://localhost/api/channels/1/outputs \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "CDN SRT",
    "output_url": "srt://cdn.example.com:9000?streamid=channel1",
    "output_protocol": "srt",
    "trigger": "always",
    "srt_latency_ms": 200,
    "srt_passphrase": "mysecretpassphrase"
  }'

# Add UDP multicast (satellite/IPTV)
curl -X POST http://localhost/api/channels/1/outputs \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "IPTV Multicast",
    "output_url": "udp://239.0.0.1:1234",
    "output_protocol": "mpeg_ts_udp",
    "trigger": "always"
  }'

# VOD-fallback-only backup RTMP (only pushes when live drops)
curl -X POST http://localhost/api/channels/1/outputs \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Backup RTMP",
    "output_url": "rtmp://backup.example.com/live/channel1",
    "output_protocol": "rtmp",
    "trigger": "fallback_only"
  }'

# Push channel to multiple ad-hoc URLs in one call
curl -X POST http://localhost/api/channels/1/outputs/push \
  -H 'Content-Type: application/json' \
  -d '{
    "destinations": [
      {"url": "rtmp://youtube.com/live2/key1", "name": "YouTube"},
      {"url": "rtmp://live.twitch.tv/live/key2", "name": "Twitch"},
      {"url": "srt://cdn.example.com:9000", "name": "CDN"}
    ]
  }'

# Push multiple channels to multiple targets at once
curl -X POST http://localhost/api/outputs/bulk-push \
  -H 'Content-Type: application/json' \
  -d '{"channel_ids": [1,2,3], "target_ids": [4,5,6]}'

# Global status of all outputs across all channels
curl http://localhost/api/outputs/status

# Start/stop/restart individual output
curl -X POST http://localhost/api/channels/1/outputs/3/restart
```


1. Detects source is unreachable (protocol-aware check)
2. Switches FFmpeg input to the VOD playlist (loops infinitely)
3. Continues HLS output — viewers see no interruption
4. **Also pushes the VOD to a remote RTMP server** if `rtmp_push_url` is set

```bash
# Create channel with VOD fallback AND outbound RTMP push
curl -X POST http://localhost/api/channels \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "My Channel",
    "slug": "my-channel",
    "vod_playlist_url": "http://cdn.example.com/fallback.m3u8",
    "rtmp_push_url": "rtmp://live.youtube.com/live2/YOUR-STREAM-KEY"
  }'

# Or set/update RTMP push target on existing channel
curl -X PUT http://localhost/api/streams/1/rtmp-push \
  -H 'Content-Type: application/json' \
  -d '{"rtmp_push_url": "rtmp://a.rtmp.youtube.com/live2/xxxx-xxxx"}'
```


```bash
curl -X POST http://localhost/api/channels \
  -H "Content-Type: application/json" \
  -d '{
    "name": "My Channel",
    "slug": "my-channel",
    "vod_playlist_url": "http://example.com/fallback.m3u8",
    "bitrate_kbps": 2000
  }'
```

### 2. Push a live stream
Push RTMP to: `rtmp://your-server:1935/live/my-channel`

MediaServer auto-detects the push and starts HLS output.

### 3. Watch
- HLS: `http://your-server/streams/my-channel/playlist.m3u8`
- Player: `http://your-server/play/my-channel`

### 4. VOD fallback
When the live push stops, the stream monitor automatically switches to the configured `vod_playlist_url` and loops it — viewers see no interruption.

---

## Icecast Audio Streaming

```bash
# Enable Icecast for a channel
curl -X POST http://localhost/api/icecast/1/enable

# Get stream URL
curl http://localhost/api/icecast/1/url

# Push audio to: icecast://source:<password>@localhost:8000/stream/my-channel
# Listen at:     http://localhost:8000/stream/my-channel
```

---

## Relay Broadcast

```bash
# Register a relay server
curl -X POST http://localhost/api/relay/servers \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Remote Icecast",
    "hostname": "relay.example.com",
    "port": 8000,
    "username": "source",
    "password": "hackme",
    "server_type": "icecast"
  }'

# Start relaying channel 1 to relay server 1
curl -X POST http://localhost/api/relay/1/start \
  -H "Content-Type: application/json" \
  -d '{"relay_server_id": 1}'
```

Supported relay types: `icecast`, `rtmp`, `shoutcast`

---

## API Reference

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/health` | Health check |
| GET | `/api/channels` | List channels |
| POST | `/api/channels` | Create channel |
| GET | `/api/channels/{id}/status` | Channel status |
| POST | `/api/streams/start` | Start stream |
| POST | `/api/streams/stop` | Stop stream |
| POST | `/api/streams/{channel}/fallback` | Force VOD fallback |
| POST | `/api/streams/{channel}/recover` | Recover to live |
| POST | `/api/icecast/{channel}/enable` | Enable Icecast |
| GET | `/api/icecast/{channel}/stats` | Icecast listener stats |
| GET | `/api/relay/servers` | List relay servers |
| POST | `/api/relay/servers` | Add relay server |
| POST | `/api/relay/{channel}/start` | Start relay |
| POST | `/api/relay/broadcast/{relay}/stop` | Stop relay |

---

## Architecture

```
RTMP Push ──► nginx-rtmp ──► MediaServer API ──► FFmpeg ──► HLS segments
                                    │                           │
                              Health Monitor              Storage/streams/
                                    │                           │
                              VOD Fallback ◄──────────── Nginx serves
                              (auto-loop)
                                    │
                              Relay Service ──► Icecast2 / RTMP / Shoutcast
```

## Supervisor Processes

| Process | Purpose |
|---------|---------|
| `mediaserver-monitor` | Checks stream health every 5s, triggers VOD fallback |
| `mediaserver-queue` | Laravel queue workers (2 processes) |
| `mediaserver-relay-monitor` | Checks relay health every 30s, auto-restarts |
| `mediaserver-scheduler` | Laravel task scheduler |

```bash
sudo supervisorctl status
sudo supervisorctl restart mediaserver-monitor
```
