@extends('layouts.client')

@section('title', 'Live Streams')

@section('content')
<div style="max-width: 900px;">
    <h2 style="font-size: 1.875rem; margin-bottom: 1.5rem;">Live Streams</h2>

    @if($channels->count())
    <div style="display: grid; gap: 1rem;">
        @foreach($channels as $channel)
        <div style="background: var(--surface); border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <div style="font-weight: 600;">{{ $channel->name }}</div>
                <div style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.25rem;">
                    @if($channel->is_live)
                        <span style="color: var(--success);">● Live</span>
                    @else
                        <span style="color: var(--text-muted);">● Offline</span>
                    @endif
                    &middot; {{ $channel->resolution ?? 'HD' }}
                </div>
            </div>
            <a href="{{ route('stream.play', $channel->slug) }}" style="background: var(--primary); color: #fff; padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; font-weight: 500; font-size: 0.875rem;">Watch</a>
        </div>
        @endforeach
    </div>
    @else
    <p style="color: var(--text-muted);">No live streams available.</p>
    @endif
</div>
@endsection
