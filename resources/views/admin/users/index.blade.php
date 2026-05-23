@extends('layouts.admin')
@section('title', 'Users')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span> Users
@endsection

@section('topbar-actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">+ New User</a>
@endsection

@section('content')
<div class="card animate-in">
    @if($users->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon"><svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75"/></svg></div>
        <div class="empty-state-title">No users</div>
        <div class="empty-state-text">Create the first user account.</div>
    </div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Name</th><th>Email</th><th>Role</th><th>Channels</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td><strong>{{ $user->name }}</strong></td>
                    <td style="color:var(--text-secondary);">{{ $user->email }}</td>
                    <td><span class="badge {{ $user->isAdmin() ? 'badge-brand' : 'badge-neutral' }}">{{ ucfirst($user->role) }}</span></td>
                    <td><span class="badge badge-info">{{ $user->managed_channels_count }}</span></td>
                    <td>
                        @if($user->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-danger">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-ghost btn-xs">Edit</a>
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete user {{ $user->name }}?')" style="display:inline;">
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost btn-xs" style="color:var(--danger);">Delete</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;">{{ $users->links() }}</div>
    @endif
</div>
@endsection
