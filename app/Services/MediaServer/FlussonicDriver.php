<?php

namespace App\Services\MediaServer;

use App\Models\Channel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlussonicDriver implements MediaServerDriver
{
    private string $baseUrl;
    private string $user;
    private string $pass;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.flussonic.url', 'http://localhost:8080'), '/');
        $this->user    = config('services.flussonic.username', 'admin');
        $this->pass    = config('services.flussonic.password', 'admin');
    }

    public function getName(): string { return 'Flussonic Media Server'; }

    public function startIngest(Channel $channel, string $sourceUrl, bool $loop = false): void
    {
        // Create or update a stream in Flussonic config
        $payload = [
            'name'  => $channel->slug,
            'input' => $this->buildFlussonicInput($sourceUrl, $loop),
            'outputs' => [
                // HLS output — Flussonic serves this natively
                ['url' => "hls://{$channel->slug}"],
                // MPEG-TS pipe for zero-latency outputs
                ['url' => "file:///" . storage_path("streams/{$channel->slug}/live.ts")],
            ],
        ];

        $this->api('PUT', "/flussonic/api/stream/{$channel->slug}", $payload);

        Log::info('Flussonic ingest started', ['channel' => $channel->slug, 'source' => $sourceUrl]);
    }

    public function stopIngest(Channel $channel): void
    {
        $this->api('DELETE', "/flussonic/api/stream/{$channel->slug}");
        Log::info('Flussonic ingest stopped', ['channel' => $channel->slug]);
    }

    public function isRunning(Channel $channel): bool
    {
        try {
            $data = $this->api('GET', "/flussonic/api/stream/{$channel->slug}/stat");
            return ($data['alive'] ?? false) === true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function startOutput(Channel $channel, string $destUrl, array $options = []): string
    {
        // Add a push output to the existing stream
        $outputId = uniqid('out_');

        $current = $this->api('GET', "/flussonic/api/stream/{$channel->slug}");
        $outputs  = $current['outputs'] ?? [];

        $outputs[] = [
            'url'  => $destUrl,
            'name' => $outputId,
        ];

        $this->api('PUT', "/flussonic/api/stream/{$channel->slug}", array_merge($current, ['outputs' => $outputs]));

        Log::info('Flussonic output started', ['channel' => $channel->slug, 'dest' => $destUrl, 'id' => $outputId]);

        return $outputId;
    }

    public function stopOutput(Channel $channel, string $handle): void
    {
        try {
            $current = $this->api('GET', "/flussonic/api/stream/{$channel->slug}");
            $outputs  = array_filter($current['outputs'] ?? [], fn($o) => ($o['name'] ?? '') !== $handle);
            $this->api('PUT', "/flussonic/api/stream/{$channel->slug}", array_merge($current, ['outputs' => array_values($outputs)]));
        } catch (\Exception $e) {
            Log::warning('Flussonic stopOutput failed', ['handle' => $handle, 'error' => $e->getMessage()]);
        }
    }

    public function getStats(Channel $channel): array
    {
        try {
            $data = $this->api('GET', "/flussonic/api/stream/{$channel->slug}/stat");

            return [
                'driver'         => $this->getName(),
                'alive'          => $data['alive'] ?? false,
                'input_bitrate'  => $data['input_bitrate'] ?? 0,
                'clients'        => $data['clients'] ?? 0,
                'bytes_in'       => $data['bytes_in'] ?? 0,
                'bytes_out'      => $data['bytes_out'] ?? 0,
                'uptime'         => $data['uptime'] ?? 0,
            ];
        } catch (\Exception $e) {
            return ['driver' => $this->getName(), 'error' => $e->getMessage()];
        }
    }

    private function buildFlussonicInput(string $url, bool $loop): string
    {
        // Flussonic uses its own URL scheme for inputs
        if ($loop) {
            // file:// with loop option for VOD
            return "file://{$url}?loop=true";
        }

        return match (true) {
            str_starts_with($url, 'rtmp://')  => $url,
            str_starts_with($url, 'rtsp://')  => $url,
            str_starts_with($url, 'srt://')   => $url,
            str_starts_with($url, 'udp://')   => $url,
            str_starts_with($url, 'http://')  => $url,
            str_starts_with($url, 'https://') => $url,
            default                           => $url,
        };
    }

    private function api(string $method, string $path, array $body = []): array
    {
        $url = $this->baseUrl . $path;

        $request = Http::withBasicAuth($this->user, $this->pass)
            ->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
            ->timeout(10);

        $response = match (strtoupper($method)) {
            'GET'    => $request->get($url),
            'PUT'    => $request->put($url, $body),
            'POST'   => $request->post($url, $body),
            'DELETE' => $request->delete($url),
            default  => throw new \InvalidArgumentException("Unknown method: {$method}"),
        };

        if ($response->failed()) {
            throw new \RuntimeException("Flussonic API error [{$response->status()}]: {$response->body()}");
        }

        return $response->json() ?? [];
    }
}
