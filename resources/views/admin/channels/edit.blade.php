@extends('layouts.admin')

@section('title', 'Edit ' . $channel->name)

@section('content')
<div class="card">
    <h1>Edit Channel</h1>
    <p style="color: var(--text-muted); margin-top: -0.5rem;">{{ $channel->name }}</p>

    <form method="POST" action="{{ route('admin.channels.update', $channel) }}">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" value="{{ old('name', $channel->name) }}">
            @error('name')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>Slug</label>
            <input type="text" name="slug" value="{{ old('slug', $channel->slug) }}">
            @error('slug')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>Description</label>
            <input type="text" name="description" value="{{ old('description', $channel->description) }}">
            @error('description')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>VOD Playlist URL</label>
            <input type="text" name="vod_playlist_url" value="{{ old('vod_playlist_url', $channel->vod_playlist_url) }}">
            @error('vod_playlist_url')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>RTMP Push URL</label>
            <input type="text" name="rtmp_push_url" value="{{ old('rtmp_push_url', $channel->rtmp_push_url) }}">
            @error('rtmp_push_url')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>Bitrate (kbps)</label>
                <input type="number" name="bitrate_kbps" value="{{ old('bitrate_kbps', $channel->bitrate_kbps) }}" min="32" max="50000">
                @error('bitrate_kbps')<small style="color: var(--danger);">{{ $message }}</small>@enderror
            </div>

            <div class="form-group">
                <label>Resolution</label>
                <input type="text" name="resolution" value="{{ old('resolution', $channel->resolution) }}">
                @error('resolution')<small style="color: var(--danger);">{{ $message }}</small>@enderror
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $channel->is_active) ? 'checked' : '' }}>
                    Active
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_icecast_enabled" value="1" {{ old('is_icecast_enabled', $channel->is_icecast_enabled) ? 'checked' : '' }}>
                    Icecast
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_relay_enabled" value="1" {{ old('is_relay_enabled', $channel->is_relay_enabled) ? 'checked' : '' }}>
                    Relay
                </label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Update Channel</button>
        <a href="{{ route('admin.channels.index') }}" style="margin-left: 0.75rem; color: var(--text-muted);">Cancel</a>
    </form>
</div>

@php
$activeStream = $channel->activeStream();
@endphp

<div class="card">
    <h2>Stream Control</h2>

    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
        @if(!$channel->is_live)
        <div style="flex: 1; min-width: 280px;">
            <form method="POST" action="{{ route('admin.channels.start', $channel) }}">
                @csrf
                <div class="form-group">
                    <label>Source URL <span class="required">*</span></label>
                    <input type="text" name="push_url" placeholder="rtmp://source/live/stream" required>
                </div>
                <button type="submit" class="btn btn-primary" style="background: var(--success);">▶ Start Stream</button>
            </form>
        </div>
        @else
        <div style="flex: 1; min-width: 200px;">
            <form method="POST" action="{{ route('admin.channels.stop', $channel) }}">
                @csrf
                <button type="submit" class="btn btn-primary" style="background: var(--danger);" onclick="return confirm('Stop the stream?')">⏹ Stop Stream</button>
            </form>
        </div>

        <div style="flex: 1; min-width: 200px;">
            <form method="POST" action="{{ route('admin.channels.fallback', $channel) }}">
                @csrf
                <button type="submit" class="btn btn-primary" style="background: var(--warning); color: #1e293b;" onclick="return confirm('Switch to VOD fallback?')">⏸ VOD Fallback</button>
            </form>
        </div>
        @endif

        @if($activeStream && $activeStream->isFallback())
        <div style="flex: 1; min-width: 280px;">
            <form method="POST" action="{{ route('admin.channels.recover', $channel) }}">
                @csrf
                <div class="form-group">
                    <label>Live Source URL <span class="required">*</span></label>
                    <input type="text" name="push_url" placeholder="rtmp://source/live/stream" required>
                </div>
                <button type="submit" class="btn btn-primary">↻ Recover to Live</button>
            </form>
        </div>
        @endif
    </div>

    <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <a href="{{ route('admin.channels.show', $channel) }}" class="btn" style="background: #f1f5f9; color: var(--text);">View Details</a>
        <a href="{{ route('admin.channels.events', $channel) }}" class="btn" style="background: #f1f5f9; color: var(--text);">Event Log</a>
        <a href="{{ route('stream.play', $channel->slug) }}" target="_blank" class="btn" style="background: #dbeafe; color: #1e40af;">Open Player</a>
    </div>
</div>
@endsection
