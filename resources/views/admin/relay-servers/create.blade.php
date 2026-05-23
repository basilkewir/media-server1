@extends('layouts.admin')
@section('title', 'Add Relay Server')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span>
    <a href="{{ route('admin.relay-servers.index') }}">Relays</a> <span class="sep">/</span> Add
@endsection

@section('content')
<div class="card animate-in" style="max-width:500px;">
    <div class="card-header"><div class="card-title">Add Relay Server</div></div>
    <form method="POST" action="{{ route('admin.relay-servers.store') }}">
        @csrf
        <div class="form-group">
            <label class="label-required">Name</label>
            <input name="name" value="{{ old('name') }}" placeholder="Primary Relay" required>
        </div>
        <div class="form-grid" style="margin-top:12px;">
            <div class="form-group">
                <label class="label-required">Hostname</label>
                <input name="hostname" value="{{ old('hostname') }}" placeholder="relay.example.com" required>
            </div>
            <div class="form-group">
                <label class="label-required">Port</label>
                <input type="number" name="port" value="{{ old('port', 8000) }}" required>
            </div>
        </div>
        <div class="form-grid" style="margin-top:12px;">
            <div class="form-group">
                <label>Username</label>
                <input name="username" value="{{ old('username', 'source') }}">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" value="{{ old('password') }}">
            </div>
        </div>
        <div class="form-group" style="margin-top:12px;">
            <label>Type</label>
            <select name="server_type" required>
                <option value="icecast">Icecast</option>
                <option value="rtmp">RTMP</option>
                <option value="shoutcast">Shoutcast</option>
            </select>
        </div>
        <div style="margin-top:16px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary">Add Server</button>
            <a href="{{ route('admin.relay-servers.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
