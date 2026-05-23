@extends('layouts.admin')

@section('title', 'VOD Library — ' . $channel->name)

@section('topbar-actions')
    <a href="{{ route('admin.vod-schedules.index', $channel) }}" class="btn btn-ghost btn-sm">📅 Schedule</a>
    <a href="{{ route('admin.channels.graphics', $channel) }}" class="btn btn-ghost btn-sm">🎨 Graphics</a>
    <a href="{{ route('admin.channels.edit', $channel) }}" class="btn btn-ghost btn-sm">← Channel</a>
@endsection

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
    <div>
        <h1 style="margin:0;">VOD Library</h1>
        <p style="margin:0.25rem 0 0; color:var(--muted);">{{ $channel->name }} &mdash; <code>{{ $channel->slug }}</code></p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:1rem;">{{ session('success') }}</div>
@endif

{{-- Storage Quota --}}
@php
    $quotaInfo = $quotaInfo ?? ['quota_bytes' => 2147483648, 'used_bytes' => $usedBytes, 'remaining_bytes' => max(0, 2147483648 - $usedBytes), 'quota_pct' => 0, 'quota_formatted' => '2 GB', 'used_formatted' => '0 MB', 'remaining_formatted' => '2 GB'];
    if (($quotaInfo['quota_bytes'] ?? 0) > 0) {
        $pct = $quotaInfo['quota_pct'] ?? min(100, round($usedBytes / $quotaInfo['quota_bytes'] * 100));
        $barColor = $pct >= 90 ? 'var(--danger)' : ($pct >= 70 ? 'orange' : 'var(--primary)');
    } else {
        $pct = 0; $barColor = 'var(--primary)';
    }
@endphp
<div class="card" style="margin-bottom:1rem;">
    <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:0.4rem;">
        <span>Storage used (uploaded files)</span>
        <span>{{ $quotaInfo['used_formatted'] ?? '0 MB' }} / {{ $quotaInfo['quota_formatted'] ?? '2 GB' }} &mdash; {{ $pct }}%</span>
    </div>
    <div style="background:var(--border); border-radius:4px; height:8px; overflow:hidden;">
        <div style="width:{{ $pct }}%; background:{{ $barColor }}; height:100%; transition:width 0.3s;"></div>
    </div>
    @if(($quotaInfo['remaining_bytes'] ?? 0) <= 0)
    <div style="margin-top:0.5rem; font-size:0.8rem; color:var(--danger);">
        ⚠ Storage full. Upgrade your plan or delete files to upload more.
    </div>
    @endif
</div>

{{-- Add Video Tabs --}}
<div class="card">
    <div class="card-header" style="gap:0;">
        <button class="btn btn-sm" id="tab-upload-btn" onclick="switchTab('upload')" style="border-radius:4px 0 0 4px;">📁 Upload File</button>
        <button class="btn btn-ghost btn-sm" id="tab-youtube-btn" onclick="switchTab('youtube')" style="border-radius:0 4px 4px 0;">▶ YouTube URL</button>
    </div>

    {{-- Upload form with drag-drop --}}
    <div id="tab-upload">
        <form method="POST" action="{{ route('admin.vod.store', $channel) }}" enctype="multipart/form-data" id="upload-form">
            @csrf
            <div id="drop-zone"
                 style="border:2px dashed var(--border); border-radius:10px; padding:2rem 1.5rem; text-align:center; cursor:pointer; transition:all 0.2s;"
                 ondrop="handleDrop(event)" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)"
                 onclick="document.getElementById('file-input').click()">
                <div style="font-size:2rem; margin-bottom:0.5rem;">📁</div>
                <p style="font-weight:600;">Drop video file here or click to browse</p>
                <p class="hint">MP4, MKV, MOV, AVI, TS, FLV, WebM — max {{ $quotaInfo['max_upload_bytes'] ?? 524288000 > 104857600 ? round(($quotaInfo['max_upload_bytes'] ?? 524288000) / 1048576) . ' MB' : round(($quotaInfo['max_upload_bytes'] ?? 524288000) / 1024) . ' KB' }}</p>
                <p id="file-name" style="color:#4ade80;font-weight:600;margin-top:0.5rem;display:none;"></p>
                <input type="file" name="file" id="file-input" accept="video/*,.ts,.m2ts" required style="display:none;" onchange="showFileName(this)">
            </div>

            {{-- Progress bar --}}
            <div id="progress-container" style="display:none; margin-top:1rem;">
                <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:0.3rem;">
                    <span id="progress-label">Uploading...</span>
                    <span id="progress-pct">0%</span>
                </div>
                <div style="background:var(--border); border-radius:10px; height:10px; overflow:hidden;">
                    <div id="progress-bar" style="width:0%; background:var(--primary); height:100%; border-radius:10px; transition:width 0.2s;"></div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr auto; gap:1rem; align-items:end; margin-top:1rem;">
                <div class="form-group">
                    <label>Title <span class="hint">(optional)</span></label>
                    <input type="text" name="title" id="upload-title" placeholder="Leave blank to use filename">
                </div>
                <button type="submit" class="btn btn-primary" id="upload-btn">Upload</button>
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
        <div class="actions">
            <a href="{{ route('admin.vod-schedules.index', $channel) }}" class="btn btn-ghost btn-sm">📅 Schedule</a>
            @if($files->count() > 1)
            <form method="POST" action="{{ route('admin.vod.reorder', $channel) }}" id="reorder-form">
                @csrf
                <div id="reorder-inputs"></div>
                <button type="submit" class="btn btn-ghost btn-sm">Save Order</button>
            </form>
            @endif
        </div>
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
                    <div class="actions">
                        <form method="POST" action="{{ route('admin.vod.destroy', [$channel, $file]) }}"
                              onsubmit="return confirm('Remove this video?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                        </form>
                    </div>
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

