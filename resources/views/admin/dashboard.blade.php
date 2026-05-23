@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')
@php
$stats = $stats ?? ['live' => 0, 'total' => 0, 'outputs' => 0, 'relays' => 0];
$channels = $channels ?? collect();
$driver = $driver ?? 'ffmpeg';
$recentEvents = $recentEvents ?? collect();
@endphp

{{-- Stats row --}}
<div class="stats-grid animate-in">
    <div class="stat-card stagger-1">
        <div class="stat-icon" style="background:var(--success-dim);"><svg fill="none" stroke="var(--success)" stroke-width="2" viewBox="0 0 24 24"><path d="M23 7l-7 5 3 9-7-5-7 5 3-9-7-5h9l4-8 4 8h9z"/></svg></div>
        <div class="stat-value" style="color:var(--success);">{{ $stats['live'] }}</div>
        <div class="stat-label">Live Channels</div>
    </div>
    <div class="stat-card stagger-2">
        <div class="stat-icon" style="background:var(--brand-glow);"><svg fill="none" stroke="var(--brand-light)" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg></div>
        <div class="stat-value" style="color:var(--brand-light);">{{ $stats['total'] }}</div>
        <div class="stat-label">Total Channels</div>
    </div>
    <div class="stat-card stagger-3">
        <div class="stat-icon" style="background:var(--info-dim);"><svg fill="none" stroke="var(--info)" stroke-width="2" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
        <div class="stat-value" style="color:var(--info);">{{ $stats['outputs'] }}</div>
        <div class="stat-label">Active Outputs</div>
    </div>
    <div class="stat-card stagger-4">
        <div class="stat-icon" style="background:var(--warning-dim);"><svg fill="none" stroke="var(--warning)" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg></div>
        <div class="stat-value" style="color:var(--warning);">{{ $stats['relays'] }}</div>
        <div class="stat-label">Active Relays</div>
    </div>
</div>

{{-- Channels + Info --}}
<div style="display:grid; grid-template-columns:2fr 1fr; gap:20px;" class="animate-in">
    {{-- Channels --}}
    <div class="card" style="margin-bottom:0;">
        <div class="card-header">
            <div>
                <div class="card-title">Channels</div>
                <div class="card-subtitle">Overview of all configured channels</div>
            </div>
            <a href="{{ route('admin.channels.index') }}" class="btn btn-ghost btn-sm">View All</a>
        </div>

        @if($channels->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon"><svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg></div>
            <div class="empty-state-title">No channels yet</div>
            <div class="empty-state-text">Create your first channel to start streaming.</div>
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
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($channels->take(10) as $ch)
                    @php $stream = $ch->streams->first(); @endphp
                    <tr>
                        <td>
                            <strong>{{ $ch->name }}</strong>
                            <div style="font-size:12px;color:var(--text-tertiary);">{{ $ch->slug }}</div>
                        </td>
                        <td>
                            @if($stream && $stream->status === 'active')
                                <span class="badge badge-success"><span class="badge-dot green" style="margin-right:4px;"></span>Live</span>
                            @elseif($stream && $stream->status === 'fallback')
                                <span class="badge badge-warning"><span class="badge-dot amber" style="margin-right:4px;"></span>VOD Fallback</span>
                            @else
                                <span class="badge badge-neutral"><span class="badge-dot gray" style="margin-right:4px;"></span>Offline</span>
                            @endif
                        </td>
                        <td>
                            @if($stream && $stream->input_protocol)
                                <span class="badge badge-brand">{{ strtoupper($stream->input_protocol) }}</span>
                            @else
                                <span class="badge badge-neutral">—</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.channels.show', $ch) }}" class="btn btn-ghost btn-xs">Manage</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Right sidebar --}}
    <div style="display:flex;flex-direction:column;gap:20px;">
        {{-- Driver --}}
        <div class="card" style="margin-bottom:0;">
            <div class="card-title" style="margin-bottom:12px;">Media Server</div>
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:40px;height:40px;border-radius:var(--radius-sm);background:var(--brand-glow);display:flex;align-items:center;justify-content:center;">
                    <svg fill="none" stroke="var(--brand-light)" stroke-width="2" viewBox="0 0 24 24" width="18" height="18"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                </div>
                <div>
                    <div style="font-weight:700;font-size:15px;">{{ strtoupper($driver) }}</div>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-ghost btn-xs" style="margin-top:4px;">Configure</a>
                </div>
            </div>
        </div>

        {{-- Recent Events --}}
        <div class="card" style="margin-bottom:0;">
            <div class="card-title" style="margin-bottom:12px;">Recent Events</div>
            @if($recentEvents->isEmpty())
                <p style="color:var(--text-tertiary);font-size:13px;">No recent events.</p>
            @else
                <div>
                    @foreach($recentEvents as $event)
                    <div class="feed-item">
                        <div class="feed-time">{{ $event->created_at->diffForHumans(null, true) }}</div>
                        <div>
                            <strong>{{ $event->channel?->name ?? '—' }}</strong>
                            <div style="font-size:12px;color:var(--text-tertiary);">{{ Str::limit($event->message, 80) }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
