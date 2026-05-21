@extends('layouts.client')

@section('title', 'Dashboard')

@section('content')
<div style="max-width: 900px;">
    <h2 style="font-size: 1.875rem; margin-bottom: 1.5rem;">Dashboard</h2>

    @if($clientSubscription)
    <div style="background: var(--surface); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; border-left: 4px solid var(--primary);">
        <h3 style="font-size: 1.125rem; margin-bottom: 0.5rem;">Active Subscription</h3>
        <p style="color: var(--text-muted);">
            <strong>{{ $clientSubscription['type_label'] }}</strong>
            @if($clientSubscription['days_remaining'])
                &mdash; {{ $clientSubscription['days_remaining'] }} days remaining
            @endif
        </p>
    </div>
    @else
    <div style="background: var(--surface); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.125rem; margin-bottom: 0.5rem;">No Active Subscription</h3>
        <p style="color: var(--text-muted);">Enter an access code to unlock content.</p>
    </div>
    @endif

    <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">Available Channels</h3>
    @if($channels->count())
    <div style="display: grid; gap: 1rem;">
        @foreach($channels as $channel)
        <div style="background: var(--surface); border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <div style="font-weight: 600;">{{ $channel->name }}</div>
                <div style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.25rem;">
                    {{ $channel->is_live ? 'Live' : 'Offline' }} &middot; {{ $channel->resolution ?? 'HD' }}
                </div>
            </div>
            <a href="{{ route('stream.play', $channel->slug) }}" style="background: var(--primary); color: #fff; padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; font-weight: 500; font-size: 0.875rem;">Watch</a>
        </div>
        @endforeach
    </div>
    @else
    <p style="color: var(--text-muted);">No channels available.</p>
    @endif
</div>
@endsection
