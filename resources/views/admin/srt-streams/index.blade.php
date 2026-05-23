@extends('layouts.admin')
@section('title', 'SRT Streams')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span> SRT Streams
@endsection

@section('topbar-actions')
    <a href="{{ route('admin.srt-streams.create') }}" class="btn btn-primary btn-sm">+ New SRT Stream</a>
@endsection

@section('content')
@php $streams = \App\Models\SrtStream::with('channel')->get(); @endphp
<div class="card animate-in">
    @if($streams->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon"><svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10m0-20a15.3 15.3 0 00-4 10 15.3 15.3 0 004 10"/></svg></div>
        <div class="empty-state-title">No SRT streams</div>
        <div class="empty-state-text">Create an SRT stream endpoint for low-latency ingest.</div>
    </div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Name</th><th>Stream ID</th><th>Port</th><th>Channel</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($streams as $srt)
                <tr>
                    <td><strong>{{ $srt->name }}</strong></td>
                    <td><code style="font-family:var(--font-mono);font-size:12px;">{{ $srt->stream_id }}</code></td>
                    <td><span class="badge badge-info">{{ $srt->srt_port }}</span></td>
                    <td>{{ $srt->channel?->name ?? '—' }}</td>
                    <td>
                        @if($srt->status === 'connected')
                            <span class="badge badge-success"><span class="badge-dot green" style="margin-right:4px;"></span>Connected</span>
                        @else
                            <span class="badge badge-neutral">{{ ucfirst($srt->status ?: 'idle') }}</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <a href="{{ route('admin.srt-streams.edit', $srt) }}" class="btn btn-ghost btn-xs">Edit</a>
                            <form method="POST" action="{{ route('admin.srt-streams.toggle', $srt) }}" style="display:inline;">
                                @csrf @method('PATCH')
                                <button class="btn btn-ghost btn-xs">{{ $srt->enabled ? 'Disable' : 'Enable' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.srt-streams.destroy', $srt) }}" onsubmit="return confirm('Delete SRT stream?')" style="display:inline;">
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost btn-xs" style="color:var(--danger);">Delete</button>
                            </form>
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
