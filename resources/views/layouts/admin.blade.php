<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - MediaServer</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --danger: #dc2626;
            --success: #16a34a;
            --warning: #ca8a04;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }
        .container { max-width: 900px; margin: 0 auto; padding: 2rem 1rem; }
        .card {
            background: var(--card);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        h1, h2 { margin-top: 0; }
        .form-group { margin-bottom: 1.25rem; }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        label .required { color: var(--danger); }
        select, input[type="text"], input[type="number"], input[type="date"] {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            background: #fff;
        }
        select:focus, input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary {
            background: var(--primary);
            color: #fff;
        }
        .btn-primary:hover { background: var(--primary-dark); }
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .summary-box {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            margin-top: 1.5rem;
        }
        .summary-box h3 { margin: 0 0 0.75rem; font-size: 1rem; }
        .summary-box dl {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 0.5rem 1.5rem;
            margin: 0;
        }
        .summary-box dt { font-weight: 600; color: var(--text-muted); }
        .summary-box dd { margin: 0; }
        .codes-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
        }
        .code-item {
            background: #fff;
            border: 2px dashed var(--border);
            border-radius: 8px;
            padding: 0.75rem;
            text-align: center;
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 0.875rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            color: var(--text);
        }
        .nav { display: flex; gap: 1rem; margin-bottom: 2rem; align-items: center; flex-wrap: wrap; }
        .nav a { color: var(--text-muted); text-decoration: none; font-weight: 500; }
        .nav a:hover, .nav a.active { color: var(--primary); }
        .nav-user { margin-left: auto; display: flex; align-items: center; gap: 0.75rem; font-size: 0.875rem; }
        .nav-user form { display: inline; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border); }
        th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-library_only { background: #dbeafe; color: #1e40af; }
        .badge-full_access { background: #dcfce7; color: #166534; }
        .badge-premium { background: #fef3c7; color: #92400e; }
        .badge-live { background: #dcfce7; color: #166534; }
        .badge-vod { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a href="{{ route('admin.channels.index') }}" class="{{ request()->routeIs('admin.channels.*') ? 'active' : '' }}">Channels</a>
            @if(auth()->user()?->isAdmin())
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Users</a>
            @endif
            <a href="{{ route('admin.access-codes.create') }}" class="{{ request()->routeIs('admin.access-codes.create') ? 'active' : '' }}">Generate Codes</a>
            <a href="{{ route('admin.access-codes.index') }}" class="{{ request()->routeIs('admin.access-codes.index') ? 'active' : '' }}">View Codes</a>

            <div class="nav-user">
                <span style="color: var(--text-muted);">{{ auth()->user()->name }} <small>({{ auth()->user()->role }})</small></span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:0.875rem;font-weight:500;">Logout</button>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @yield('content')
    </div>
</body>
</html>
