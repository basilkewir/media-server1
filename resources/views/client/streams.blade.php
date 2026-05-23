@extends('layouts.client')
@section('title', 'Live Streams')

@section('content')
<div class="card">
    <div class="card-title">Live Streams</div>
    @php $channels = \App\Models\Channel::where('is_active',true)->get(); @endphp
    @if($channels->isEmpty())
    <div class="empty-state">
        <div class="empty-state-title">No live streams available</div>
        <div class="empty-state-text">No channels are broadcasting right now.</div>
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
                @else
                    <span class="badge badge-neutral"><span class="badge-dot gray" style="margin-right:4px;"></span>Offline</span>
                @endif
                @if($ch->resolution) {{ $ch->resolution }} @endif
            </div>
            <a href="{{ route('stream.play', $ch->slug) }}" class="btn btn-primary btn-sm">Watch</a>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
