<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MediaServer')</title>
    <style>
        :root {
            --sidebar-width: 260px;
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --bg: #0f172a;
            --surface: #1e293b;
            --surface-light: #334155;
            --text: #f1f5f9;
            --text-muted: #94a3b8;
            --border: #334155;
            --success: #22c55e;
            --warning: #f59e0b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: var(--sidebar-width);
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        .sidebar-header h1 {
            font-size: 1.25rem;
            color: var(--text);
        }
        .sidebar-header .badge {
            display: inline-block;
            margin-top: 0.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-library { background: #dbeafe; color: #1e40af; }
        .badge-full { background: #dcfce7; color: #166534; }
        .badge-premium { background: #fef3c7; color: #92400e; }
        .badge-guest { background: #e2e8f0; color: #475569; }
        .sidebar-nav { padding: 1rem 0; flex: 1; }
        .nav-section { margin-bottom: 1.5rem; }
        .nav-section-title {
            padding: 0 1.5rem;
            font-size: 0.6875rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 1.5rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9375rem;
            transition: all 0.15s;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--text);
            background: rgba(255,255,255,0.05);
        }
        .nav-link svg { width: 20px; height: 20px; opacity: 0.7; }
        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border);
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        .main {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 2rem;
            min-height: 100vh;
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; z-index: 100; }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>
    @include('layouts.partials.client-sidebar')

    <main class="main">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
