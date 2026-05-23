@extends('layouts.admin')
@section('title', 'Events — ' . $channel->name)
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span>
    <a href="{{ route('admin.channels.index') }}">Channels</a> <span class="sep">/</span>
    <a href="{{ route('admin.channels.show', $channel) }}">{{ $channel->name }}</a> <span class="sep">/</span> Events
@endsection

@section('content')
<div class="card animate-in">
    <div class="card-header">
        <div>
            <div class="card-title">Event Log</div>
            <div class="card-subtitle">{{ $channel->name }} — lifecycle events</div>
        </div>
    </div>

    @if($events->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon"><svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
            <div class="empty-state-title">No events recorded</div>
            <div class="empty-state-text">Stream lifecycle events will appear here when streaming begins.</div>
        </div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Time</th><th>Type</th><th>Message</th><th>Severity</th></tr>
            </thead>
            <tbody>
                @foreach($events as $event)
                <tr>
                    <td style="white-space:nowrap;font-size:12px;">{{ $event->created_at->format('Y-m-d H:i:s') }}</td>
                    <td><span class="badge badge-brand">{{ $event->event_type }}</span></td>
                    <td>{{ $event->message }}</td>
                    <td>
                        <span class="badge {{ match($event->severity) {
                            'info'     => 'badge-info',
                            'warning'  => 'badge-warning',
                            'error', 'critical' => 'badge-danger',
                            default    => 'badge-neutral'
                        } }}">{{ ucfirst($event->severity) }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
