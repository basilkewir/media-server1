<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — MediaServer</title>

    {{-- Font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        /* ═════════════════════════════════════════════════════════════════════
           MEDIASERVER DESIGN SYSTEM v2
           ═══════════════════════════════════════════════════════════════════ */

        :root {
            /* Surface palette */
            --surface-0: #09090b;
            --surface-1: #121216;
            --surface-2: #18181d;
            --surface-3: #1e1e24;
            --surface-4: #25252c;
            --surface-5: #2d2d35;

            /* Text palette */
            --text-primary: #f4f4f6;
            --text-secondary: #a1a1aa;
            --text-tertiary: #71717a;
            --text-inverse: #09090b;

            /* Brand */
            --brand: #6366f1;
            --brand-light: #818cf8;
            --brand-dim: #4f46e5;
            --brand-glow: rgba(99,102,241,0.25);

            /* Semantic */
            --success: #22c55e;
            --success-dim: #166534;
            --success-glow: rgba(34,197,94,0.2);
            --danger: #ef4444;
            --danger-dim: #7f1d1d;
            --danger-glow: rgba(239,68,68,0.2);
            --warning: #f59e0b;
            --warning-dim: #78350f;
            --warning-glow: rgba(245,158,11,0.2);
            --info: #3b82f6;
            --info-dim: #1e3a5f;
            --info-glow: rgba(59,130,246,0.2);

            /* Borders */
            --border: #27272a;
            --border-light: #3f3f46;

            /* Radii */
            --radius-sm: 6px;
            --radius: 10px;
            --radius-lg: 14px;
            --radius-xl: 18px;

            /* Shadows */
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.3);
            --shadow: 0 4px 12px rgba(0,0,0,0.4);
            --shadow-lg: 0 12px 40px rgba(0,0,0,0.5);

            /* Layout */
            --sidebar-w: 260px;
            --sidebar-collapsed-w: 68px;
            --topbar-h: 60px;

            /* Transitions */
            --ease: cubic-bezier(0.4, 0, 0.2, 1);
            --ease-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
            --transition-fast: 150ms var(--ease);
            --transition: 250ms var(--ease);
            --transition-slow: 400ms var(--ease);

            /* Typography */
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --font-mono: 'JetBrains Mono', 'SF Mono', 'Cascadia Code', monospace;
        }

        /* ═══ RESET ═══ */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        body {
            font-family: var(--font-sans);
            font-size: 14px;
            line-height: 1.5;
            background: var(--surface-0);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
        }
        a { color: var(--brand-light); text-decoration: none; transition: color var(--transition-fast); }
        a:hover { color: var(--brand); }
        img, svg { display: block; max-width: 100%; }
        button { font-family: inherit; cursor: pointer; }
        input, select, textarea { font-family: inherit; font-size: inherit; }
        ::selection { background: var(--brand); color: white; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--surface-4); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--surface-5); }

        /* ═══ SIDEBAR ═══ */
        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0; z-index: 200;
            width: var(--sidebar-w);
            background: var(--surface-1);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            transition: width var(--transition-slow), transform var(--transition-slow);
            overflow: hidden;
        }
        .sidebar.collapsed { width: var(--sidebar-collapsed-w); }
        .sidebar.collapsed .sidebar-logo-title,
        .sidebar.collapsed .sidebar-version,
        .sidebar.collapsed .sidebar-section-label,
        .sidebar.collapsed .nav-label,
        .sidebar.collapsed .sidebar-user-info { opacity: 0; pointer-events: none; }

        .sidebar-header {
            padding: 18px 20px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 12px;
            min-height: var(--topbar-h);
            flex-shrink: 0;
        }
        .sidebar-logo {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--brand), #a855f7);
            border-radius: var(--radius);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 18px; color: white;
            flex-shrink: 0;
            box-shadow: 0 4px 12px var(--brand-glow);
        }
        .sidebar-logo-text { transition: opacity var(--transition); line-height: 1.15; }
        .sidebar-logo-title { font-weight: 700; font-size: 16px; white-space: nowrap; }
        .sidebar-version { font-size: 11px; color: var(--text-tertiary); white-space: nowrap; }

        .sidebar-nav {
            flex: 1; overflow-y: auto; overflow-x: hidden;
            padding: 12px 10px;
            display: flex; flex-direction: column; gap: 2px;
        }
        .sidebar-section-label {
            font-size: 10px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.12em; color: var(--text-tertiary);
            padding: 16px 12px 4px;
            white-space: nowrap;
            transition: opacity var(--transition);
        }
        .nav-item {
            display: flex; align-items: center; gap: 11px;
            padding: 9px 12px;
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            font-size: 13.5px; font-weight: 500;
            transition: all var(--transition-fast);
            position: relative;
            white-space: nowrap;
            text-decoration: none;
        }
        .nav-item:hover { color: var(--text-primary); background: var(--surface-3); }
        .nav-item.active {
            color: var(--text-primary);
            background: linear-gradient(135deg, var(--brand-dim), color-mix(in srgb, var(--brand-dim) 70%, transparent));
            box-shadow: 0 2px 8px var(--brand-glow);
        }
        .nav-icon {
            width: 20px; height: 20px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .nav-icon svg { width: 100%; height: 100%; stroke: currentColor; }
        .nav-label { transition: opacity var(--transition); }

        .nav-badge {
            margin-left: auto;
            font-size: 11px; font-weight: 600;
            background: var(--brand); color: white;
            padding: 2px 7px; border-radius: 99px;
            opacity: 0; transition: opacity var(--transition);
        }
        .sidebar:not(.collapsed) .nav-badge { opacity: 1; }

        .sidebar-footer {
            padding: 12px 14px;
            border-top: 1px solid var(--border);
            flex-shrink: 0;
            display: flex; flex-direction: column; gap: 6px;
        }
        .sidebar-user {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 10px;
            border-radius: var(--radius);
            transition: background var(--transition-fast);
            text-decoration: none; color: inherit;
        }
        .sidebar-user:hover { background: var(--surface-3); }
        .sidebar-user-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: linear-gradient(135deg, var(--brand), #a855f7);
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; color: white; flex-shrink: 0;
        }
        .sidebar-user-info { transition: opacity var(--transition); line-height: 1.2; }
        .sidebar-user-name { font-weight: 600; font-size: 13px; }
        .sidebar-user-role { font-size: 11px; color: var(--text-tertiary); }
        .sidebar-logout {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 10px; border-radius: var(--radius-sm);
            color: var(--text-tertiary); font-size: 13px;
            transition: all var(--transition-fast);
            background: none; border: none; width: 100%; text-align: left;
        }
        .sidebar-logout:hover { color: var(--danger); background: var(--danger-dim); }

        /* Sidebar collapse button */
        .sidebar-collapse-btn {
            position: absolute; bottom: 80px; right: -14px;
            width: 28px; height: 28px; border-radius: 50%;
            background: var(--surface-3); border: 1px solid var(--border);
            color: var(--text-tertiary); display: flex; align-items: center; justify-content: center;
            cursor: pointer; z-index: 210; transition: all var(--transition-fast);
        }
        .sidebar-collapse-btn:hover { background: var(--surface-4); color: var(--text-primary); }
        .sidebar-collapse-btn svg { transition: transform var(--transition); }
        .sidebar.collapsed .sidebar-collapse-btn svg { transform: rotate(180deg); }

        /* ═══ MAIN ═══ */
        .main {
            margin-left: var(--sidebar-w);
            transition: margin-left var(--transition-slow);
            min-height: 100vh;
            display: flex; flex-direction: column;
        }
        .main.expanded { margin-left: var(--sidebar-collapsed-w); }

        .topbar {
            height: var(--topbar-h);
            background: var(--surface-1);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center;
            padding: 0 28px;
            position: sticky; top: 0; z-index: 100;
            backdrop-filter: blur(12px);
        }
        .topbar-left {
            display: flex; align-items: center; gap: 16px;
            min-width: 0;
        }
        .hamburger {
            display: none; background: none; border: none;
            color: var(--text-secondary); padding: 4px; border-radius: var(--radius-sm);
            transition: all var(--transition-fast);
        }
        .hamburger:hover { color: var(--text-primary); background: var(--surface-3); }
        .topbar-title {
            font-size: 20px; font-weight: 700; letter-spacing: -0.02em;
        }
        .topbar-breadcrumb {
            font-size: 12.5px; color: var(--text-tertiary);
            display: flex; align-items: center; gap: 6px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .topbar-breadcrumb a { color: var(--text-tertiary); }
        .topbar-breadcrumb a:hover { color: var(--text-secondary); }
        .topbar-breadcrumb .sep { color: var(--border-light); }
        .topbar-right {
            margin-left: auto;
            display: flex; align-items: center; gap: 10px;
        }
        .topbar-actions {
            display: flex; align-items: center; gap: 8px;
        }

        /* Quick Stat Pills */
        .stat-pill {
            display: flex; align-items: center; gap: 6px;
            padding: 5px 12px;
            border-radius: 99px;
            font-size: 12px; font-weight: 600;
            background: var(--surface-3);
            color: var(--text-secondary);
            white-space: nowrap;
        }
        .stat-pill .dot { width: 7px; height: 7px; border-radius: 50%; }
        .stat-pill .dot.green { background: var(--success); box-shadow: 0 0 6px var(--success-glow); }
        .stat-pill .dot.amber { background: var(--warning); }
        .stat-pill .dot.red { background: var(--danger); }

        /* ═══ CONTENT ═══ */
        .content { padding: 32px 28px; flex: 1; }

        /* ═══ CARDS ═══ */
        .card {
            background: var(--surface-1);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 20px;
            transition: border-color var(--transition-fast);
        }
        .card:hover { border-color: var(--border-light); }
        .card-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px; gap: 12px; flex-wrap: wrap;
        }
        .card-title { font-size: 16px; font-weight: 700; letter-spacing: -0.01em; }
        .card-subtitle { font-size: 13px; color: var(--text-tertiary); margin-top: 2px; }

        /* ═══ STATS GRID ═══ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 14px; margin-bottom: 24px;
        }
        .stat-card {
            background: var(--surface-1);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 20px;
            transition: all var(--transition-fast);
            position: relative; overflow: hidden;
        }
        .stat-card:hover { border-color: var(--border-light); transform: translateY(-1px); box-shadow: var(--shadow-sm); }
        .stat-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
            background: var(--brand); opacity: 0; transition: opacity var(--transition-fast);
        }
        .stat-card:hover::before { opacity: 1; }
        .stat-icon {
            width: 36px; height: 36px; border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 12px;
        }
        .stat-icon svg { width: 18px; height: 18px; }
        .stat-value { font-size: 28px; font-weight: 800; letter-spacing: -0.02em; line-height: 1; margin-bottom: 4px; }
        .stat-label { font-size: 12px; color: var(--text-tertiary); font-weight: 500; text-transform: uppercase; letter-spacing: 0.04em; }
        .stat-change { font-size: 12px; font-weight: 600; margin-left: 6px; }
        .stat-change.up { color: var(--success); }
        .stat-change.down { color: var(--danger); }

        /* ═══ TABLE ═══ */
        .table-wrap { overflow-x: auto; border-radius: var(--radius); }
        table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        thead th {
            padding: 10px 14px;
            text-align: left;
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.06em;
            color: var(--text-tertiary);
            border-bottom: 2px solid var(--border);
            white-space: nowrap;
            user-select: none;
        }
        thead th.sortable { cursor: pointer; transition: color var(--transition-fast); }
        thead th.sortable:hover { color: var(--text-primary); }
        tbody td {
            padding: 11px 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        tbody tr { transition: background var(--transition-fast); }
        tbody tr:hover td { background: var(--surface-2); }
        tbody tr:last-child td { border-bottom: none; }

        /* ═══ BADGES ═══ */
        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px; border-radius: 99px;
            font-size: 11.5px; font-weight: 600; white-space: nowrap;
            letter-spacing: 0.01em;
        }
        .badge-success { background: var(--success-dim); color: var(--success); }
        .badge-danger  { background: var(--danger-dim); color: var(--danger); }
        .badge-warning { background: var(--warning-dim); color: var(--warning); }
        .badge-info    { background: var(--info-dim); color: var(--info); }
        .badge-brand   { background: rgba(99,102,241,0.15); color: var(--brand-light); }
        .badge-neutral { background: var(--surface-3); color: var(--text-secondary); }
        .badge-dot { width: 7px; height: 7px; border-radius: 50%; }
        .badge-dot.green { background: var(--success); box-shadow: 0 0 6px var(--success-glow); animation: pulse-dot 2s ease-in-out infinite; }
        .badge-dot.amber { background: var(--warning); box-shadow: 0 0 6px var(--warning-glow); }
        .badge-dot.gray  { background: var(--text-tertiary); }

        /* ═══ BUTTONS ═══ */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            padding: 9px 17px; border-radius: var(--radius-sm); font-size: 13.5px; font-weight: 600;
            border: 1px solid transparent; cursor: pointer; text-decoration: none;
            transition: all var(--transition-fast); white-space: nowrap;
            letter-spacing: 0.01em;
        }
        .btn:active { transform: scale(0.97); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .btn-primary { background: var(--brand); color: white; }
        .btn-primary:hover { background: var(--brand-dim); box-shadow: 0 4px 14px var(--brand-glow); }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #16a34a; box-shadow: 0 4px 14px var(--success-glow); }
        .btn-danger  { background: var(--danger); color: white; }
        .btn-danger:hover  { background: #dc2626; box-shadow: 0 4px 14px var(--danger-glow); }
        .btn-warning { background: var(--warning); color: #1c1917; }
        .btn-warning:hover { background: #d97706; }
        .btn-ghost {
            background: transparent; color: var(--text-secondary);
            border-color: var(--border);
        }
        .btn-ghost:hover { background: var(--surface-2); color: var(--text-primary); border-color: var(--border-light); }
        .btn-sm { padding: 6px 12px; font-size: 12.5px; border-radius: 5px; }
        .btn-xs { padding: 4px 9px; font-size: 11.5px; border-radius: 4px; }
        .btn-icon { padding: 7px; width: 34px; height: 34px; }
        .btn-group { display: flex; }
        .btn-group .btn:first-child { border-radius: var(--radius-sm) 0 0 var(--radius-sm); }
        .btn-group .btn:last-child { border-radius: 0 var(--radius-sm) var(--radius-sm) 0; }
        .btn-group .btn:not(:first-child) { margin-left: -1px; }

        /* ═══ FORMS ═══ */
        .form-grid   { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
        .form-grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 14px; }
        .form-full   { grid-column: 1 / -1; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        label { font-size: 13px; font-weight: 600; color: var(--text-secondary); }
        .label-required::after { content: ' *'; color: var(--danger); }
        input, select, textarea {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            color: var(--text-primary);
            font-size: 14px;
            transition: all var(--transition-fast);
            outline: none;
        }
        input:hover, select:hover, textarea:hover { border-color: var(--border-light); }
        input:focus, select:focus, textarea:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px var(--brand-glow);
        }
        input::placeholder, textarea::placeholder { color: var(--text-tertiary); }
        textarea { resize: vertical; min-height: 80px; }
        input[type="checkbox"], input[type="radio"] {
            width: 17px; height: 17px; accent-color: var(--brand);
            cursor: pointer;
        }
        input[type="color"] {
            width: 44px; height: 38px; padding: 2px; border-radius: var(--radius-sm); cursor: pointer;
        }
        select { cursor: pointer; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2371717a' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px; }
        .hint { font-size: 12px; color: var(--text-tertiary); margin-top: 3px; }
        .form-section {
            font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.1em; color: var(--text-tertiary);
            padding: 20px 0 8px; border-top: 1px solid var(--border);
            margin-top: 8px; grid-column: 1 / -1;
        }
        .toggle-row { display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13.5px; color: var(--text-secondary); }

        /* ═══ ALERTS ═══ */
        .alert {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 13px 16px; border-radius: var(--radius); margin-bottom: 16px;
            font-size: 13.5px; line-height: 1.5;
            border: 1px solid;
            animation: slideDown var(--transition);
        }
        .alert-success { background: var(--success-dim); border-color: rgba(34,197,94,0.25); color: #86efac; }
        .alert-danger  { background: var(--danger-dim); border-color: rgba(239,68,68,0.25); color: #fca5a5; }
        .alert-warning { background: var(--warning-dim); border-color: rgba(245,158,11,0.25); color: #fcd34d; }
        .alert-info    { background: var(--info-dim); border-color: rgba(59,130,246,0.25); color: #93c5fd; }
        .alert-icon { flex-shrink: 0; margin-top: 1px; }
        .alert-icon svg { width: 18px; height: 18px; }

        /* ═══ TOAST NOTIFICATIONS ═══ */
        .toast-container {
            position: fixed; top: 20px; right: 20px; z-index: 9999;
            display: flex; flex-direction: column; gap: 10px;
            pointer-events: none;
        }
        .toast {
            pointer-events: auto;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 14px 18px;
            display: flex; align-items: center; gap: 10px;
            font-size: 13.5px; font-weight: 500;
            box-shadow: var(--shadow-lg);
            animation: toastIn 0.4s var(--ease-bounce);
            max-width: 420px;
            min-width: 300px;
        }
        .toast.removing { animation: toastOut 0.3s var(--ease) forwards; }
        .toast-success { border-left: 3px solid var(--success); }
        .toast-error   { border-left: 3px solid var(--danger); }
        .toast-warning { border-left: 3px solid var(--warning); }
        .toast-info    { border-left: 3px solid var(--info); }
        .toast-close {
            margin-left: auto; background: none; border: none;
            color: var(--text-tertiary); cursor: pointer; padding: 2px;
            border-radius: 4px; transition: all var(--transition-fast);
        }
        .toast-close:hover { color: var(--text-primary); background: var(--surface-3); }

        /* ═══ MODAL ═══ */
        .modal-overlay {
            position: fixed; inset: 0; z-index: 9000;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center;
            animation: fadeIn 0.2s var(--ease);
        }
        .modal {
            background: var(--surface-1);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 28px;
            max-width: 520px; width: 92%;
            max-height: 85vh; overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: scaleIn 0.25s var(--ease-bounce);
        }
        .modal-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px;
        }
        .modal-title { font-size: 18px; font-weight: 700; }
        .modal-close {
            background: var(--surface-3); border: none; color: var(--text-tertiary);
            width: 32px; height: 32px; border-radius: 50%; display: flex;
            align-items: center; justify-content: center;
            transition: all var(--transition-fast);
        }
        .modal-close:hover { background: var(--surface-4); color: var(--text-primary); }

        /* ═══ LOADING ═══ */
        .spinner {
            width: 20px; height: 20px; border: 2px solid var(--border);
            border-top-color: var(--brand); border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        .spinner-sm { width: 14px; height: 14px; }
        .spinner-lg { width: 32px; height: 32px; border-width: 3px; }
        .loading-overlay {
            position: absolute; inset: 0;
            background: rgba(9,9,11,0.7);
            display: flex; align-items: center; justify-content: center;
            border-radius: inherit; z-index: 10;
        }

        /* ═══ EMPTY STATE ═══ */
        .empty-state {
            text-align: center; padding: 48px 20px;
        }
        .empty-state-icon {
            width: 64px; height: 64px; margin: 0 auto 16px;
            background: var(--surface-2); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }
        .empty-state-icon svg { width: 32px; height: 32px; stroke: var(--text-tertiary); }
        .empty-state-title { font-size: 16px; font-weight: 600; margin-bottom: 6px; }
        .empty-state-text { font-size: 13.5px; color: var(--text-tertiary); max-width: 380px; margin: 0 auto 16px; }

        /* ═══ URL COPY BOX ═══ */
        .url-box {
            display: flex; align-items: center; gap: 8px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 8px 12px;
            transition: all var(--transition-fast);
        }
        .url-box:hover { border-color: var(--border-light); }
        .url-box code {
            flex: 1; font-family: var(--font-mono); font-size: 12.5px;
            color: var(--text-secondary); word-break: break-all;
        }
        .copy-btn {
            background: var(--surface-3); border: none; color: var(--text-secondary);
            padding: 6px 10px; border-radius: 4px; font-size: 12px; font-weight: 600;
            cursor: pointer; transition: all var(--transition-fast);
        }
        .copy-btn:hover { background: var(--surface-4); color: var(--text-primary); }
        .copy-btn.copied { background: var(--success-dim); color: var(--success); }

        /* ═══ KEYBOARD SHORTCUT HINT ═══ */
        .kbd {
            display: inline-flex; align-items: center; gap: 2px;
            background: var(--surface-3); border: 1px solid var(--border);
            border-radius: 4px; padding: 1px 6px; font-size: 11px;
            font-family: var(--font-mono); font-weight: 500;
            color: var(--text-secondary);
        }

        /* ═══ PROGRESS BAR ═══ */
        .progress-bar {
            height: 8px; background: var(--surface-3);
            border-radius: 99px; overflow: hidden;
        }
        .progress-fill {
            height: 100%; border-radius: 99px; background: var(--brand);
            transition: width 0.5s var(--ease); min-width: 0;
        }
        .progress-fill.danger { background: var(--danger); }
        .progress-fill.warning { background: var(--warning); }
        .progress-fill.success { background: var(--success); }

        /* ═══ TOOLTIP ═══ */
        [data-tooltip] { position: relative; }
        [data-tooltip]::after {
            content: attr(data-tooltip);
            position: absolute; bottom: calc(100% + 6px); left: 50%;
            transform: translateX(-50%);
            background: var(--surface-4); color: var(--text-primary);
            padding: 5px 10px; border-radius: 5px; font-size: 12px;
            white-space: nowrap; pointer-events: none;
            opacity: 0; transition: opacity var(--transition-fast);
        }
        [data-tooltip]:hover::after { opacity: 1; }

        /* ═══ DASHBOARD FEED ═══ */
        .feed-item {
            display: flex; gap: 10px; padding: 10px 0;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
        }
        .feed-item:last-child { border-bottom: none; }
        .feed-time { font-size: 11.5px; color: var(--text-tertiary); white-space: nowrap; min-width: 55px; }

        /* ═══ DROP ZONE ═══ */
        .drop-zone {
            border: 2px dashed var(--border);
            border-radius: var(--radius);
            padding: 36px 20px; text-align: center;
            cursor: pointer; transition: all var(--transition-fast);
        }
        .drop-zone:hover, .drop-zone.dragover {
            border-color: var(--brand); background: rgba(99,102,241,0.05);
        }
        .drop-zone-title { font-weight: 600; margin-bottom: 4px; }
        .drop-zone-hint { font-size: 12.5px; color: var(--text-tertiary); }

        /* ═══ SKELETON LOADERS ═══ */
        .skeleton {
            background: linear-gradient(90deg, var(--surface-3) 25%, var(--surface-4) 50%, var(--surface-3) 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s ease-in-out infinite;
            border-radius: var(--radius-sm);
        }
        .skeleton-text { height: 14px; margin-bottom: 8px; }
        .skeleton-text-sm { height: 12px; width: 60%; }
        .skeleton-avatar { width: 40px; height: 40px; border-radius: 50%; }

        /* ═══ ANIMATIONS ═══ */
        @keyframes pulse-dot { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes toastIn { from { opacity: 0; transform: translateX(60px) scale(0.95); } to { opacity: 1; transform: translateX(0) scale(1); } }
        @keyframes toastOut { from { opacity: 1; transform: translateX(0) scale(1); } to { opacity: 0; transform: translateX(60px) scale(0.95); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

        .animate-in { animation: slideUp 0.4s var(--ease) both; }
        .stagger-1 { animation-delay: 0.05s; }
        .stagger-2 { animation-delay: 0.1s; }
        .stagger-3 { animation-delay: 0.15s; }
        .stagger-4 { animation-delay: 0.2s; }

        /* ═══ RESPONSIVE ═══ */
        @media (max-width: 1024px) {
            .form-grid-4 { grid-template-columns: 1fr 1fr; }
            .form-grid-3 { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: var(--shadow-lg);
            }
            .sidebar.open { transform: translateX(0); }
            .sidebar.collapsed { width: var(--sidebar-w); }
            .sidebar.collapsed .sidebar-logo-title,
            .sidebar.collapsed .sidebar-version,
            .sidebar.collapsed .sidebar-section-label,
            .sidebar.collapsed .nav-label,
            .sidebar.collapsed .sidebar-user-info { opacity: 1; pointer-events: auto; }

            .main { margin-left: 0 !important; }
            .hamburger { display: flex; }
            .topbar { padding: 0 16px; }
            .content { padding: 20px 16px; }
            .stats-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; }
            .form-grid, .form-grid-3, .form-grid-4 { grid-template-columns: 1fr; }
            .card { padding: 16px; margin-bottom: 14px; }
            .card-header { flex-direction: column; align-items: flex-start; }
            .sidebar-collapse-btn { display: none; }

            .mobile-sidebar-backdrop {
                display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5);
                z-index: 190; backdrop-filter: blur(2px);
            }
            .mobile-sidebar-backdrop.visible { display: block; }
        }
        @media (max-width: 480px) {
            .topbar-title { font-size: 16px; }
            .stat-card { padding: 14px; }
            .stat-value { font-size: 22px; }
            .toast { min-width: auto; max-width: calc(100vw - 32px); }
            .modal { padding: 20px; }
        }
    </style>
    @stack('styles')
</head>
<body>
{{-- Mobile backdrop --}}
<div class="mobile-sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()"></div>

{{-- Sidebar --}}
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">M</div>
        <div class="sidebar-logo-text">
            <div class="sidebar-logo-title">MediaServer</div>
            <div class="sidebar-version">v{{ config('app.version', '1.2.0') }}</div>
        </div>
    </div>

    {{-- Collapse toggle (desktop only) --}}
    <button class="sidebar-collapse-btn" onclick="toggleSidebar()" title="Toggle sidebar">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
    </button>

    <nav class="sidebar-nav">
        <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <span class="nav-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span>
            <span class="nav-label">Dashboard</span>
        </a>

        <div class="sidebar-section-label">Streaming</div>

        <a href="{{ route('admin.channels.index') }}" class="nav-item {{ request()->routeIs('admin.channels.*') || request()->routeIs('admin.vod.*') || request()->routeIs('admin.vod-schedules.*') || request()->routeIs('admin.channels.graphics') ? 'active' : '' }}">
            <span class="nav-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg></span>
            <span class="nav-label">Channels</span>
            @php $liveCh = \App\Models\Channel::where('is_live', true)->count(); @endphp
            @if($liveCh) <span class="nav-badge">{{ $liveCh }} live</span> @endif
        </a>

        <a href="{{ route('admin.srt-streams.index') }}" class="nav-item {{ request()->routeIs('admin.srt-streams.*') ? 'active' : '' }}">
            <span class="nav-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10m0-20a15.3 15.3 0 00-4 10 15.3 15.3 0 004 10"/></svg></span>
            <span class="nav-label">SRT Streams</span>
        </a>

        <a href="{{ route('admin.outputs.index') }}" class="nav-item {{ request()->routeIs('admin.outputs.*') ? 'active' : '' }}">
            <span class="nav-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></span>
            <span class="nav-label">Outputs</span>
        </a>

        <a href="{{ route('admin.relay-servers.index') }}" class="nav-item {{ request()->routeIs('admin.relay-servers.*') || request()->routeIs('admin.icecast.*') ? 'active' : '' }}">
            <span class="nav-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></span>
            <span class="nav-label">Icecast & Relay</span>
        </a>

        <div class="sidebar-section-label">Management</div>

        <a href="{{ route('admin.access-codes.index') }}" class="nav-item {{ request()->routeIs('admin.access-codes.*') ? 'active' : '' }}">
            <span class="nav-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 11-7.778 7.778 5.5 5.5 0 017.777-7.777z"/><path d="M15.5 7.5l2.5 2.5"/></svg></span>
            <span class="nav-label">Access Codes</span>
        </a>

        @if(auth()->user()?->isAdmin())
        <a href="{{ route('admin.users.index') }}" class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <span class="nav-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75"/></svg></span>
            <span class="nav-label">Users</span>
        </a>
        <a href="{{ route('admin.plans.index') }}" class="nav-item {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}">
            <span class="nav-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 12V8H6a2 2 0 01-2-2c0-1.1.9-2 2-2h12v4"/><path d="M4 6v12c0 1.1.9 2 2 2h14v-4"/><path d="M18 12a2 2 0 000 4h4v-4z"/></svg></span>
            <span class="nav-label">Plans</span>
        </a>
        @endif

        <a href="{{ route('admin.api-tokens.index') }}" class="nav-item {{ request()->routeIs('admin.api-tokens.*') ? 'active' : '' }}">
            <span class="nav-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 1v4m0 14v4M4.22 4.22l2.83 2.83m9.9 9.9l2.83 2.83M1 12h4m14 0h4M4.22 19.78l2.83-2.83m9.9-9.9l2.83-2.83"/></svg></span>
            <span class="nav-label">API Tokens</span>
        </a>

        <a href="{{ route('admin.settings.index') }}" class="nav-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
            <span class="nav-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg></span>
            <span class="nav-label">Settings</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="{{ route('profile') }}" class="sidebar-user">
            <div class="sidebar-user-avatar">{{ strtoupper(substr(auth()->user()?->name ?? 'A', 0, 1)) }}</div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name">{{ auth()->user()?->name ?? 'Admin' }}</div>
                <div class="sidebar-user-role">{{ auth()->user()?->role ?? 'admin' }}</div>
            </div>
        </a>
        <form method="POST" action="{{ route('logout') }}" style="display:contents;">
            @csrf
            <button type="submit" class="sidebar-logout">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Sign Out
            </button>
        </form>
    </div>
</aside>

{{-- Main --}}
<div class="main" id="mainContent">
    <div class="topbar">
        <div class="topbar-left">
            <button class="hamburger" onclick="toggleMobileSidebar()" aria-label="Menu">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div>
                <div class="topbar-title">@yield('title', 'Dashboard')</div>
                @hasSection('breadcrumb')
                    <div class="topbar-breadcrumb">@yield('breadcrumb')</div>
                @endif
            </div>
        </div>
        <div class="topbar-right">
            @yield('topbar-actions')
        </div>
    </div>

    <div class="content">
        {{-- Session alerts (auto-dismiss) --}}
        @if(session('success'))
            <div class="alert alert-success" id="autoAlert">
                <span class="alert-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger" id="autoAlert">
                <span class="alert-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></span>
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <span class="alert-icon"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></span>
                <div>
                    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                </div>
            </div>
        @endif

        @yield('content')
    </div>
</div>

{{-- Toast container --}}
<div class="toast-container" id="toastContainer"></div>

{{-- Global scripts --}}
<script>
// ═══ Sidebar Toggle (Desktop) ═══
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    sidebar.classList.toggle('collapsed');
    main.classList.toggle('expanded');
    localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
}
// Restore state
if (localStorage.getItem('sidebar-collapsed') === 'true' && window.innerWidth > 768) {
    document.getElementById('sidebar').classList.add('collapsed');
    document.getElementById('mainContent').classList.add('expanded');
}

// ═══ Mobile Sidebar ═══
function toggleMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    sidebar.classList.toggle('open');
    backdrop.classList.toggle('visible');
    document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarBackdrop').classList.remove('visible');
    document.body.style.overflow = '';
}

// ═══ Toast Notifications ═══
window.showToast = function(message, type = 'info', duration = 4000) {
    const container = document.getElementById('toastContainer');
    const icons = {
        success: '<svg width="18" height="18" fill="none" stroke="#22c55e" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        error: '<svg width="18" height="18" fill="none" stroke="#ef4444" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        warning: '<svg width="18" height="18" fill="none" stroke="#f59e0b" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        info: '<svg width="18" height="18" fill="none" stroke="#3b82f6" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
    };
    const toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.innerHTML = icons[type] + '<span>' + message + '</span>'
        + '<button class="toast-close" onclick="this.parentElement.remove()"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>';
    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('removing');
        setTimeout(() => toast.remove(), 300);
    }, duration);
};

// ═══ Auto-dismiss alerts ═══
document.querySelectorAll('#autoAlert').forEach(el => {
    setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity 0.4s'; setTimeout(() => el.remove(), 400); }, 5000);
});

// ═══ Copy to clipboard ═══
window.copyToClipboard = function(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        btn.classList.add('copied');
        btn.textContent = 'Copied!';
        setTimeout(() => { btn.classList.remove('copied'); btn.textContent = 'Copy'; }, 2000);
    });
};

// ═══ Modal helpers ═══
window.openModal = function(id) {
    const el = document.getElementById(id);
    if (el) { el.style.display = 'flex'; el.querySelector('input,select,textarea')?.focus(); }
};
window.closeModal = function(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
};
// Close modals on overlay click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) e.target.style.display = 'none';
});

// ═══ Keyboard shortcuts ═══
document.addEventListener('keydown', function(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay[style*="flex"]').forEach(m => m.style.display = 'none');
        closeSidebar();
    }
    // Ctrl+S: Submit first form
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        const form = document.querySelector('form[method="POST"]:not([action*="logout"])');
        if (form) form.requestSubmit();
    }
});
</script>

@stack('scripts')
</body>
</html>
