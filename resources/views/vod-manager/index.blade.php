<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOD Manager — {{ $channel->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --surface-0:#09090b; --surface-1:#121216; --surface-2:#18181d; --surface-3:#1e1e24; --surface-4:#25252c;
            --text-primary:#f4f4f6; --text-secondary:#a1a1aa; --text-tertiary:#71717a;
            --brand:#6366f1; --brand-light:#818cf8; --brand-dim:#4f46e5; --brand-glow:rgba(99,102,241,0.25);
            --success:#22c55e; --success-dim:#166534;
            --danger:#ef4444; --danger-dim:#7f1d1d;
            --warning:#f59e0b; --warning-dim:#78350f;
            --border:#27272a; --border-light:#3f3f46;
            --radius-sm:6px; --radius:10px; --radius-lg:14px;
            --font-sans:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
            --font-mono:'JetBrains Mono',monospace;
            --ease:cubic-bezier(0.4,0,0.2,1);
        }
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:var(--font-sans);background:var(--surface-0);color:var(--text-primary);-webkit-font-smoothing:antialiased}
        ::selection{background:var(--brand);color:white}
        ::-webkit-scrollbar{width:6px}
        ::-webkit-scrollbar-thumb{background:var(--surface-4);border-radius:3px}

        .topbar{background:var(--surface-1);border-bottom:1px solid var(--border);padding:16px 24px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}
        .topbar-title{font-size:18px;font-weight:700;display:flex;align-items:center;gap:8px}
        .topbar-title span{font-size:13px;color:var(--text-tertiary);font-weight:400}
        .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:var(--radius-sm);font-size:13px;font-weight:600;border:1px solid transparent;cursor:pointer;transition:all .15s var(--ease);text-decoration:none;font-family:inherit}
        .btn-primary{background:var(--brand);color:white}
        .btn-primary:hover{background:var(--brand-dim)}
        .btn-ghost{background:transparent;color:var(--text-secondary);border-color:var(--border)}
        .btn-ghost:hover{background:var(--surface-2);color:var(--text-primary)}
        .btn-danger{background:var(--danger);color:white}
        .btn-danger:hover{background:#dc2626}
        .btn-sm{padding:5px 10px;font-size:12px}
        .btn-xs{padding:3px 8px;font-size:11px}

        .wrap{max-width:900px;margin:0 auto;padding:24px}

        .alert{padding:12px 16px;border-radius:var(--radius);font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:8px;border:1px solid}
        .alert-success{background:var(--success-dim);border-color:rgba(34,197,94,0.2);color:#86efac}
        .alert-danger{background:var(--danger-dim);border-color:rgba(239,68,68,0.2);color:#fca5a5}

        .card{background:var(--surface-1);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;margin-bottom:16px}
        .card-title{font-size:15px;font-weight:700;margin-bottom:14px}

        .badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:600}
        .badge-success{background:var(--success-dim);color:var(--success)}
        .badge-neutral{background:var(--surface-3);color:var(--text-secondary)}
        .badge-brand{background:rgba(99,102,241,0.15);color:var(--brand-light)}

        .progress-bar{height:8px;background:var(--surface-3);border-radius:99px;overflow:hidden}
        .progress-fill{height:100%;border-radius:99px;background:var(--brand);transition:width .5s var(--ease)}
        .progress-fill.danger{background:var(--danger)}
        .progress-fill.warning{background:var(--warning)}

        input,select,textarea{background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:9px 12px;color:var(--text-primary);font-size:14px;font-family:inherit;transition:all .15s var(--ease);outline:none;width:100%}
        input:focus,select:focus{border-color:var(--brand);box-shadow:0 0 0 3px var(--brand-glow)}
        input::placeholder{color:var(--text-tertiary)}
        label{font-size:13px;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:4px}
        .hint{font-size:12px;color:var(--text-tertiary);margin-top:3px}

        .tab-btn{padding:8px 16px;font-size:13px;font-weight:600;border:none;background:transparent;color:var(--text-secondary);cursor:pointer;border-bottom:2px solid transparent;transition:all .15s var(--ease)}
        .tab-btn.active{color:var(--brand-light);border-bottom-color:var(--brand)}

        table{width:100%;border-collapse:collapse;font-size:13px}
        th{text-align:left;padding:8px 10px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-tertiary);border-bottom:2px solid var(--border)}
        td{padding:10px;border-bottom:1px solid var(--border)}
        tr:hover td{background:var(--surface-2)}
        tr:last-child td{border-bottom:none}

        .form-row{display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap}
        .drop-zone{border:2px dashed var(--border);border-radius:var(--radius);padding:32px 20px;text-align:center;cursor:pointer;transition:all .15s var(--ease)}
        .drop-zone:hover{border-color:var(--brand);background:rgba(99,102,241,0.03)}
    </style>
</head>
<body>
<div class="topbar">
    <div class="topbar-title">
        VOD Manager <span>— {{ $channel->name }}</span>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <a href="{{ route('vod-manager.logout', $channel) }}" class="btn btn-ghost btn-sm"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            Logout
        </a>
        <form id="logout-form" method="POST" action="{{ route('vod-manager.logout', $channel) }}" style="display:none;">@csrf</form>
    </div>
</div>

<div class="wrap">
    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger">@foreach($errors->all() as $e){{ $e }}<br>@endforeach</div>
    @endif

    {{-- Quota Bar --}}
    @php
        $quotaBytes = 2147483648;
        $pct = $quotaBytes > 0 ? min(100, round($usedBytes / $quotaBytes * 100)) : 0;
        $usedFmt = $usedBytes >= 1073741824 ? round($usedBytes/1073741824,2).' GB' : round($usedBytes/1048576,2).' MB';
        $barColor = $pct >= 90 ? 'danger' : ($pct >= 70 ? 'warning' : '');
        $remainFmt = max(0, $quotaBytes - $usedBytes) >= 1073741824 ? round(max(0,$quotaBytes-$usedBytes)/1073741824,2).' GB' : round(max(0,$quotaBytes-$usedBytes)/1048576,2).' MB';
    @endphp
    <div class="card">
        <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--text-secondary);margin-bottom:8px;">
            <span>Storage used</span>
            <span>{{ $usedFmt }} / 2 GB — {{ $pct }}%</span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill {{ $barColor }}" style="width:{{ $pct }}%;"></div>
        </div>
        @if($pct >= 90)
        <div style="margin-top:8px;font-size:12px;color:var(--danger);">Quota nearly full ({{ $remainFmt }} remaining).</div>
        @endif
    </div>

    {{-- Upload Tabs --}}
    <div class="card">
        <div style="display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:16px;">
            <button class="tab-btn active" id="tab-upload-btn" onclick="switchTab('upload')">Upload File</button>
            <button class="tab-btn" id="tab-youtube-btn" onclick="switchTab('youtube')">YouTube URL</button>
        </div>

        <div id="tab-upload">
            <form method="POST" action="{{ route('vod-manager.store', $channel) }}" enctype="multipart/form-data">
                @csrf
                <div class="drop-zone" onclick="document.getElementById('file-input').click()" id="dropZone"
                     ondragover="this.classList.add('dragover');event.preventDefault()"
                     ondragleave="this.classList.remove('dragover')"
                     ondrop="handleDrop(event,this)">
                    <div style="font-size:32px;margin-bottom:8px;">📁</div>
                    <div style="font-weight:600;">Drop video or click to browse</div>
                    <div class="hint" style="margin-top:4px;">MP4, MKV, MOV, AVI, TS, FLV, WebM — max 2 GB</div>
                    <div id="file-name" style="color:var(--success);font-weight:600;margin-top:8px;display:none;"></div>
                </div>
                <input type="file" name="file" id="file-input" accept="video/*,.ts,.m2ts" required style="display:none;" onchange="showFileName(this)">
                <div class="form-row" style="margin-top:12px;">
                    <div style="flex:1;">
                        <label>Title (optional)</label>
                        <input name="title" placeholder="Leave blank to use filename">
                    </div>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>

        <div id="tab-youtube" style="display:none;">
            <form method="POST" action="{{ route('vod-manager.store-youtube', $channel) }}">
                @csrf
                <div class="form-row">
                    <div style="flex:1;">
                        <label>YouTube URL</label>
                        <input type="url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..." required>
                    </div>
                    <div style="flex:1;">
                        <label>Title (optional)</label>
                        <input name="title" placeholder="Leave blank to use video ID">
                    </div>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
            <p class="hint" style="margin-top:8px;">YouTube videos are streamed via yt-dlp during VOD fallback.</p>
        </div>
    </div>

    {{-- Videos Table --}}
    <div class="card">
        <div class="card-title" style="display:flex;justify-content:space-between;align-items:center;">
            Videos ({{ $files->count() }})
            @if($files->count() > 1)
            <form method="POST" action="{{ route('vod-manager.reorder', $channel) }}" id="reorder-form">
                @csrf
                <div id="reorder-inputs"></div>
                <button type="submit" class="btn btn-ghost btn-sm">Save Order</button>
            </form>
            @endif
        </div>

        @if($files->isEmpty())
        <div style="text-align:center;padding:36px;color:var(--text-tertiary);">
            <div style="font-size:36px;margin-bottom:8px;">🎬</div>
            <p>No videos added yet.</p>
        </div>
        @else
        <table>
            <thead>
                <tr><th></th><th>Title</th><th>Source</th><th>Size</th><th>Duration</th><th></th></tr>
            </thead>
            <tbody id="sortable-list">
                @foreach($files as $file)
                <tr data-id="{{ $file->id }}" style="cursor:grab;">
                    <td style="color:var(--text-tertiary);">⠿</td>
                    <td><strong>{{ $file->title }}</strong></td>
                    <td>
                        @if($file->isYoutube())
                            <span class="badge" style="background:rgba(239,68,68,0.15);color:#f87171;">YouTube</span>
                        @else
                            <span class="badge badge-brand">Upload</span>
                        @endif
                    </td>
                    <td>{{ $file->isYoutube() ? '—' : $file->formattedSize() }}</td>
                    <td>{{ $file->formattedDuration() }}</td>
                    <td>
                        <form method="POST" action="{{ route('vod-manager.destroy', [$channel, $file]) }}" onsubmit="return confirm('Remove?')" style="display:inline;">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-xs">Remove</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    @if($channel->vod_playlist_url)
    <div class="card" style="border-color:rgba(34,197,94,0.3);">
        <div class="card-title" style="color:var(--success);">Active VOD Playlist</div>
        <p style="font-size:13px;color:var(--text-tertiary);">Used as fallback when live push goes offline.</p>
        <code style="font-family:var(--font-mono);font-size:12px;color:var(--text-secondary);word-break:break-all;display:block;margin-top:8px;">{{ $channel->vod_playlist_url }}</code>
    </div>
    @endif
