@extends('layouts.admin')
@section('title', $channel->name)
@section('breadcrumb')
    <a href="{{ route('admin.channels.index') }}">Channels</a> / {{ $channel->name }}
@endsection
@section('topbar-actions')
    <a href="{{ route('admin.channels.edit', $channel) }}" class="btn btn-ghost btn-sm">Edit</a>
    <a href="{{ route('admin.vod.index', $channel) }}" class="btn btn-ghost btn-sm">🎬 VOD Library</a>
    <a href="{{ route('admin.channels.events', $channel) }}" class="btn btn-ghost btn-sm">📋 Events</a>
@endsection

@section('content')
@php
    $activeStream = $channel->activeStream();
    $isLive       = $channel->is_live && !$activeStream?->isFallback();
    $isFallback   = $activeStream?->isFallback();
    $hasVod       = !empty($channel->vod_playlist_url);
    $hasAbr       = $channel->resolution && $channel->bitrate_kbps;
    $slug         = $channel->slug;
@endphp

{{-- Status bar --}}
<div class="card" style="padding:1.25rem 1.5rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;align-items:center;gap:1.25rem;">
            @if($isLive)
                <span style="display:flex;align-items:center;gap:0.5rem;font-size:1.1rem;font-weight:700;color:var(--live)">
                    <span class="dot dot-live"></span> LIVE
                </span>
            @elseif($isFallback)
                <span style="display:flex;align-items:center;gap:0.5rem;font-size:1.1rem;font-weight:700;color:#f59e0b">
                    <span class="dot dot-vod"></span> VOD FALLBACK
                </span>
            @else
                <span style="display:flex;align-items:center;gap:0.5rem;font-size:1.1rem;font-weight:700;color:var(--muted)">
                    <span class="dot dot-offline"></span> OFFLINE
                </span>
            @endif

            @if($activeStream)
                <span class="badge badge-{{ strtolower($activeStream->input_protocol ?? 'rtmp') }}">
                    {{ strtoupper($activeStream->input_protocol ?? '') }}
                </span>
                <span class="text-sm text-muted">{{ gmdate('H:i:s', $activeStream->getDuration()) }}</span>
            @endif
        </div>

        <div class="actions">
            @if(!$channel->is_live)
                <button onclick="document.getElementById('start-modal').style.display='flex'" class="btn btn-success btn-sm">▶ Start Stream</button>
            @else
                <form method="POST" action="{{ route('admin.channels.stop', $channel) }}" style="display:inline">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Stop the stream?')">⏹ Stop</button>
                </form>
                @if($hasVod && !$isFallback)
                <form method="POST" action="{{ route('admin.channels.fallback', $channel) }}" style="display:inline">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Switch to VOD fallback?')">⏸ VOD Fallback</button>
                </form>
                @endif
            @endif
            @if($isFallback)
                <button onclick="document.getElementById('recover-modal').style.display='flex'" class="btn btn-primary btn-sm">↻ Recover Live</button>
            @endif
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">

