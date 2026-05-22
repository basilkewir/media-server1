@extends('layouts.admin')
@section('title', 'Channels')

@section('content')
@php
    $liveCount    = $channels->where('is_live', true)->count();
    $vodCount     = $channels->filter(fn($c) => $c->activeStream()?->isFallback())->count();
    $offlineCount = $channels->count() - $liveCount - $vodCount;
@endphp

{{-- Stats --}}
<div class="stats">
    <div class="stat stat-total">
        <div class="stat-value">{{ $channels->count() }}</div>
        <div class="stat-label">Total Channels</div>
    </div>
    <div class="stat stat-live">
        <div class="stat-value">{{ $liveCount }}</div>
        <div class="stat-label">Live Now</div>
    </div>
    <div class="stat stat-vod">
        <div class="stat-value">{{ $vodCount }}</div>
        <div class="stat-label">VOD Fallback</div>
    </div>
    <div class="stat stat-offline">
        <div class="stat-value">{{ $offlineCount }}</div>
        <div class="stat-label">Offline</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">All Channels</span>
        <a href="{{ route('admin.channels.create') }}" class="btn btn-primary btn-sm">+ New Channel</a>
    </div>

    @if($channels->count())
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Channel</th>
                <th>Status</th>
                <th>Protocol</th>
                <th>Quality</th>
                <th>VOD</th>
                <th>Output</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($channels as $channel)
            @php
                $stream = $channel->activeStream();
                $isLive = $channel->is_live;
                $isFallback = $stream?->isFallback();
                $hasVod = !empty($channel->vod_playlist_url);
                $hasAbr = $channel->resolution && $channel->bitrate_kbps;
            @endphp
            <tr>
                <td>
                    <div style="font-weight:600;">{{ $channel->name }}</div>
                    <div class="text-xs text-muted mono">{{ $channel->slug }}</div>
                </td>
                <td>
                    @if($isLive && !$isFallback)
                        <span class="flex"><span class="dot dot-live"></span> <strong style="color:var(--live)">Live</strong></span>
                    @elseif($isFallback)
                        <span class="flex"><span class="dot dot-vod"></span> <strong style="color:#f59e0b">VOD Fallback</strong></span>
                    @else
                        <span class="flex"><span class="dot dot-offline"></span> <span class="text-muted">Offline</span></span>
                    @endif
                </td>
                <td>
                    @if($stream)
                        <span class="badge badge-{{ strtolower($stream->input_protocol ?? 'rtmp') }}">
                            {{ strtoupper($stream->input_protocol ?? '—') }}
                        </span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($hasAbr)
                        <div class="abr-ladder">
                            <span class="abr-rung">{{ $channel->resolution }}</span>
                            <span class="abr-rung">{{ $channel->bitrate_kbps }}k</span>
                            <span class="badge badge-info" style="font-size:0.65rem;">ABR</span>
                        </div>
                    @elseif($channel->resolution)
                        <span class="text-sm">{{ $channel->resolution }}</span>
                    @else
                        <span class="text-muted">Copy</span>
                    @endif
                </td>
                <td>
                    @if($hasVod)
                        <span class="badge badge-vod">✓ VOD</span>
                    @else
                        <span class="text-muted text-xs">None</span>
                    @endif
                </td>
                <td>
                    @php $outputCount = $channel->outputTargets()->where('is_enabled', true)->count(); @endphp
                    @if($outputCount)
                        <span class="badge badge-active">{{ $outputCount }} output{{ $outputCount > 1 ? 's' : '' }}</span>
                    @else
                        <span class="text-muted text-xs">None</span>
                    @endif
                </td>
                <td>
                    <div class="actions">
                        <a href="{{ route('admin.channels.show', $channel) }}" class="btn btn-ghost btn-xs">View</a>
                        <a href="{{ route('admin.channels.edit', $channel) }}" class="btn btn-ghost btn-xs">Edit</a>
                        <a href="{{ route('admin.vod.index', $channel) }}" class="btn btn-ghost btn-xs">VOD</a>
                        <a href="{{ route('stream.play', $channel->slug) }}" target="_blank" class="btn btn-ghost btn-xs">▶ Play</a>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    @else
    <div class="empty">
        <div class="empty-icon">📺</div>
        <p>No channels yet. <a href="{{ route('admin.channels.create') }}" style="color:var(--primary)">Create your first channel</a>.</p>
    </div>
    @endif
</div>
@endsection
