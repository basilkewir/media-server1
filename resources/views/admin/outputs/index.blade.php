@extends('layouts.admin')
@section('title', 'Output Targets')

@section('content')
<div class="card">
    <div class="card-header">
        <span class="card-title">↗ Output Targets</span>
        <a href="{{ route('admin.outputs.create') }}" class="btn btn-primary btn-sm">+ Add Output</a>
    </div>
    @if($targets->count())
    <div class="table-wrap">
    <table>
        <thead><tr><th>Name</th><th>Channel</th><th>Protocol</th><th>Trigger</th><th>Status</th><th>Passthrough</th><th>Actions</th></tr></thead>
        <tbody>
        @foreach($targets as $t)
        <tr>
            <td style="font-weight:600;">{{ $t->name }}</td>
            <td><a href="{{ route('admin.channels.show', $t->channel) }}" style="color:var(--primary);">{{ $t->channel->name }}</a></td>
            <td><span class="badge badge-active">{{ strtoupper($t->output_protocol) }}</span></td>
            <td><span class="text-sm">{{ $t->trigger }}</span></td>
            <td>
                @if($t->status === 'connected') <span class="badge badge-live">Connected</span>
                @elseif($t->status === 'connecting') <span class="badge badge-fallback">Connecting</span>
                @elseif($t->status === 'error') <span class="badge badge-error">Error</span>
                @else <span class="badge badge-stopped">{{ ucfirst($t->status) }}</span>
                @endif
            </td>
            <td>{{ $t->isPassthrough() ? '✓ Zero-latency' : '⚙ Transcoding' }}</td>
            <td>
                <div class="actions">
                    @if(in_array($t->status, ['connected','connecting','reconnecting']))
                        <form method="POST" action="{{ route('admin.outputs.stop', $t) }}">@csrf <button class="btn btn-danger btn-sm">Stop</button></form>
                        <form method="POST" action="{{ route('admin.outputs.restart', $t) }}">@csrf <button class="btn btn-ghost btn-sm">Restart</button></form>
                    @else
                        <form method="POST" action="{{ route('admin.outputs.start', $t) }}">@csrf <button class="btn btn-success btn-sm">Start</button></form>
                    @endif
                    <a href="{{ route('admin.outputs.edit', $t) }}" class="btn btn-ghost btn-sm">Edit</a>
                    <form method="POST" action="{{ route('admin.outputs.destroy', $t) }}" onsubmit="return confirm('Delete this output?')">@csrf @method('DELETE') <button class="btn btn-danger btn-sm">Del</button></form>
                </div>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    {{ $targets->links() }}
    @else
    <div class="empty"><div class="empty-icon">↗</div><p>No output targets. <a href="{{ route('admin.outputs.create') }}" style="color:var(--primary)">Add one</a>.</p></div>
    @endif
</div>
@endsection
