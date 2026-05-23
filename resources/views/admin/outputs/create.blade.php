@extends('layouts.admin')
@section('title', 'New Output Target')
@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Dashboard</a> <span class="sep">/</span>
    <a href="{{ route('admin.outputs.index') }}">Outputs</a> <span class="sep">/</span> New
@endsection

@section('content')
<div class="card animate-in" style="max-width:600px;">
    <div class="card-header"><div class="card-title">Create Output Target</div></div>
    <form method="POST" action="{{ route('admin.outputs.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group form-full">
                <label class="label-required">Name</label>
                <input name="name" value="{{ old('name') }}" placeholder="YouTube Live, Twitch, etc." required>
            </div>
        </div>
        <div class="form-grid">
            <div class="form-group form-full">
                <label class="label-required">URL</label>
                <input name="url" value="{{ old('url') }}" placeholder="rtmp://a.rtmp.youtube.com/live2/..." required>
            </div>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label class="label-required">Protocol</label>
                <select name="protocol" required>
                    <option value="rtmp">RTMP</option>
                    <option value="srt">SRT</option>
                    <option value="hls">HLS Push</option>
                    <option value="icecast">Icecast</option>
                    <option value="rtsp">RTSP</option>
                </select>
            </div>
            <div class="form-group">
                <label>Channel</label>
                <select name="channel_id" required>
                    @foreach(\App\Models\Channel::orderBy('name')->get() as $ch)
                        <option value="{{ $ch->id }}">{{ $ch->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:20px;">
            <button type="submit" class="btn btn-primary">Create Output</button>
            <a href="{{ route('admin.outputs.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
