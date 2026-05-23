@extends('layouts.admin')
@section('title', 'Generate Access Codes')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span>
    <a href="{{ route('admin.access-codes.index') }}">Access Codes</a> <span class="sep">/</span> Generate
@endsection

@section('content')
<div class="card animate-in" style="max-width:600px;">
    <div class="card-header"><div class="card-title">Generate Access Codes</div></div>
    <form method="POST" action="{{ route('admin.access-codes.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label>Type</label>
                <select name="type" required>
                    <option value="library_only">Library Only</option>
                    <option value="full_access">Full Access</option>
                    <option value="premium">Premium</option>
                    <option value="vod_manager">VOD Manager</option>
                </select>
            </div>
            <div class="form-group">
                <label>Duration (days)</label>
                <input type="number" name="duration_days" value="{{ old('duration_days', 30) }}" min="1" required>
            </div>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" max="1000" required>
            </div>
            <div class="form-group">
                <label>Max Uses (0=unlimited)</label>
                <input type="number" name="max_uses" value="{{ old('max_uses', 1) }}" min="0">
            </div>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Expiry Date</label>
                <input type="date" name="expires_at" value="{{ old('expires_at') }}">
            </div>
            <div class="form-group">
                <label>Channel (optional)</label>
                <select name="channel_id">
                    <option value="">All Channels</option>
                    @foreach(\App\Models\Channel::orderBy('name')->get() as $ch)
                        <option value="{{ $ch->id }}">{{ $ch->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div style="margin-top:20px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary">Generate</button>
            <a href="{{ route('admin.access-codes.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
