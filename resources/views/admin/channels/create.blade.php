@extends('layouts.admin')
@section('title', 'New Channel')
@section('breadcrumb') <a href="{{ route('admin.channels.index') }}">Channels</a> / New @endsection

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Create Channel</div>
            <div class="card-subtitle">Configure a new streaming channel with ingest, output, and VOD fallback settings.</div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.channels.store') }}">
        @csrf

        <div class="form-section">Basic Info</div>
        <div class="form-grid" style="margin-bottom:1rem;">
            <div class="form-group">
                <label>Channel Name <span style="color:var(--danger)">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="Sports Channel">
                @error('name')<span class="hint" style="color:var(--danger)">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>Slug <span style="color:var(--danger)">*</span></label>
                <input type="text" name="slug" value="{{ old('slug') }}" required placeholder="sports">
                <span class="hint">Used in stream URLs: /streams/<strong>slug</strong>/playlist.m3u8</span>
                @error('slug')<span class="hint" style="color:var(--danger)">{{ $message }}</span>@enderror
            </div>
            <div class="form-group form-full">
                <label>Description</label>
                <input type="text" name="description" value="{{ old('description') }}" placeholder="Optional description">
            </div>
        </div>

        <div class="form-section">Quality &amp; ABR</div>
        <div class="alert alert-info" style="margin-bottom:1rem;">
            Set both Resolution and Bitrate to enable <strong>multi-bitrate ABR</strong> (adaptive streaming ladder with HLS + DASH output).
            Leave blank to use stream copy (no transcoding).
        </div>
        <div class="form-grid" style="margin-bottom:1rem;">
            <div class="form-group">
                <label>Resolution</label>
                <select name="resolution">
                    <option value="">— Stream Copy (no transcode) —</option>
                    <option value="3840x2160" {{ old('resolution') === '3840x2160' ? 'selected' : '' }}>4K — 3840×2160</option>
                    <option value="1920x1080" {{ old('resolution') === '1920x1080' ? 'selected' : '' }}>Full HD — 1920×1080</option>
                    <option value="1280x720"  {{ old('resolution') === '1280x720'  ? 'selected' : '' }}>HD — 1280×720</option>
                    <option value="854x480"   {{ old('resolution') === '854x480'   ? 'selected' : '' }}>SD — 854×480</option>
                    <option value="640x360"   {{ old('resolution') === '640x360'   ? 'selected' : '' }}>Low — 640×360</option>
                </select>
                @error('resolution')<span class="hint" style="color:var(--danger)">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>Bitrate (kbps)</label>
                <input type="number" name="bitrate_kbps" value="{{ old('bitrate_kbps') }}" min="32" max="50000" placeholder="e.g. 4000 for 1080p">
                <span class="hint">Top-rung bitrate. Lower rungs are auto-calculated.</span>
                @error('bitrate_kbps')<span class="hint" style="color:var(--danger)">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="form-section">VOD Fallback</div>
        <div class="form-group" style="margin-bottom:1rem;">
            <label>VOD Playlist URL <span class="hint">(auto-switches when live stream goes offline)</span></label>
            <input type="text" name="vod_playlist_url" value="{{ old('vod_playlist_url') }}"
                   placeholder="Leave blank — upload videos via VOD Library after creating the channel">
            <span class="hint">You can also upload videos via the VOD Library page after creating the channel.</span>
            @error('vod_playlist_url')<span class="hint" style="color:var(--danger)">{{ $message }}</span>@enderror
        </div>

        <div class="form-section">Output Push Target</div>
        <div class="form-group" style="margin-bottom:1rem;">
            <label>RTMP Push URL <span class="hint">(optional — push stream to another server)</span></label>
            <input type="text" name="rtmp_push_url" value="{{ old('rtmp_push_url') }}"
                   placeholder="rtmp://a.rtmp.youtube.com/live2/xxxx-xxxx  or  srt://...">
            @error('rtmp_push_url')<span class="hint" style="color:var(--danger)">{{ $message }}</span>@enderror
        </div>

        <div class="form-section">Options</div>
        <div class="form-grid-3" style="margin-bottom:1.5rem;">
            <label class="toggle-label">
                <input type="checkbox" name="is_icecast_enabled" value="1" {{ old('is_icecast_enabled') ? 'checked' : '' }}>
                Enable Icecast Audio
            </label>
            <label class="toggle-label">
                <input type="checkbox" name="is_relay_enabled" value="1" {{ old('is_relay_enabled') ? 'checked' : '' }}>
                Enable Relay
            </label>
        </div>

        <div class="actions">
            <button type="submit" class="btn btn-primary">Create Channel</button>
            <a href="{{ route('admin.channels.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// Auto-generate slug from name
document.querySelector('[name=name]').addEventListener('input', function() {
    const slugField = document.querySelector('[name=slug]');
    if (!slugField.dataset.touched) {
        slugField.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }
});
document.querySelector('[name=slug]').addEventListener('input', function() {
    this.dataset.touched = '1';
});
</script>
@endpush
