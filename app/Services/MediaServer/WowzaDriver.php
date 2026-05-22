<?php

namespace App\Services\MediaServer;

use App\Models\Channel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WowzaDriver implements MediaServerDriver
{
    private string $baseUrl;
    private string $user;
    private string $pass;
    private string $app;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.wowza.url', 'http://localhost:8087'), '/');
        $this->user    = config('services.wowza.username', 'admin');
        $this->pass    = config('services.wowza.password', 'admin');
        $this->app     = config('services.wowza.application', 'live');
    }

    public function getName(): string { return 'Wowza Streaming Engine'; }

    public function startIngest(Channel $channel, string $sourceUrl, bool $loop = false): void
    {
        // Create a stream file in Wowza pointing to the source
        $streamName = $channel->slug;

        // Add stream file (for pull-based ingest like RTSP/HLS)
        $this->api("POST", "/v2/servers/_defaultServer_/vhosts/_defaultVHost_/applications/{$this->app}/streamfiles", [
            'name'       => $streamName,
            'serverName' => '_defaultServer_',
            'uri'        => $sourceUrl,
        ]);

        // Connect the stream file
        $this->api("PUT", "/v2/servers/_defaultServer_/vhosts/_defaultVHost_/applications/{$this->app}/streamfiles/{$streamName}/actions/connect", [
            'vhostName'       => '_defaultVHost_',
            'applicationName' => $this->app,
            'appInstanceName' => '_definst_',
            'mediaCasterType' => $this->detectCasterType($sourceUrl),
        ]);

        Log::info('Wowza ingest started', ['channel' => $channel->slug, 'source' => $sourceUrl]);
    }

    public function stopIngest(Channel $channel): void
    {
        $streamName = $channel->slug;

        // Disconnect stream file
        $this->api("PUT", "/v2/servers/_defaultServer_/vhosts/_defaultVHost_/applications/{$this->app}/streamfiles/{$streamName}/actions/disconnect");

        // Delete stream file
        $this->api("DELETE", "/v2/servers/_defaultServer_/vhosts/_defaultVHost_/applications/{$this->app}/streamfiles/{$streamName}");

        Log::info('Wowza ingest stopped', ['channel' => $channel->slug]);
    }

    public function isRunning(Channel $channel): bool
    {
        try {
            $response = $this->api("GET", "/v2/servers/_defaultServer_/vhosts/_defaultVHost_/applications/{$this->app}/instances/_definst_/incomingstreams/{$channel->slug}");
            return isset($response['isConnected']) && $response['isConnected'];
        } catch (\Exception $e) {
            return false;
        }
    }

    public function startOutput(Channel $channel, string $destUrl, array $options = []): string
    {
        // Use Wowza's Stream Target (push publishing) feature
        $targetName = $channel->slug . '_' . uniqid();

        $payload = [
            'name'        => $targetName,
            'streamName'  => $channel->slug,
            'hostPort'    => $this->parseHostPort($destUrl),
            'application' => $this->parseApp($destUrl),
            'userName'    => $options['username'] ?? '',
            'password'    => $options['password'] ?? '',
            'sourceStream'=> $channel->slug,
        ];

        $this->api("POST", "/v2/servers/_defaultServer_/vhosts/_defaultVHost_/applications/{$this->app}/pushpublish/mapentries/{$targetName}", $payload);

        Log::info('Wowza output started', ['channel' => $channel->slug, 'dest' => $destUrl, 'handle' => $targetName]);

        return $targetName;
    }

    public function stopOutput(Channel $channel, string $handle): void
    {
        $this->api("DELETE", "/v2/servers/_defaultServer_/vhosts/_defaultVHost_/applications/{$this->app}/pushpublish/mapentries/{$handle}");
        Log::info('Wowza output stopped', ['handle' => $handle]);
    }

    public function getStats(Channel $channel): array
    {
        try {
            $data = $this->api("GET", "/v2/servers/_defaultServer_/vhosts/_defaultVHost_/applications/{$this->app}/instances/_definst_/incomingstreams/{$channel->slug}");

            return [
                'driver'         => $this->getName(),
                'is_connected'   => $data['isConnected'] ?? false,
                'bytes_in'       => $data['bytesIn'] ?? 0,
                'bytes_out'      => $data['bytesOut'] ?? 0,
                'current_fps'    => $data['currentFPS'] ?? 0,
                'current_kbps'   => $data['currentKBitsPerSecond'] ?? 0,
                'uptime_seconds' => $data['uptimeSeconds'] ?? 0,
            ];
        } catch (\Exception $e) {
            return ['driver' => $this->getName(), 'error' => $e->getMessage()];
        }
    }

    private function api(string $method, string $path, array $body = []): array
    {
        $url = $this->baseUrl . $path;

        $request = Http::withBasicAuth($this->user, $this->pass)
            ->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
            ->timeout(10);

        $response = match (strtoupper($method)) {
            'GET'    => $request->get($url),
            'POST'   => $request->post($url, $body),
            'PUT'    => $request->put($url, $body),
            'DELETE' => $request->delete($url),
            default  => throw new \InvalidArgumentException("Unknown method: {$method}"),
        };

        if ($response->failed()) {
            throw new \RuntimeException("Wowza API error [{$response->status()}]: {$response->body()}");
        }

        return $response->json() ?? [];
    }

    private function detectCasterType(string $url): string
    {
        return match (true) {
            str_starts_with($url, 'rtsp://')  => 'rtp',
            str_starts_with($url, 'rtmp://')  => 'rtmp',
            str_starts_with($url, 'http://')  => 'applehls',
            str_starts_with($url, 'https://') => 'applehls',
            default                           => 'rtp',
        };
    }

    private function parseHostPort(string $url): string
    {
        $parsed = parse_url($url);
        $port   = $parsed['port'] ?? 1935;
        return ($parsed['host'] ?? 'localhost') . ':' . $port;
    }

    private function parseApp(string $url): string
    {
        $path = trim(parse_url($url, PHP_URL_PATH) ?? '/live', '/');
        $parts = explode('/', $path);
        return $parts[0] ?? 'live';
    }
}
