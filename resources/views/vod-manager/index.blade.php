<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOD Manager — {{ $channel->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #f8fafc; min-height: 100vh; }
        .topbar { background: #1e293b; border-bottom: 1px solid #334155; padding: 0.9rem 1.5rem; display: flex; justify-content: space-between; align-items: center; }
        .topbar h1 { font-size: 1.1rem; }
        .topbar .sub { color: #94a3b8; font-size: 0.8rem; }
        .logout { font-size: 0.8rem; color: #94a3b8; text-decoration: none; padding: 0.4rem 0.8rem; border: 1px solid #334155; border-radius: 6px; }
        .logout:hover { color: #f87171; border-color: #f87171; }
        .wrap { max-width: 860px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 10px; padding: 1.25rem; margin-bottom: 1.25rem; }
        .card-title { font-weight: 600; margin-bottom: 1rem; }
        .alert-success { color: #4ade80; background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); border-radius: 6px; padding: 0.6rem 0.9rem; margin-bottom: 1rem; font-size: 0.875rem; }
        .alert-error   { color: #f87171; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); border-radius: 6px; padding: 0.6rem 0.9rem; margin-bottom: 1rem; font-size: 0.875rem; }
        /* Quota */
        .quota-row { display: flex; justify-content: space-between; font-size: 0.82rem; color: #94a3b8; margin-bottom: 0.4rem; }
        .bar-bg { background: #334155; border-radius: 4px; height: 8px; overflow: hidden; }
        .bar-fill { height: 100%; border-radius: 4px; transition: width 0.3s; }
        /* Tabs */
        .tabs { display: flex; gap: 0; margin-bottom: 1rem; }
        .tab-btn { padding: 0.5rem 1.1rem; border: 1px solid #334155; background: transparent; color: #94a3b8; cursor: pointer; font-size: 0.875rem; }
        .tab-btn:first-child { border-radius: 6px 0 0 6px; }
        .tab-btn:last-child  { border-radius: 0 6px 6px 0; border-left: none; }
        .tab-btn.active { background: #3b82f6; border-color: #3b82f6; color: #fff; }
        /* Form */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr auto; gap: 0.75rem; align-items: end; }
        label { display: block; font-size: 0.8rem; color: #94a3b8; margin-bottom: 0.3rem; }
        input[type=text], input[type=url], input[type=file] {
            width: 100%; padding: 0.6rem 0.8rem; border: 1px solid #334155;
            border-radius: 6px; background: #0f172a; color: #fff; font-size: 0.875rem;
        }
        input:focus { outline: none; border-color: #3b82f6; }
        .hint { font-size: 0.75rem; color: #64748b; margin-top: 0.25rem; }
        .btn { padding: 0.6rem 1.1rem; border: none; border-radius: 6px; cursor: pointer; font-size: 0.875rem; font-weight: 500; }
        .btn-primary { background: #3b82f6; color: #fff; }
        .btn-primary:hover { background: #2563eb; }
        .btn-danger  { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
        .btn-danger:hover { background: rgba(239,68,68,0.3); }
        .btn-ghost   { background: transparent; color: #94a3b8; border: 1px solid #334155; }
        .btn-ghost:hover { color: #fff; border-color: #64748b; }
        /* Table */
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        th { text-align: left; padding: 0.5rem 0.75rem; color: #64748b; font-weight: 500; border-bottom: 1px solid #334155; }
        td { padding: 0.6rem 0.75rem; border-bottom: 1px solid #1e293b; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        .badge { padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; }
        .badge-yt     { background: rgba(255,0,0,0.15); color: #f87171; }
        .badge-upload { background: rgba(99,102,241,0.15); color: #818cf8; }
        .empty { text-align: center; padding: 2.5rem; color: #64748b; }
        .yt-note { font-size: 0.78rem; color: #64748b; margin-top: 0.5rem; }
    </style>
</head>
<body>

<div class="topbar">
    <div>
        <h1>🎬 VOD Manager</h1>
        <div class="sub">{{ $channel->name }}</div>
    </div>
    <form method="POST" action="{{ route('vod-manager.logout', $channel) }}" style="display:inline;">
        @csrf
        <button type="submit" class="logout">Logout</button>
    </form>
</div>

<div class="wrap">

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    {{-- Quota --}}
    @php
        $quota    = 2 * 1024 * 1024 * 1024;
        $pct      = $quota > 0 ? min(100, round($usedBytes / $quota * 100)) : 0;
        $usedFmt  = $usedBytes >= 1073741824 ? round($usedBytes/1073741824,2).' GB' : round($usedBytes/1048576,2).' MB';
        $barColor = $pct >= 90 ? '#f87171' : ($pct >= 70 ? '#fb923c' : '#3b82f6');
    @endphp
    <div class="card">
        <div class="quota-row">
            <span>Storage used</span>
            <span>{{ $usedFmt }} / 2 GB &mdash; {{ $pct }}%</span>
        </div>
        <div class="bar-bg">
            <div class="bar-fill" style="width:{{ $pct }}%; background:{{ $barColor }};"></div>
        </div>
    </div>

    {{-- Add Video --}}
    <div class="card">
        <div class="card-title">Add Video</div>

        <div class="tabs">
            <button class="tab-btn active" id="tab-upload-btn" onclick="switchTab('upload')">📁 Upload File</button>
            <button class="tab-btn" id="tab-yt-btn" onclick="switchTab('yt')">▶ YouTube URL</button>
        </div>

        <div id="tab-upload">
            <form method="POST" action="{{ route('vod-manager.store', $channel) }}" enctype="multipart/form-data">
                @csrf
                <div class="form-grid">
                    <div>
                        <label>Video File *</label>
                        <input type="file" name="file" accept="video/*,.ts,.m2ts" required>
                        <div class="hint">MP4, MKV, MOV, AVI, TS, FLV, WebM — max {{ $pct < 100 ? round((2048 * (1 - $pct/100))) : 0 }} MB remaining</div>
                    </div>
                    <div>
                        <label>Title (optional)</label>
                        <input type="text" name="title" placeholder="Leave blank to use filename">
                    </div>
                    <button type="submit" class="btn btn-primary" style="align-self:end;">Upload</button>
                </div>
            </form>
        </div>

        <div id="tab-yt" style="display:none;">
            <form method="POST" action="{{ route('vod-manager.store-youtube', $channel) }}">
                @csrf
                <div class="form-grid">
                    <div>
                        <label>YouTube URL *</label>
                        <input type="url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..."
                               required value="{{ old('youtube_url') }}">
                        <div class="hint">Paste a YouTube link — no download needed</div>
                    </div>
                    <div>
                        <label>Title (optional)</label>
                        <input type="text" name="title" placeholder="Leave blank to use video ID" value="{{ old('title') }}">
                    </div>
                    <button type="submit" class="btn btn-primary" style="align-self:end;">Add</button>
                </div>
            </form>
            <p class="yt-note">⚠ YouTube videos stream via yt-dlp during VOD fallback. Ensure yt-dlp is installed on the server.</p>
        </div>
    </div>

    {{-- File List --}}
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <div class="card-title" style="margin:0;">Videos ({{ $files->count() }})</div>
            @if($files->count() > 1)
            <form method="POST" action="{{ route('vod-manager.reorder', $channel) }}" id="reorder-form">
                @csrf
                <div id="reorder-inputs"></div>
                <button type="submit" class="btn btn-ghost" style="font-size:0.8rem; padding:0.4rem 0.8rem;">Save Order</button>
            </form>
            @endif
        </div>

        @if($files->isEmpty())
            <div class="empty">🎬 No videos yet. Upload a file or add a YouTube link above.</div>
        @else
        <table>
            <thead>
                <tr>
                    <th style="width:24px;"></th>
                    <th>Title</th>
                    <th>Source</th>
                    <th>Size</th>
                    <th>Duration</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="sortable-list">
                @foreach($files as $file)
                <tr data-id="{{ $file->id }}" style="cursor:grab;">
                    <td style="color:#475569;">⠿</td>
                    <td><strong>{{ $file->title }}</strong></td>
                    <td>
                        @if($file->isYoutube())
                            <span class="badge badge-yt">▶ YouTube</span>
                        @else
                            <span class="badge badge-upload">📁 Upload</span>
                        @endif
                    </td>
                    <td>{{ $file->isYoutube() ? '—' : $file->formattedSize() }}</td>
                    <td>{{ $file->formattedDuration() }}</td>
                    <td>
                        <form method="POST" action="{{ route('vod-manager.destroy', [$channel, $file]) }}"
                              onsubmit="return confirm('Remove this video?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="padding:0.3rem 0.7rem; font-size:0.8rem;">Remove</button>
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
        <div style="color:#4ade80; font-weight:600; margin-bottom:0.5rem;">✓ Active VOD Playlist</div>
        <div style="font-size:0.8rem; color:#64748b; margin-bottom:0.5rem;">Used automatically as fallback when the live stream goes offline.</div>
        <code style="font-size:0.78rem; word-break:break-all; color:#94a3b8;">{{ $channel->vod_playlist_url }}</code>
    </div>
    @endif

</div>

<script>
function switchTab(tab) {
    document.getElementById('tab-upload').style.display = tab === 'upload' ? '' : 'none';
    document.getElementById('tab-yt').style.display     = tab === 'yt'     ? '' : 'none';
    document.getElementById('tab-upload-btn').className = 'tab-btn' + (tab === 'upload' ? ' active' : '');
    document.getElementById('tab-yt-btn').className     = 'tab-btn' + (tab === 'yt'     ? ' active' : '');
}

@if($errors->has('youtube_url')) switchTab('yt'); @endif

const list = document.getElementById('sortable-list');
if (list) {
    let dragging = null;
    list.querySelectorAll('tr').forEach(row => {
        row.draggable = true;
        row.addEventListener('dragstart', () => { dragging = row; row.style.opacity = '0.4'; });
        row.addEventListener('dragend',   () => { dragging = null; row.style.opacity = '1'; updateOrder(); });
        row.addEventListener('dragover',  e => {
            e.preventDefault();
            const after = [...list.querySelectorAll('tr:not([style*="opacity: 0.4"])')].reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = e.clientY - box.top - box.height / 2;
                return offset < 0 && offset > closest.offset ? { offset, element: child } : closest;
            }, { offset: Number.NEGATIVE_INFINITY }).element;
            after ? list.insertBefore(dragging, after) : list.appendChild(dragging);
        });
    });
}

function updateOrder() {
    const inputs = document.getElementById('reorder-inputs');
    if (!inputs) return;
    inputs.innerHTML = '';
    document.querySelectorAll('#sortable-list tr').forEach((row) => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'order[]'; inp.value = row.dataset.id;
        inputs.appendChild(inp);
    });
}
updateOrder();
</script>
</body>
</html>
