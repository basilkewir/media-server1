@extends('layouts.admin')
@section('title', 'Subscription Plans')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> &rsaquo; Plans
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Subscription Plans</div>
            <div class="card-subtitle">Manage dynamic pricing tiers and features</div>
        </div>
        <a href="{{ route('admin.plans.create') }}" class="btn btn-primary btn-sm">+ New Plan</a>
    </div>

    @if($plans->isEmpty())
        <div class="empty">
            <div class="empty-icon">📦</div>
            <p>No plans created yet.</p>
        </div>
    @else
        <div class="stats">
            @foreach($plans as $plan)
            <div class="stat">
                <div class="stat-value" style="font-size:1.5rem;">{{ $plan->name }}</div>
                <div class="stat-label" style="font-size:0.72rem;">
                    <span class="badge badge-{{ $plan->tier === 'free' ? 'offline' : ($plan->tier === 'enterprise' ? 'active' : 'info') }}">{{ ucfirst($plan->tier) }}</span>
                    <span style="margin-left:0.25rem;">{{ $plan->formattedPrice() }}</span>
                </div>
                <div style="margin-top:0.75rem;font-size:0.75rem;color:var(--muted);">
                    {{ $plan->formattedQuota() }} &bull; {{ $plan->max_channels }} channel(s)
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

@if($plans->isNotEmpty())
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Tier</th>
                    <th>Storage</th>
                    <th>Channels</th>
                    <th>Features</th>
                    <th>Price</th>
                    <th>Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($plans as $plan)
                <tr>
                    <td><strong>{{ $plan->name }}</strong></td>
                    <td><span class="badge badge-info">{{ ucfirst($plan->tier) }}</span></td>
                    <td>{{ $plan->formattedQuota() }}</td>
                    <td>{{ $plan->max_channels }}</td>
                    <td>
                        @if($plan->features)
                            @foreach($plan->features as $f)
                                <span class="badge badge-live" style="font-size:0.6rem;">{{ $f }}</span>
                            @endforeach
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{{ $plan->formattedPrice() }}</td>
                    <td>
                        @if($plan->is_active)
                            <span class="badge badge-active">Active</span>
                        @else
                            <span class="badge badge-stopped">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="actions">
                            <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-ghost btn-xs">Edit</a>
                            <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" onsubmit="return confirm('Delete plan \'{{ $plan->name }}\'?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost btn-xs" style="color:var(--danger);">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
