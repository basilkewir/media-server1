@extends('layouts.admin')

@section('title', 'New User')

@section('content')
<div class="card">
    <h1>Create User</h1>
    <p style="color: var(--text-muted); margin-top: -0.5rem;">Add a new admin or manager account.</p>

    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf

        <div class="form-group">
            <label>Name <span class="required">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required>
            @error('name')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>Email <span class="required">*</span></label>
            <input type="email" name="email" value="{{ old('email') }}" required>
            @error('email')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>Password <span class="required">*</span></label>
            <input type="password" name="password" required>
            @error('password')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>Role <span class="required">*</span></label>
            <select name="role" required>
                <option value="manager" {{ old('role') == 'manager' ? 'selected' : '' }}>Manager — Can manage assigned channels only</option>
                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin — Full access to all channels and users</option>
            </select>
            @error('role')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>Assigned Channels <small>(for managers — admins see all channels)</small></label>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.5rem; margin-top: 0.5rem;">
                @foreach($channels as $channel)
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 500; cursor: pointer;">
                    <input type="checkbox" name="channels[]" value="{{ $channel->id }}" {{ in_array($channel->id, old('channels', [])) ? 'checked' : '' }}>
                    {{ $channel->name }}
                </label>
                @endforeach
            </div>
            @error('channels')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <button type="submit" class="btn btn-primary">Create User</button>
        <a href="{{ route('admin.users.index') }}" style="margin-left: 0.75rem; color: var(--text-muted);">Cancel</a>
    </form>
</div>
@endsection
