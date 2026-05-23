@extends('layouts.admin')

@section('title', 'Generate Access Codes')

@section('content')
<div class="card">
    <h1>Generate Access Codes</h1>
    <p style="color: var(--text-muted); margin-top: -0.5rem;">Create new subscription access codes for distribution.</p>

    <form method="POST" action="{{ route('admin.access-codes.store') }}">
        @csrf

        <div class="form-group">
            <label>Subscription Type <span class="required">*</span></label>
            <select name="type" id="type-select" required onchange="toggleChannel(this.value)">
                <option value="library_only" {{ old('type') == 'library_only' ? 'selected' : '' }}>Library Only</option>
                <option value="full_access" {{ old('type', 'full_access') == 'full_access' ? 'selected' : '' }}>Full Access</option>
                <option value="premium" {{ old('type') == 'premium' ? 'selected' : '' }}>Premium</option>
                <option value="vod_manager" {{ old('type') == 'vod_manager' ? 'selected' : '' }}>VOD Manager (channel-scoped)</option>
            </select>
            @error('type')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group" id="channel-group" style="display:none;">
            <label>Channel <span class="required">*</span></label>
            <select name="channel_id">
                <option value="">— Select a channel —</option>
                @foreach($channels as $ch)
                    <option value="{{ $ch->id }}" {{ old('channel_id') == $ch->id ? 'selected' : '' }}>
                        {{ $ch->name }} ({{ $ch->slug }})
                    </option>
                @endforeach
            </select>
            <small style="color:var(--muted);">The code will only work for this channel's VOD manager.</small>
            @error('channel_id')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>Subscription Duration <span class="required">*</span></label>
            <select name="duration_days" required>
                <option value="30" {{ old('duration_days') == 30 ? 'selected' : '' }}>1 Month</option>
                <option value="90" {{ old('duration_days') == 90 ? 'selected' : '' }}>3 Months</option>
                <option value="180" {{ old('duration_days') == 180 ? 'selected' : '' }}>6 Months</option>
                <option value="365" {{ old('duration_days', 365) == 365 ? 'selected' : '' }}>1 Year</option>
                <option value="730" {{ old('duration_days') == 730 ? 'selected' : '' }}>2 Years</option>
            </select>
            @error('duration_days')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>Number of Codes to Generate <span class="required">*</span></label>
            <select name="quantity" required>
                <option value="1" {{ old('quantity') == 1 ? 'selected' : '' }}>1</option>
                <option value="5" {{ old('quantity') == 5 ? 'selected' : '' }}>5</option>
                <option value="10" {{ old('quantity', 10) == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ old('quantity') == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ old('quantity') == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ old('quantity') == 100 ? 'selected' : '' }}>100</option>
            </select>
            @error('quantity')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>Max Uses per Code <small>(default: 1)</small></label>
            <input type="number" name="max_uses" value="{{ old('max_uses', 1) }}" min="1" max="1000">
            @error('max_uses')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>Code Length <small>(min 8, default: 12)</small></label>
            <input type="number" name="code_length" value="{{ old('code_length', 12) }}" min="8" max="32">
            @error('code_length')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <div class="form-group">
            <label>Valid Until <small>(Optional — leave empty if codes never expire)</small></label>
            <input type="date" name="expires_at" value="{{ old('expires_at') }}" min="{{ now()->addDay()->format('Y-m-d') }}">
            @error('expires_at')<small style="color: var(--danger);">{{ $message }}</small>@enderror
        </div>

        <button type="submit" class="btn btn-primary">Generate Codes</button>
    </form>
</div>

@if(session('summary'))
<div class="card">
    <div class="summary-box">
        <h3>Summary</h3>
        <dl>
            <dt>Type:</dt>
            <dd>{{ session('summary.type_label') }}@if(session('summary.channel')) &mdash; {{ session('summary.channel') }}@endif</dd>
            <dt>Duration:</dt>
            <dd>{{ session('summary.duration_days') }} days</dd>
            <dt>Codes Generated:</dt>
            <dd>{{ session('summary.quantity') }}</dd>
            <dt>Max Uses:</dt>
            <dd>{{ session('summary.max_uses') }}</dd>
            <dt>Valid:</dt>
            <dd>{{ session('summary.expires_at') ? 'Until ' . session('summary.expires_at') : 'Until manually deactivated' }}</dd>
        </dl>
    </div>

    @if(session('generated_codes'))
    <h3 style="margin-top: 1.5rem;">Generated Codes</h3>
    <div class="codes-list">
        @foreach(session('generated_codes') as $code)
        <div class="code-item">{{ $code }}</div>
        @endforeach
    </div>
    @endif
</div>
@endif
@endsection

@push('scripts')
<script>
function toggleChannel(type) {
    const group = document.getElementById('channel-group');
    group.style.display = type === 'vod_manager' ? '' : 'none';
    group.querySelector('select').required = type === 'vod_manager';
}
toggleChannel(document.getElementById('type-select').value);
</script>
@endpush
