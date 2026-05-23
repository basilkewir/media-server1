@extends('layouts.admin')
@section('title', 'Edit Output')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span>
    <a href="{{ route('admin.outputs.index') }}">Outputs</a> <span class="sep">/</span> {{ $output->name }}
@endsection

@section('content')
<div class="card animate-in" style="max-width:600px;">
    <div class="card-header"><div class="card-title">Edit Output Target</div></div>
    <form method="POST" action="{{ route('admin.outputs.update', $output) }}">
        @csrf @method('PUT')
        <div class="form-group form-full">
            <label class="label-required">Name</label>
            <input name="name" value="{{ old('name', $output->name) }}" required>
        </div>
        <div class="form-group form-full" style="margin-top:12px;">
            <label class="label-required">URL</label>
            <input name="url" value="{{ old('url', $output->url) }}" placeholder="rtmp://..." required>
        </div>
        <div class="form-grid" style="margin-top:12px;">
            <div class="form-group">
                <label class="label-required">Protocol</label>
                <select name="protocol" required>
                    @foreach(['rtmp','srt','hls','icecast','rtsp'] as $p)
                    <option value="{{ $p }}" {{ old('protocol', $output->protocol) === $p ? 'selected' : '' }}>{{ strtoupper($p) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Channel</label>
                <select name="channel_id" required>
                    @foreach(\App\Models\Channel::orderBy('name')->get() as $ch)
                    <option value="{{ $ch->id }}" {{ old('channel_id', $output->channel_id) == $ch->id ? 'selected' : '' }}>{{ $ch->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div style="margin-top:16px;display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('admin.outputs.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
