@extends('layouts.admin')
@section('title', 'Plans & Pricing')
@section('topbar-actions')
    <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Sign In</a>
    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Create Account</a>
@endsection

@section('content')
<div style="text-align:center;margin-bottom:32px;" class="animate-in">
    <h1 style="font-size:28px;font-weight:800;letter-spacing:-0.02em;">Choose Your Plan</h1>
    <p style="color:var(--text-tertiary);margin-top:6px;">Dynamic plans with flexible storage and feature tiers</p>
</div>

@if($freePlans->isNotEmpty())
<div style="margin-bottom:16px;font-weight:600;font-size:15px;">Free Plans</div>
<div class="stats-grid animate-in">
    @foreach($freePlans as $plan)
    <div class="card" style="padding:24px;text-align:center;">
        <h3 style="font-size:18px;font-weight:700;">{{ $plan->name }}</h3>
        <p style="font-size:13px;color:var(--text-tertiary);margin:4px 0 12px;">{{ $plan->formattedQuota() }} storage</p>
        <div style="font-size:32px;font-weight:800;color:var(--success);">{{ $plan->formattedPrice() }}</div>
        <ul style="list-style:none;margin:16px 0;font-size:13px;color:var(--text-secondary);text-align:left;display:inline-block;">
            <li style="padding:4px 0;">{{ $plan->max_channels }} channel(s)</li>
            <li style="padding:4px 0;">{{ $plan->max_vod_files }} VOD files</li>
            @if($plan->features)
                @foreach($plan->features as $f)
                <li style="padding:4px 0;">{{ ucfirst(str_replace('_',' ',$f)) }}</li>
                @endforeach
            @endif
        </ul>
        <a href="{{ route('register') }}?plan_id={{ $plan->id }}" class="btn btn-primary btn-sm" style="width:100%;">Get Started</a>
    </div>
    @endforeach
</div>
@endif

@if($paidPlans->isNotEmpty())
<div style="margin:32px 0 16px;font-weight:600;font-size:15px;">Paid Plans</div>
<div class="stats-grid animate-in" style="animation-delay:0.1s;">
    @foreach($paidPlans as $plan)
    <div class="card" style="padding:24px;text-align:center;border-color:var(--brand-glow);">
        <span class="badge badge-brand" style="margin-bottom:8px;">{{ ucfirst($plan->tier) }}</span>
        <h3 style="font-size:18px;font-weight:700;margin-top:4px;">{{ $plan->name }}</h3>
        <p style="font-size:13px;color:var(--text-tertiary);margin:4px 0 12px;">{{ $plan->formattedQuota() }} storage</p>
        <div style="font-size:32px;font-weight:800;color:var(--brand-light);">{{ $plan->formattedPrice() }}</div>
        <ul style="list-style:none;margin:16px 0;font-size:13px;color:var(--text-secondary);text-align:left;display:inline-block;">
            <li style="padding:4px 0;">{{ $plan->max_channels }} channel(s)</li>
            <li style="padding:4px 0;">{{ $plan->max_vod_files }} VOD files</li>
            @if($plan->features)
                @foreach($plan->features as $f)
                <li style="padding:4px 0;">{{ ucfirst(str_replace('_',' ',$f)) }}</li>
                @endforeach
            @endif
        </ul>
        <a href="{{ route('register') }}?plan_id={{ $plan->id }}" class="btn btn-primary btn-sm" style="width:100%;">Subscribe</a>
    </div>
    @endforeach
</div>
@endif
@endsection
