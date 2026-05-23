@extends('layouts.admin')
@section('title', $channel->name)
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span>
    <a href="{{ route('admin.channels.index') }}">Channels</a> <span class="sep">/</span> {{ $channel->name }}
@endsection

@section('topbar-actions')
    <a href="{{ route('admin.channels.edit', $channel) }}" class="btn btn-ghost btn-sm">Edit</a>
    <a href="{{ route('admin.vod.index', $channel) }}" class="btn btn-ghost btn-sm">VOD Library</a>
    <a href="{{ route('admin.channels.graphics', $channel) }}" class="btn btn-ghost btn-sm">Graphics</a>
    <a href="{{ route('admin.channels.events', $channel) }}" class="btn btn-ghost btn-sm">Events</a>
    <a href="{{ route('stream.play', $channel->slug) }}" target="_blank" class="btn btn-ghost btn-sm">Player</a>
@endsection

@section('content')
@php $stream = $channel->activeStream(); @endphp

{{-- Status Bar --}}
<div class="animate-in">
    <div class="card" style="padding:18px 24px; margin-bottom:20px; display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
        @if($stream && $stream->status === 'active')
            <span class="badge badge-success" style="font-size:13px;"><span class="badge-dot green" style="margin-right:6px;"></span>LIVE</span>
            <span class="badge badge-brand">{{ strtoupper($stream->input_protocol) }}</span>
            <span style="font-size:13px;color:var(--text-secondary);">Duration: {{ gmdate('H:i:s', $stream->getDuration()) }}</span>
            @if($channel->vod_playlist_url)
                <form method="POST" action="{{ route('admin.channels.fallback', $channel) }}" style="display:inline;margin-left:auto;">
                    @csrf
                    <button class="btn btn-warning btn-sm">Switch to VOD Fallback</button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.channels.stop', $channel) }}" style="display:inline;">
                @csrf
                <button class="btn btn-danger btn-sm" onclick="return confirm('Stop this stream?')">Stop Stream</button>
            </form>
        @elseif($stream && $stream->status === 'fallback')
            <span class="badge badge-warning" style="font-size:13px;"><span class="badge-dot amber" style="margin-right:6px;"></span>VOD FALLBACK</span>
            <span style="font-size:13px;color:var(--text-secondary);">Duration: {{ gmdate('H:i:s', $stream->getDuration()) }}</span>
            <form method="POST" action="{{ route('admin.channels.recover', $channel) }}" style="display:inline;margin-left:auto;" onsubmit="return showRecoverModal(event)">
                @csrf
                <button class="btn btn-success btn-sm">Recover Live</button>
            </form>
        @else
            <span class="badge badge-neutral" style="font-size:13px;"><span class="badge-dot gray" style="margin-right:6px;"></span>OFFLINE</span>
            <button class="btn btn-primary btn-sm" onclick="openModal('startModal')" style="margin-left:auto;">Start Stream</button>
        @endif
    </div>
</div>

{{-- Main Grid --}}
<div style="display:grid; grid-template-columns:1.5fr 1fr; gap:20px;" class="animate-in" style="animation-delay:0.1s;">
    {{-- Left Column --}}
    <div style="display:flex;flex-direction:column;gap:20px;">
        {{-- Ingest Endpoints --}}
        <div class="card">
            <div class="card-title" style="margin-bottom:16px;">Ingest Endpoints</div>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-tertiary);margin-bottom:6px;">RTMP</div>
                    <div class="url-box">
                        <code>rtmp://{{ request()->getHost() }}/live/{{ $channel->slug }}</code>
                        <button class="copy-btn" onclick="copyToClipboard('rtmp://{{ request()->getHost() }}/live/{{ $channel->slug }}', this)">Copy</button>
                    </div>
                </div>
                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-tertiary);margin-bottom:6px;">SRT (Low Latency)</div>
                    <div class="url-box">
                        <code>srt://{{ request()->getHost() }}:10080?streamid=#!::r=live/{{ $channel->slug }},m=publish</code>
                        <button class="copy-btn" onclick="copyToClipboard('srt://{{ request()->getHost() }}:10080?streamid=#!::r=live/{{ $channel->slug }},m=publish', this)">Copy</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Output URLs --}}
        <div class="card">
            <div class="card-title" style="margin-bottom:16px;">Output URLs</div>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div class="url-box">
                    <code>{{ url('/streams/'.$channel->slug.'/playlist.m3u8') }}</code>
                    <button class="copy-btn" onclick="copyToClipboard('{{ url('/streams/'.$channel->slug.'/playlist.m3u8') }}', this)">Copy</button>
                </div>
                <div class="url-box">
                    <code>{{ url('/streams/'.$channel->slug.'/manifest.mpd') }}</code>
                    <button class="copy-btn" onclick="copyToClipboard('{{ url('/streams/'.$channel->slug.'/manifest.mpd') }}', this)">Copy</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Right Column --}}
    <div style="display:flex;flex-direction:column;gap:20px;">
        {{-- Channel Info --}}
        <div class="card">
            <div class="card-title" style="margin-bottom:16px;">Channel Info</div>
            <div style="display:grid;grid-template-columns:auto 1fr;gap:6px 16px;font-size:13px;">
                <span style="color:var(--text-tertiary);">Name</span>
                <span style="font-weight:600;">{{ $channel->name }}</span>
                <span style="color:var(--text-tertiary);">Slug</span>
                <code style="font-size:12px;font-family:var(--font-mono);">{{ $channel->slug }}</code>
                <span style="color:var(--text-tertiary);">Active</span>
                <span class="badge {{ $channel->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $channel->is_active ? 'Yes' : 'No' }}</span>
                @if($channel->resolution && $channel->bitrate_kbps)
                <span style="color:var(--text-tertiary);">Quality</span>
                <span>{{ $channel->resolution }} @ {{ round($channel->bitrate_kbps/1000,1) }} Mbps</span>
                @endif
                @if($channel->vod_playlist_url)
                <span style="color:var(--text-tertiary);">VOD Fallback</span>
                <span class="badge badge-success">Configured</span>
                @endif
            </div>
        </div>

        {{-- Stream Info --}}
        @if($stream)
        <div class="card">
            <div class="card-title" style="margin-bottom:16px;">Active Stream</div>
            <div style="display:grid;grid-template-columns:auto 1fr;gap:6px 16px;font-size:13px;">
                <span style="color:var(--text-tertiary);">Source</span>
                <code style="font-size:12px;font-family:var(--font-mono);word-break:break-all;">{{ $stream->source_url }}</code>
                <span style="color:var(--text-tertiary);">Protocol</span>
                <span class="badge badge-brand">{{ strtoupper($stream->input_protocol) }}</span>
                <span style="color:var(--text-tertiary);">Type</span>
                <span>{{ ucfirst($stream->stream_type) }}</span>
                <span style="color:var(--text-tertiary);">Started</span>
                <span>{{ $stream->started_at?->format('Y-m-d H:i:s') }}</span>
                <span style="color:var(--text-tertiary);">Duration</span>
                <span>{{ gmdate('H:i:s', $stream->getDuration()) }}</span>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Recent Streams --}}
