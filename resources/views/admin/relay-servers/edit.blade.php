@extends('layouts.admin')
@section('title', 'Edit Relay')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span>
    <a href="{{ route('admin.relay-servers.index') }}">Relays</a> <span class="sep">/</span> {{ $relayServer->name }}
@endsection

@section('content')
<div class="card animate-in" style="max-width:500px;">
    <div class="card-header"><div class="card-title">Edit Relay Server</div></div>
    <form method="POST" action="{{ route('admin.relay-servers.update', $relayServer) }}">
        @csrf @method('PUT')
        <div class="form-group">
            <label class="label-required">Name</label>
            <input name="name" value="{{ old('name', $relayServer->name) }}" required>
        </div>
        <div class="form-grid" style="margin-top:12px;">
            <div class="form-group">
                <label class="label-required">Hostname</label>
                <input name="hostname" value="{{ old('hostname', $relayServer->hostname) }}" required>
            </div>
            <div class="form-group">
                <label class="label-required">Port</label>
                <input type="number" name="port" value="{{ old('port', $relayServer->port) }}" required>
            </div>
        </div>
        <div class="form-grid" style="margin-top:12px;">
            <div class="form-group">
                <label>Username</label>
                <input name="username" value="{{ old('username', $relayServer->username) }}">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Leave blank to keep">
            </div>
        </div>
        <div class="form-group" style="margin-top:12px;">
            <label>Type</label>
            <select name="server_type" required>
                <option value="icecast" {{ $relayServer->server_type === 'icecast' ? 'selected' : '' }}>Icecast</option>
                <option value="rtmp" {{ $relayServer->server_type === 'rtmp' ? 'selected' : '' }}>RTMP</option>
                <option value="shoutcast" {{ $relayServer->server_type === 'shoutcast' ? 'selected' : '' }}>Shoutcast</option>
            </select>
        </div>
        <div style="margin-top:12px;">
            <label class="toggle-row">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $relayServer->is_active) ? 'checked' : '' }}>
                Active
            </label>
        </div>
        <div style="margin-top:16px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('admin.relay-servers.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
