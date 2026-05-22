@extends('layouts.admin')
@section('title', 'Relay Servers')

@section('content')
<div class="card">
    <div class="card-header">
        <span class="card-title">🔁 Relay Servers</span>
        <a href="{{ route('admin.relay-servers.create') }}" class="btn btn-primary btn-sm">+ Add Server</a>
    </div>
    @if($servers->count())
    <div class="table-wrap">
    <table>
        <thead><tr><th>Name</th><th>Host</th><th>Type</th><th>Location</th><th>Broadcasts</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @foreach($servers as $s)
        <tr>
            <td style="font-weight:600;">{{ $s->name }}</td>
            <td class="mono text-sm">{{ $s->hostname }}:{{ $s->port }}</td>
            <td><span class="badge badge-active">{{ strtoupper($s->server_type) }}</span></td>
            <td class="text-muted text-sm">{{ $s->location ?? '—' }}</td>
            <td>{{ $s->broadcasts_count }}</td>
            <td>
                @if($s->is_active) <span class="badge badge-live">Active</span>
                @else <span class="badge badge-stopped">Inactive</span>
                @endif
            </td>
            <td>
                <div class="actions">
                    <a href="{{ route('admin.relay-servers.edit', $s) }}" class="btn btn-ghost btn-sm">Edit</a>
                    <form method="POST" action="{{ route('admin.relay-servers.destroy', $s) }}" onsubmit="return confirm('Deactivate?')">@csrf @method('DELETE') <button class="btn btn-danger btn-sm">Deactivate</button></form>
                </div>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    {{ $servers->links() }}
    @else
    <div class="empty"><div class="empty-icon">🔁</div><p>No relay servers. <a href="{{ route('admin.relay-servers.create') }}" style="color:var(--primary)">Add one</a>.</p></div>
    @endif
</div>

{{-- Quick start relay --}}
<div class="card" style="max-width:500px;">
    <div class="card-header"><span class="card-title">Start Relay</span></div>
    <form method="POST" action="{{ route('admin.relay-servers.start-relay') }}">
        @csrf
        <div class="form-group" style="margin-bottom:1rem;">
            <label>Channel</label>
            <select name="channel_id" required>
                @foreach(\App\Models\Channel::where('is_active',true)->orderBy('name')->get() as $ch)
                <option value="{{ $ch->id }}">{{ $ch->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin-bottom:1rem;">
            <label>Relay Server</label>
            <select name="relay_server_id" required>
                @foreach(\App\Models\RelayServer::where('is_active',true)->get() as $s)
                <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->hostname }})</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-success">Start Relay</button>
    </form>
</div>
@endsection
