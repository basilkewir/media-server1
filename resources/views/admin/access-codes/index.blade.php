@extends('layouts.admin')
@section('title', 'Access Codes')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span> Access Codes
@endsection

@section('topbar-actions')
    <a href="{{ route('admin.access-codes.create') }}" class="btn btn-primary btn-sm">+ Generate Codes</a>
@endsection

@section('content')
<div class="card animate-in">
    @if($codes->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon"><svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 11-7.778 7.778 5.5 5.5 0 017.777-7.777z"/></svg></div>
        <div class="empty-state-title">No access codes</div>
        <div class="empty-state-text">Generate access codes for clients to redeem subscriptions.</div>
    </div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Code</th><th>Type</th><th>Duration</th><th>Uses</th><th>Expires</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($codes as $code)
                <tr>
                    <td><code style="font-family:var(--font-mono);font-size:13px;">{{ $code->code }}</code></td>
                    <td><span class="badge badge-brand">{{ $code->getTypeLabel() }}</span></td>
                    <td>{{ $code->duration_days }} days</td>
                    <td>{{ $code->uses_count }}/{{ $code->max_uses ?: '∞' }}</td>
                    <td>{{ $code->expires_at?->format('Y-m-d') ?? '—' }}</td>
                    <td>
                        @if($code->isValid())
                        <span class="badge badge-success">Valid</span>
                        @else
                        <span class="badge badge-neutral">Used/Expired</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.access-codes.destroy', $code) }}" onsubmit="return confirm('Delete this code?')" style="display:inline;">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-xs" style="color:var(--danger);">Delete</button>
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
