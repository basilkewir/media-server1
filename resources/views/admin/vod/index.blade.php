@extends('layouts.admin')

@section('title', 'VOD Library — ' . $channel->name)

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
    <div>
        <h1 style="margin:0;">VOD Library</h1>
        <p style="margin:0.25rem 0 0; color:var(--muted);">{{ $channel->name }} &mdash; <code>{{ $channel->slug }}</code></p>
    </div>
    <div style="display:flex; gap:0.5rem;">
        <a href="{{ route('admin.channels.edit', $channel) }}" class="btn btn-ghost btn-sm">← Channel</a>
    </div>
</div>

{{-- Upload --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">Upload Video</span>
    </div>
    <form method="POST" action="{{ route('admin.vod.store', $channel) }}" enctype="multipart/form-data">
        @csrf
        <div style="display:grid; grid-template-columns:1fr 1fr auto; gap:1rem; align-items:end;">
            <div class="form-group">
                <label>Video File <span style="color:var(--danger)">*</span></label>
                <input type="file" name="file" accept="video/*,.ts,.m2ts" required>
                <span class="hint">MP4, MKV, MOV, AVI, TS, FLV, WebM — max 10 GB</span>
            </div>
            <div class="form-group">
                <label>Title <span class="hint">(optional)</span></label>
                <input type="text" name="title" placeholder="Leave blank to use filename">
            </div>
            <button type="submit" class="btn btn-primary">Upload</button>
        </div>
    </form>
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
            <p>No videos uploaded yet.</p>
        </div>
    @else
    <table>
        <thead>
            <tr>
                <th style="width:30px;"></th>
                <th>Title</th>
                <th>File</th>
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
                <td><span class="mono text-muted">{{ $file->original_name }}</span></td>
                <td>{{ $file->formattedSize() }}</td>
                <td>{{ $file->formattedDuration() }}</td>
                <td>
                    <form method="POST" action="{{ route('admin.vod.destroy', [$channel, $file]) }}"
                          onsubmit="return confirm('Delete this video?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
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
        <span class="card-title" style="color:#4ade80;">✓ Auto-generated Playlist</span>
    </div>
    <p class="text-sm text-muted">This playlist is automatically used as the VOD fallback when the live stream goes offline.</p>
    <code class="mono" style="display:block; margin-top:0.5rem; word-break:break-all;">{{ $channel->vod_playlist_url }}</code>
</div>
@endif
@endsection

@push('scripts')
<script>
// Simple drag-to-reorder
const list = document.getElementById('sortable-list');
if (list) {
    let dragging = null;
    list.querySelectorAll('tr').forEach(row => {
        row.draggable = true;
        row.addEventListener('dragstart', () => { dragging = row; row.style.opacity = '0.5'; });
        row.addEventListener('dragend',   () => { dragging = null; row.style.opacity = '1'; updateOrder(); });
        row.addEventListener('dragover',  e => { e.preventDefault(); const after = getAfter(list, e.clientY);
            after ? list.insertBefore(dragging, after) : list.appendChild(dragging); });
    });
}

function getAfter(container, y) {
    return [...container.querySelectorAll('tr:not([style*="opacity: 0.5"])')]
        .reduce((closest, child) => {
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
