<?php

namespace App\Services\MediaServer;

use App\Models\Channel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Flussonic Media Server v24 driver.
 *
 * Flussonic runs on port 8935 (HTTP API + UI).
 * RTMP ingest stays on port 1935.
 *
 * API base: http://localhost:8935
 * Auth:     HTTP Basic  (edit_auth user pass in flussonic.conf)
 * Docs:     https://flussonic.com/doc/api/
 */
class FlussonicDriver implements MediaServerDriver
{
    private string $baseUrl;
    private string $user;
    private string $pass;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.flussonic.url', 'http://localhost:8935'), '/');
        $this->user    = config('services.flussonic.username', 'flussonic');
        $this->pass    = config('services.flussonic.password', 'letmein!');
    }

    public function getName(): string
    {
        return 'Flussonic Media Server v24';
    }

    // ── Ingest ────────────────────────────────────────────────────────────────

    public function startIngest(Channel $channel, string $sourceUrl, bool $loop = false): void
    {
        $input = $this->buildInput($sourceUrl, $loop);

        // Flussonic v24 stream config via REST API
        $payload = [
            'name'   => $channel->slug,
            'input'  => $input,
            'dvr'    => null,
        ];

        // PUT creates or replaces the stream
        $this->api('PUT', "/streamer/api/v3/streams/{$channel->slug}", $payload);

        Log::info('Flussonic ingest started', [
            'channel' => $channel->slug,
            'source'  => $sourceUrl,
            'input'   => $input,
        ]);
    }

    public function stopIngest(Channel $channel): void
    {
        try {
            $this->api('DELETE', "/streamer/api/v3/streams/{$channel->slug}");
        } catch (\Exception $e) {
            // Stream may not exist — not an error
            Log::debug('Flussonic stopIngest: ' . $e->getMessage());
        }

        Log::info('Flussonic ingest stopped', ['channel' => $channel->slug]);
    }

    public function isRunning(Channel $channel): bool
    {
        try {
            $data = $this->api('GET', "/streamer/api/v3/streams/{$channel->slug}");
            // alive=true means Flussonic has an active source
            return ($data['alive'] ?? false) === true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // ── Outputs ───────────────────────────────────────────────────────────────

    public function startOutput(Channel $channel, string $destUrl, array $options = []): string
    {
        // Fetch current stream config
        $stream  = $this->api('GET', "/streamer/api/v3/streams/{$channel->slug}");
        $outputs = $stream['outputs'] ?? [];

        $outputName = 'out_' . substr(md5($destUrl . microtime()), 0, 8);

        $outputs[] = [
            'url'  => $destUrl,
            'name' => $outputName,
        ];

        $this->api('PUT', "/streamer/api/v3/streams/{$channel->slug}",
            array_merge($stream, ['outputs' => $outputs]));

        Log::info('Flussonic output started', [
            'channel' => $channel->slug,
            'dest'    => $destUrl,
            'name'    => $outputName,
        ]);

        return $outputName;
    }

    public function stopOutput(Channel $channel, string $handle): void
    {
        try {
            $stream  = $this->api('GET', "/streamer/api/v3/streams/{$channel->slug}");
            $outputs = array_values(
                array_filter($stream['outputs'] ?? [], fn($o) => ($o['name'] ?? '') !== $handle)
            );
            $this->api('PUT', "/streamer/api/v3/streams/{$channel->slug}",
                array_merge($stream, ['outputs' => $outputs]));
        } catch (\Exception $e) {
            Log::warning('Flussonic stopOutput failed', ['handle' => $handle, 'error' => $e->getMessage()]);
        }
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    public function getStats(Channel $channel): array
    {
        try {
            $data = $this->api('GET', "/streamer/api/v3/streams/{$channel->slug}");

            return [
                'driver'        => $this->getName(),
                'alive'         => $data['alive'] ?? false,
                'input_bitrate' => $data['input_bitrate'] ?? 0,
                'clients'       => $data['clients'] ?? 0,
                'bytes_in'      => $data['bytes_in'] ?? 0,
                'bytes_out'     => $data['bytes_out'] ?? 0,
                'uptime'        => $data['uptime'] ?? 0,
                'outputs'       => collect($data['outputs'] ?? [])->map(fn($o) => [
                    'name'   => $o['name'] ?? '',
                    'url'    => $o['url'] ?? '',
                    'alive'  => $o['alive'] ?? false,
                ])->toArray(),
            ];
        } catch (\Exception $e) {
            return ['driver' => $this->getName(), 'alive' => false, 'error' => $e->getMessage()];
        }
    }

    // ── VOD fallback helper ───────────────────────────────────────────────────

    /**
     * Build the Flussonic input string.
     * For VOD fallback: use file:// with loop=true so Flussonic loops the playlist.
     * For live: pass the URL as-is (rtmp, rtsp, srt, udp, http all work natively).
     */
    private function buildInput(string $url, bool $loop): string
    {
        if ($loop) {
            // Flussonic file input with loop for VOD fallback
            $path = str_starts_with($url, 'http') ? $url : ltrim($url, '/');
            return str_starts_with($url, 'http')
                ? "{$url}?loop=true"
                : "file://{$url}?loop=true";
        }

        // Live protocols — Flussonic handles all natively
        return $url;
    }

    // ── HTTP API client ───────────────────────────────────────────────────────

    private function api(string $method, string $path, array $body = []): array
    {
        $url = $this->baseUrl . $path;

        $req = Http::withBasicAuth($this->user, $this->pass)
            ->withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout(10);

        $response = match (strtoupper($method)) {
            'GET'    => $req->get($url),
            'PUT'    => $req->put($url, $body),
            'POST'   => $req->post($url, $body),
            'DELETE' => $req->delete($url),
            default  => throw new \InvalidArgumentException("Unknown HTTP method: {$method}"),
        };

        if ($response->serverError()) {
            throw new \RuntimeException(
                "Flussonic API server error [{$response->status()}] {$path}: {$response->body()}"
            );
        }

        // 404 on DELETE = already gone, that's fine
        if ($response->status() === 404 && strtoupper($method) === 'DELETE') {
            return [];
        }

        if ($response->failed()) {
            throw new \RuntimeException(
                "Flussonic API error [{$response->status()}] {$path}: {$response->body()}"
            );
        }

        return $response->json() ?? [];
    }
}
