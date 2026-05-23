@extends('layouts.admin')
@section('title', 'Settings')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span> Settings
@endsection

@section('content')
<div class="card animate-in" style="max-width:600px;">
    <div class="card-header">
        <div>
            <div class="card-title">Media Server Settings</div>
            <div class="card-subtitle">Configure the media server driver backend</div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        <div class="form-group">
            <label>Media Server Driver</label>
            <select name="media_server_driver" required>
                <option value="ffmpeg" {{ config('services.media_server.driver') === 'ffmpeg' ? 'selected' : '' }}>FFmpeg (Built-in)</option>
                <option value="wowza" {{ config('services.media_server.driver') === 'wowza' ? 'selected' : '' }}>Wowza Streaming Engine</option>
                <option value="flussonic" {{ config('services.media_server.driver') === 'flussonic' ? 'selected' : '' }}>Flussonic Media Server</option>
            </select>
            <span class="hint">FFmpeg is built-in. Wowza/Flussonic require external servers.</span>
        </div>

        <div style="margin-top:24px;padding:16px;background:var(--surface-2);border-radius:var(--radius);">
            <div style="font-weight:600;margin-bottom:8px;">FFmpeg Paths</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>FFmpeg Path</label>
                    <input name="ffmpeg_path" value="{{ config('services.ffmpeg.path', '/usr/bin/ffmpeg') }}">
                </div>
                <div class="form-group">
                    <label>FFprobe Path</label>
                    <input name="ffprobe_path" value="{{ config('services.ffmpeg.probe_path', '/usr/bin/ffprobe') }}">
                </div>
            </div>
        </div>

        <div style="margin-top:16px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </form>
</div>
@endsection
