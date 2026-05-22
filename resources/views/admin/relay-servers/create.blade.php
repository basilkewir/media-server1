@extends('layouts.admin')
@section('title', isset($relayServer) ? 'Edit Relay Server' : 'Add Relay Server')

@section('content')
<div class="card" style="max-width:600px;">
    <div class="card-header"><span class="card-title">{{ isset($relayServer) ? 'Edit Relay Server' : 'Add Relay Server' }}</span></div>
    <form method="POST" action="{{ isset($relayServer) ? route('admin.relay-servers.update', $relayServer) : route('admin.relay-servers.store') }}">
        @csrf
        @if(isset($relayServer)) @method('PUT') @endif
        <div class="form-grid">
            <div class="form-group form-full">
                <label>Name *</label>
                <input type="text" name="name" value="{{ old('name', $relayServer->name ?? '') }}" placeholder="My Icecast Server" required>
            </div>
            <div class="form-group">
                <label>Hostname *</label>
                <input type="text" name="hostname" value="{{ old('hostname', $relayServer->hostname ?? '') }}" placeholder="relay.example.com" required>
            </div>
            <div class="form-group">
                <label>Port *</label>
                <input type="number" name="port" value="{{ old('port', $relayServer->port ?? 8000) }}" required>
            </div>
            <div class="form-group">
                <label>Type *</label>
                <select name="server_type" required>
                    @foreach(['icecast'=>'Icecast','rtmp'=>'RTMP','shoutcast'=>'Shoutcast'] as $v => $l)
                    <option value="{{ $v }}" {{ old('server_type', $relayServer->server_type ?? 'icecast') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="{{ old('username', $relayServer->username ?? 'source') }}">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="text" name="password" value="{{ old('password', $relayServer->password ?? '') }}">
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" value="{{ old('location', $relayServer->location ?? '') }}" placeholder="US-East">
            </div>
            <div class="form-group">
                <label>Max Listeners</label>
                <input type="number" name="max_listeners" value="{{ old('max_listeners', $relayServer->max_listeners ?? 1000) }}">
            </div>
            @if(isset($relayServer))
            <div class="form-group" style="padding-top:1.5rem;">
                <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $relayServer->is_active ?? true) ? 'checked' : '' }}>
                    Active
                </label>
            </div>
            @endif
        </div>
        <div class="actions" style="margin-top:1.5rem;">
            <button type="submit" class="btn btn-primary">{{ isset($relayServer) ? 'Update' : 'Add Server' }}</button>
            <a href="{{ route('admin.relay-servers.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
