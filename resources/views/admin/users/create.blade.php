@extends('layouts.admin')
@section('title', 'Create User')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span>
    <a href="{{ route('admin.users.index') }}">Users</a> <span class="sep">/</span> Create
@endsection

@section('content')
<div class="card animate-in">
    <div class="card-header"><div class="card-title">Create User</div></div>
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="label-required">Name</label>
                <input name="name" value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label class="label-required">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required>
            </div>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label class="label-required">Password</label>
                <input type="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label class="label-required">Role</label>
                <select name="role" required>
                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="manager" {{ old('role') === 'manager' ? 'selected' : '' }} selected>Manager</option>
                </select>
            </div>
        </div>
        @if($channels->isNotEmpty())
        <div class="form-group" style="margin-top:12px;">
            <label>Assign Channels</label>
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;">
                @foreach($channels as $ch)
                <label class="toggle-row">
                    <input type="checkbox" name="channels[]" value="{{ $ch->id }}" {{ in_array($ch->id, old('channels', [])) ? 'checked' : '' }}>
                    {{ $ch->name }}
                </label>
                @endforeach
            </div>
        </div>
        @endif
        <div style="margin-top:20px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary">Create User</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
