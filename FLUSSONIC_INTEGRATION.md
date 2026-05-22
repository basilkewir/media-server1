# Flussonic Integration Guide

This guide shows how to integrate Flussonic with your Laravel Media Server for seamless streaming management.

## Table of Contents

1. [Architecture](#architecture)
2. [Configuration](#configuration)
3. [Implementation](#implementation)
4. [API Integration](#api-integration)
5. [Stream Management](#stream-management)
6. [Monitoring](#monitoring)
7. [Troubleshooting](#troubleshooting)

## Architecture

### Integration Overview

```
┌─────────────────────────────────────────────────────────┐
│           Laravel Media Server                          │
│  - Channel Management                                   │
│  - Stream Orchestration                                 │
│  - VOD Fallback                                         │
│  - Icecast/Relay Support                                │
└──────────────────────┬──────────────────────────────────┘
                       │ HTTP API
                       ↓
┌─────────────────────────────────────────────────────────┐
│           Flussonic Media Server                        │
│  - Live Streaming (RTMP, HLS, DASH)                    │
│  - DVR Recording                                        │
│  - VOD Streaming                                        │
│  - Relay Broadcasting                                   │
└──────────────────────┬──────────────────────────────────┘
                       │
        ┌──────────────┼──────────────┐
        ↓              ↓              ↓
    ┌────────┐  ┌────────┐  ┌────────┐
    │  Live  │  │  DVR   │  │  VOD   │
    │ RTMP   │  │Record. │  │ Stream │
    └────────┘  └────────┘  └────────┘
```

### Data Flow

1. **Encoder** pushes RTMP to Media Server or Flussonic
2. **Media Server** receives stream and manages it
3. **Media Server** instructs Flussonic to create streams
4. **Flussonic** handles transcoding, DVR, distribution
5. **Viewers** consume HLS/DASH from Flussonic
6. **Media Server** monitors stats via Flussonic API

## Configuration

### 1. Update Media Server .env

```bash
# Add to .env file
FLUSSONIC_ENABLED=true
FLUSSONIC_HOST=localhost
FLUSSONIC_PORT=8080
FLUSSONIC_RTMP_PORT=1935
FLUSSONIC_HTTP_PORT=80
FLUSSONIC_ADMIN_USER=admin
FLUSSONIC_ADMIN_PASSWORD=your_secure_password
FLUSSONIC_API_URL=http://localhost:8080/api/v1
FLUSSONIC_API_TOKEN=your_api_token
FLUSSONIC_STORAGE_PATH=/var/cache/flussonic
FLUSSONIC_STREAM_PREFIX=stream_
```

### 2. Update config/services.php

```php
'flussonic' => [
    'enabled' => env('FLUSSONIC_ENABLED', false),
    'host' => env('FLUSSONIC_HOST', 'localhost'),
    'port' => env('FLUSSONIC_PORT', 8080),
    'rtmp_port' => env('FLUSSONIC_RTMP_PORT', 1935),
    'http_port' => env('FLUSSONIC_HTTP_PORT', 80),
    'admin_user' => env('FLUSSONIC_ADMIN_USER', 'admin'),
    'admin_password' => env('FLUSSONIC_ADMIN_PASSWORD', 'hackme'),
    'api_url' => env('FLUSSONIC_API_URL', 'http://localhost:8080/api/v1'),
    'api_token' => env('FLUSSONIC_API_TOKEN', ''),
    'storage_path' => env('FLUSSONIC_STORAGE_PATH', '/var/cache/flussonic'),
    'stream_prefix' => env('FLUSSONIC_STREAM_PREFIX', 'stream_'),
    'dvr_enabled' => true,
    'hls_enabled' => true,
    'dash_enabled' => true,
    'rtmp_relay_enabled' => true,
],
```

## Implementation

### 1. Create FlussonicService

Create `app/Services/FlussonicService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class FlussonicService
{
    protected string $apiUrl;
    protected ?string $apiToken;
    protected string $streamPrefix;

    public function __construct()
    {
        $this->apiUrl = config('services.flussonic.api_url');
        $this->apiToken = config('services.flussonic.api_token');
        $this->streamPrefix = config('services.flussonic.stream_prefix', 'stream_');
    }

    /**
     * Create stream in Flussonic
     */
    public function createStream(string $channelName, string $input, array $options = []): array
    {
        $streamName = $this->streamPrefix . str_slug($channelName);

        $payload = [
            'name' => $streamName,
            'input' => $input,
        ];

        // Add optional settings
        if (isset($options['outputs'])) {
            $payload['outputs'] = $options['outputs'];
        }

        if (isset($options['dvr'])) {
            $payload['dvr'] = $options['dvr'];
        }

        if (isset($options['hls'])) {
            $payload['hls'] = $options['hls'];
        }

        try {
            $response = Http::withHeaders([
                'X-Auth-Token' => $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/streams", $payload);

            if ($response->successful()) {
                Log::info("Flussonic stream created: {$streamName}", $response->json());
                return [
                    'success' => true,
                    'stream_name' => $streamName,
                    'data' => $response->json(),
                ];
            }

            throw new Exception("Failed to create stream: " . $response->body());
        } catch (Exception $e) {
            Log::error("Flussonic stream creation failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete stream from Flussonic
     */
    public function deleteStream(string $channelName): array
    {
        $streamName = $this->streamPrefix . str_slug($channelName);

        try {
            $response = Http::withHeaders([
                'X-Auth-Token' => $this->apiToken,
            ])->delete("{$this->apiUrl}/streams/{$streamName}");

            if ($response->successful()) {
                Log::info("Flussonic stream deleted: {$streamName}");
                return ['success' => true];
            }

            throw new Exception("Failed to delete stream: " . $response->body());
        } catch (Exception $e) {
            Log::error("Flussonic stream deletion failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get stream statistics
     */
    public function getStreamStats(string $channelName): array
    {
        $streamName = $this->streamPrefix . str_slug($channelName);

        try {
            $response = Http::withHeaders([
                'X-Auth-Token' => $this->apiToken,
            ])->get("{$this->apiUrl}/streams/{$streamName}/stats");

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception("Failed to get stream stats: " . $response->body());
        } catch (Exception $e) {
            Log::error("Flussonic stats retrieval failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all streams
     */
    public function listStreams(): array
    {
        try {
            $response = Http::withHeaders([
                'X-Auth-Token' => $this->apiToken,
            ])->get("{$this->apiUrl}/streams");

            if ($response->successful()) {
                return $response->json()['streams'] ?? [];
            }

            throw new Exception("Failed to list streams: " . $response->body());
        } catch (Exception $e) {
            Log::error("Flussonic streams list failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Start DVR recording
     */
    public function startDVR(string $channelName): array
    {
        $streamName = $this->streamPrefix . str_slug($channelName);

        try {
            $response = Http::withHeaders([
                'X-Auth-Token' => $this->apiToken,
            ])->post("{$this->apiUrl}/streams/{$streamName}/dvr/start");

            if ($response->successful()) {
                Log::info("DVR started for: {$streamName}");
                return ['success' => true];
            }

            throw new Exception("Failed to start DVR: " . $response->body());
        } catch (Exception $e) {
            Log::error("Flussonic DVR start failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Stop DVR recording
     */
    public function stopDVR(string $channelName): array
    {
        $streamName = $this->streamPrefix . str_slug($channelName);

        try {
            $response = Http::withHeaders([
                'X-Auth-Token' => $this->apiToken,
            ])->post("{$this->apiUrl}/streams/{$streamName}/dvr/stop");

            if ($response->successful()) {
                Log::info("DVR stopped for: {$streamName}");
                return ['success' => true];
            }

            throw new Exception("Failed to stop DVR: " . $response->body());
        } catch (Exception $e) {
            Log::error("Flussonic DVR stop failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get DVR recordings
     */
    public function getDVRRecordings(string $channelName): array
    {
        $streamName = $this->streamPrefix . str_slug($channelName);

        try {
            $response = Http::withHeaders([
                'X-Auth-Token' => $this->apiToken,
            ])->get("{$this->apiUrl}/streams/{$streamName}/dvr");

            if ($response->successful()) {
                return $response->json()['recordings'] ?? [];
            }

            throw new Exception("Failed to get DVR recordings: " . $response->body());
        } catch (Exception $e) {
            Log::error("Flussonic DVR recordings retrieval failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get server info
     */
    public function getServerInfo(): array
    {
        try {
            $response = Http::withHeaders([
                'X-Auth-Token' => $this->apiToken,
            ])->get("{$this->apiUrl}/info");

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception("Failed to get server info: " . $response->body());
        } catch (Exception $e) {
            Log::error("Flussonic server info retrieval failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Build HLS URL for stream
     */
    public function getHLSUrl(string $channelName): string
    {
        $streamName = $this->streamPrefix . str_slug($channelName);
        $host = config('services.flussonic.host');
        $port = config('services.flussonic.http_port');

        return "http://{$host}:{$port}/{$streamName}/index.m3u8";
    }

    /**
     * Build DASH URL for stream
     */
    public function getDASHUrl(string $channelName): string
    {
        $streamName = $this->streamPrefix . str_slug($channelName);
        $host = config('services.flussonic.host');
        $port = config('services.flussonic.http_port');

        return "http://{$host}:{$port}/{$streamName}/manifest.mpd";
    }

    /**
     * Build RTMP URL for pushing
     */
    public function getRTMPUrl(string $channelName): string
    {
        $streamName = $this->streamPrefix . str_slug($channelName);
        $host = config('services.flussonic.host');
        $port = config('services.flussonic.rtmp_port');

        return "rtmp://{$host}:{$port}/live/{$streamName}";
    }
}
```

### 2. Update Channel Model

Add Flussonic integration to `app/Models/Channel.php`:

```php
// Add to Channel model

/**
 * Get Flussonic HLS URL
 */
public function getFlussonicHLSUrl(): string
{
    $flussonicService = app(FlussonicService::class);
    return $flussonicService->getHLSUrl($this->slug);
}

/**
 * Get Flussonic DASH URL
 */
public function getFlussonicDASHUrl(): string
{
    $flussonicService = app(FlussonicService::class);
    return $flussonicService->getDASHUrl($this->slug);
}

/**
 * Get Flussonic RTMP URL
 */
public function getFlussonicRTMPUrl(): string
{
    $flussonicService = app(FlussonicService::class);
    return $flussonicService->getRTMPUrl($this->slug);
}
```

### 3. Create FlussonicController

Create `app/Http/Controllers/API/FlussonicController.php`:

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Services\FlussonicService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FlussonicController extends Controller
{
    protected FlussonicService $flussonicService;

    public function __construct(FlussonicService $flussonicService)
    {
        $this->flussonicService = $flussonicService;
    }

    /**
     * Get server info
     */
    public function serverInfo(): JsonResponse
    {
        try {
            $info = $this->flussonicService->getServerInfo();
            return response()->json([
                'success' => true,
                'data' => $info,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List all streams
     */
    public function listStreams(): JsonResponse
    {
        try {
            $streams = $this->flussonicService->listStreams();
            return response()->json([
                'success' => true,
                'data' => $streams,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get channel stream stats
     */
    public function getStats(Channel $channel): JsonResponse
    {
        try {
            $stats = $this->flussonicService->getStreamStats($channel->slug);
            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get streaming URLs
     */
    public function getUrls(Channel $channel): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'rtmp' => $channel->getFlussonicRTMPUrl(),
                'hls' => $channel->getFlussonicHLSUrl(),
                'dash' => $channel->getFlussonicDASHUrl(),
            ],
        ]);
    }

    /**
     * Start DVR recording
     */
    public function startDVR(Channel $channel): JsonResponse
    {
        try {
            $this->flussonicService->startDVR($channel->slug);
            return response()->json([
                'success' => true,
                'message' => 'DVR recording started',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stop DVR recording
     */
    public function stopDVR(Channel $channel): JsonResponse
    {
        try {
            $this->flussonicService->stopDVR($channel->slug);
            return response()->json([
                'success' => true,
                'message' => 'DVR recording stopped',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get DVR recordings
     */
    public function getDVRRecordings(Channel $channel): JsonResponse
    {
        try {
            $recordings = $this->flussonicService->getDVRRecordings($channel->slug);
            return response()->json([
                'success' => true,
                'data' => $recordings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
```

## API Integration

### Add API Routes

Update `routes/api.php`:

```php
// Flussonic Integration Routes
Route::prefix('flussonic')->group(function () {
    Route::get('/info', [FlussonicController::class, 'serverInfo']);
    Route::get('/streams', [FlussonicController::class, 'listStreams']);
    Route::get('/{channel}/stats', [FlussonicController::class, 'getStats']);
    Route::get('/{channel}/urls', [FlussonicController::class, 'getUrls']);
    Route::post('/{channel}/dvr/start', [FlussonicController::class, 'startDVR']);
    Route::post('/{channel}/dvr/stop', [FlussonicController::class, 'stopDVR']);
    Route::get('/{channel}/dvr/recordings', [FlussonicController::class, 'getDVRRecordings']);
});
```

## Stream Management

### Workflow: Create Channel with Flussonic Stream

```php
// In Channel creation controller
$channel = Channel::create([
    'name' => 'My Live Channel',
    'slug' => 'my-live-channel',
    'is_flussonic_enabled' => true,
]);

// Automatically create stream in Flussonic
$flussonicService = app(FlussonicService::class);
$flussonicService->createStream($channel->slug, [
    'output' => 'hls',
    'dvr' => true,
]);

// Get streaming URLs
$channel->getFlussonicHLSUrl();    // HLS playback
$channel->getFlussonicRTMPUrl();   // RTMP push
$channel->getFlussonicDASHUrl();   // DASH playback
```

## Monitoring

### Monitor Stream Health

```php
// Create console command for monitoring
php artisan flussonic:monitor

// This command should:
// 1. Check if Flussonic is running
// 2. List all active streams
// 3. Get statistics for each stream
// 4. Alert on issues
// 5. Log metrics
```

## Troubleshooting

### Connection Issues

```bash
# Test Flussonic API connectivity
curl -H "X-Auth-Token: your_token" http://localhost:8080/api/v1/info

# Check if Flussonic is running
sudo systemctl status flussonic

# Check logs
sudo tail -f /var/log/flussonic/flussonic.log
```

### Stream Creation Failed

```bash
# Verify stream name is valid
# Stream names should be lowercase alphanumeric with underscores/hyphens

# Check Flussonic storage permissions
ls -la /var/cache/flussonic/

# Verify API token is correct
grep api_token /etc/flussonic/flussonic.conf
```

## Next Steps

1. Update your `.env` with Flussonic settings
2. Implement FlussonicService in your application
3. Add controller endpoints for Flussonic management
4. Create console commands for monitoring
5. Set up automated DVR recording
6. Configure relay streams

---

**Integration Status:** ✅ Ready for Implementation
