@extends('layouts.admin')
@section('title', 'Media Server Settings')

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" style="max-width:700px;">
    @csrf

    <div class="card">
        <div class="card-header"><span class="card-title">⚙ Media Server Driver</span></div>
        <div class="hint" style="margin-bottom:1.25rem;">Choose which media server handles stream ingest and output. FFmpeg is built-in and requires no extra setup. Wowza and Flussonic require those servers to be installed and running.</div>

        <div class="form-group" style="margin-bottom:1.5rem;">
            <label>Active Driver</label>
            <select name="driver" id="driver-select" onchange="showDriverConfig(this.value)">
                <option value="ffmpeg"    {{ $settings['driver'] === 'ffmpeg'    ? 'selected' : '' }}>FFmpeg (built-in, default)</option>
                <option value="wowza"     {{ $settings['driver'] === 'wowza'     ? 'selected' : '' }}>Wowza Streaming Engine</option>
                <option value="flussonic" {{ $settings['driver'] === 'flussonic' ? 'selected' : '' }}>Flussonic Media Server</option>
            </select>
        </div>

        {{-- FFmpeg config --}}
        <div id="config-ffmpeg" class="driver-config">
            <div class="form-grid">
                <div class="form-group">
                    <label>FFmpeg Binary Path</label>
                    <input type="text" name="ffmpeg_path" value="{{ old('ffmpeg_path', $settings['ffmpeg_path']) }}" placeholder="/usr/bin/ffmpeg">
                </div>
                <div class="form-group">
                    <label>HLS Segment Duration (s)</label>
                    <input type="number" name="hls_segment_duration" value="{{ old('hls_segment_duration', $settings['hls_segment_duration']) }}" min="1" max="30">
                </div>
                <div class="form-group">
                    <label>HLS Segments in Playlist</label>
                    <input type="number" name="hls_segments_in_playlist" value="{{ old('hls_segments_in_playlist', $settings['hls_segments_in_playlist']) }}" min="2" max="60">
                </div>
            </div>
        </div>

        {{-- Wowza config --}}
        <div id="config-wowza" class="driver-config" style="display:none;">
            <div class="alert alert-warning">Wowza Streaming Engine must be installed and running. <a href="https://www.wowza.com/docs/wowza-streaming-engine-rest-api" target="_blank" style="color:inherit;">API Docs ↗</a></div>
            <div class="form-grid">
                <div class="form-group form-full">
                    <label>Wowza REST API URL</label>
                    <input type="url" name="wowza_url" value="{{ old('wowza_url', $settings['wowza_url']) }}" placeholder="http://localhost:8087">
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="wowza_username" value="{{ old('wowza_username', $settings['wowza_username']) }}" placeholder="admin">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="wowza_password" placeholder="Leave blank to keep current">
                </div>
                <div class="form-group">
                    <label>Application Name</label>
                    <input type="text" name="wowza_application" value="{{ old('wowza_application', $settings['wowza_application']) }}" placeholder="live">
                </div>
            </div>
        </div>

        {{-- Flussonic config --}}
        <div id="config-flussonic" class="driver-config" style="display:none;">
            <div class="alert alert-warning">Flussonic Media Server must be installed and running. <a href="https://flussonic.com/doc/api/" target="_blank" style="color:inherit;">API Docs ↗</a></div>
            <div class="form-grid">
                <div class="form-group form-full">
                    <label>Flussonic API URL</label>
                    <input type="url" name="flussonic_url" value="{{ old('flussonic_url', $settings['flussonic_url']) }}" placeholder="http://localhost:8080">
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="flussonic_username" value="{{ old('flussonic_username', $settings['flussonic_username']) }}" placeholder="admin">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="flussonic_password" placeholder="Leave blank to keep current">
                </div>
            </div>
        </div>

        <div class="actions" style="margin-top:1.5rem;">
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </div>
</form>

@push('scripts')
<script>
function showDriverConfig(driver) {
    document.querySelectorAll('.driver-config').forEach(el => el.style.display = 'none');
    const el = document.getElementById('config-' + driver);
    if (el) el.style.display = 'block';
}
showDriverConfig('{{ $settings['driver'] }}');
</script>
@endpush
@endsection
