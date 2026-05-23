@extends('layouts.admin')

@section('title', 'VOD Library — ' . $channel->name)

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
    <div>
        <h1 style="margin:0;">VOD Library</h1>
        <p style="margin:0.25rem 0 0; color:var(--muted);">{{ $channel->name }} &mdash; <code>{{ $channel->slug }}</code></p>
    </div>
    <a href="{{ route('admin.channels.edit', $channel) }}" class="btn btn-ghost btn-sm">← Channel</a>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:1rem;">
    <ul style="margin:0; padding-left:1.2rem;">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:1rem;">{{ session('success') }}</div>
@endif

{{-- Storage Quota --}}
@php
    $quotaBytes = 2 * 1024 * 1024 * 1024;
    $pct        = $quotaBytes > 0 ? min(100, round($usedBytes / $quotaBytes * 100)) : 0;
    $usedFmt    = $usedBytes >= 1073741824
        ? round($usedBytes / 1073741824, 2) . ' GB'
        : round($usedBytes / 1048576, 2) . ' MB';
    $barColor   = $pct >= 90 ? 'var(--danger)' : ($pct >= 70 ? 'orange' : 'var(--primary)');
@endphp
<div class="card" style="margin-bottom:1rem;">
    <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:0.4rem;">
        <span>Storage used (uploaded files)</span>
        <span>{{ $usedFmt }} / 2 GB &mdash; {{ $pct }}%</span>
    </div>
    <div style="background:var(--border); border-radius:4px; height:8px; overflow:hidden;">
        <div style="width:{{ $pct }}%; background:{{ $barColor }}; height:100%; transition:width 0.3s;"></div>
    </div>
</div>

{{-- Add Video Tabs --}}
<div class="card">
    <div class="card-header" style="gap:0;">
        <button class="btn btn-sm" id="tab-upload-btn" onclick="switchTab('upload')" style="border-radius:4px 0 0 4px;">📁 Upload File</button>
        <button class="btn btn-ghost btn-sm" id="tab-youtube-btn" onclick="switchTab('youtube')" style="border-radius:0 4px 4px 0;">▶ YouTube URL</button>
    </div>

    {{-- Upload form --}}
    <div id="tab-upload">
        <form method="POST" action="{{ route('admin.vod.store', $channel) }}" enctype="multipart/form-data">
            @csrf
            <div style="display:grid; grid-template-columns:1fr 1fr auto; gap:1rem; align-items:end;">
                <div class="form-group">
                    <label>Video File <span style="color:var(--danger)">*</span></label>
                    <input type="file" name="file" accept="video/*,.ts,.m2ts" required>
                    <span class="hint">MP4, MKV, MOV, AVI, TS, FLV, WebM — max 2 GB</span>
                </div>
                <div class="form-group">
                    <label>Title <span class="hint">(optional)</span></label>
                    <input type="text" name="title" placeholder="Leave blank to use filename">
                </div>
                <button type="submit" class="btn btn-primary">Upload</button>
            </div>
        </form>
    </div>

    {{-- YouTube form --}}
    <div id="tab-youtube" style="display:none;">
        <form method="POST" action="{{ route('admin.vod.store-youtube', $channel) }}">
            @csrf
            <div style="display:grid; grid-template-columns:1fr 1fr auto; gap:1rem; align-items:end;">
                <div class="form-group">
                    <label>YouTube URL <span style="color:var(--danger)">*</span></label>
                    <input type="url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..." required
                           value="{{ old('youtube_url') }}">
                    <span class="hint">Paste a YouTube video link — no download needed</span>
                </div>
                <div class="form-group">
                    <label>Title <span class="hint">(optional)</span></label>
                    <input type="text" name="title" placeholder="Leave blank to use video ID" value="{{ old('title') }}">
                </div>
                <button type="submit" class="btn btn-primary">Add</button>
            </div>
        </form>
        <p class="hint" style="margin-top:0.5rem; color:var(--muted);">
            ⚠ YouTube entries are streamed via <code>yt-dlp</code> during VOD fallback. Ensure <code>yt-dlp</code> is installed on the server (<code>pip install yt-dlp</code>).
        </p>
    </div>
</div>

