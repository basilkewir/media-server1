@extends('layouts.admin')

@section('title', 'Edit ' . $user->name)

@section('content')
<div class="card">
    <h1>Edit User</h1>
    <p style="color: var(--text-muted); margin-top: -0.5rem;">{{ $user->email }}</p>

    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label>Name <span class="required">*</span></label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
            @error('name')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>Email <span class="required">*</span></label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
            @error('email')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>New Password <small>(leave blank to keep current)</small></label>
            <input type="password" name="password">
            @error('password')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>Role <span class="required">*</span></label>
            <select name="role" required>
                <option value="manager" {{ old('role', $user->role) == 'manager' ? 'selected' : '' }}>Manager — Can manage assigned channels only</option>
                <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin — Full access to all channels and users</option>
            </select>
            @error('role')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                Account Active
            </label>
            @error('is_active')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>Assigned Channels <small>(for managers — admins see all channels)</small></label>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.5rem; margin-top: 0.5rem;">
                @foreach($channels as $channel)
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 500; cursor: pointer;">
                    <input type="checkbox" name="channels[]" value="{{ $channel->id }}" {{ in_array($channel->id, old('channels', $user->managedChannels->pluck('id')->toArray())) ? 'checked' : '' }}>
                    {{ $channel->name }}
                </label>
                @endforeach
            </div>
            @error('channels')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <button type="submit" class="btn btn-primary">Update User</button>
        <a href="{{ route('admin.users.index') }}" style="margin-left: 0.75rem; color: var(--text-muted);">Cancel</a>
    </form>
</div>
@endsection
