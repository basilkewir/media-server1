<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Channel;
use Illuminate\Http\Response;

class StreamPlayerController extends Controller
{
    public function play(string $slug, string $format = 'hls'): Response
    {
        $channel = Channel::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $hlsUrl  = url("/streams/{$slug}/playlist.m3u8");
        $dashUrl = url("/streams/{$slug}/manifest.mpd");

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{$this->e($channel->name)} - MediaServer</title>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <style>
        body { margin: 0; background: #000; display: flex; align-items: center; justify-content: center; height: 100vh; }
        video { width: 100%; height: 100%; max-height: 100vh; object-fit: contain; }
        .error { color: #fff; font-family: sans-serif; text-align: center; padding: 2rem; }
    </style>
</head>
<body>
<video id="video" controls autoplay playsinline></video>
<script>
(function() {
    var src = "{$hlsUrl}";
    var video = document.getElementById('video');
    if (Hls.isSupported()) {
        var hls = new Hls({ enableWorker: true, lowLatencyMode: true });
        hls.loadSource(src);
        hls.attachMedia(video);
        hls.on(Hls.Events.ERROR, function(event, data) {
            console.error('HLS error:', data);
        });
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = src;
    } else {
        document.body.innerHTML = '<div class="error">Your browser does not support HLS playback.</div>';
    }
})();
</script>
</body>
</html>
HTML;

        return response($html)
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function hlsManifest(string $slug): Response
    {
        $channel = Channel::where('slug', $slug)->firstOrFail();
        $path    = storage_path("streams/{$slug}/playlist.m3u8");

        if (!file_exists($path)) {
            return response('Stream not available', 503)
                ->header('Content-Type', 'text/plain');
        }

        return response(file_get_contents($path))
            ->header('Content-Type', 'application/vnd.apple.mpegurl')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function hlsSegment(string $slug, string $segment): Response
    {
        $channel = Channel::where('slug', $slug)->firstOrFail();
        $path    = storage_path("streams/{$slug}/{$segment}.ts");

        if (!file_exists($path)) {
            return response('Segment not found', 404)
                ->header('Content-Type', 'text/plain');
        }

        return response(file_get_contents($path))
            ->header('Content-Type', 'video/MP2T')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function dashManifest(string $slug): Response
    {
        $channel = Channel::where('slug', $slug)->firstOrFail();
        $path    = storage_path("streams/{$slug}/manifest.mpd");

        if (!file_exists($path)) {
            return response('DASH manifest not available', 503)
                ->header('Content-Type', 'text/plain');
        }

        return response(file_get_contents($path))
            ->header('Content-Type', 'application/dash+xml')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Access-Control-Allow-Origin', '*');
    }

    private function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
