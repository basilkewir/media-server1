@extends('layouts.admin')
@section('title', 'Output Targets')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span> Outputs
@endsection

@section('topbar-actions')
    <a href="{{ route('admin.outputs.create') }}" class="btn btn-primary btn-sm">+ New Output</a>
@endsection

@section('content')
@php $targets = \App\Models\OutputTarget::with('channel')->get(); @endphp
<div class="card animate-in">
    @if($targets->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon"><svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
        <div class="empty-state-title">No output targets</div>
        <div class="empty-state-text">Configure output targets to push streams to external services.</div>
    </div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Name</th><th>Channel</th><th>Protocol</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($targets as $t)
                <tr>
                    <td><strong>{{ $t->name }}</strong><br><code style="font-family:var(--font-mono);font-size:11px;color:var(--text-tertiary);">{{ $t->url }}</code></td>
                    <td>{{ $t->channel?->name ?? '—' }}</td>
                    <td><span class="badge badge-brand">{{ strtoupper($t->protocol) }}</span></td>
                    <td>
                        @if($t->status === 'connected')
                            <span class="badge badge-success">Connected</span>
                        @elseif($t->status === 'connecting')
                            <span class="badge badge-warning">Connecting</span>
                        @else
                            <span class="badge badge-neutral">{{ ucfirst($t->status ?: 'stopped') }}</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <a href="{{ route('admin.outputs.edit', $t) }}" class="btn btn-ghost btn-xs">Edit</a>
                            <form method="POST" action="{{ route('admin.outputs.start', $t) }}" style="display:inline;">@csrf<button class="btn btn-ghost btn-xs">Start</button></form>
                            <form method="POST" action="{{ route('admin.outputs.stop', $t) }}" style="display:inline;">@csrf<button class="btn btn-ghost btn-xs">Stop</button></form>
                            <form method="POST" action="{{ route('admin.outputs.destroy', $t) }}" onsubmit="return confirm('Delete?')" style="display:inline;">
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
