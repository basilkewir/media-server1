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
 * Handles SRT push callbacks from vMix, OBS, FFmpeg, etc.
 *
 * SRT protocol supports receiving streams via:
 * - streamid parameter: srt://server:port?streamid=channel_slug
 * - Path-based: srt://server:port/channel_slug
 *
 * This controller validates the channel and starts the stream.
 */
class SrtWebhookController extends Controller
{
    /**
     * Handle incoming SRT stream connection.
     *
     * Called when an encoder (vMix/OBS) initiates SRT push.
     * The streamid parameter contains the channel slug.
     *
     * Example vMix SRT URL: srt://5.180.182.232:9000?streamid=compassiontv
     */
    public function onConnect(Request $request): Response
    {
        // Get streamid from query parameter or path
        $streamId = $request->input('streamid') 
            ?? $request->input('stream_id')
            ?? $request->path();

        // Clean up streamid (remove /srt/ prefix if present)
        $slug = preg_replace('#^/?srt/#i', '', $streamId);
        $slug = preg_replace('#[^a-z0-9\-_]#i', '', $slug);

        Log::info('SRT on_connect', [
            'streamid' => $streamId,
            'slug' => $slug,
            'ip' => $request->ip(),
            'all_input' => $request->all(),
        ]);

        // Validate channel exists and is active
        $channel = Channel::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$channel) {
            Log::warning('SRT connect rejected — unknown slug', ['slug' => $slug]);
            return response('Channel not found', 403);
        }

        try {
            // SRT source URL uses the local server and the streamid parameter
            $sourceUrl = "srt://127.0.0.1:9000?streamid={$slug}";
            app(StreamingService::class)->startStream($channel, $sourceUrl);
            
            Log::info('SRT stream started', ['channel_id' => $channel->id, 'slug' => $slug]);
        } catch (\Exception $e) {
            Log::error('SRT startStream failed', [
                'slug' => $slug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Return 200 anyway so the encoder doesn't retry excessively
        }

        return response('OK', 200);
    }

    /**
     * Handle SRT stream disconnection.
     *
     * Called when the encoder stops pushing or connection is lost.
     */
    public function onDisconnect(Request $request): Response
    {
        $streamId = $request->input('streamid') 
            ?? $request->input('stream_id')
            ?? $request->path();

        $slug = preg_replace('#^/?srt/#i', '', $streamId);
        $slug = preg_replace('#[^a-z0-9\-_]#i', '', $slug);

        Log::info('SRT on_disconnect', ['slug' => $slug]);

        $channel = Channel::where('slug', $slug)->first();

        if ($channel) {
            try {
                app(StreamingService::class)->stopStream($channel);
                Log::info('SRT stream stopped', ['channel_id' => $channel->id, 'slug' => $slug]);
            } catch (\Exception $e) {
                Log::error('SRT stopStream failed', [
                    'slug' => $slug,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response('OK', 200);
    }

    /**
     * Alternative endpoint for direct stream start.
     * 
     * Can be called from vMix or used for testing.
     * POST /api/srt/stream/start?streamid=compassiontv
     */
    public function startStream(Request $request): Response
    {
        $request->validate([
            'streamid' => 'required|string|min:1|max:255',
        ]);

        $streamId = $request->input('streamid');
        $slug = preg_replace('#[^a-z0-9\-_]#i', '', $streamId);

        Log::info('SRT startStream API called', ['slug' => $slug]);

        $channel = Channel::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$channel) {
            return response()->json(['error' => 'Channel not found'], 404);
        }

        try {
            $sourceUrl = "srt://127.0.0.1:9000?streamid={$slug}";
            app(StreamingService::class)->startStream($channel, $sourceUrl);
            return response()->json(['status' => 'ok', 'message' => "SRT stream started for {$slug}"]);
        } catch (\Exception $e) {
            Log::error('SRT startStream API failed', ['slug' => $slug, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to start stream'], 500);
        }
    }

    /**
     * Stop SRT stream.
     * 
     * POST /api/srt/stream/stop?streamid=compassiontv
     */
    public function stopStream(Request $request): Response
    {
        $request->validate([
            'streamid' => 'required|string|min:1|max:255',
        ]);

        $streamId = $request->input('streamid');
        $slug = preg_replace('#[^a-z0-9\-_]#i', '', $streamId);

        Log::info('SRT stopStream API called', ['slug' => $slug]);

        $channel = Channel::where('slug', $slug)->first();

        if (!$channel) {
            return response()->json(['error' => 'Channel not found'], 404);
        }

        try {
            app(StreamingService::class)->stopStream($channel);
            return response()->json(['status' => 'ok', 'message' => "SRT stream stopped for {$slug}"]);
        } catch (\Exception $e) {
            Log::error('SRT stopStream API failed', ['slug' => $slug, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to stop stream'], 500);
        }
    }
}
