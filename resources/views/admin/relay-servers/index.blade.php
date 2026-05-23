@extends('layouts.admin')
@section('title', 'Relay Servers')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span> Relays
@endsection

@section('topbar-actions')
    <a href="{{ route('admin.relay-servers.create') }}" class="btn btn-primary btn-sm">+ Add Server</a>
@endsection

@section('content')
@php $servers = \App\Models\RelayServer::all(); @endphp
<div class="card animate-in">
    @if($servers->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon"><svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/></svg></div>
        <div class="empty-state-title">No relay servers</div>
        <div class="empty-state-text">Add relay servers for Icecast/RTMP rebroadcasting.</div>
    </div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Name</th><th>Address</th><th>Type</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($servers as $srv)
                <tr>
                    <td><strong>{{ $srv->name }}</strong></td>
                    <td><code style="font-family:var(--font-mono);font-size:12px;">{{ $srv->hostname }}:{{ $srv->port }}</code></td>
                    <td><span class="badge badge-brand">{{ strtoupper($srv->server_type) }}</span></td>
                    <td>
                        <span class="badge {{ $srv->is_active ? 'badge-success' : 'badge-neutral' }}">
                            {{ $srv->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <a href="{{ route('admin.relay-servers.edit', $srv) }}" class="btn btn-ghost btn-xs">Edit</a>
                            <form method="POST" action="{{ route('admin.relay-servers.destroy', $srv) }}" onsubmit="return confirm('Delete?')" style="display:inline;">
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
