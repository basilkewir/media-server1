<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOD Manager Login — {{ $channel->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --surface-0:#09090b; --surface-1:#121216; --surface-2:#18181d; --surface-3:#1e1e24; --surface-4:#25252c;
            --text-primary:#f4f4f6; --text-secondary:#a1a1aa; --text-tertiary:#71717a;
            --brand:#6366f1; --brand-light:#818cf8; --brand-dim:#4f46e5; --brand-glow:rgba(99,102,241,0.25);
            --danger:#ef4444; --danger-dim:#7f1d1d; --success:#22c55e; --success-dim:#166534;
            --border:#27272a; --radius-sm:6px; --radius:10px; --radius-lg:14px;
            --font-sans:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
            --font-mono:'JetBrains Mono',monospace;
            --ease:cubic-bezier(0.4,0,0.2,1);
        }
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:var(--font-sans);background:var(--surface-0);color:var(--text-primary);min-height:100vh;display:flex;align-items:center;justify-content:center;-webkit-font-smoothing:antialiased}
        .card{background:var(--surface-1);border:1px solid var(--border);border-radius:var(--radius-lg);padding:36px;width:100%;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,0.5);animation:cardIn .5s cubic-bezier(.34,1.56,.64,1)}
        @keyframes cardIn{from{opacity:0;transform:translateY(20px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
        .logo{width:48px;height:48px;background:linear-gradient(135deg,var(--brand),#a855f7);border-radius:var(--radius);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:white;margin:0 auto 16px;box-shadow:0 8px 24px var(--brand-glow)}
        h1{text-align:center;font-size:20px;font-weight:800;letter-spacing:-.02em;margin-bottom:4px}
        .subtitle{text-align:center;font-size:14px;color:var(--text-tertiary);margin-bottom:24px}
        input{width:100%;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:11px 14px;color:var(--text-primary);font-size:14px;font-family:var(--font-mono);letter-spacing:.05em;text-align:center;outline:none;transition:all .15s var(--ease)}
        input:focus{border-color:var(--brand);box-shadow:0 0 0 3px var(--brand-glow)}
        input::placeholder{color:var(--text-tertiary);font-family:var(--font-sans);letter-spacing:0}
        .btn{width:100%;padding:11px;background:var(--brand);color:white;border:none;border-radius:var(--radius-sm);font-size:14px;font-weight:700;cursor:pointer;margin-top:12px;transition:all .15s var(--ease);font-family:inherit}
        .btn:hover{background:var(--brand-dim);box-shadow:0 6px 20px var(--brand-glow)}
        .alert{padding:12px 14px;border-radius:var(--radius-sm);font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:8px}
        .alert-success{background:var(--success-dim);color:#86efac;border:1px solid rgba(34,197,94,.2)}
        .alert-danger{background:var(--danger-dim);color:#fca5a5;border:1px solid rgba(239,68,68,.2)}
    </style>
</head>
<body>
<div class="card">
    <div class="logo">V</div>
    <h1>VOD Manager</h1>
    <div class="subtitle">{{ $channel->name }}</div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger">@foreach($errors->all() as $e){{ $e }}<br>@endforeach</div>
    @endif

    <form method="POST" action="{{ route('vod-manager.login.post', $channel) }}">
        @csrf
        <input type="text" name="code" placeholder="XXXX-XXXX-XXXX" maxlength="20" required autofocus>
        <button type="submit" class="btn">Unlock</button>
    </form>
</div>
</body>
</html>