</div>

<script>
function switchTab(tab) {
    document.getElementById('tab-upload').style.display = tab === 'upload' ? '' : 'none';
    document.getElementById('tab-youtube').style.display = tab === 'youtube' ? '' : 'none';
    document.getElementById('tab-upload-btn').className = 'tab-btn' + (tab === 'upload' ? ' active' : '');
    document.getElementById('tab-youtube-btn').className = 'tab-btn' + (tab === 'youtube' ? ' active' : '');
}
@if($errors->has('youtube_url')) switchTab('youtube'); @else switchTab('upload'); @endif

function showFileName(input) {
    if (input.files.length > 0) {
        const f = input.files[0];
        document.getElementById('file-name').textContent = f.name + ' (' + (f.size/1048576).toFixed(1) + ' MB)';
        document.getElementById('file-name').style.display = '';
    }
}
function handleDrop(e, zone) {
    e.preventDefault(); zone.classList.remove('dragover');
    if (e.dataTransfer.files.length > 0) {
        document.getElementById('file-input').files = e.dataTransfer.files;
        showFileName(document.getElementById('file-input'));
    }
}

// Drag-to-reorder
const list = document.getElementById('sortable-list');
if (list) {
    let dragging = null;
    list.querySelectorAll('tr').forEach(row => {
        row.draggable = true;
        row.addEventListener('dragstart', () => { dragging = row; row.style.opacity = '0.5'; });
        row.addEventListener('dragend', () => { dragging = null; row.style.opacity = '1'; updateOrder(); });
        row.addEventListener('dragover', e => {
            e.preventDefault();
            const after = getAfter(list, e.clientY);
            after && after !== dragging.nextElementSibling ? list.insertBefore(dragging, after) : list.appendChild(dragging);
        });
    });
}
function getAfter(container, y) {
    return [...container.querySelectorAll('tr:not([style*="opacity: 0.5"])')].reduce((closest, child) => {
        const box = child.getBoundingClientRect(), offset = y - box.top - box.height / 2;
        return offset < 0 && offset > closest.offset ? {offset, element: child} : closest;
    }, {offset: Number.NEGATIVE_INFINITY}).element;
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
</body>
</html>
