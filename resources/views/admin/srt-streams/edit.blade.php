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
                <div class="divider">Status</div>

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
</script>
@endsection
