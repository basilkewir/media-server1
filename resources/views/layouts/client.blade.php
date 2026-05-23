<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — MediaServer</title>
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
            --warning:#f59e0b;
            --border:#27272a; --border-light:#3f3f46;
            --radius-sm:6px; --radius:10px; --radius-lg:14px;
            --font-sans:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
            --font-mono:'JetBrains Mono',monospace;
            --ease:cubic-bezier(0.4,0,0.2,1);
        }
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:var(--font-sans);background:var(--surface-0);color:var(--text-primary);-webkit-font-smoothing:antialiased}
        a{color:var(--brand-light);text-decoration:none}
        a:hover{color:var(--brand)}
        ::selection{background:var(--brand);color:white}
        ::-webkit-scrollbar{width:6px}
        ::-webkit-scrollbar-thumb{background:var(--surface-4);border-radius:3px}

        .layout{display:flex;min-height:100vh}
        .sidebar{width:220px;background:var(--surface-1);border-right:1px solid var(--border);padding:20px;display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100}
        .sidebar-logo{font-size:16px;font-weight:800;color:var(--brand-light);margin-bottom:32px;display:flex;align-items:center;gap:8px}
        .sidebar-logo svg{width:28px;height:28px}
        .sidebar nav{display:flex;flex-direction:column;gap:4px}
        .sidebar nav a{display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:var(--radius-sm);color:var(--text-secondary);font-size:13.5px;font-weight:500;transition:all .15s var(--ease)}
        .sidebar nav a:hover{color:var(--text-primary);background:var(--surface-3)}
        .sidebar nav a.active{color:var(--text-primary);background:var(--brand-dim)}
        .sidebar-footer{margin-top:auto;padding-top:16px;border-top:1px solid var(--border);font-size:12px;color:var(--text-tertiary)}

        .main{flex:1;margin-left:220px}
        .topbar{height:56px;background:var(--surface-1);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 24px;position:sticky;top:0;z-index:50}
        .topbar-title{font-size:18px;font-weight:700}
        .content{padding:24px;max-width:1200px}

        .card{background:var(--surface-1);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;margin-bottom:16px}
        .card-title{font-size:16px;font-weight:700;margin-bottom:12px}

        .badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:600}
        .badge-success{background:var(--success-dim);color:var(--success)}
        .badge-neutral{background:var(--surface-3);color:var(--text-secondary)}
        .badge-brand{background:rgba(99,102,241,0.15);color:var(--brand-light)}
        .badge-dot{width:7px;height:7px;border-radius:50%}
        .badge-dot.green{background:var(--success)}
        .badge-dot.gray{background:var(--text-tertiary)}
        @keyframes pulse-dot{0%,100%{opacity:1}50%{opacity:.4}}
        .badge-dot.green{animation:pulse-dot 2s infinite}

        .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:var(--radius-sm);font-size:13px;font-weight:600;border:1px solid transparent;cursor:pointer;transition:all .15s var(--ease);text-decoration:none}
        .btn-primary{background:var(--brand);color:white}
        .btn-primary:hover{background:var(--brand-dim);color:white}
        .btn-ghost{background:transparent;color:var(--text-secondary);border-color:var(--border)}
        .btn-ghost:hover{background:var(--surface-2);color:var(--text-primary)}
        .btn-sm{padding:5px 10px;font-size:12px}

        .channel-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px}
        .channel-card{background:var(--surface-1);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;transition:all .15s var(--ease)}
        .channel-card:hover{border-color:var(--border-light);transform:translateY(-1px)}
        .channel-card-title{font-weight:700;font-size:15px;margin-bottom:6px}
        .channel-card-meta{font-size:12px;color:var(--text-tertiary);margin-bottom:12px}

        .empty-state{text-align:center;padding:48px 20px}
        .empty-state-title{font-size:16px;font-weight:600;margin-bottom:6px}
        .empty-state-text{font-size:13px;color:var(--text-tertiary)}

        .alert{padding:12px 16px;border-radius:var(--radius);font-size:13px;margin-bottom:16px;border:1px solid}
        .alert-warning{background:var(--surface-2);border-color:rgba(245,158,11,0.3);color:#fcd34d}
        .alert-success{background:var(--success-dim);border-color:rgba(34,197,94,0.2);color:#86efac}

        @media(max-width:768px){.sidebar{display:none}.main{margin-left:0}}
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <svg fill="none" stroke="var(--brand-light)" stroke-width="2" viewBox="0 0 24 24"><polygon points="23 7 16 12 7 21 1 14 8 9 1 3"/></svg>
            MediaServer
        </div>
        <nav>
            <a href="{{ route('client.dashboard') }}" class="{{ request()->routeIs('client.dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('client.library') }}" class="{{ request()->routeIs('client.library') ? 'active' : '' }}">Library</a>
            <a href="{{ route('client.streams') }}" class="{{ request()->routeIs('client.streams') ? 'active' : '' }}">Live Streams</a>
            @if($clientSubscription && $clientSubscription['type'] === 'premium')
            <a href="{{ route('client.premium') }}" class="{{ request()->routeIs('client.premium') ? 'active' : '' }}">Premium</a>
            @endif
        </nav>
        <div class="sidebar-footer">
            @if($clientSubscription)
                <div>{{ $clientSubscription['type_label'] }}</div>
                <div>{{ $clientSubscription['days_remaining'] }} days left</div>
            @else
                <div>No access code</div>
            @endif
        </div>
    </aside>
    <div class="main">
        <div class="topbar">
            <div class="topbar-title">@yield('title','Dashboard')</div>
        </div>
        <div class="content">@yield('content')</div>
    </div>
</div>
</body>
</html>
