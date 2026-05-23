@extends('layouts.admin')
@section('title', 'Channel Graphics — ' . $channel->name)
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> &rsaquo;
    <a href="{{ route('admin.channels.index') }}">Channels</a> &rsaquo;
    <a href="{{ route('admin.channels.show', $channel) }}">{{ $channel->name }}</a> &rsaquo; Graphics
@endsection

@section('topbar-actions')
    <a href="{{ route('admin.channels.show', $channel) }}" class="btn btn-ghost btn-sm">Back to Channel</a>
@endsection

@section('content')

{{-- Logo Overlay --}}
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">🏷️ Channel Logo</div>
            <div class="card-subtitle">Overlay a logo on your live stream</div>
        </div>
        @if($channel->logo_path)
        <form method="POST" action="{{ route('admin.channels.graphics.logo', $channel) }}" style="display:inline;">
            @csrf @method('PUT')
            <input type="hidden" name="remove_logo" value="1">
            <button class="btn btn-danger btn-sm">Remove Logo</button>
        </form>
        @endif
    </div>
    <form method="POST" action="{{ route('admin.channels.graphics.logo', $channel) }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        @if($channel->logo_path)
        <div style="margin-bottom:1rem;">
            <img src="{{ asset('storage/' . $channel->logo_path) }}" style="max-height:80px;border-radius:6px;background:rgba(255,255,255,0.05);padding:0.5rem;">
            <span class="text-sm text-muted">{{ basename($channel->logo_path) }}</span>
        </div>
        @endif

        <div class="form-grid">
            <div class="form-group">
                <label>Logo Image (PNG recommended, max 5MB)</label>
                <input type="file" name="logo" accept="image/*" style="padding:0.4rem;">
            </div>
        </div>
        <div class="form-grid-4">
            <div class="form-group">
                <label>Position</label>
                <select name="logo_position" required>
                    @foreach(['top-left','top-right','bottom-left','bottom-right'] as $pos)
                        <option value="{{ $pos }}" {{ $channel->logo_position === $pos ? 'selected' : '' }}>{{ ucfirst(str_replace('-',' ',$pos)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Opacity (0-100)</label>
                <input type="number" name="logo_opacity" value="{{ old('logo_opacity', $channel->logo_opacity ?? 80) }}" min="0" max="100">
            </div>
            <div class="form-group">
                <label>Width (px)</label>
                <input type="number" name="logo_width" value="{{ old('logo_width', $channel->logo_width ?? 150) }}" min="20" max="800">
            </div>
            <div class="form-group">
                <label>Height (px, 0=auto)</label>
                <input type="number" name="logo_height" value="{{ old('logo_height', $channel->logo_height ?? 0) }}" min="0" max="800">
            </div>
        </div>
        <div class="mt-2">
            <button type="submit" class="btn btn-primary btn-sm">Save Logo Settings</button>
        </div>
    </form>
</div>

{{-- Watermark Overlay --}}
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">💧 Watermark</div>
            <div class="card-subtitle">Semi-transparent watermark overlay</div>
        </div>
        @if($channel->watermark_path)
        <form method="POST" action="{{ route('admin.channels.graphics.watermark', $channel) }}" style="display:inline;">
            @csrf @method('PUT')
            <input type="hidden" name="remove_watermark" value="1">
            <button class="btn btn-danger btn-sm">Remove</button>
        </form>
        @endif
    </div>
    <form method="POST" action="{{ route('admin.channels.graphics.watermark', $channel) }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        @if($channel->watermark_path)
        <div style="margin-bottom:1rem;">
            <img src="{{ asset('storage/' . $channel->watermark_path) }}" style="max-height:60px;border-radius:6px;background:rgba(255,255,255,0.05);padding:0.5rem;">
        </div>
        @endif

        <div class="form-grid">
            <div class="form-group">
                <label>Watermark Image (max 2MB)</label>
                <input type="file" name="watermark" accept="image/*" style="padding:0.4rem;">
            </div>
        </div>
        <div class="form-grid-4">
            <div class="form-group">
                <label>Position</label>
                <select name="watermark_position" required>
                    @foreach(['top-left','top-right','bottom-left','bottom-right'] as $pos)
                        <option value="{{ $pos }}" {{ $channel->watermark_position === $pos ? 'selected' : '' }}>{{ ucfirst(str_replace('-',' ',$pos)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Opacity (0-100)</label>
                <input type="number" name="watermark_opacity" value="{{ old('watermark_opacity', $channel->watermark_opacity ?? 40) }}" min="0" max="100">
            </div>
        </div>
        <div class="mt-2">
            <button type="submit" class="btn btn-primary btn-sm">Save Watermark</button>
        </div>
    </form>
</div>

{{-- Scrolling Ticker --}}
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">📜 Scrolling Ticker</div>
            <div class="card-subtitle">News ticker bar at top or bottom of stream</div>
        </div>
        <label class="toggle-label">
            <input type="checkbox" form="ticker-form" name="ticker_enabled" value="1" {{ $channel->ticker_enabled ? 'checked' : '' }}> Enabled
        </label>
    </div>
    <form id="ticker-form" method="POST" action="{{ route('admin.channels.graphics.ticker', $channel) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group form-full">
                <label>Ticker Text</label>
                <input name="ticker_text" value="{{ old('ticker_text', $channel->ticker_text) }}" maxlength="500" placeholder="Breaking news...">
            </div>
        </div>
        <div class="form-grid-4">
            <div class="form-group">
                <label>Position</label>
                <select name="ticker_position" required>
                    <option value="top" {{ $channel->ticker_position === 'top' ? 'selected' : '' }}>Top</option>
                    <option value="bottom" {{ $channel->ticker_position === 'bottom' ? 'selected' : '' }}>Bottom</option>
                </select>
            </div>
            <div class="form-group">
                <label>Font Size</label>
                <input type="number" name="ticker_font_size" value="{{ old('ticker_font_size', $channel->ticker_font_size ?? 24) }}" min="12" max="100">
            </div>
            <div class="form-group">
                <label>Speed (ms/px)</label>
                <input type="number" name="ticker_speed_ms" value="{{ old('ticker_speed_ms', $channel->ticker_speed_ms ?? 120) }}" min="50" max="1000">
            </div>
        </div>
        <div class="form-grid-3">
            <div class="form-group">
                <label>Text Color</label>
                <input type="color" name="ticker_text_color" value="{{ old('ticker_text_color', $channel->ticker_text_color ?? '#ffffff') }}">
            </div>
            <div class="form-group">
                <label>Background Color</label>
                <input type="color" name="ticker_bg_color" value="{{ old('ticker_bg_color', $channel->ticker_bg_color ?? '#000000') }}">
            </div>
        </div>
        <div class="mt-2">
            <button type="submit" class="btn btn-primary btn-sm">Save Ticker</button>
        </div>
    </form>
</div>
@endsection
