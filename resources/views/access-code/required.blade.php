<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Required — MediaServer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --surface-0:#09090b; --surface-1:#121216; --surface-2:#18181d; --surface-3:#1e1e24;
            --text-primary:#f4f4f6; --text-secondary:#a1a1aa; --text-tertiary:#71717a;
            --brand:#6366f1; --brand-light:#818cf8; --brand-dim:#4f46e5; --brand-glow:rgba(99,102,241,0.25);
            --danger:#ef4444; --danger-dim:#7f1d1d; --success:#22c55e;
            --border:#27272a; --radius-sm:6px; --radius:10px; --radius-lg:14px;
            --font-sans:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
            --font-mono:'JetBrains Mono',monospace;
            --ease:cubic-bezier(0.4,0,0.2,1);
        }
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:var(--font-sans);background:var(--surface-0);color:var(--text-primary);min-height:100vh;display:flex;align-items:center;justify-content:center;-webkit-font-smoothing:antialiased}
        .card{background:var(--surface-1);border:1px solid var(--border);border-radius:var(--radius-lg);padding:40px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,0.5);animation:cardIn .5s cubic-bezier(.34,1.56,.64,1)}
        @keyframes cardIn{from{opacity:0;transform:translateY(20px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
        h1{text-align:center;font-size:22px;font-weight:800;letter-spacing:-.02em;margin-bottom:8px}
        .subtitle{text-align:center;font-size:14px;color:var(--text-tertiary);margin-bottom:24px}
        input{width:100%;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:11px 14px;color:var(--text-primary);font-size:14px;font-family:var(--font-mono);text-align:center;outline:none;transition:all .15s var(--ease)}
        input:focus{border-color:var(--brand);box-shadow:0 0 0 3px var(--brand-glow)}
        .btn{width:100%;padding:11px;background:var(--brand);color:white;border:none;border-radius:var(--radius-sm);font-size:14px;font-weight:700;cursor:pointer;margin-top:12px;transition:all .15s var(--ease);font-family:inherit}
        .btn:hover{background:var(--brand-dim)}
        .alert{padding:12px 14px;border-radius:var(--radius-sm);font-size:13px;margin-bottom:12px}
        .alert-danger{background:var(--danger-dim);color:#fca5a5;border:1px solid rgba(239,68,68,.2)}
        .alert-success{background:#166534;color:#86efac;border:1px solid rgba(34,197,94,.2)}
    </style>
</head>
<body>
<div class="card">
    <h1>Access Required</h1>
    <div class="subtitle">Enter your access code to continue</div>
    <div id="alert-box"></div>
    <form id="redeem-form" onsubmit="redeemCode(event)">
        @csrf
        <input type="text" id="code-input" placeholder="XXXX-XXXX-XXXX" maxlength="20" required autofocus>
        <button type="submit" class="btn">Redeem</button>
    </form>
</div>

<script>
async function redeemCode(e) {
    e.preventDefault();
    const code = document.getElementById('code-input').value.trim();
    const alertBox = document.getElementById('alert-box');
    if (!code) return;

    try {
        const resp = await fetch('/api/access-codes/redeem', {
            method: 'POST',
            headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},
            body: JSON.stringify({code: code})
        });
        const data = await resp.json();
        if (data.success) {
            alertBox.innerHTML = '<div class="alert alert-success">Code accepted! Redirecting...</div>';
            setTimeout(() => location.reload(), 1500);
        } else {
            alertBox.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Invalid code') + '</div>';
        }
    } catch(err) {
        alertBox.innerHTML = '<div class="alert alert-danger">Network error. Try again.</div>';
    }
}
</script>
</body>
</html>
