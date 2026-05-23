@extends('layouts.admin')
@section('title', 'My Profile')

@section('content')
<div class="animate-in">
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--brand-glow);"><svg fill="none" stroke="var(--brand-light)" stroke-width="2" viewBox="0 0 24 24" width="18" height="18"><path d="M20 12V8H6a2 2 0 01-2-2c0-1.1.9-2 2-2h12v4"/></svg></div>
        <div class="stat-value" style="font-size:22px;">{{ $plan ? $plan->name : 'No Plan' }}</div>
        <div class="stat-label">Current Plan</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--info-dim);"><svg fill="none" stroke="var(--info)" stroke-width="2" viewBox="0 0 24 24" width="18" height="18"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg></div>
        <div class="stat-value" style="font-size:22px;">{{ $quotaInfo['used_formatted'] ?? '0 MB' }}</div>
        <div class="stat-label">Storage Used</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--success-dim);"><svg fill="none" stroke="var(--success)" stroke-width="2" viewBox="0 0 24 24" width="18" height="18"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
        <div class="stat-value" style="font-size:22px;">{{ $quotaInfo['remaining_formatted'] ?? '0 MB' }}</div>
        <div class="stat-label">Remaining</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--warning-dim);"><svg fill="none" stroke="var(--warning)" stroke-width="2" viewBox="0 0 24 24" width="18" height="18"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></div>
        <div class="stat-value" style="font-size:22px;">{{ $quotaInfo['quota_pct'] ?? 0 }}%</div>
        <div class="stat-label">Quota Used</div>
    </div>
</div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="animate-in" style="animation-delay:0.1s;">
    <div class="card">
        <div class="card-title" style="margin-bottom:16px;">Account Settings</div>
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label>Name</label>
                <input name="name" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="form-group" style="margin-top:12px;">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>
            <div class="form-grid" style="margin-top:12px;">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" minlength="6" placeholder="Leave blank">
                </div>
                <div class="form-group">
                    <label>Confirm</label>
                    <input type="password" name="password_confirmation" minlength="6" placeholder="Confirm">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="margin-top:16px;">Update Profile</button>
        </form>
    </div>

    <div class="card">
        <div class="card-title" style="margin-bottom:16px;">Subscription</div>
        @if($subscription)
        <div style="display:grid;grid-template-columns:auto 1fr;gap:8px 16px;font-size:13px;">
            <span style="color:var(--text-tertiary);">Plan</span>
            <span><strong>{{ $plan->name }}</strong> <span class="badge badge-brand" style="font-size:10px;">{{ ucfirst($plan->tier) }}</span></span>
            <span style="color:var(--text-tertiary);">Price</span>
            <span>{{ $plan->formattedPrice() }}</span>
            <span style="color:var(--text-tertiary);">Started</span>
            <span>{{ $subscription->starts_at->format('Y-m-d') }}</span>
            <span style="color:var(--text-tertiary);">Expires</span>
            <span>{{ $subscription->ends_at?->format('Y-m-d') ?? 'Never' }}</span>
            <span style="color:var(--text-tertiary);">Status</span>
            <span>
                @if($subscription->isCurrentlyActive())
                    <span class="badge badge-success">Active</span>
                @else
                    <span class="badge badge-danger">Inactive</span>
                @endif
            </span>
        </div>
        @else
        <div class="empty-state" style="padding:24px;">
            <div class="empty-state-title">No active subscription</div>
            <a href="{{ route('pricing') }}" class="btn btn-primary btn-sm" style="margin-top:12px;">View Plans</a>
        </div>
        @endif

        @if($plan && $plan->storage_quota_bytes > 0)
        <div style="margin-top:20px;">
            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-tertiary);margin-bottom:6px;">
                <span>Storage: {{ $quotaInfo['used_formatted'] ?? '0' }} / {{ $plan->formattedQuota() }}</span>
                <span>{{ $quotaInfo['quota_pct'] ?? 0 }}%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill {{ ($quotaInfo['quota_pct'] ?? 0) > 80 ? 'danger' : (($quotaInfo['quota_pct'] ?? 0) > 60 ? 'warning' : '') }}" style="width:{{ $quotaInfo['quota_pct'] ?? 0 }}%;"></div>
            </div>
            @if(!empty($plan->features))
            <div style="margin-top:12px;display:flex;flex-wrap:wrap;gap:4px;">
                @foreach($plan->features as $f)
                    <span class="badge badge-success" style="font-size:10px;">{{ $f }}</span>
                @endforeach
            </div>
            @endif
        </div>
        @endif
    </div>
</div>

<div style="margin-top:20px;">
    <a href="{{ route('pricing') }}" class="btn btn-primary btn-sm">Upgrade Plan</a>
</div>
@endsection