{{-- Left column --}}
<div>
    {{-- Ingest endpoints --}}
    <div class="card">
        <div class="card-header"><span class="card-title">📡 Ingest Endpoints</span></div>
        <p class="text-xs text-muted" style="margin-bottom:1rem;">Push your encoder to any of these URLs using slug <code class="mono">{{ $slug }}</code></p>

        <div style="display:flex;flex-direction:column;gap:0.6rem;">
            @foreach([
                ['RTMP',  "rtmp://".request()->getHost()."/live/{$slug}", 'badge-rtmp'],
                ['SRT',   "srt://".request()->getHost().":10080?streamid=#!::r=live/{$slug},m=publish", 'badge-srt'],
                ['RTSP',  "rtsp://".request()->getHost().":8554/live/{$slug}", 'badge-active'],
                ['HLS In',"http://".request()->getHost()."/streams/{$slug}/playlist.m3u8", 'badge-hls'],
            ] as [$label, $url, $badge])
            <div>
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.25rem;">
                    <span class="badge {{ $badge }}" style="font-size:0.65rem;">{{ $label }}</span>
                </div>
                <div class="url-box">
                    <code>{{ $url }}</code>
                    <button class="copy-btn" onclick="copyText('{{ $url }}', this)">Copy</button>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Output URLs --}}
    <div class="card">
        <div class="card-header"><span class="card-title">📤 Output URLs</span></div>
        <div style="display:flex;flex-direction:column;gap:0.6rem;">
            @if($hasAbr)
            <div>
                <div style="margin-bottom:0.25rem;"><span class="badge badge-hls">Master HLS (ABR)</span></div>
                <div class="url-box">
                    <code>{{ url("/streams/{$slug}/master.m3u8") }}</code>
                    <button class="copy-btn" onclick="copyText('{{ url("/streams/{$slug}/master.m3u8") }}', this)">Copy</button>
                </div>
            </div>
            @endif
            @foreach([
                ['HLS',  url("/streams/{$slug}/playlist.m3u8"), 'badge-hls'],
                ['DASH', url("/streams/{$slug}/manifest.mpd"),  'badge-active'],
            ] as [$label, $url, $badge])
            <div>
                <div style="margin-bottom:0.25rem;"><span class="badge {{ $badge }}">{{ $label }}</span></div>
                <div class="url-box">
                    <code>{{ $url }}</code>
                    <button class="copy-btn" onclick="copyText('{{ $url }}', this)">Copy</button>
                </div>
            </div>
            @endforeach
            <div>
                <div style="margin-bottom:0.25rem;"><span class="badge badge-rtmp">Player</span></div>
                <div class="url-box">
                    <code>{{ route('stream.play', $slug) }}</code>
                    <a href="{{ route('stream.play', $slug) }}" target="_blank" class="copy-btn">Open ↗</a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Right column --}}
<div>
    {{-- Channel info --}}
    <div class="card">
        <div class="card-header"><span class="card-title">Channel Info</span></div>
        <dl class="dl-grid">
            <dt>Name</dt><dd>{{ $channel->name }}</dd>
            <dt>Slug</dt><dd><code class="mono">{{ $channel->slug }}</code></dd>
            @if($channel->description)
            <dt>Description</dt><dd>{{ $channel->description }}</dd>
            @endif
            <dt>Active</dt><dd>{{ $channel->is_active ? '✓ Yes' : '✕ No' }}</dd>
        </dl>

        @if($hasAbr)
        <hr class="section-divider">
        <div class="card-title" style="margin-bottom:0.75rem;">ABR Ladder</div>
        @php
            [$w, $h] = array_map('intval', explode('x', strtolower($channel->resolution)));
            $bitrate = $channel->bitrate_kbps;
            $rungs = [[$channel->resolution, $bitrate]];
            if ($h >= 1080) { $rungs[] = ['1280x720', (int)($bitrate*0.5)]; $rungs[] = ['854x480', (int)($bitrate*0.25)]; $rungs[] = ['640x360', (int)($bitrate*0.12)]; }
            elseif ($h >= 720) { $rungs[] = ['854x480', (int)($bitrate*0.5)]; $rungs[] = ['640x360', (int)($bitrate*0.25)]; }
        @endphp
        <div class="abr-ladder">
            @foreach($rungs as [$res, $br])
                <span class="abr-rung">{{ $res }} @ {{ $br }}k</span>
            @endforeach
        </div>
        @endif

        @if($hasVod)
        <hr class="section-divider">
        <div class="flex" style="justify-content:space-between;">
            <div>
                <div class="card-title">VOD Fallback</div>
                <div class="text-xs text-muted mono" style="margin-top:0.25rem;word-break:break-all;">{{ $channel->vod_playlist_url }}</div>
            </div>
            <a href="{{ route('admin.vod.index', $channel) }}" class="btn btn-ghost btn-sm">Manage</a>
        </div>
        @else
        <hr class="section-divider">
        <div class="flex" style="justify-content:space-between;align-items:center;">
            <span class="text-sm text-muted">No VOD fallback configured</span>
            <a href="{{ route('admin.vod.index', $channel) }}" class="btn btn-ghost btn-sm">+ Add VOD</a>
        </div>
        @endif
    </div>

    {{-- Active stream details --}}
    @if($activeStream)
    <div class="card">
        <div class="card-header"><span class="card-title">Active Stream</span></div>
        <dl class="dl-grid">
            <dt>Source</dt><dd><code class="mono" style="word-break:break-all;font-size:0.75rem;">{{ $activeStream->source_url }}</code></dd>
            <dt>Protocol</dt><dd><span class="badge badge-{{ strtolower($activeStream->input_protocol ?? 'rtmp') }}">{{ strtoupper($activeStream->input_protocol ?? '—') }}</span></dd>
            <dt>Type</dt><dd>{{ ucfirst($activeStream->stream_type) }}</dd>
            <dt>Started</dt><dd>{{ $activeStream->started_at?->format('Y-m-d H:i:s') }}</dd>
            <dt>Duration</dt><dd>{{ gmdate('H:i:s', $activeStream->getDuration()) }}</dd>
        </dl>
    </div>
    @endif
