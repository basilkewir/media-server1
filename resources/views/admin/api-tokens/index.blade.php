@extends('layouts.admin')
@section('title', 'API Tokens')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span> API Tokens
@endsection

@section('content')
<div class="card animate-in">
    <div class="card-header">
        <div>
            <div class="card-title">API Tokens</div>
            <div class="card-subtitle">Manage bearer tokens for API access</div>
        </div>
        <form method="POST" action="{{ route('admin.api-tokens.store') }}" style="display:flex;gap:8px;">
            @csrf
            <input name="name" placeholder="Token name" required style="width:180px;">
            <button type="submit" class="btn btn-primary btn-sm">Generate</button>
        </form>
    </div>

    @php $tokens = \App\Models\ApiToken::latest()->get(); @endphp
    @if($tokens->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon"><svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12 1v4m0 14v4M4.22 4.22l2.83 2.83"/></svg></div>
        <div class="empty-state-title">No tokens</div>
        <div class="empty-state-text">Generate an API token to access the REST API.</div>
    </div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Name</th><th>Token</th><th>Abilities</th><th>Expires</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($tokens as $t)
                <tr>
                    <td><strong>{{ $t->name }}</strong></td>
                    <td><code style="font-family:var(--font-mono);font-size:11px;">{{ $t->plain_token ?? '••••••••' }}</code></td>
                    <td>{{ $t->abilities ? implode(', ', $t->abilities) : 'All' }}</td>
                    <td>{{ $t->expires_at?->format('Y-m-d') ?? '—' }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.api-tokens.destroy', $t) }}" onsubmit="return confirm('Revoke token?')" style="display:inline;">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-xs" style="color:var(--danger);">Revoke</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