<div class="card animate-in" style="animation-delay:0.15s;">
    <div class="card-title" style="margin-bottom:16px;">Recent Streams</div>
    @php $allStreams = $channel->streams()->latest()->limit(10)->get(); @endphp
    @if($allStreams->isEmpty())
        <p style="color:var(--text-tertiary);font-size:13px;">No streams recorded.</p>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Type</th><th>Status</th><th>Protocol</th><th>Started</th><th>Duration</th></tr>
            </thead>
            <tbody>
                @foreach($allStreams as $s)
                <tr>
                    <td><span class="badge {{ $s->stream_type === 'live' ? 'badge-success' : 'badge-warning' }}">{{ ucfirst($s->stream_type) }}</span></td>
                    <td><span class="badge badge-neutral">{{ ucfirst($s->status) }}</span></td>
                    <td><span class="badge badge-brand">{{ strtoupper($s->input_protocol) }}</span></td>
                    <td>{{ $s->started_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ gmdate('H:i:s', $s->getDuration()) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- Delete Channel --}}
<div class="card animate-in" style="animation-delay:0.2s; border-color:var(--danger-dim);">
    <div class="card-header" style="margin-bottom:12px;">
        <div>
            <div class="card-title" style="color:var(--danger);">Danger Zone</div>
            <div class="card-subtitle">Permanently delete this channel and all associated data.</div>
        </div>
        <form method="POST" action="{{ route('admin.channels.destroy', $channel) }}" onsubmit="return confirm('Permanently delete channel \'{{ $channel->name }}\'? This cannot be undone.')">
            @csrf @method('DELETE')
            <button class="btn btn-danger btn-sm">Delete Channel</button>
        </form>
    </div>
</div>

{{-- Start Stream Modal --}}
<div class="modal-overlay" id="startModal" style="display:none;">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Start Stream</div>
            <button class="modal-close" onclick="closeModal('startModal')">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.channels.start', $channel) }}">
            @csrf
            <div class="form-group" style="margin-bottom:16px;">
                <label>Source URL</label>
                <input name="source_url" placeholder="rtmp://..., srt://..., https://..." required>
                <span class="hint">RTMP, SRT, RTSP, HLS, or HTTP stream URL</span>
            </div>
            <button type="submit" class="btn btn-primary">Start Streaming</button>
        </form>
    </div>
</div>

{{-- Recover Modal --}}
<div class="modal-overlay" id="recoverModal" style="display:none;">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Recover to Live</div>
            <button class="modal-close" onclick="closeModal('recoverModal')">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <p style="font-size:13px;color:var(--text-secondary);margin-bottom:16px;">Enter the live source URL to switch back from VOD fallback.</p>
        <form method="POST" action="{{ route('admin.channels.recover', $channel) }}">
            @csrf
            <div class="form-group" style="margin-bottom:16px;">
                <label>Live Source URL</label>
                <input name="source_url" value="{{ $channel->push_url }}" required>
            </div>
            <button type="submit" class="btn btn-success">Recover Live Stream</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showRecoverModal(e) {
    e.preventDefault();
    openModal('recoverModal');
}
</script>
@endpush
