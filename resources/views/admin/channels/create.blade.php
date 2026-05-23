@extends('layouts.admin')
@section('title', 'New Channel')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span>
    <a href="{{ route('admin.channels.index') }}">Channels</a> <span class="sep">/</span> New
@endsection

@section('content')
<div class="card animate-in">
    <div class="card-header">
        <div>
            <div class="card-title">Create Channel</div>
            <div class="card-subtitle">Configure a new streaming channel</div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.channels.store') }}">
        @csrf
        <div class="form-section">Basic Information</div>
        <div class="form-grid">
            <div class="form-group">
                <label class="label-required">Channel Name</label>
                <input name="name" id="channel-name" value="{{ old('name') }}" placeholder="My Channel" required>
            </div>
            <div class="form-group">
                <label class="label-required">Slug</label>
                <input name="slug" id="channel-slug" value="{{ old('slug') }}" placeholder="my-channel" required data-touched="false">
                <span class="hint">Used in stream URLs: /live/<code>slug</code></span>
            </div>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" placeholder="What this channel is about...">{{ old('description') }}</textarea>
        </div>

        <div class="form-section">Quality & Encoding</div>
        <div class="form-grid">
            <div class="form-group">
                <label>Resolution</label>
                <select name="resolution">
                    <option value="">Stream Copy (no transcode)</option>
                    <option value="3840x2160">4K (3840x2160)</option>
                    <option value="1920x1080">1080p (1920x1080)</option>
                    <option value="1280x720">720p (1280x720)</option>
                    <option value="854x480">480p (854x480)</option>
                    <option value="640x360">360p (640x360)</option>
                </select>
                <span class="hint">Selecting a resolution enables adaptive bitrate ladder with lower renditions</span>
            </div>
            <div class="form-group">
                <label>Bitrate (kbps)</label>
                <input type="number" name="bitrate_kbps" value="{{ old('bitrate_kbps', 3000) }}" placeholder="3000" min="100" max="50000">
                <span class="hint">Top-rung bitrate for ABR ladder</span>
            </div>
        </div>

        <div class="form-section">VOD Fallback</div>
        <div class="form-group">
            <label>VOD Playlist URL (optional)</label>
            <input name="vod_playlist_url" value="{{ old('vod_playlist_url') }}" placeholder="https://... or leave blank">
            <span class="hint">Auto-generated after uploading videos in VOD Library. Manual URL also accepted.</span>
        </div>

        <div class="form-section">Output Push Target</div>
        <div class="form-group">
            <label>RTMP Push URL (optional)</label>
            <input name="rtmp_push_url" value="{{ old('rtmp_push_url') }}" placeholder="rtmp://...">
            <span class="hint">Optional: push stream to external RTMP server</span>
        </div>

        <div class="form-section">Options</div>
        <div style="display:flex;gap:24px;flex-wrap:wrap;">
            <label class="toggle-row">
                <input type="checkbox" name="is_icecast_enabled" value="1" {{ old('is_icecast_enabled') ? 'checked' : '' }}>
                Icecast Audio Relay
            </label>
            <label class="toggle-row">
                <input type="checkbox" name="is_relay_enabled" value="1" {{ old('is_relay_enabled') ? 'checked' : '' }}>
                External Relay
            </label>
        </div>

        <div style="margin-top:24px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary">Create Channel</button>
            <a href="{{ route('admin.channels.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function() {
    const nameEl = document.getElementById('channel-name');
    const slugEl = document.getElementById('channel-slug');
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
</script>
@endpush
