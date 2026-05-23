@extends('layouts.admin')
@section('title', 'Icecast Radio')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span> Icecast Radio
@endsection

@section('topbar-actions')
    <button class="btn btn-primary btn-sm" onclick="openModal('createRadioModal')">Create Radio Stream</button>
    <a href="{{ route('admin.relay-servers.index') }}" class="btn btn-ghost btn-sm">Relay Servers</a>
    <a href="{{ route('admin.outputs.index') }}" class="btn btn-ghost btn-sm">Output Targets</a>
@endsection

@section('content')

{{-- Icecast Server Info --}}
<div class="card animate-in" style="margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:12px;">
        <div style="width:40px;height:40px;border-radius:var(--radius-sm);background:rgba(99,102,241,0.15);display:flex;align-items:center;justify-content:center;">
            <svg fill="none" stroke="var(--brand-light)" stroke-width="2" viewBox="0 0 24 24" width="18" height="18"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
        </div>
        <div>
            <strong>Icecast Server</strong>
            <code style="font-family:var(--font-mono);font-size:12px;color:var(--text-tertiary);margin-left:8px;">{{ $icecastHost }}:{{ $icecastPort }}</code>
        </div>
    </div>
</div>

{{-- Per-channel management --}}
@foreach($channels as $item)
@php $ch = $item['channel']; @endphp
<div class="card animate-in" style="animation-delay:{{ $loop->index * 0.05 }}s;">
    <div class="card-header">
        <div>
            <div class="card-title">{{ $ch->name }}</div>
            <div class="card-subtitle">{{ $ch->slug }} &mdash;
                @if($ch->is_icecast_enabled)
                    <span class="badge badge-success">Icecast Enabled</span>
                @else
                    <span class="badge badge-neutral">Icecast Disabled</span>
                @endif
                @if($item['audio_relay']['active'])
                    <span class="badge badge-success">Audio Relay Active</span>
                @endif
                @if($item['audio_fallback'])
                    <span class="badge badge-info">Fallback On</span>
                @endif
            </div>
        </div>
        <div style="display:flex;gap:6px;">
            @if($ch->is_icecast_enabled)
                <form method="POST" action="{{ route('admin.icecast.disable', $ch) }}" style="display:inline;">
                    @csrf<button class="btn btn-warning btn-xs">Disable Icecast</button>
                </form>
                @if($item['audio_relay']['active'])
                <form method="POST" action="{{ route('admin.icecast.audio-relay.stop', $ch) }}" style="display:inline;">
                    @csrf<button class="btn btn-danger btn-xs">Stop Relay</button>
                </form>
                @endif
            @else
                <form method="POST" action="{{ route('admin.icecast.enable', $ch) }}" style="display:inline;">
                    @csrf<button class="btn btn-success btn-xs">Enable Icecast</button>
                </form>
            @endif
        </div>
    </div>

    @if($ch->is_icecast_enabled)
    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:16px;">
        {{-- Icecast Stats --}}
        <div style="background:var(--surface-2);border-radius:var(--radius);padding:14px;">
            <div style="font-weight:600;font-size:13px;margin-bottom:8px;">Icecast Stream</div>
            <div style="display:grid; grid-template-columns:auto 1fr; gap:4px 12px; font-size:12.5px;">
                <span style="color:var(--text-tertiary);">Mount:</span>
                <code style="font-family:var(--font-mono);">{{ $item['mount'] ?? '—' }}</code>
                <span style="color:var(--text-tertiary);">Listeners:</span>
                <span>{{ $item['icecast_stats']['listeners'] ?? 0 }}</span>
                <span style="color:var(--text-tertiary);">Bitrate:</span>
                <span>{{ ($item['icecast_stats']['bitrate'] ?? 0) > 0 ? ($item['icecast_stats']['bitrate'].' kbps') : '—' }}</span>
                <span style="color:var(--text-tertiary);">Status:</span>
                <span>
                    @if($item['icecast_stats']['connected'] ?? false)
                        <span class="badge badge-success">Connected</span>
                    @else
                        <span class="badge badge-neutral">No source</span>
                    @endif
                </span>
                <span style="color:var(--text-tertiary);">Stream URL:</span>
                <a href="{{ $item['stream_url'] }}" target="_blank" style="font-size:12px;">{{ $item['stream_url'] }}</a>
            </div>
        </div>

        {{-- Push Credentials --}}
        @if($item['credentials'])
        <div style="background:var(--surface-2);border-radius:var(--radius);padding:14px;">
            <div style="font-weight:600;font-size:13px;margin-bottom:8px;">Push Credentials</div>
            <div style="display:grid; grid-template-columns:auto 1fr; gap:4px 12px; font-size:12.5px;">
                <span style="color:var(--text-tertiary);">Host:</span>
                <code style="font-family:var(--font-mono);">{{ $item['credentials']['host'] }}:{{ $item['credentials']['port'] }}</code>
                <span style="color:var(--text-tertiary);">Mount:</span>
                <code style="font-family:var(--font-mono);">{{ $item['credentials']['mount_point'] }}</code>
                <span style="color:var(--text-tertiary);">Password:</span>
                <code id="pwd-{{ $ch->id }}" style="font-family:var(--font-mono);background:var(--surface-1);padding:2px 6px;border-radius:4px;">{{ $item['credentials']['password'] }}</code>
                <span colspan="2">
                    <button type="button" class="btn btn-xs btn-ghost" onclick="copyToClipboard('pwd-{{ $ch->id }}')" style="font-size:11px;">Copy</button>
                </span>
                <span style="color:var(--text-tertiary);">Push URL:</span>
                <code id="push-{{ $ch->id }}" style="font-family:var(--font-mono);font-size:11px;word-break:break-all;">{{ $item['credentials']['push_url'] }}</code>
                <span colspan="2">
                    <button type="button" class="btn btn-xs btn-ghost" onclick="copyToClipboard('push-{{ $ch->id }}')" style="font-size:11px;">Copy</button>
                </span>
            </div>
        </div>
        @endif

        {{-- Audio Relay Config --}}
        <div style="background:var(--surface-2);border-radius:var(--radius);padding:14px;">
            <div style="font-weight:600;font-size:13px;margin-bottom:8px;">Audio Relay Config</div>
            <form method="POST" action="{{ route('admin.icecast.audio-relay.start', $ch) }}">
                @csrf
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div class="form-group" style="gap:2px;">
                        <label style="font-size:11px;">Audio Source URL</label>
                        <input name="audio_source_url" value="{{ $ch->audio_source_url }}" placeholder="https://stream.example.com/audio.mp3" style="font-size:12px;padding:6px 10px;">
                    </div>
                    <div class="form-group" style="gap:2px;">
                        <label style="font-size:11px;">Audio Playlist URL (M3U8)</label>
                        <input name="audio_relay_playlist_url" value="{{ $ch->audio_relay_playlist_url }}" placeholder="https://example.com/playlist.m3u8" style="font-size:12px;padding:6px 10px;">
                        <span class="hint" style="font-size:10px;">Audio files played sequentially during fallback</span>
                    </div>
                    <div class="form-group" style="gap:2px;">
                        <label style="font-size:11px;">Custom Target URL (optional)</label>
                        <input name="audio_relay_target_url" value="{{ $ch->audio_relay_target_url }}" placeholder="icecast://source:pass@host:8000/mount" style="font-size:12px;padding:6px 10px;">
                        <span class="hint" style="font-size:10px;">Leave blank to use Icecast mount</span>
                    </div>
                    <div style="display:flex;gap:16px;align-items:center;">
                        <div class="form-group" style="gap:2px;">
                            <label style="font-size:11px;">Bitrate (kbps)</label>
                            <input type="number" name="bitrate_kbps" value="{{ $ch->bitrate_kbps ?? 128 }}" min="16" max="512" style="width:80px;font-size:12px;padding:6px 10px;">
                        </div>
                        <label class="toggle-row" style="font-size:12px;">
                            <input type="checkbox" name="audio_fallback_enabled" value="1" {{ $ch->audio_fallback_enabled ? 'checked' : '' }}>
                            Auto-fallback on push offline
                        </label>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="submit" class="btn btn-primary btn-xs">
                            @if($item['audio_relay']['active']) Restart Relay @else Start Audio Relay @endif
                        </button>
                        @if($item['audio_relay']['active'])
                            <span class="badge badge-success" style="font-size:11px;">PID: {{ $item['audio_relay']['pid'] }}</span>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Forward to Server --}}
    <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border);">
        <div style="font-weight:600;font-size:13px;margin-bottom:8px;">Forward to External Server</div>
        @if($relayServers->isNotEmpty())
        <form method="POST" action="{{ route('admin.icecast.forward', $ch) }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
            @csrf
            <div class="form-group" style="gap:2px;">
                <label style="font-size:11px;">Target Server</label>
                <select name="relay_server_id" required style="font-size:12px;padding:6px 10px;min-width:180px;">
                    @foreach($relayServers as $srv)
                    <option value="{{ $srv->id }}">{{ $srv->name }} ({{ strtoupper($srv->server_type) }}) — {{ $srv->hostname }}:{{ $srv->port }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="gap:2px;">
                <label style="font-size:11px;">Mode</label>
                <select name="mode" required style="font-size:12px;padding:6px 10px;">
                    <option value="video">Video + Audio</option>
                    <option value="audio">Audio Only</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-xs">Forward</button>
        </form>
        @else
        <p style="font-size:12px;color:var(--text-tertiary);">No relay servers configured. <a href="{{ route('admin.relay-servers.create') }}">Add one</a>.</p>
        @endif
    </div>
    @endif
</div>
@endforeach

{{-- Create Radio Stream Modal --}}
<div class="modal-overlay" id="createRadioModal" style="display:none;">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Create Radio Stream</div>
            <button class="modal-close" onclick="closeModal('createRadioModal')">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.icecast.create-stream') }}">
            @csrf
            <div class="form-group" style="margin-bottom:12px;">
                <label>Channel Name</label>
                <input name="name" id="radio-name" placeholder="My Radio Station" required>
            </div>
            <div class="form-group" style="margin-bottom:12px;">
                <label>Slug</label>
                <input name="slug" id="radio-slug" placeholder="my-radio-station" required data-touched="false">
                <span class="hint">Used in the stream mount point: /stream/<code>slug</code></span>
            </div>
            <div class="form-group" style="margin-bottom:12px;">
                <label>Description (optional)</label>
                <input name="description" placeholder="24/7 radio stream">
            </div>
            <div class="form-group" style="margin-bottom:12px;">
                <label>Bitrate (kbps)</label>
                <input type="number" name="bitrate_kbps" value="128" min="16" max="512" style="width:100px;">
                <span class="hint">Audio quality: 128 kbps is standard for MP3 radio</span>
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label>VOD Playlist URL (optional)</label>
                <input name="vod_playlist_url" placeholder="https://example.com/playlist.m3u8">
                <span class="hint">Audio fallback playlist when no live source is pushing</span>
            </div>
            <p style="font-size:12px;color:var(--text-secondary);margin-bottom:16px;padding:8px;background:var(--surface-2);border-radius:var(--radius-sm);">
                Icecast runs locally on <code style="font-family:var(--font-mono);">localhost:{{ $icecastPort }}</code>. Push credentials will be shown after creation.
            </p>
            <button type="submit" class="btn btn-primary">Create Radio Stream</button>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function() {
    var nameEl = document.getElementById('radio-name');
    var slugEl = document.getElementById('radio-slug');
    if (!nameEl || !slugEl) return;
    function slugify(text) {
        return text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    }
    nameEl.addEventListener('input', function() {
        if (slugEl.dataset.touched === 'false') {
            slugEl.value = slugify(this.value);
        }
    });
    slugEl.addEventListener('input', function() {
        slugEl.dataset.touched = 'true';
    });
})();

function copyToClipboard(elementId) {
    const el = document.getElementById(elementId);
    if (!el) return;
    navigator.clipboard.writeText(el.textContent).then(function() {
        var btn = el.parentElement.nextElementSibling;
        if (btn) {
            var orig = btn.textContent;
            btn.textContent = 'Copied!';
            setTimeout(function() { btn.textContent = orig; }, 1500);
        }
    });
}
</script>
@endpush
