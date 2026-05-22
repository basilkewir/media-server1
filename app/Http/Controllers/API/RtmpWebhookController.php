<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Services\StreamingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Handles nginx-rtmp / SRS on_publish / on_publish_done callbacks.
 *
 * nginx-rtmp posts: app, name, addr, flashver, swfurl, tcurl, pageurl
 * SRS posts:        action, client_id, ip, vhost, app, stream, param
 *
 * Stream key = channel slug  →  rtmp://server/live/{slug}
 */
class RtmpWebhookController extends Controller
{
    public function onPublish(Request $request): Response
    {
        $slug = $this->resolveSlug($request);

        Log::info('RTMP on_publish', ['slug' => $slug, 'ip' => $request->ip()]);

        $channel = Channel::where('slug', $slug)->where('is_active', true)->first();

        if (!$channel) {
            Log::warning('RTMP publish rejected — unknown slug', ['slug' => $slug]);
            // nginx-rtmp: non-2xx = reject stream
            return response('Channel not found', 403);
        }

        try {
            // Source URL is the local RTMP re-stream from nginx-rtmp/SRS
            $sourceUrl = "rtmp://127.0.0.1/live/{$slug}";
            app(StreamingService::class)->startStream($channel, $sourceUrl);
        } catch (\Exception $e) {
            Log::error('RTMP startStream failed', ['slug' => $slug, 'error' => $e->getMessage()]);
            // Still return 200 so nginx-rtmp allows the stream through
        }

        return response('OK', 200);
    }

    public function onPublishDone(Request $request): Response
    {
        $slug = $this->resolveSlug($request);

        Log::info('RTMP on_publish_done', ['slug' => $slug]);

        $channel = Channel::where('slug', $slug)->first();

        if ($channel) {
            try {
                $service = app(StreamingService::class);

                // Auto-switch to VOD fallback if configured, otherwise stop
                if ($channel->vod_playlist_url) {
                    $service->switchToVODFallback($channel);
                    Log::info('Auto-switched to VOD fallback', ['channel' => $slug]);
                } else {
                    $service->stopStream($channel);
                }
            } catch (\Exception $e) {
                Log::error('RTMP on_publish_done handler failed', ['slug' => $slug, 'error' => $e->getMessage()]);
            }
        }

        return response('OK', 200);
    }

    private function resolveSlug(Request $request): string
    {
        // SRS sends 'stream', nginx-rtmp sends 'name'
        return $request->input('stream') ?? $request->input('name') ?? '';
    }
}
