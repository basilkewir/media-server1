@extends('layouts.admin')

@section('title', 'Channels')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h1 style="margin: 0;">Channels</h1>
    <a href="{{ route('admin.channels.create') }}" class="btn btn-primary">+ New Channel</a>
</div>

<div class="card">
    @if($channels->count())
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Status</th>
                <th>Type</th>
                <th>Resolution</th>
                <th style="width: 280px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($channels as $channel)
            @php
                $activeStream = $channel->activeStream();
            @endphp
            <tr>
                <td><strong>{{ $channel->name }}</strong></td>
                <td><code>{{ $channel->slug }}</code></td>
                <td>
                    @if($channel->is_live)
                        <span style="color: var(--success); font-weight: 600;">● Live</span>
                    @elseif($activeStream && $activeStream->isFallback())
                        <span style="color: var(--warning); font-weight: 600;">● VOD Fallback</span>
                    @else
                        <span style="color: var(--text-muted);">● Offline</span>
                    @endif
                </td>
                <td>
                    @if($activeStream)
                        <span class="badge badge-{{ $activeStream->stream_type }}">{{ ucfirst($activeStream->stream_type) }}</span>
                    @else
                        <span class="badge" style="background: #f1f5f9; color: var(--text-muted);">—</span>
                    @endif
                </td>
                <td>{{ $channel->resolution ?? '—' }}</td>
                <td>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <a href="{{ route('admin.channels.show', $channel) }}" class="btn" style="padding: 0.375rem 0.75rem; font-size: 0.875rem; background: #f1f5f9; color: var(--text);">View</a>
                        <a href="{{ route('admin.channels.edit', $channel) }}" class="btn" style="padding: 0.375rem 0.75rem; font-size: 0.875rem; background: #dbeafe; color: #1e40af;">Edit</a>
                        <a href="{{ route('stream.play', $channel->slug) }}" target="_blank" class="btn" style="padding: 0.375rem 0.75rem; font-size: 0.875rem; background: #dcfce7; color: #166534;">Player</a>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="color: var(--text-muted);">No channels found. <a href="{{ route('admin.channels.create') }}">Create your first channel</a>.</p>
    @endif
</div>
@endsection