// Auto-open correct tab
@if($errors->has('youtube_url') || $errors->has('file') || $errors->any())
    @if($errors->has('youtube_url'))
        switchTab('youtube');
    @else
        switchTab('upload');
    @endif
@else
    switchTab('upload');
@endif

// Drag-drop upload
function showFileName(input) {
    if (input.files.length > 0) {
        const f = input.files[0];
        const mb = (f.size / 1048576).toFixed(1);
        document.getElementById('file-name').textContent = f.name + ' (' + mb + ' MB)';
        document.getElementById('file-name').style.display = '';
    }
}

function handleDragOver(e) {
    e.preventDefault(); e.stopPropagation();
    document.getElementById('drop-zone').style.borderColor = 'var(--primary)';
    document.getElementById('drop-zone').style.background = 'rgba(37,99,235,0.05)';
}

function handleDragLeave(e) {
    e.preventDefault(); e.stopPropagation();
    document.getElementById('drop-zone').style.borderColor = 'var(--border)';
    document.getElementById('drop-zone').style.background = '';
}

function handleDrop(e) {
    e.preventDefault(); e.stopPropagation();
    document.getElementById('drop-zone').style.borderColor = 'var(--border)';
    document.getElementById('drop-zone').style.background = '';
    const dt = e.dataTransfer;
    if (dt.files.length > 0) {
        document.getElementById('file-input').files = dt.files;
        showFileName(document.getElementById('file-input'));
    }
}

// Upload progress simulation
document.getElementById('upload-form').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('file-input');
    if (!fileInput.files.length) return;
    const file = fileInput.files[0];
    const maxSize = {{ $quotaInfo['max_upload_bytes'] ?? 524288000 }};
    if (file.size > maxSize) {
        e.preventDefault();
        alert('File size exceeds the maximum allowed upload size.');
        return false;
    }
    document.getElementById('progress-container').style.display = '';
    document.getElementById('upload-btn').disabled = true;
    document.getElementById('upload-btn').textContent = 'Uploading...';
    simulateProgress(file.size);
});

function simulateProgress(fileSize) {
    let loaded = 0;
    const total = fileSize;
    const interval = setInterval(() => {
        loaded += Math.min(total * 0.1, total - loaded);
        if (loaded >= total) { loaded = total; clearInterval(interval); }
        const pct = Math.round((loaded / total) * 100);
        document.getElementById('progress-bar').style.width = pct + '%';
        document.getElementById('progress-pct').textContent = pct + '%';
    }, 200);
}

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
            if (after && dragging) {
                after === dragging.nextElementSibling ? list.insertBefore(dragging, after) : list.insertBefore(dragging, after);
            } else if (dragging) {
                list.appendChild(dragging);
            }
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
