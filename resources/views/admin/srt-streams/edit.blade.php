@extends('layouts.admin')
@section('title', 'Edit SRT Stream')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span>
    <a href="{{ route('admin.srt-streams.index') }}">SRT Streams</a> <span class="sep">/</span> {{ $srtStream->name }}
@endsection

@section('content')
<div class="card animate-in" style="max-width:600px;">
    <div class="card-header"><div class="card-title">Edit SRT Stream</div></div>
    <form method="POST" action="{{ route('admin.srt-streams.update', $srtStream) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label>Name</label>
                <input name="name" value="{{ old('name', $srtStream->name) }}" required>
            </div>
            <div class="form-group">
                <label>Stream ID</label>
                <input name="stream_id" value="{{ old('stream_id', $srtStream->stream_id) }}" required>
            </div>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>SRT Port</label>
                <input type="number" name="srt_port" value="{{ old('srt_port', $srtStream->srt_port) }}" required>
            </div>
            <div class="form-group">
                <label>RTMP Stream</label>
                <input name="rtmp_stream" value="{{ old('rtmp_stream', $srtStream->rtmp_stream) }}">
            </div>
        </div>
        <div class="form-group" style="margin-top:12px;">
            <label>Channel</label>
            <select name="channel_id">
                <option value="">None</option>
                @foreach(\App\Models\Channel::orderBy('name')->get() as $ch)
                    <option value="{{ $ch->id }}" {{ old('channel_id', $srtStream->channel_id) == $ch->id ? 'selected' : '' }}>{{ $ch->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;gap:24px;margin:12px 0;">
            <label class="toggle-row">
                <input type="checkbox" name="enabled" value="1" {{ old('enabled', $srtStream->enabled) ? 'checked' : '' }}>
                Enabled
            </label>
            <label class="toggle-row">
                <input type="checkbox" name="vod_fallback_enabled" value="1" {{ old('vod_fallback_enabled', $srtStream->vod_fallback_enabled) ? 'checked' : '' }}>
                VOD Fallback
            </label>
        </div>
        <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('admin.srt-streams.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
