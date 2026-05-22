<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Admin'); ?> — MediaServer</title>
    <style>
        :root {
            --primary: #2563eb; --primary-dark: #1d4ed8;
            --danger: #dc2626; --success: #16a34a; --warning: #d97706;
            --bg: #0f172a; --sidebar: #1e293b; --card: #1e293b;
            --card2: #273549; --text: #f1f5f9; --muted: #94a3b8;
            --border: #334155; --live: #22c55e; --offline: #ef4444;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
               background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar { width: 240px; background: var(--sidebar); border-right: 1px solid var(--border);
                   display: flex; flex-direction: column; position: fixed; top: 0; left: 0; height: 100vh; overflow-y: auto; }
        .sidebar-logo { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); }
        .sidebar-logo span { font-size: 1.1rem; font-weight: 700; color: var(--text); }
        .sidebar-logo small { display: block; font-size: 0.7rem; color: var(--muted); margin-top: 2px; }
        .sidebar-section { padding: 0.75rem 1rem 0.25rem; font-size: 0.65rem; font-weight: 700;
                           text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); }
        .sidebar nav a {
            display: flex; align-items: center; gap: 0.625rem;
            padding: 0.6rem 1.5rem; color: var(--muted); text-decoration: none;
            font-size: 0.875rem; font-weight: 500; transition: all 0.15s;
        }
        .sidebar nav a:hover { color: var(--text); background: rgba(255,255,255,0.05); }
        .sidebar nav a.active { color: #fff; background: var(--primary); border-radius: 0; }
        .sidebar nav a .icon { width: 16px; text-align: center; }
        .sidebar-bottom { margin-top: auto; padding: 1rem 1.5rem; border-top: 1px solid var(--border); }
        .sidebar-bottom a { color: var(--muted); font-size: 0.8rem; text-decoration: none; }

        /* Main */
        .main { margin-left: 240px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
        .topbar { background: var(--sidebar); border-bottom: 1px solid var(--border);
                  padding: 0.875rem 2rem; display: flex; align-items: center; justify-content: space-between; }
        .topbar h1 { font-size: 1.1rem; font-weight: 600; }
        .topbar-actions { display: flex; gap: 0.75rem; align-items: center; }
        .content { padding: 2rem; flex: 1; }

        /* Cards */
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 10px;
                padding: 1.5rem; margin-bottom: 1.5rem; }
        .card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
        .card-title { font-size: 1rem; font-weight: 600; }

        /* Stats grid */
        .stats { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 1.25rem; }
        .stat-value { font-size: 2rem; font-weight: 700; line-height: 1; }
        .stat-label { font-size: 0.75rem; color: var(--muted); margin-top: 0.375rem; }
        .stat-live .stat-value { color: var(--live); }
        .stat-offline .stat-value { color: var(--offline); }

        /* Table */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        th { padding: 0.625rem 0.875rem; text-align: left; font-size: 0.7rem; font-weight: 700;
             text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted);
             border-bottom: 1px solid var(--border); }
        td { padding: 0.75rem 0.875rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,0.02); }

        /* Badges */
        .badge { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.2rem 0.6rem;
                 border-radius: 9999px; font-size: 0.7rem; font-weight: 600; }
        .badge-live { background: rgba(34,197,94,0.15); color: #4ade80; }
        .badge-offline { background: rgba(239,68,68,0.15); color: #f87171; }
        .badge-fallback { background: rgba(234,179,8,0.15); color: #fbbf24; }
        .badge-active { background: rgba(37,99,235,0.15); color: #60a5fa; }
        .badge-stopped { background: rgba(100,116,139,0.15); color: #94a3b8; }
        .badge-error { background: rgba(239,68,68,0.15); color: #f87171; }
        .dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
        .dot-live { background: var(--live); box-shadow: 0 0 6px var(--live); animation: pulse 2s infinite; }
        .dot-offline { background: var(--offline); }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }

        /* Forms */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }
        .form-full { grid-column: 1 / -1; }
        .form-group { display: flex; flex-direction: column; gap: 0.375rem; }
        label { font-size: 0.8rem; font-weight: 600; color: var(--muted); }
        input, select, textarea {
            background: var(--bg); border: 1px solid var(--border); border-radius: 7px;
            padding: 0.6rem 0.875rem; color: var(--text); font-size: 0.875rem; width: 100%;
        }
        input:focus, select:focus, textarea:focus {
            outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,0.2);
        }
        textarea { resize: vertical; min-height: 80px; }
        .hint { font-size: 0.72rem; color: var(--muted); }

        /* Buttons */
        .btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1.1rem;
               border: none; border-radius: 7px; font-size: 0.875rem; font-weight: 600;
               cursor: pointer; text-decoration: none; transition: all 0.15s; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-success { background: #16a34a; color: #fff; }
        .btn-success:hover { background: #15803d; }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-ghost { background: transparent; color: var(--muted); border: 1px solid var(--border); }
        .btn-ghost:hover { color: var(--text); border-color: var(--text); }
        .btn-sm { padding: 0.35rem 0.75rem; font-size: 0.78rem; }
        .btn-icon { padding: 0.4rem; }

        /* Alerts */
        .alert { padding: 0.875rem 1.125rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.875rem; }
        .alert-success { background: rgba(22,163,74,0.15); color: #4ade80; border: 1px solid rgba(22,163,74,0.3); }
        .alert-danger  { background: rgba(220,38,38,0.15); color: #f87171; border: 1px solid rgba(220,38,38,0.3); }
        .alert-warning { background: rgba(217,119,6,0.15); color: #fbbf24; border: 1px solid rgba(217,119,6,0.3); }

        /* Misc */
        .actions { display: flex; gap: 0.5rem; }
        .empty { text-align: center; padding: 3rem; color: var(--muted); }
        .empty-icon { font-size: 2.5rem; margin-bottom: 0.75rem; }
        .mono { font-family: 'SF Mono', Monaco, monospace; font-size: 0.8rem; }
        .text-muted { color: var(--muted); }
        .text-sm { font-size: 0.8rem; }
        .flex { display: flex; align-items: center; gap: 0.5rem; }
        .gap-1 { gap: 0.25rem; }
        .mt-1 { margin-top: 0.5rem; }
        .pagination { display: flex; gap: 0.5rem; justify-content: center; margin-top: 1.5rem; }
        .pagination a, .pagination span { padding: 0.4rem 0.75rem; border-radius: 6px; font-size: 0.8rem;
            border: 1px solid var(--border); color: var(--muted); text-decoration: none; }
        .pagination .active { background: var(--primary); color: #fff; border-color: var(--primary); }
    </style>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-logo">
        <span>📡 MediaServer</span>
        <small>v<?php echo e(config('app.version', '1.2.0')); ?></small>
    </div>

    <div class="sidebar-section">Streaming</div>
    <nav>
        <a href="<?php echo e(route('admin.channels.index')); ?>" class="<?php echo e(request()->routeIs('admin.channels.*') ? 'active' : ''); ?>">
            <span class="icon">📺</span> Channels
        </a>
    </nav>

    <div class="sidebar-section">Access</div>
    <nav>
        <a href="<?php echo e(route('admin.access-codes.index')); ?>" class="<?php echo e(request()->routeIs('admin.access-codes.*') ? 'active' : ''); ?>">
            <span class="icon">🔑</span> Access Codes
        </a>
    </nav>

    <div class="sidebar-bottom">
        <a href="<?php echo e(url('/api/health')); ?>" target="_blank">API Health ↗</a>
    </div>
</aside>

<div class="main">
    <div class="topbar">
        <h1><?php echo $__env->yieldContent('title', 'Dashboard'); ?></h1>
        <div class="topbar-actions">
            <a href="<?php echo e(route('admin.channels.create')); ?>" class="btn btn-primary btn-sm">+ New Channel</a>
        </div>
    </div>

    <div class="content">
        <?php if(session('success')): ?>
            <div class="alert alert-success"><?php echo e(session('success')); ?></div>
        <?php endif; ?>
        <?php if(session('error')): ?>
            <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="alert alert-danger">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> <?php echo e($e); ?><br> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <?php echo $__env->yieldContent('content'); ?>
    </div>
</div>

<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\Users\FT_Basil\Documents\streaming\media-server\resources\views/layouts/admin.blade.php ENDPATH**/ ?>