</div>
</div>

{{-- Recent streams --}}
@if($channel->streams->count())
<div class="card">
    <div class="card-header">
        <span class="card-title">Recent Streams</span>
        <a href="{{ route('admin.channels.events', $channel) }}" class="btn btn-ghost btn-sm">Event Log</a>
    </div>
    <div class="table-wrap">
    <table>
        <thead>
            <tr><th>Type</th><th>Status</th><th>Protocol</th><th>Source</th><th>Started</th><th>Duration</th></tr>
        </thead>
        <tbody>
            @foreach($channel->streams as $s)
            <tr>
                <td><span class="badge badge-{{ $s->stream_type === 'vod' ? 'vod' : 'active' }}">{{ ucfirst($s->stream_type) }}</span></td>
                <td>
                    @if($s->isActive()) <span style="color:var(--live);font-weight:600;">Active</span>
                    @elseif($s->isFallback()) <span style="color:#f59e0b;font-weight:600;">Fallback</span>
                    @else <span class="text-muted">{{ ucfirst($s->status) }}</span>
                    @endif
                </td>
                <td><span class="badge badge-{{ strtolower($s->input_protocol ?? 'rtmp') }}">{{ strtoupper($s->input_protocol ?? '—') }}</span></td>
                <td><code class="mono" style="font-size:0.72rem;">{{ Str::limit($s->source_url, 45) }}</code></td>
                <td class="text-sm">{{ $s->started_at?->format('Y-m-d H:i') ?? '—' }}</td>
                <td class="text-sm">{{ gmdate('H:i:s', $s->getDuration()) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif

{{-- Danger zone --}}
<div class="card" style="border-color:rgba(220,38,38,0.3);">
    <div class="card-header"><span class="card-title" style="color:var(--danger);">Danger Zone</span></div>
    <p class="text-sm text-muted" style="margin-bottom:1rem;">Permanently deletes this channel, all streams, events, and VOD files.</p>
    <form method="POST" action="{{ route('admin.channels.destroy', $channel) }}" onsubmit="return confirm('Permanently delete {{ $channel->name }}?')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm">Delete Channel</button>
    </form>
</div>

{{-- Start stream modal --}}
<div id="start-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:999;align-items:center;justify-content:center;"
     onclick="if(event.target===this)this.style.display='none'">
    <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;padding:2rem;max-width:480px;width:90%;">
        <h2 style="margin-bottom:1.25rem;">▶ Start Stream</h2>
        <form method="POST" action="{{ route('admin.channels.start', $channel) }}">
            @csrf
            <div class="form-group" style="margin-bottom:1rem;">
                <label>Source URL</label>
                <input type="text" name="push_url" placeholder="rtmp://source/live/key  or  srt://...  or  rtsp://..." required>
                <span class="hint">Supports RTMP, SRT, RTSP, HLS, UDP, RTP, HTTP</span>
            </div>
            <div class="actions">
                <button type="submit" class="btn btn-success">▶ Start</button>
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('start-modal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Recover modal --}}
<div id="recover-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:999;align-items:center;justify-content:center;"
     onclick="if(event.target===this)this.style.display='none'">
    <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;padding:2rem;max-width:480px;width:90%;">
        <h2 style="margin-bottom:1.25rem;">↻ Recover to Live</h2>
        <form method="POST" action="{{ route('admin.channels.recover', $channel) }}">
            @csrf
            <div class="form-group" style="margin-bottom:1rem;">
                <label>Live Source URL</label>
                <input type="text" name="push_url" placeholder="rtmp://source/live/key" required>
            </div>
            <div class="actions">
                <button type="submit" class="btn btn-primary">↻ Recover</button>
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('recover-modal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection
