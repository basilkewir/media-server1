@extends('layouts.admin')

@section('title', 'Users')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h1 style="margin: 0;">Users</h1>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">+ New User</a>
</div>

<div class="card">
    @if($users->count())
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Managed Channels</th>
                <th style="width: 160px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td><strong>{{ $user->name }}</strong></td>
                <td>{{ $user->email }}</td>
                <td>
                    @if($user->isAdmin())
                        <span class="badge badge-full_access">Admin</span>
                    @else
                        <span class="badge badge-library_only">Manager</span>
                    @endif
                </td>
                <td>
                    @if($user->is_active)
                        <span style="color: var(--success); font-weight: 600;">Active</span>
                    @else
                        <span style="color: var(--text-muted);">Inactive</span>
                    @endif
                </td>
                <td>{{ $user->managed_channels_count }}</td>
                <td>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn" style="padding: 0.375rem 0.75rem; font-size: 0.875rem; background: #dbeafe; color: #1e40af;">Edit</a>
                        @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" style="display:inline;" onsubmit="return confirm('Delete this user?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn" style="padding: 0.375rem 0.75rem; font-size: 0.875rem; background: #fee2e2; color: var(--danger); border: none;">Delete</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 1.5rem;">
        {{ $users->links() }}
    </div>
    @else
    <p style="color: var(--text-muted);">No users found. <a href="{{ route('admin.users.create') }}">Create your first user</a>.</p>
    @endif
</div>
@endsection
