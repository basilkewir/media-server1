@extends('layouts.client')
@section('title', 'Dashboard')

@section('content')
@if($clientSubscription)
<div class="alert alert-success" style="border-left:3px solid var(--success);">
    <strong>{{ $clientSubscription['type_label'] }}</strong>
    @if($clientSubscription['days_remaining'] !== null)
        — {{ $clientSubscription['days_remaining'] }} days remaining
    @endif
</div>
@else
<div class="alert alert-warning">Redeem an access code to unlock features.</div>
@endif

<div class="card">
    <div class="card-title">Available Channels</div>
    @php $channels = \App\Models\Channel::where('is_active',true)->get(); @endphp
    @if($channels->isEmpty())
    <div class="empty-state">
        <div class="empty-state-title">No channels available</div>
        <div class="empty-state-text">Check back later for live streams.</div>
    </div>
    @else
    <div class="channel-grid">
        @foreach($channels as $ch)
        @php $activeStream = $ch->activeStream(); @endphp
        <div class="channel-card">
            <div class="channel-card-title">{{ $ch->name }}</div>
            <div class="channel-card-meta">
                @if($activeStream)
                    <span class="badge badge-success"><span class="badge-dot green" style="margin-right:4px;"></span>Live</span>
                    @if($ch->resolution) {{ $ch->resolution }} @endif
                @else
                    <span class="badge badge-neutral"><span class="badge-dot gray" style="margin-right:4px;"></span>Offline</span>
                @endif
            </div>
            <a href="{{ route('stream.play', $ch->slug) }}" class="btn btn-primary btn-sm">Watch</a>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