{{-- File list --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">Videos ({{ $files->count() }})</span>
        @if($files->count() > 1)
        <form method="POST" action="{{ route('admin.vod.reorder', $channel) }}" id="reorder-form">
            @csrf
            <div id="reorder-inputs"></div>
            <button type="submit" class="btn btn-ghost btn-sm">Save Order</button>
        </form>
        @endif
    </div>

    @if($files->isEmpty())
        <div class="empty">
            <div class="empty-icon">🎬</div>
            <p>No videos added yet.</p>
        </div>
    @else
    <table>
        <thead>
            <tr>
                <th style="width:30px;"></th>
                <th>Title</th>
                <th>Source</th>
                <th>Size</th>
                <th>Duration</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="sortable-list">
            @foreach($files as $file)
            <tr data-id="{{ $file->id }}" style="cursor:grab;">
                <td style="color:var(--muted);">⠿</td>
                <td><strong>{{ $file->title }}</strong></td>
                <td>
                    @if($file->isYoutube())
                        <span style="background:rgba(255,0,0,0.15); color:#f87171; padding:2px 8px; border-radius:4px; font-size:0.78rem;">▶ YouTube</span>
                    @else
                        <span style="background:rgba(99,102,241,0.15); color:#818cf8; padding:2px 8px; border-radius:4px; font-size:0.78rem;">📁 Upload</span>
                    @endif
                </td>
                <td>{{ $file->isYoutube() ? '—' : $file->formattedSize() }}</td>
                <td>{{ $file->formattedDuration() }}</td>
                <td>
                    <form method="POST" action="{{ route('admin.vod.destroy', [$channel, $file]) }}"
                          onsubmit="return confirm('Remove this video?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

{{-- Playlist info --}}
@if($channel->vod_playlist_url)
<div class="card" style="border-color: rgba(34,197,94,0.3);">
    <div class="card-header">
        <span class="card-title" style="color:#4ade80;">✓ Active VOD Playlist</span>
    </div>
    <p class="text-sm text-muted">Used automatically as fallback when the live push goes offline.</p>
    <code class="mono" style="display:block; margin-top:0.5rem; word-break:break-all;">{{ $channel->vod_playlist_url }}</code>
</div>
@endif
@endsection

@push('scripts')
<script>
function switchTab(tab) {
    document.getElementById('tab-upload').style.display  = tab === 'upload'  ? '' : 'none';
    document.getElementById('tab-youtube').style.display = tab === 'youtube' ? '' : 'none';
    document.getElementById('tab-upload-btn').className  = 'btn btn-sm'       + (tab === 'upload'  ? ' btn-primary' : ' btn-ghost');
    document.getElementById('tab-youtube-btn').className = 'btn btn-sm'       + (tab === 'youtube' ? ' btn-primary' : ' btn-ghost');
    document.getElementById('tab-upload-btn').style.borderRadius  = '4px 0 0 4px';
    document.getElementById('tab-youtube-btn').style.borderRadius = '0 4px 4px 0';
}

// Auto-open YouTube tab if there were YouTube validation errors
@if($errors->has('youtube_url'))
switchTab('youtube');
@else
switchTab('upload');
@endif

// Drag-to-reorder
const list = document.getElementById('sortable-list');
if (list) {
    let dragging = null;
    list.querySelectorAll('tr').forEach(row => {
        row.draggable = true;
        row.addEventListener('dragstart', () => { dragging = row; row.style.opacity = '0.5'; });
        row.addEventListener('dragend',   () => { dragging = null; row.style.opacity = '1'; updateOrder(); });
        row.addEventListener('dragover',  e => {
            e.preventDefault();
            const after = getAfter(list, e.clientY);
            after ? list.insertBefore(dragging, after) : list.appendChild(dragging);
        });
    });
}

function getAfter(container, y) {
    return [...container.querySelectorAll('tr:not([style*="opacity: 0.5"])')].reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        return offset < 0 && offset > closest.offset ? { offset, element: child } : closest;
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

function updateOrder() {
    const inputs = document.getElementById('reorder-inputs');
    if (!inputs) return;
    inputs.innerHTML = '';
    document.querySelectorAll('#sortable-list tr').forEach((row, i) => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'order[]'; inp.value = row.dataset.id;
        inputs.appendChild(inp);
    });
}
updateOrder();
</script>
@endpush
