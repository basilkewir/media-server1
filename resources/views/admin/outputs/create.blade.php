@extends('layouts.admin')
@section('title', isset($output) ? 'Edit Output' : 'New Output Target')

@section('content')
<div class="card" style="max-width:700px;">
    <div class="card-header">
        <span class="card-title">{{ isset($output) ? 'Edit Output Target' : 'New Output Target' }}</span>
    </div>

    <form method="POST" action="{{ isset($output) ? route('admin.outputs.update', $output) : route('admin.outputs.store') }}">
        @csrf
        @if(isset($output)) @method('PUT') @endif

        <div class="form-grid">
            <div class="form-group form-full">
                <label>Channel <span style="color:var(--danger)">*</span></label>
                <select name="channel_id" required>
                    @foreach($channels as $ch)
                    <option value="{{ $ch->id }}" {{ (old('channel_id', $output->channel_id ?? $selected) == $ch->id) ? 'selected' : '' }}>{{ $ch->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group form-full">
                <label>Name <span style="color:var(--danger)">*</span></label>
                <input type="text" name="name" value="{{ old('name', $output->name ?? '') }}" placeholder="e.g. YouTube Live, Facebook, CDN Backup" required>
            </div>

            <div class="form-group form-full">
                <label>Destination URL <span style="color:var(--danger)">*</span></label>
                <input type="text" name="output_url" value="{{ old('output_url', $output->output_url ?? '') }}" placeholder="rtmp://a.rtmp.youtube.com/live2/KEY  or  srt://cdn:9000" required>
            </div>

            <div class="form-group">
                <label>Protocol <span style="color:var(--danger)">*</span></label>
                <select name="output_protocol" required>
                    @foreach(['rtmp'=>'RTMP','rtmps'=>'RTMPS (TLS)','srt'=>'SRT','mpeg_ts_udp'=>'MPEG-TS UDP','mpeg_ts_tcp'=>'MPEG-TS TCP','rtp'=>'RTP','hls_push'=>'HLS Push','icecast'=>'Icecast','shoutcast'=>'Shoutcast','file'=>'Record to File'] as $val => $label)
                    <option value="{{ $val }}" {{ old('output_protocol', $output->output_protocol ?? 'rtmp') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Trigger</label>
                <select name="trigger">
                    @foreach(['always'=>'Always (live + VOD fallback)','live_only'=>'Live only','fallback_only'=>'VOD fallback only','manual'=>'Manual (API only)'] as $val => $label)
                    <option value="{{ $val }}" {{ old('trigger', $output->trigger ?? 'always') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <hr style="border-color:var(--border);margin:1.5rem 0;">
        <div style="margin-bottom:1rem;">
            <div style="font-weight:600;margin-bottom:0.25rem;">Transcoding (optional)</div>
            <div class="hint">Leave blank / set to "copy" for zero-latency passthrough. Only fill in if you need format conversion.</div>
        </div>

        <div class="form-grid-3">
            <div class="form-group">
                <label>Video Codec</label>
                <select name="video_codec">
                    <option value="copy" {{ old('video_codec', $output->video_codec ?? 'copy') === 'copy' ? 'selected' : '' }}>copy (passthrough)</option>
                    <option value="libx264" {{ old('video_codec', $output->video_codec ?? '') === 'libx264' ? 'selected' : '' }}>H.264 (libx264)</option>
                    <option value="libx265" {{ old('video_codec', $output->video_codec ?? '') === 'libx265' ? 'selected' : '' }}>H.265 (libx265)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Audio Codec</label>
                <select name="audio_codec">
                    <option value="copy" {{ old('audio_codec', $output->audio_codec ?? 'copy') === 'copy' ? 'selected' : '' }}>copy (passthrough)</option>
                    <option value="aac" {{ old('audio_codec', $output->audio_codec ?? '') === 'aac' ? 'selected' : '' }}>AAC</option>
                    <option value="libmp3lame" {{ old('audio_codec', $output->audio_codec ?? '') === 'libmp3lame' ? 'selected' : '' }}>MP3</option>
                </select>
            </div>
            <div class="form-group">
                <label>Resolution</label>
                <input type="text" name="resolution" value="{{ old('resolution', $output->resolution ?? '') }}" placeholder="1280x720">
            </div>
            <div class="form-group">
                <label>Video Bitrate (kbps)</label>
                <input type="number" name="video_bitrate_kbps" value="{{ old('video_bitrate_kbps', $output->video_bitrate_kbps ?? '') }}" placeholder="2500">
            </div>
            <div class="form-group">
                <label>Audio Bitrate (kbps)</label>
                <input type="number" name="audio_bitrate_kbps" value="{{ old('audio_bitrate_kbps', $output->audio_bitrate_kbps ?? '') }}" placeholder="128">
            </div>
            <div class="form-group">
                <label>Framerate</label>
                <input type="number" name="framerate" value="{{ old('framerate', $output->framerate ?? '') }}" placeholder="30">
            </div>
            <div class="form-group">
                <label>SRT Latency (ms)</label>
                <input type="number" name="srt_latency_ms" value="{{ old('srt_latency_ms', $output->srt_latency_ms ?? 120) }}" placeholder="120">
            </div>
            <div class="form-group">
                <label>SRT Passphrase</label>
                <input type="text" name="srt_passphrase" value="{{ old('srt_passphrase', $output->srt_passphrase ?? '') }}" placeholder="min 10 chars">
            </div>
            <div class="form-group" style="justify-content:flex-end;padding-top:1.5rem;">
                <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                    <input type="checkbox" name="is_enabled" value="1" {{ old('is_enabled', $output->is_enabled ?? true) ? 'checked' : '' }}>
                    Enabled
                </label>
            </div>
        </div>

        <div class="actions" style="margin-top:1.5rem;">
            <button type="submit" class="btn btn-primary">{{ isset($output) ? 'Update Output' : 'Create Output' }}</button>
            <a href="{{ route('admin.outputs.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
