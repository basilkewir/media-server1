@extends('layouts.admin')

@section('title', $channel->name)

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h1 style="margin: 0;">{{ $channel->name }}</h1>
        <p style="margin: 0.25rem 0 0; color: var(--text-muted);"><code>{{ $channel->slug }}</code></p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="{{ route('admin.channels.edit', $channel) }}" class="btn" style="background: #dbeafe; color: #1e40af;">Edit</a>
        <a href="{{ route('admin.channels.events', $channel) }}" class="btn" style="background: #f1f5f9; color: var(--text);">Events</a>
    </div>
</div>

@php
$activeStream = $channel->activeStream();
@endphp

<div class="card">
    <h2>Status</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-top: 1rem;">
        <div style="background: #f1f5f9; padding: 1rem; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">State</div>
            <div style="font-weight: 600; margin-top: 0.25rem;">
                @if($channel->is_live)
                    <span style="color: var(--success);">● Live</span>
                @elseif($activeStream && $activeStream->isFallback())
                    <span style="color: var(--warning);">● VOD Fallback</span>
                @else
                    <span style="color: var(--text-muted);">● Offline</span>
                @endif
            </div>
        </div>
        <div style="background: #f1f5f9; padding: 1rem; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Type</div>
            <div style="font-weight: 600; margin-top: 0.25rem;">{{ $activeStream ? ucfirst($activeStream->stream_type) : '—' }}</div>
        </div>
        <div style="background: #f1f5f9; padding: 1rem; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Resolution</div>
            <div style="font-weight: 600; margin-top: 0.25rem;">{{ $channel->resolution ?? '—' }}</div>
        </div>
        <div style="background: #f1f5f9; padding: 1rem; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Bitrate</div>
            <div style="font-weight: 600; margin-top: 0.25rem;">{{ $channel->bitrate_kbps ? $channel->bitrate_kbps . ' kbps' : '—' }}</div>
        </div>
    </div>

    @if($activeStream)
    <div style="margin-top: 1.5rem;">
        <h3 style="font-size: 1rem;">Active Stream</h3>
        <dl style="display: grid; grid-template-columns: auto 1fr; gap: 0.5rem 1.5rem; margin: 0.75rem 0 0;">
            <dt style="color: var(--text-muted);">Source:</dt>
            <dd><code style="word-break: break-all;">{{ $activeStream->source_url }}</code></dd>
            <dt style="color: var(--text-muted);">Protocol:</dt>
            <dd>{{ $activeStream->input_protocol }}</dd>
            <dt style="color: var(--text-muted);">Started:</dt>
            <dd>{{ $activeStream->started_at->format('Y-m-d H:i:s') }}</dd>
            <dt style="color: var(--text-muted);">Duration:</dt>
            <dd>{{ gmdate('H:i:s', $activeStream->getDuration()) }}</dd>
        </dl>
    </div>
    @endif

    <div style="margin-top: 1.5rem;">
        <h3 style="font-size: 1rem;">URLs</h3>
        <dl style="display: grid; grid-template-columns: auto 1fr; gap: 0.5rem 1.5rem; margin: 0.75rem 0 0;">
            <dt style="color: var(--text-muted);">HLS:</dt>
            <dd><code style="word-break: break-all;">{{ url("/streams/{$channel->slug}/playlist.m3u8") }}</code></dd>
            <dt style="color: var(--text-muted);">DASH:</dt>
            <dd><code style="word-break: break-all;">{{ url("/streams/{$channel->slug}/manifest.mpd") }}</code></dd>
            <dt style="color: var(--text-muted);">Player:</dt>
            <dd><a href="{{ route('stream.play', $channel->slug) }}" target="_blank">{{ route('stream.play', $channel->slug) }}</a></dd>
        </dl>
    </div>
</div>

@if($channel->streams->count())
<div class="card">
    <h2>Recent Streams</h2>
    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th>Status</th>
                <th>Source</th>
                <th>Started</th>
                <th>Ended</th>
                <th>Duration</th>
            </tr>
        </thead>
        <tbody>
            @foreach($channel->streams as $stream)
            <tr>
                <td><span class="badge badge-{{ $stream->stream_type }}">{{ ucfirst($stream->stream_type) }}</span></td>
                <td>
                    @if($stream->isActive())
                        <span style="color: var(--success); font-weight: 600;">Active</span>
                    @elseif($stream->isFallback())
                        <span style="color: var(--warning); font-weight: 600;">Fallback</span>
                    @else
                        <span style="color: var(--text-muted);">{{ ucfirst($stream->status) }}</span>
                    @endif
                </td>
                <td><code style="font-size: 0.75rem;">{{ Str::limit($stream->source_url, 50) }}</code></td>
                <td>{{ $stream->started_at?->format('Y-m-d H:i') ?? '—' }}</td>
                <td>{{ $stream->ended_at?->format('Y-m-d H:i') ?? '—' }}</td>
                <td>{{ gmdate('H:i:s', $stream->getDuration()) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="card" style="border: 1px solid #fee2e2;">
    <h2 style="color: var(--danger);">Danger Zone</h2>
    <p style="color: var(--text-muted);">Deleting a channel will stop any active stream and remove all associated data.</p>
    <form method="POST" action="{{ route('admin.channels.destroy', $channel) }}" onsubmit="return confirm('Permanently delete this channel?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn" style="background: var(--danger); color: #fff;">Delete Channel</button>
    </form>
</div>
@endsection
