@extends('layouts.admin')
@section('title', 'Channels')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span> Channels
@endsection

@section('topbar-actions')
    <a href="{{ route('admin.channels.create') }}" class="btn btn-primary btn-sm">+ New Channel</a>
@endsection

@section('content')
@php
    $liveCount = $channels->filter(fn($c) => $c->streams->first()?->status === 'active')->count();
    $vodCount  = $channels->filter(fn($c) => $c->streams->first()?->status === 'fallback')->count();
    $offCount  = $channels->count() - $liveCount - $vodCount;
@endphp

<div class="stats-grid animate-in">
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--success-dim);"><svg fill="none" stroke="var(--success)" stroke-width="2" viewBox="0 0 24 24" width="18" height="18"><polygon points="23 7 16 12 7 21 1 14 8 9 1 3"/></svg></div>
        <div class="stat-value" style="color:var(--success);">{{ $liveCount }}</div>
        <div class="stat-label">Live</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--warning-dim);"><svg fill="none" stroke="var(--warning)" stroke-width="2" viewBox="0 0 24 24" width="18" height="18"><polygon points="5 3 19 12 5 21 5 3"/></svg></div>
        <div class="stat-value" style="color:var(--warning);">{{ $vodCount }}</div>
        <div class="stat-label">VOD Fallback</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--surface-3);"><svg fill="none" stroke="var(--text-tertiary)" stroke-width="2" viewBox="0 0 24 24" width="18" height="18"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="9" y1="10" x2="15" y2="10"/><line x1="9" y1="14" x2="15" y2="14"/></svg></div>
        <div class="stat-value" style="color:var(--text-tertiary);">{{ $offCount }}</div>
        <div class="stat-label">Offline</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--brand-glow);"><svg fill="none" stroke="var(--brand-light)" stroke-width="2" viewBox="0 0 24 24" width="18" height="18"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg></div>
        <div class="stat-value" style="color:var(--brand-light);">{{ $channels->count() }}</div>
        <div class="stat-label">Total</div>
    </div>
</div>

<div class="card animate-in" style="animation-delay:0.1s;">
    @if($channels->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon"><svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg></div>
        <div class="empty-state-title">No channels configured</div>
        <div class="empty-state-text">Create your first channel to begin streaming.</div>
        <a href="{{ route('admin.channels.create') }}" class="btn btn-primary btn-sm">+ New Channel</a>
    </div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Channel</th>
                    <th>Status</th>
                    <th>Protocol</th>
                    <th>Quality</th>
                    <th>VOD</th>
                    <th>Outputs</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($channels as $ch)
                @php $stream = $ch->streams->first(); @endphp
                <tr>
                    <td>
                        <strong>{{ $ch->name }}</strong>
                        <div style="font-size:12px;color:var(--text-tertiary);">{{ $ch->slug }}</div>
                    </td>
                    <td>
                        @if($stream && $stream->status === 'active')
                            <span class="badge badge-success"><span class="badge-dot green" style="margin-right:5px;"></span>Live</span>
                        @elseif($stream && $stream->status === 'fallback')
                            <span class="badge badge-warning"><span class="badge-dot amber" style="margin-right:5px;"></span>VOD</span>
                        @else
                            <span class="badge badge-neutral"><span class="badge-dot gray" style="margin-right:5px;"></span>Offline</span>
                        @endif
                    </td>
                    <td>
                        @if($stream?->input_protocol)
                            <span class="badge badge-brand">{{ strtoupper($stream->input_protocol) }}</span>
                        @else
                            <span class="badge badge-neutral">—</span>
                        @endif
                    </td>
                    <td>
                        @if($ch->resolution && $ch->bitrate_kbps)
                            {{ $ch->resolution }} @ {{ round($ch->bitrate_kbps/1000, 1) }} Mbps
                            @if($ch->resolution && in_array($ch->resolution, ['1920x1080','1280x720']))
                                <span class="badge badge-info" style="font-size:10px;margin-left:4px;">ABR</span>
                            @endif
                        @else
                            <span class="badge badge-neutral">Copy</span>
                        @endif
                    </td>
                    <td>
                        @if($ch->vod_playlist_url)
                            <span class="badge badge-success">Configured</span>
                        @else
                            <span class="badge badge-neutral">None</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-brand">{{ $ch->outputTargets->where('is_enabled',true)->count() }}</span>
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <a href="{{ route('admin.channels.show', $ch) }}" class="btn btn-ghost btn-xs">View</a>
                            <a href="{{ route('admin.channels.edit', $ch) }}" class="btn btn-ghost btn-xs">Edit</a>
                            <a href="{{ route('admin.vod.index', $ch) }}" class="btn btn-ghost btn-xs">VOD</a>
                            <a href="{{ route('stream.play', $ch->slug) }}" target="_blank" class="btn btn-ghost btn-xs">Play</a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
