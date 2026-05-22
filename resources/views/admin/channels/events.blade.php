@extends('layouts.admin')

@section('title', $channel->name . ' Events')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h1 style="margin: 0;">Event Log</h1>
        <p style="margin: 0.25rem 0 0; color: var(--text-muted);">{{ $channel->name }}</p>
    </div>
    <a href="{{ route('admin.channels.show', $channel) }}" class="btn" style="background: #f1f5f9; color: var(--text);">Back to Channel</a>
</div>

<div class="card">
    @if($events->count())
    <table>
        <thead>
            <tr>
                <th>Time</th>
                <th>Event</th>
                <th>Message</th>
                <th>Severity</th>
            </tr>
        </thead>
        <tbody>
            @foreach($events as $event)
            <tr>
                <td style="white-space: nowrap;">{{ $event->created_at->format('Y-m-d H:i:s') }}</td>
                <td><span class="badge" style="background: #f1f5f9; color: var(--text);">{{ $event->event_type }}</span></td>
                <td>{{ $event->message }}</td>
                <td>
                    @if($event->severity === 'info')
                        <span style="color: var(--success); font-weight: 600;">Info</span>
                    @elseif($event->severity === 'warning')
                        <span style="color: var(--warning); font-weight: 600;">Warning</span>
                    @elseif($event->severity === 'error')
                        <span style="color: var(--danger); font-weight: 600;">Error</span>
                    @else
                        <span style="color: var(--text-muted);">{{ ucfirst($event->severity) }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 1.5rem;">
        {{ $events->links() }}
    </div>
    @else
    <p style="color: var(--text-muted);">No events found for this channel.</p>
    @endif
</div>
@endsection
