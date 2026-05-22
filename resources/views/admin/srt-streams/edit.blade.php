@extends('layouts.admin')

@section('content')
<div class="container mx-auto p-4">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold">Edit SRT Stream</h1>
        <a href="{{ route('admin.srt-streams.index') }}" class="btn btn-ghost btn-sm">← Back to Streams</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-error mb-6">
            <div class="flex-1">
                <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.srt-streams.update', $stream->id) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Stream Name -->
                <div class="form-group">
                    <label class="form-label">
                        <span class="label-text font-semibold">Stream Name</span>
                    </label>
                    <input 
                        type="text" 
                        name="name" 
                        value="{{ old('name', $stream->name) }}"
                        placeholder="e.g., Compassion TV SRT"
                        class="input input-bordered w-full @error('name') input-error @enderror"
                        required
                    >
                    @error('name')
                        <span class="text-error text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label class="form-label">
                        <span class="label-text font-semibold">Description</span>
                    </label>
                    <textarea 
                        name="description"
                        placeholder="Optional description or notes"
                        class="textarea textarea-bordered w-full @error('description') textarea-error @enderror"
                        rows="3"
                    >{{ old('description', $stream->description) }}</textarea>
                    @error('description')
                        <span class="text-error text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Stream Info (Read-only) -->
                <div class="divider">Stream Configuration</div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Stream ID (Read-only) -->
                    <div class="form-group">
                        <label class="form-label">
                            <span class="label-text font-semibold">Stream ID</span>
                        </label>
                        <input 
                            type="text" 
                            value="{{ $stream->stream_id }}"
                            class="input input-bordered w-full bg-gray-100"
                            disabled
                        >
                        <p class="text-xs text-gray-500 mt-1">SRT Stream Identifier (immutable)</p>
                    </div>

                    <!-- SRT Port (Read-only) -->
                    <div class="form-group">
                        <label class="form-label">
                            <span class="label-text font-semibold">SRT Port</span>
                        </label>
                        <input 
                            type="text" 
                            value="{{ $stream->srt_port }}"
                            class="input input-bordered w-full bg-gray-100"
                            disabled
                        >
                        <p class="text-xs text-gray-500 mt-1">Assigned listening port (immutable)</p>
                    </div>
                </div>

                <!-- RTMP Stream Name (Read-only) -->
                <div class="form-group">
                    <label class="form-label">
                        <span class="label-text font-semibold">RTMP Stream Name</span>
                    </label>
                    <input 
                        type="text" 
                        value="{{ $stream->rtmp_stream }}"
                        class="input input-bordered w-full bg-gray-100"
                        disabled
                    >
                    <p class="text-xs text-gray-500 mt-1">RTMP endpoint name (immutable)</p>
                </div>

                <!-- Encoding Settings -->
                <div class="divider">Encoding & Quality</div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Bitrate -->
                    <div class="form-group">
                        <label class="form-label">
                            <span class="label-text font-semibold">Target Bitrate (kbps)</span>
                        </label>
                        <input 
                            type="number" 
                            name="bitrate" 
                            value="{{ old('bitrate', $stream->bitrate) }}"
                            min="100"
                            max="50000"
                            step="100"
                            class="input input-bordered w-full @error('bitrate') input-error @enderror"
                        >
                        @error('bitrate')
                            <span class="text-error text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Resolution -->
                    <div class="form-group">
                        <label class="form-label">
                            <span class="label-text font-semibold">Resolution</span>
                        </label>
                        <select 
                            name="resolution" 
                            class="select select-bordered w-full @error('resolution') select-error @enderror"
                        >
                            <option value="">-- Select Resolution --</option>
                            @foreach (['720p', '1080p', '2K', '4K'] as $res)
                                <option value="{{ $res }}" @selected(old('resolution', $stream->resolution) === $res)>
                                    {{ $res }}
                                </option>
                            @endforeach
                        </select>
                        @error('resolution')
                            <span class="text-error text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Video Codec -->
                <div class="form-group">
                    <label class="form-label">
                        <span class="label-text font-semibold">Video Codec</span>
                    </label>
                    <select 
                        name="codec_video" 
                        class="select select-bordered w-full @error('codec_video') select-error @enderror"
                    >
                        <option value="">-- Select Video Codec --</option>
                        @foreach (['h264', 'h265', 'vp9'] as $codec)
                            <option value="{{ $codec }}" @selected(old('codec_video', $stream->codec_video) === $codec)>
                                {{ strtoupper($codec) }}
                            </option>
                        @endforeach
                    </select>
                    @error('codec_video')
                        <span class="text-error text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Audio Codec -->
                <div class="form-group">
                    <label class="form-label">
                        <span class="label-text font-semibold">Audio Codec</span>
                    </label>
                    <select 
                        name="codec_audio" 
                        class="select select-bordered w-full @error('codec_audio') select-error @enderror"
                    >
                        <option value="">-- Select Audio Codec --</option>
                        @foreach (['aac', 'mp3', 'flac'] as $codec)
                            <option value="{{ $codec }}" @selected(old('codec_audio', $stream->codec_audio) === $codec)>
                                {{ strtoupper($codec) }}
                            </option>
                        @endforeach
                    </select>
                    @error('codec_audio')
                        <span class="text-error text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Status & Control -->
                <div class="divider">Status & Fallback</div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Enabled Toggle -->
                    <div class="form-group">
                        <label class="form-label">
                            <span class="label-text font-semibold">Enabled</span>
                        </label>
                        <div class="flex items-center gap-4">
                            <input 
                                type="checkbox" 
                                id="enabled-toggle"
                                class="toggle toggle-primary"
                                @checked($stream->enabled)
                            >
                            <span class="text-sm" id="enabled-text">
                                {{ $stream->enabled ? '✓ Enabled' : '✗ Disabled' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            Use the toggle button above to enable/disable this stream.
                        </p>
                    </div>

                    <!-- Current Status -->
                    <div class="form-group">
                        <label class="form-label">
                            <span class="label-text font-semibold">Stream Status</span>
                        </label>
                        <div class="flex items-center gap-2 text-sm font-semibold">
                            <span class="badge" 
                                @class([
                                    'badge-success' => $stream->status === 'connected',
                                    'badge-warning' => $stream->status === 'pending',
                                    'badge-error' => in_array($stream->status, ['disconnected', 'error']),
                                ])
                            >
                                {{ ucfirst($stream->status) }}
                            </span>
                            @if ($stream->last_connected_at)
                                <span class="text-xs text-gray-500">
                                    (Last: {{ $stream->last_connected_at->format('Y-m-d H:i:s') }})
                                </span>
                            @else
                                <span class="text-xs text-gray-500">(Never)</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- VOD Fallback Config -->
                <div class="divider">VOD Standby Playlist (Never Off-Air)</div>

                <p class="text-sm text-gray-600 mb-4">
                    When enabled, if the live SRT stream goes offline, the system will automatically play your VOD playlist so the channel never goes dark.
                </p>

                <!-- Link to Channel -->
                <div class="form-group">
                    <label class="form-label">
                        <span class="label-text font-semibold">Link to Channel (for VOD Fallback)</span>
                    </label>
                    <select 
                        name="channel_id" 
                        class="select select-bordered w-full @error('channel_id') select-error @enderror"
                    >
                        <option value="">-- Select Channel (Optional) --</option>
                        @foreach (\App\Models\Channel::orderBy('name')->get() as $ch)
                            <option value="{{ $ch->id }}" @selected(old('channel_id', $stream->channel_id) == $ch->id)>
                                {{ $ch->name }} ({{ $ch->slug }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-2">
                        Select a channel to manage VOD files and enable automatic fallback.
                    </p>
                    @error('channel_id')
                        <span class="text-error text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- VOD Fallback Toggle -->
                <div class="form-group">
                    <label class="form-label">
                        <span class="label-text font-semibold">Enable VOD Fallback</span>
                    </label>
                    <div class="flex items-center gap-4">
                        <input 
                            type="checkbox" 
                            name="vod_fallback_enabled"
                            id="vod-fallback-toggle"
                            class="toggle toggle-success"
                            @checked(old('vod_fallback_enabled', $stream->vod_fallback_enabled))
                            {{ $stream->channel_id ? '' : 'disabled' }}
                        >
                        <span class="text-sm" id="vod-fallback-text">
                            {{ old('vod_fallback_enabled', $stream->vod_fallback_enabled) ? '✓ Enabled' : '✗ Disabled' }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        Requires a linked channel and VOD files configured.
                    </p>
                </div>

                <!-- Manage VOD for this Channel -->
                @if ($stream->channel_id)
                    <div class="alert alert-info mt-4">
                        <svg class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h3 class="font-bold">Manage VOD Files</h3>
                            <div class="text-sm">
                                To add VOD files for this channel's fallback, click the button below.
                                <div class="mt-2">
                                    <a href="{{ route('admin.vod.index', $stream->channel) }}" class="btn btn-sm btn-primary">
                                        📁 Manage VOD Files
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Actions -->
                <div class="divider">Save Changes</div>

                <div class="flex gap-4 justify-end">
                    <a href="{{ route('admin.srt-streams.index') }}" class="btn btn-ghost">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stream Details Card -->
    <div class="mt-8 card bg-base-200 shadow">
        <div class="card-body">
            <h3 class="card-title text-lg">Stream Details</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-600">Created At</p>
                    <p class="font-mono">{{ $stream->created_at->format('Y-m-d H:i:s') }}</p>
                </div>
                <div>
                    <p class="text-gray-600">Last Updated</p>
                    <p class="font-mono">{{ $stream->updated_at->format('Y-m-d H:i:s') }}</p>
                </div>
                <div>
                    <p class="text-gray-600">Current Bitrate</p>
                    <p class="font-mono">{{ $stream->bitrate ?? 'N/A' }} kbps</p>
                </div>
                <div>
                    <p class="text-gray-600">SRT URL</p>
                    <p class="font-mono text-xs">srt://localhost:{{ $stream->srt_port }}?streamid={{ $stream->stream_id }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Update the enabled text label when toggle changes
    const toggle = document.getElementById('enabled-toggle');
    const text = document.getElementById('enabled-text');
    
    if (toggle) {
        toggle.addEventListener('change', function() {
            text.textContent = this.checked ? '✓ Enabled' : '✗ Disabled';
        });
    }

    // Enable/disable VOD fallback toggle based on channel selection
    const channelSelect = document.querySelector('select[name="channel_id"]');
    const vodToggle = document.getElementById('vod-fallback-toggle');
    const vodText = document.getElementById('vod-fallback-text');

    if (channelSelect && vodToggle) {
        const updateVodToggleState = () => {
            const hasChannel = channelSelect.value !== '';
            vodToggle.disabled = !hasChannel;
            if (!hasChannel) {
                vodToggle.checked = false;
                vodText.textContent = '✗ Disabled (select channel first)';
            }
        };

        channelSelect.addEventListener('change', updateVodToggleState);
        vodToggle.addEventListener('change', function() {
            vodText.textContent = this.checked ? '✓ Enabled' : '✗ Disabled';
        });

        // Initialize state on page load
        updateVodToggleState();
    }
</script>
@endsection
