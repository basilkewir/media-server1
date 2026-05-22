@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')
<div class="stats">
    <div class="stat stat-live">
        <div class="stat-value">{{ $stats['live_channels'] }}</div>
        <div class="stat-label">Live Channels</div>
    </div>
    <div class="stat">
        <div class="stat-value">{{ $stats['total_channels'] }}</div>
        <div class="stat-label">Total Channels</div>
    </div>
    <div class="stat stat-active">
        <div class="stat-value">{{ $stats['active_outputs'] }}</div>
        <div class="stat-label">Active Outputs</div>
    </div>
    <div class="stat">
        <div class="stat-value">{{ $stats['active_relays'] }}</div>
        <div class="stat-label">Active Relays</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;">

<div class="card">
    <div class="card-header">
        <span class="card-title">📺 Channels</span>
        <a href="{{ route('admin.channels.index') }}" class="btn btn-ghost btn-sm">View All</a>
    </div>
    @forelse($channels as $ch)
    @php $stream = $ch->streams->first(); @endphp
    <div style="display:flex;align-items:center;justify-content:space-between;padding:0.75rem 0;border-bottom:1px solid var(--border);">
        <div style="display:flex;align-items:center;gap:0.75rem;">
            @if($ch->is_live && !$stream?->isFallback())
                <span class="dot dot-live"></span>
            @elseif($stream?->isFallback())
                <span class="dot" style="background:#f59e0b;"></span>
            @else
                <span class="dot dot-offline"></span>
            @endif
            <div>
                <div style="font-weight:600;font-size:0.875rem;">{{ $ch->name }}</div>
                <div class="text-muted text-sm mono">{{ $ch->slug }}</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:0.5rem;">
            @if($stream)
                <span class="badge badge-{{ strtolower($stream->input_protocol ?? 'rtmp') }}">{{ strtoupper($stream->input_protocol ?? '') }}</span>
            @endif
            <a href="{{ route('admin.channels.show', $ch) }}" class="btn btn-ghost btn-sm">Manage</a>
        </div>
    </div>
    @empty
    <div class="empty">
        <div class="empty-icon">📺</div>
        <p>No channels yet. <a href="{{ route('admin.channels.create') }}" style="color:var(--primary)">Create one</a>.</p>
    </div>
    @endforelse
</div>

<div>
    <div class="card">
        <div class="card-header"><span class="card-title">⚙ Media Server</span></div>
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1rem;">
            <span class="badge badge-active" style="font-size:0.8rem;">{{ strtoupper($driver) }}</span>
            <span class="text-sm text-muted">Active Driver</span>
        </div>
        <a href="{{ route('admin.settings.index') }}" class="btn btn-ghost btn-sm" style="width:100%;justify-content:center;">Configure Driver</a>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">📋 Recent Events</span></div>
        @forelse($recentEvents as $event)
        <div style="padding:0.5rem 0;border-bottom:1px solid var(--border);font-size:0.8rem;">
            <div style="display:flex;justify-content:space-between;margin-bottom:0.2rem;">
                <span style="font-weight:600;">{{ $event->channel?->name ?? '—' }}</span>
                <span class="text-muted">{{ $event->created_at->diffForHumans() }}</span>
            </div>
            <div class="text-muted">{{ Str::limit($event->message, 60) }}</div>
        </div>
        @empty
        <div class="text-muted text-sm">No recent events.</div>
        @endforelse
    </div>
</div>

</div>
@endsection
