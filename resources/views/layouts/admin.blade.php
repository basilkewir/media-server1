<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — MediaServer</title>
    <style>
        :root {
            --primary:#2563eb; --primary-dark:#1d4ed8;
            --danger:#dc2626;  --success:#16a34a; --warning:#d97706;
            --bg:#0f172a;      --sidebar:#1e293b; --card:#1e293b;
            --card2:#273549;   --text:#f1f5f9;    --muted:#94a3b8;
            --border:#334155;  --live:#22c55e;    --offline:#ef4444;
        }
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
             background:var(--bg);color:var(--text);display:flex;min-height:100vh}

        /* ── Sidebar ── */
        .sidebar{width:240px;background:var(--sidebar);border-right:1px solid var(--border);
                 display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;overflow-y:auto;z-index:100}
        .sidebar-logo{padding:1.25rem 1.5rem;border-bottom:1px solid var(--border)}
        .sidebar-logo .logo-title{font-size:1.1rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:0.5rem}
        .sidebar-logo small{display:block;font-size:0.7rem;color:var(--muted);margin-top:3px}
        .live-dot{width:8px;height:8px;border-radius:50%;background:var(--live);
                  box-shadow:0 0 6px var(--live);animation:pulse 2s infinite;flex-shrink:0}
        .sidebar-section{padding:0.75rem 1rem 0.25rem;font-size:0.65rem;font-weight:700;
                         text-transform:uppercase;letter-spacing:0.1em;color:var(--muted)}
        .sidebar nav a{display:flex;align-items:center;gap:0.625rem;padding:0.6rem 1.5rem;
                       color:var(--muted);text-decoration:none;font-size:0.875rem;font-weight:500;transition:all 0.15s}
        .sidebar nav a:hover{color:var(--text);background:rgba(255,255,255,0.05)}
        .sidebar nav a.active{color:#fff;background:var(--primary)}
        .sidebar nav a .icon{width:18px;text-align:center;font-size:1rem}
        .sidebar-bottom{margin-top:auto;padding:1rem 1.5rem;border-top:1px solid var(--border);
                        display:flex;flex-direction:column;gap:0.5rem}
        .sidebar-bottom a{color:var(--muted);font-size:0.8rem;text-decoration:none;display:flex;align-items:center;gap:0.4rem}
        .sidebar-bottom a:hover{color:var(--text)}
        .sidebar-user{font-size:0.78rem;color:var(--muted);padding-bottom:0.5rem;border-bottom:1px solid var(--border);margin-bottom:0.25rem}
        .sidebar-user strong{color:var(--text);display:block}

        /* ── Main ── */
        .main{margin-left:240px;flex:1;display:flex;flex-direction:column;min-height:100vh}
        .topbar{background:var(--sidebar);border-bottom:1px solid var(--border);
                padding:0.875rem 2rem;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}
        .topbar-title{font-size:1.05rem;font-weight:600;display:flex;align-items:center;gap:0.75rem}
        .topbar-breadcrumb{font-size:0.78rem;color:var(--muted)}
        .topbar-breadcrumb a{color:var(--muted);text-decoration:none}
        .topbar-breadcrumb a:hover{color:var(--text)}
        .topbar-actions{display:flex;gap:0.75rem;align-items:center}
        .content{padding:2rem;flex:1}

        /* ── Cards ── */
        .card{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:1.5rem;margin-bottom:1.5rem}
        .card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem}
        .card-title{font-size:1rem;font-weight:600}
        .card-subtitle{font-size:0.8rem;color:var(--muted);margin-top:0.2rem}

        /* ── Stats ── */
        .stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem}
        .stat{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:1.25rem}
        .stat-value{font-size:2rem;font-weight:700;line-height:1}
        .stat-label{font-size:0.72rem;color:var(--muted);margin-top:0.375rem;text-transform:uppercase;letter-spacing:0.05em}
        .stat-live .stat-value{color:var(--live)}
        .stat-vod .stat-value{color:#f59e0b}
        .stat-offline .stat-value{color:var(--offline)}
        .stat-total .stat-value{color:#60a5fa}

        /* ── Table ── */
        .table-wrap{overflow-x:auto}
        table{width:100%;border-collapse:collapse;font-size:0.875rem}
        th{padding:0.625rem 0.875rem;text-align:left;font-size:0.7rem;font-weight:700;
           text-transform:uppercase;letter-spacing:0.05em;color:var(--muted);border-bottom:1px solid var(--border)}
        td{padding:0.75rem 0.875rem;border-bottom:1px solid var(--border);vertical-align:middle}
        tr:last-child td{border-bottom:none}
        tr:hover td{background:rgba(255,255,255,0.02)}

        /* ── Badges ── */
        .badge{display:inline-flex;align-items:center;gap:0.25rem;padding:0.2rem 0.6rem;
               border-radius:9999px;font-size:0.7rem;font-weight:600}
        .badge-live{background:rgba(34,197,94,0.15);color:#4ade80}
        .badge-vod{background:rgba(245,158,11,0.15);color:#fbbf24}
        .badge-fallback{background:rgba(234,179,8,0.15);color:#fbbf24}
        .badge-offline{background:rgba(239,68,68,0.15);color:#f87171}
        .badge-active{background:rgba(37,99,235,0.15);color:#60a5fa}
        .badge-stopped{background:rgba(100,116,139,0.15);color:#94a3b8}
        .badge-error{background:rgba(239,68,68,0.15);color:#f87171}
        .badge-rtmp{background:rgba(139,92,246,0.15);color:#a78bfa}
        .badge-srt{background:rgba(6,182,212,0.15);color:#22d3ee}
        .badge-hls{background:rgba(16,185,129,0.15);color:#34d399}
        .badge-info{background:rgba(37,99,235,0.15);color:#60a5fa}
        .dot{width:7px;height:7px;border-radius:50%;display:inline-block}
        .dot-live{background:var(--live);box-shadow:0 0 6px var(--live);animation:pulse 2s infinite}
        .dot-vod{background:#f59e0b}
        .dot-offline{background:var(--offline)}
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}

        /* ── Forms ── */
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
        .form-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem}
        .form-grid-4{display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:1rem}
        .form-full{grid-column:1/-1}
        .form-group{display:flex;flex-direction:column;gap:0.375rem}
        label{font-size:0.8rem;font-weight:600;color:var(--muted)}
        input,select,textarea{background:var(--bg);border:1px solid var(--border);border-radius:7px;
            padding:0.6rem 0.875rem;color:var(--text);font-size:0.875rem;width:100%}
        input[type=checkbox]{width:auto}
        input:focus,select:focus,textarea:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,0.2)}
        textarea{resize:vertical;min-height:80px}
        .hint{font-size:0.72rem;color:var(--muted)}
        .form-section{font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;
                      color:var(--muted);padding:1rem 0 0.5rem;border-top:1px solid var(--border);margin-top:0.5rem}
        .toggle-label{display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.875rem;color:var(--text);font-weight:500}

        /* ── Buttons ── */
        .btn{display:inline-flex;align-items:center;gap:0.4rem;padding:0.55rem 1.1rem;
             border:none;border-radius:7px;font-size:0.875rem;font-weight:600;
             cursor:pointer;text-decoration:none;transition:all 0.15s;white-space:nowrap}
        .btn-primary{background:var(--primary);color:#fff}
        .btn-primary:hover{background:var(--primary-dark)}
        .btn-success{background:#16a34a;color:#fff}
        .btn-success:hover{background:#15803d}
        .btn-warning{background:#d97706;color:#fff}
        .btn-warning:hover{background:#b45309}
        .btn-danger{background:var(--danger);color:#fff}
        .btn-danger:hover{background:#b91c1c}
        .btn-ghost{background:transparent;color:var(--muted);border:1px solid var(--border)}
        .btn-ghost:hover{color:var(--text);border-color:var(--text)}
        .btn-sm{padding:0.35rem 0.75rem;font-size:0.78rem}
        .btn-xs{padding:0.25rem 0.55rem;font-size:0.72rem}

        /* ── Alerts ── */
        .alert{padding:0.875rem 1.125rem;border-radius:8px;margin-bottom:1.25rem;font-size:0.875rem}
        .alert-success{background:rgba(22,163,74,0.15);color:#4ade80;border:1px solid rgba(22,163,74,0.3)}
        .alert-danger{background:rgba(220,38,38,0.15);color:#f87171;border:1px solid rgba(220,38,38,0.3)}
        .alert-warning{background:rgba(217,119,6,0.15);color:#fbbf24;border:1px solid rgba(217,119,6,0.3)}
        .alert-info{background:rgba(37,99,235,0.15);color:#60a5fa;border:1px solid rgba(37,99,235,0.3)}

        /* ── URL copy box ── */
        .url-box{display:flex;align-items:center;gap:0.5rem;background:var(--bg);
                 border:1px solid var(--border);border-radius:7px;padding:0.5rem 0.75rem}
        .url-box code{flex:1;font-size:0.78rem;word-break:break-all;color:var(--muted)}
        .copy-btn{background:none;border:none;cursor:pointer;color:var(--muted);font-size:0.8rem;
                  padding:0.2rem 0.4rem;border-radius:4px;transition:all 0.15s;white-space:nowrap}
        .copy-btn:hover{background:rgba(255,255,255,0.1);color:var(--text)}

        /* ── Ingest info box ── */
        .ingest-box{background:rgba(37,99,235,0.08);border:1px solid rgba(37,99,235,0.25);
                    border-radius:8px;padding:1rem 1.25rem;margin-bottom:1rem}
        .ingest-box h4{font-size:0.8rem;font-weight:700;color:#60a5fa;margin-bottom:0.5rem;text-transform:uppercase;letter-spacing:0.05em}
        .ingest-box .proto-row{display:flex;align-items:center;gap:0.75rem;margin-bottom:0.35rem}
        .ingest-box .proto-label{font-size:0.72rem;font-weight:700;color:var(--muted);width:50px;flex-shrink:0}
        .ingest-box code{font-size:0.78rem;color:var(--text)}

        /* ── Misc ── */
        .actions{display:flex;gap:0.5rem;flex-wrap:wrap}
        .empty{text-align:center;padding:3rem;color:var(--muted)}
        .empty-icon{font-size:2.5rem;margin-bottom:0.75rem}
        .mono{font-family:'SF Mono',Monaco,monospace;font-size:0.8rem}
        .text-muted{color:var(--muted)}
        .text-sm{font-size:0.8rem}
        .text-xs{font-size:0.72rem}
        .flex{display:flex;align-items:center;gap:0.5rem}
        .mt-1{margin-top:0.5rem}
        .mt-2{margin-top:1rem}
        .dl-grid{display:grid;grid-template-columns:auto 1fr;gap:0.4rem 1.5rem}
        .dl-grid dt{color:var(--muted);font-size:0.8rem;white-space:nowrap}
        .dl-grid dd{font-size:0.875rem}
        .section-divider{border:none;border-top:1px solid var(--border);margin:1.5rem 0}
        .pagination{display:flex;gap:0.5rem;justify-content:center;margin-top:1.5rem}
        .pagination a,.pagination span{padding:0.4rem 0.75rem;border-radius:6px;font-size:0.8rem;
            border:1px solid var(--border);color:var(--muted);text-decoration:none}
        .pagination .active{background:var(--primary);color:#fff;border-color:var(--primary)}
        .abr-ladder{display:flex;gap:0.4rem;flex-wrap:wrap}
        .abr-rung{background:rgba(37,99,235,0.12);border:1px solid rgba(37,99,235,0.25);
                  border-radius:5px;padding:0.2rem 0.5rem;font-size:0.72rem;color:#60a5fa;font-weight:600}
    </style>
    @stack('styles')
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-title">
            <span class="live-dot"></span> MediaServer
        </div>
        <small>v{{ config('app.version', '1.2.0') }} &mdash; Production</small>
    </div>

    <div class="sidebar-section">Streaming</div>
    <nav>
        <a href="{{ route('admin.channels.index') }}" class="{{ request()->routeIs('admin.channels.*') || request()->routeIs('admin.vod.*') ? 'active' : '' }}">
            <span class="icon">📺</span> Channels
        </a>
    </nav>

    <div class="sidebar-section">Access</div>
    <nav>
        <a href="{{ route('admin.access-codes.index') }}" class="{{ request()->routeIs('admin.access-codes.*') ? 'active' : '' }}">
            <span class="icon">🔑</span> Access Codes
        </a>
        @if(auth()->user()?->role === 'admin')
        <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <span class="icon">👥</span> Users
        </a>
        @endif
    </nav>

    <div class="sidebar-section">Ingest</div>
    <nav>
        <a href="#" onclick="document.getElementById('ingest-modal').style.display='flex'; return false;">
            <span class="icon">📡</span> Ingest Info
        </a>
    </nav>

    <div class="sidebar-bottom">
        <div class="sidebar-user">
            <strong>{{ auth()->user()?->name ?? 'Admin' }}</strong>
            {{ auth()->user()?->email }}
        </div>
        <a href="{{ url('/api/health') }}" target="_blank">🟢 API Health ↗</a>
        <form method="POST" action="{{ route('logout') }}" style="margin:0">
            @csrf
            <button type="submit" style="background:none;border:none;cursor:pointer;color:var(--muted);font-size:0.8rem;padding:0;display:flex;align-items:center;gap:0.4rem;">
                🚪 Sign Out
            </button>
        </form>
    </div>
</aside>

{{-- Ingest Info Modal --}}
<div id="ingest-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:999;align-items:center;justify-content:center;"
     onclick="if(event.target===this)this.style.display='none'">
    <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;padding:2rem;max-width:560px;width:90%;max-height:90vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
            <h2 style="margin:0;">📡 Ingest Endpoints</h2>
            <button onclick="document.getElementById('ingest-modal').style.display='none'" class="btn btn-ghost btn-sm">✕</button>
        </div>
        <p class="text-sm text-muted" style="margin-bottom:1.25rem;">Push your stream to any of these endpoints. Use your <strong>channel slug</strong> as the stream key.</p>

        <div class="ingest-box">
            <h4>RTMP (OBS, vMix, Wirecast)</h4>
            <div class="proto-row"><span class="proto-label">URL</span><code>rtmp://{{ request()->getHost() }}/live/{slug}</code></div>
            <div class="proto-row"><span class="proto-label">Port</span><code>1935</code></div>
        </div>
        <div class="ingest-box">
            <h4>SRT (low-latency)</h4>
            <div class="proto-row"><span class="proto-label">URL</span><code>srt://{{ request()->getHost() }}:10080</code></div>
            <div class="proto-row"><span class="proto-label">Stream ID</span><code>#!::r=live/{slug},m=publish</code></div>
        </div>
        <div class="ingest-box">
            <h4>RTSP</h4>
            <div class="proto-row"><span class="proto-label">URL</span><code>rtsp://{{ request()->getHost() }}:8554/live/{slug}</code></div>
        </div>
        <div class="ingest-box">
            <h4>HLS / HTTP Pull</h4>
            <div class="proto-row"><span class="proto-label">URL</span><code>http://{{ request()->getHost() }}/streams/{slug}/playlist.m3u8</code></div>
        </div>
        <p class="hint mt-2">Replace <code>{slug}</code> with your channel slug (e.g. <code>main</code>, <code>sports</code>).</p>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <div>
            <div class="topbar-title">@yield('title', 'Dashboard')</div>
            @hasSection('breadcrumb')
            <div class="topbar-breadcrumb">@yield('breadcrumb')</div>
            @endif
        </div>
        <div class="topbar-actions">
            @yield('topbar-actions')
            <a href="{{ route('admin.channels.create') }}" class="btn btn-primary btn-sm">+ New Channel</a>
        </div>
    </div>

    <div class="content">
        @if(session('success'))
            <div class="alert alert-success">✓ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">✕ {{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $e){{ $e }}<br>@endforeach
            </div>
        @endif

        @yield('content')
    </div>
</div>

<script>
function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.textContent;
        btn.textContent = '✓ Copied';
        setTimeout(() => btn.textContent = orig, 1500);
    });
}
</script>
@stack('scripts')
</body>
</html>
