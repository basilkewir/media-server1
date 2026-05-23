@extends('layouts.admin')
@section('title', 'Edit — ' . $channel->name)
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span>
    <a href="{{ route('admin.channels.index') }}">Channels</a> <span class="sep">/</span> {{ $channel->name }}
@endsection

@section('topbar-actions')
    <a href="{{ route('admin.channels.show', $channel) }}" class="btn btn-ghost btn-sm">View Details</a>
    <a href="{{ route('admin.vod.index', $channel) }}" class="btn btn-ghost btn-sm">VOD Library</a>
    <a href="{{ route('admin.channels.graphics', $channel) }}" class="btn btn-ghost btn-sm">Graphics</a>
@endsection

@section('content')

{{-- Edit Form --}}
<div class="card animate-in">
    <div class="card-header">
        <div>
            <div class="card-title">Edit Channel</div>
            <div class="card-subtitle">{{ $channel->slug }}</div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.channels.update', $channel) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label>Name</label>
                <input name="name" value="{{ old('name', $channel->name) }}" required>
            </div>
            <div class="form-group">
                <label>Slug</label>
                <input name="slug" value="{{ old('slug', $channel->slug) }}" required>
                <span class="hint">Changing the slug will break existing stream URLs</span>
            </div>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description">{{ old('description', $channel->description) }}</textarea>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>VOD Playlist URL</label>
                <input name="vod_playlist_url" value="{{ old('vod_playlist_url', $channel->vod_playlist_url) }}">
            </div>
            <div class="form-group">
                <label>RTMP Push URL</label>
                <input name="rtmp_push_url" value="{{ old('rtmp_push_url', $channel->rtmp_push_url) }}">
            </div>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Bitrate (kbps)</label>
                <input type="number" name="bitrate_kbps" value="{{ old('bitrate_kbps', $channel->bitrate_kbps) }}">
            </div>
            <div class="form-group">
                <label>Resolution</label>
                <input name="resolution" value="{{ old('resolution', $channel->resolution) }}" placeholder="1920x1080">
            </div>
        </div>
        <div style="display:flex;gap:24px;flex-wrap:wrap;margin:16px 0;">
            <label class="toggle-row">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $channel->is_active) ? 'checked' : '' }}>
                Active
            </label>
            <label class="toggle-row">
                <input type="checkbox" name="is_icecast_enabled" value="1" {{ old('is_icecast_enabled', $channel->is_icecast_enabled) ? 'checked' : '' }}>
                Icecast
            </label>
            <label class="toggle-row">
                <input type="checkbox" name="is_relay_enabled" value="1" {{ old('is_relay_enabled', $channel->is_relay_enabled) ? 'checked' : '' }}>
                Relay
            </label>
        </div>
        <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('admin.channels.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>

{{-- Stream Control --}}
<div class="card animate-in" style="animation-delay:0.1s;">
    <div class="card-header">
        <div class="card-title">Stream Control</div>
    </div>
    @php $stream = $channel->activeStream(); @endphp

    @if(!$stream || $stream->status === 'completed')
        <form method="POST" action="{{ route('admin.channels.start', $channel) }}">
            @csrf
            <div style="display:flex;gap:10px;align-items:flex-end;">
                <div class="form-group" style="flex:1;">
                    <label>Source URL</label>
                    <input name="source_url" value="{{ old('source_url', $channel->push_url) }}" placeholder="rtmp://..., srt://..." required>
                </div>
                <button type="submit" class="btn btn-primary">Start Stream</button>
            </div>
        </form>
    @elseif($stream->status === 'active')
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <form method="POST" action="{{ route('admin.channels.stop', $channel) }}" style="display:inline;">
                @csrf
                <button class="btn btn-danger btn-sm" onclick="return confirm('Stop stream?')">Stop Stream</button>
            </form>
            @if($channel->vod_playlist_url)
            <form method="POST" action="{{ route('admin.channels.fallback', $channel) }}" style="display:inline;">
                @csrf
                <button class="btn btn-warning btn-sm">Switch to VOD</button>
            </form>
            @endif
            <a href="{{ route('admin.channels.show', $channel) }}" class="btn btn-ghost btn-sm">View Details</a>
            <a href="{{ route('admin.channels.events', $channel) }}" class="btn btn-ghost btn-sm">Event Log</a>
            <a href="{{ route('stream.play', $channel->slug) }}" target="_blank" class="btn btn-ghost btn-sm">Open Player</a>
        </div>
    @elseif($stream->status === 'fallback')
        <form method="POST" action="{{ route('admin.channels.recover', $channel) }}">
            @csrf
            <div style="display:flex;gap:10px;align-items:flex-end;">
                <div class="form-group" style="flex:1;">
                    <label>Live Source URL</label>
                    <input name="source_url" value="{{ old('source_url', $channel->push_url) }}" required>
                </div>
                <button type="submit" class="btn btn-success">Recover Live</button>
            </div>
        </form>
    @endif
</div>
@endsection
