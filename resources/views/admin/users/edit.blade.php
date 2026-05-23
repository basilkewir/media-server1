@extends('layouts.admin')
@section('title', 'Edit — ' . $user->name)
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span>
    <a href="{{ route('admin.users.index') }}">Users</a> <span class="sep">/</span> {{ $user->name }}
@endsection

@section('content')
<div class="card animate-in">
    <div class="card-header"><div class="card-title">Edit User</div></div>
    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label class="label-required">Name</label>
                <input name="name" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="form-group">
                <label class="label-required">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>New Password (leave blank to keep)</label>
                <input type="password" name="password" minlength="6" placeholder="••••••••">
            </div>
            <div class="form-group">
                <label class="label-required">Role</label>
                <select name="role" required>
                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="manager" {{ old('role', $user->role) === 'manager' ? 'selected' : '' }}>Manager</option>
                </select>
            </div>
        </div>
        <div style="display:flex;gap:24px;margin:12px 0;">
            <label class="toggle-row">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                Active Account
            </label>
        </div>
        @if($channels->isNotEmpty())
        <div class="form-group" style="margin-top:12px;">
            <label>Assigned Channels</label>
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;">
                @foreach($channels as $ch)
                <label class="toggle-row">
                    <input type="checkbox" name="channels[]" value="{{ $ch->id }}" {{ in_array($ch->id, old('channels', $user->managedChannels->pluck('id')->toArray())) ? 'checked' : '' }}>
                    {{ $ch->name }}
                </label>
                @endforeach
            </div>
        </div>
        @endif
        <div style="margin-top:20px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
