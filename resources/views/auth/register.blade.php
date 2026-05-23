@extends('layouts.admin')
@section('title', 'Create Account')
@section('topbar-actions')
    <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Sign In</a>
    <a href="{{ route('pricing') }}" class="btn btn-ghost btn-sm">View Plans</a>
@endsection

@section('content')
<div style="max-width:600px;margin:2rem auto;">
<div class="card animate-in">
    <div class="card-header">
        <div>
            <div class="card-title">Create Your Account</div>
            <div class="card-subtitle">Start streaming in minutes</div>
        </div>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group form-full">
                <label class="label-required">Full Name</label>
                <input name="name" value="{{ old('name') }}" placeholder="Your name" required>
            </div>
        </div>
        <div class="form-grid">
            <div class="form-group form-full">
                <label class="label-required">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required>
            </div>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label class="label-required">Password</label>
                <input type="password" name="password" placeholder="Min 6 characters" required minlength="6">
            </div>
            <div class="form-group">
                <label class="label-required">Confirm Password</label>
                <input type="password" name="password_confirmation" placeholder="Confirm password" required minlength="6">
            </div>
        </div>

        <div class="form-section">Choose Your Plan</div>
        @if($plans->isNotEmpty())
        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach($plans as $plan)
            <label style="display:flex;align-items:flex-start;gap:12px;padding:14px;background:var(--surface-2);border:2px solid var(--border);border-radius:var(--radius);cursor:pointer;transition:all var(--transition-fast);" onmouseenter="this.style.borderColor='var(--brand)'" onmouseleave="this.style.borderColor='var(--border)'">
                <input type="radio" name="plan_id" value="{{ $plan->id }}" required {{ old('plan_id') == $plan->id ? 'checked' : '' }}>
                <div>
                    <strong>{{ $plan->name }}</strong>
                    <div style="font-size:12px;color:var(--text-tertiary);">{{ $plan->formattedQuota() }} &bull; {{ $plan->max_channels }} channel(s)</div>
                    <div style="font-size:14px;font-weight:700;color:var(--success);margin-top:4px;">{{ $plan->formattedPrice() }}</div>
                    @if($plan->features)
                    <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:4px;">
                        @foreach($plan->features as $f)
                            <span class="badge badge-brand" style="font-size:10px;">{{ $f }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </label>
            @endforeach
        </div>
        @else
        <div class="alert alert-warning">No free plans available. Contact the administrator.</div>
        @endif

        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:20px;">Create Account</button>
    </form>
</div>
</div>
@endsection
