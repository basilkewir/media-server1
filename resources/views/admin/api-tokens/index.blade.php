@extends('layouts.admin')
@section('title', 'API Tokens')

@section('content')

@if(session('new_token'))
<div class="alert alert-success">
    <strong>Token created — copy it now, it won't be shown again:</strong><br>
    <code class="mono" style="font-size:1rem;word-break:break-all;">{{ session('new_token') }}</code>
</div>
@endif

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;">

<div class="card">
    <div class="card-header"><span class="card-title">🔐 API Tokens</span></div>
    @if($tokens->count())
    <table>
        <thead><tr><th>Name</th><th>Last Used</th><th>Expires</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @foreach($tokens as $t)
        <tr>
            <td style="font-weight:600;">{{ $t->name }}</td>
            <td class="text-sm text-muted">{{ $t->last_used_at?->diffForHumans() ?? 'Never' }}</td>
            <td class="text-sm text-muted">{{ $t->expires_at?->format('Y-m-d') ?? 'Never' }}</td>
            <td>
                @if($t->is_active && (!$t->expires_at || $t->expires_at->isFuture()))
                    <span class="badge badge-live">Active</span>
                @else
                    <span class="badge badge-stopped">Revoked</span>
                @endif
            </td>
            <td>
                @if($t->is_active)
                <form method="POST" action="{{ route('admin.api-tokens.destroy', $t) }}" onsubmit="return confirm('Revoke this token?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-sm">Revoke</button>
                </form>
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    {{ $tokens->links() }}
    @else
    <div class="empty"><div class="empty-icon">🔐</div><p>No tokens yet.</p></div>
    @endif
</div>

<div class="card">
    <div class="card-header"><span class="card-title">Generate Token</span></div>
    <form method="POST" action="{{ route('admin.api-tokens.store') }}">
        @csrf
        <div class="form-group" style="margin-bottom:1rem;">
            <label>Token Name *</label>
            <input type="text" name="name" placeholder="My App" required>
        </div>
        <div class="form-group" style="margin-bottom:1.5rem;">
            <label>Expires in (days)</label>
            <input type="number" name="expires_in" placeholder="Leave blank = never" min="1">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Generate Token</button>
    </form>
    <div class="hint" style="margin-top:1rem;">Use tokens in API requests:<br><code class="mono">Authorization: Bearer &lt;token&gt;</code></div>
</div>

</div>
@endsection
