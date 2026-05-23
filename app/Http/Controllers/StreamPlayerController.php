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
    <title>{$this->e($channel->name)} — MediaServer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <style>
        :root {
            --surface-0:#09090b; --surface-1:#121216; --surface-2:#18181d;
            --text-primary:#f4f4f6; --text-secondary:#a1a1aa; --text-tertiary:#71717a;
            --brand:#6366f1; --brand-light:#818cf8; --brand-glow:rgba(99,102,241,0.25);
            --success:#22c55e; --danger:#ef4444; --danger-dim:#7f1d1d;
            --border:#27272a; --radius:10px;
            --font-sans:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
        }
        *{box-sizing:border-box;margin:0;padding:0}
        body{
            font-family:var(--font-sans);background:var(--surface-0);color:var(--text-primary);
            display:flex;flex-direction:column;height:100vh;overflow:hidden;
            -webkit-font-smoothing:antialiased;
        }
        .player-header{
            display:flex;align-items:center;justify-content:space-between;
            padding:12px 20px;background:var(--surface-1);border-bottom:1px solid var(--border);
            flex-shrink:0;z-index:10;
        }
        .player-title{font-size:15px;font-weight:700;display:flex;align-items:center;gap:8px;}
        .live-dot{width:8px;height:8px;border-radius:50%;background:var(--success);box-shadow:0 0 8px rgba(34,197,94,0.5);animation:pulse 2s ease-in-out infinite;flex-shrink:0;}
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
        .player-actions{display:flex;gap:8px;align-items:center;}
        .btn{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:5px;font-size:12px;font-weight:600;border:1px solid var(--border);background:transparent;color:var(--text-secondary);cursor:pointer;transition:all .15s;text-decoration:none;font-family:inherit;}
        .btn:hover{background:var(--surface-2);color:var(--text-primary)}
        .btn.active{background:var(--brand);color:white;border-color:var(--brand)}
        .video-container{flex:1;display:flex;align-items:center;justify-content:center;background:#000;position:relative;min-height:0;}
        video{max-width:100%;max-height:100%;width:100%;height:100%;object-fit:contain;}
        .state-overlay{
            position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
            background:rgba(0,0,0,0.8);z-index:5;
        }
        .state-message{text-align:center;padding:40px;}
        .state-message svg{width:48px;height:48px;margin-bottom:12px;stroke:var(--text-tertiary)}
        .state-title{font-size:18px;font-weight:700;margin-bottom:6px}
        .state-text{font-size:14px;color:var(--text-tertiary)}
        .spinner{width:28px;height:28px;border:3px solid var(--border);border-top-color:var(--brand);border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 12px;}
        @keyframes spin{to{transform:rotate(360deg)}}
        @media(max-width:640px){.player-header{padding:8px 12px}.player-title{font-size:13px}}
    </style>
</head>
<body>
<header class="player-header">
    <div class="player-title">
        <span class="live-dot"></span>{$this->e($channel->name)}
    </div>
    <div class="player-actions">
        <button class="btn" onclick="toggleFullscreen()" title="Fullscreen">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3"/></svg>
        </button>
        <button class="btn active" id="btn-hls" onclick="switchFormat('hls')">HLS</button>
        <button class="btn" id="btn-dash" onclick="switchFormat('dash')">DASH</button>
    </div>
</header>

<div class="video-container">
    <div class="state-overlay" id="loading-state">
        <div class="state-message">
            <div class="spinner"></div>
            <div class="state-title">Loading Stream</div>
            <div class="state-text">Connecting to {$this->e($channel->name)}...</div>
        </div>
    </div>
    <video id="video" controls autoplay playsinline></video>
</div>

<script>
(function() {
    var video = document.getElementById('video');
    var loading = document.getElementById('loading-state');
    var hlsUrl = "{$hlsUrl}";
    var dashUrl = "{$dashUrl}";
    var currentFormat = '{$this->e($format)}';
    var hls = null;

    function initHls() {
        if (Hls.isSupported()) {
            hls = new Hls({ enableWorker: true, lowLatencyMode: true });
            hls.loadSource(hlsUrl);
            hls.attachMedia(video);
            hls.on(Hls.Events.MANIFEST_PARSED, function() {
                loading.style.display = 'none';
                video.play().catch(function(){});
            });
            hls.on(Hls.Events.ERROR, function(event, data) {
                if (data.fatal) {
                    loading.innerHTML = '<div class="state-message"><svg fill="none" stroke="#ef4444" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg><div class="state-title" style="color:#f87171;">Stream Error</div><div class="state-text">The stream may be offline. Retrying...</div></div>';
                    loading.style.display = 'flex';
                    setTimeout(function() { hls.loadSource(hlsUrl); }, 5000);
                }
            });
        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = hlsUrl;
            video.addEventListener('loadeddata', function(){ loading.style.display = 'none'; });
        } else {
            loading.innerHTML = '<div class="state-message"><svg fill="none" stroke="#ef4444" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><div class="state-title" style="color:#f87171;">Unsupported</div><div class="state-text">Your browser does not support HLS playback.</div></div>';
            loading.style.display = 'flex';
        }
    }

    if (currentFormat === 'hls') {
        initHls();
    } else {
        loading.innerHTML = '<div class="state-message"><svg fill="none" stroke="#f59e0b" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg><div class="state-title">DASH Format</div><div class="state-text">DASH playback requires an external player or native browser support.</div></div>';
        loading.style.display = 'flex';
    }

    window.switchFormat = function(f) {
        document.getElementById('btn-hls').className = 'btn' + (f === 'hls' ? ' active' : '');
        document.getElementById('btn-dash').className = 'btn' + (f === 'dash' ? ' active' : '');
        if (f === 'hls' && !hls) {
            loading.innerHTML = '<div class="state-message"><div class="spinner"></div><div class="state-title">Loading Stream</div></div>';
            loading.style.display = 'flex';
            initHls();
        }
    };

    window.toggleFullscreen = function() {
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            document.documentElement.requestFullscreen();
        }
    };
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
