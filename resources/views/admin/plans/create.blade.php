@extends('layouts.admin')
@section('title', 'Create Plan')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> &rsaquo; <a href="{{ route('admin.plans.index') }}">Plans</a> &rsaquo; Create
@endsection

@section('content')
<div class="card">
    <div class="card-header"><div class="card-title">Create Subscription Plan</div></div>

    <form method="POST" action="{{ route('admin.plans.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label>Name</label>
                <input name="name" value="{{ old('name') }}" required placeholder="e.g. Pro Monthly">
            </div>
            <div class="form-group">
                <label>Slug</label>
                <input name="slug" value="{{ old('slug') }}" required placeholder="pro-monthly">
            </div>
        </div>

        <div class="form-group mt-2">
            <label>Description</label>
            <textarea name="description" placeholder="Plan description">{{ old('description') }}</textarea>
        </div>

        <div class="form-section">Configuration</div>
        <div class="form-grid-4">
            <div class="form-group">
                <label>Tier</label>
                <select name="tier" required>
                    @foreach(\App\Models\Plan::tiers() as $key => $label)
                        <option value="{{ $key }}" {{ old('tier') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Storage Quota (MB)</label>
                <input type="number" name="storage_quota_mb" value="{{ old('storage_quota_mb', 500) }}" required min="1">
                <span class="hint">500 MB for free tier</span>
            </div>
            <div class="form-group">
                <label>Max Channels</label>
                <input type="number" name="max_channels" value="{{ old('max_channels', 1) }}" required min="1">
            </div>
            <div class="form-group">
                <label>Max VOD Files</label>
                <input type="number" name="max_vod_files" value="{{ old('max_vod_files', 10) }}" required min="1">
            </div>
        </div>

        <div class="form-grid-4">
            <div class="form-group">
                <label>Max Upload (MB)</label>
                <input type="number" name="max_upload_mb" value="{{ old('max_upload_mb', 500) }}" required min="1">
            </div>
            <div class="form-group">
                <label>Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
            </div>
            <div class="form-group">
                <label>&nbsp;</label>
                <label class="toggle-label">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}> Active
                </label>
            </div>
        </div>

        <div class="form-section">Features</div>
        <div class="form-grid-4">
            @foreach(['overlay' => 'Logo/Watermark Overlay', 'ticker' => 'Scrolling Ticker', 'scheduling' => 'VOD Scheduling', 'priority_support' => 'Priority Support', 'api_access' => 'API Access'] as $key => $label)
            <label class="toggle-label">
                <input type="checkbox" name="features[]" value="{{ $key }}" {{ in_array($key, old('features', [])) ? 'checked' : '' }}> {{ $label }}
            </label>
            @endforeach
        </div>

        <div class="form-section">Pricing</div>
        <div class="form-grid-3">
            <div class="form-group">
                <label>Price (cents)</label>
                <input type="number" name="price_cents" value="{{ old('price_cents', 0) }}" required min="0">
                <span class="hint">0 = Free plan</span>
            </div>
            <div class="form-group">
                <label>Currency</label>
                <select name="currency" required>
                    <option value="USD" {{ old('currency', 'USD') == 'USD' ? 'selected' : '' }}>USD</option>
                    <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR</option>
                    <option value="GBP" {{ old('currency') == 'GBP' ? 'selected' : '' }}>GBP</option>
                </select>
            </div>
            <div class="form-group">
                <label>Billing Interval</label>
                <select name="billing_interval" required>
                    <option value="month" {{ old('billing_interval', 'month') == 'month' ? 'selected' : '' }}>Monthly</option>
                    <option value="year" {{ old('billing_interval') == 'year' ? 'selected' : '' }}>Yearly</option>
                    <option value="once" {{ old('billing_interval') == 'once' ? 'selected' : '' }}>One-time</option>
                </select>
            </div>
        </div>

        <div class="mt-2">
            <button type="submit" class="btn btn-primary">Create Plan</button>
            <a href="{{ route('admin.plans.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
