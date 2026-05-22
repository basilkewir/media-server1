@extends('layouts.admin')

@section('title', 'New Channel')

@section('content')
<div class="card">
    <h1>Create Channel</h1>
    <p style="color: var(--text-muted); margin-top: -0.5rem;">Add a new streaming channel.</p>

    <form method="POST" action="{{ route('admin.channels.store') }}">
        @csrf

        <div class="form-group">
            <label>Name <span class="required">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required>
            @error('name')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>Slug <span class="required">*</span></label>
            <input type="text" name="slug" value="{{ old('slug') }}" required placeholder="my-channel">
            @error('slug')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>Description</label>
            <input type="text" name="description" value="{{ old('description') }}">
            @error('description')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>VOD Playlist URL <small>(fallback playlist)</small></label>
            <input type="text" name="vod_playlist_url" value="{{ old('vod_playlist_url') }}" placeholder="https://example.com/playlist.m3u8">
            @error('vod_playlist_url')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>RTMP Push URL <small>(optional output target)</small></label>
            <input type="text" name="rtmp_push_url" value="{{ old('rtmp_push_url') }}" placeholder="rtmp://destination/live/key">
            @error('rtmp_push_url')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>Bitrate (kbps)</label>
                <input type="number" name="bitrate_kbps" value="{{ old('bitrate_kbps') }}" min="32" max="50000" placeholder="3000">
                @error('bitrate_kbps')<small style="color: var(--danger);">{{ $message }}</small>@enderror
            </div>

            <div class="form-group">
                <label>Resolution</label>
                <input type="text" name="resolution" value="{{ old('resolution') }}" placeholder="1920x1080">
                @error('resolution')<small style="color: var(--danger);">{{ $message }}</small>@enderror
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_icecast_enabled" value="1" {{ old('is_icecast_enabled') ? 'checked' : '' }}>
                    Enable Icecast
                </label>
                @error('is_icecast_enabled')<small style="color: var(--danger);">{{ $message }}</small>@enderror
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_relay_enabled" value="1" {{ old('is_relay_enabled') ? 'checked' : '' }}>
                    Enable Relay
                </label>
                @error('is_relay_enabled')<small style="color: var(--danger);">{{ $message }}</small>@enderror
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Create Channel</button>
        <a href="{{ route('admin.channels.index') }}" style="margin-left: 0.75rem; color: var(--text-muted);">Cancel</a>
    </form>
</div>
@endsection
