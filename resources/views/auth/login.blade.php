<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — MediaServer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --surface-0: #09090b; --surface-1: #121216; --surface-2: #18181d;
            --surface-3: #1e1e24; --surface-4: #25252c;
            --text-primary: #f4f4f6; --text-secondary: #a1a1aa; --text-tertiary: #71717a;
            --brand: #6366f1; --brand-light: #818cf8; --brand-dim: #4f46e5;
            --brand-glow: rgba(99,102,241,0.25);
            --danger: #ef4444; --danger-dim: #7f1d1d;
            --border: #27272a; --border-light: #3f3f46;
            --radius-sm: 6px; --radius: 10px; --radius-lg: 14px;
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --ease: cubic-bezier(0.4, 0, 0.2, 1);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: var(--font-sans);
            background: var(--surface-0);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            -webkit-font-smoothing: antialiased;
        }
        .bg-grid {
            position: fixed; inset: 0; pointer-events: none;
            background-image:
                linear-gradient(var(--border) 1px, transparent 1px),
                linear-gradient(90deg, var(--border) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
        }
        .bg-glow {
            position: fixed; pointer-events: none;
            width: 600px; height: 600px;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            background: radial-gradient(circle, var(--brand-glow) 0%, transparent 70%);
            opacity: 0.5;
        }
        .login-card {
            background: var(--surface-1);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 40px;
            width: 100%; max-width: 420px;
            position: relative; z-index: 1;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            animation: cardIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(20px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .login-logo {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, var(--brand), #a855f7);
            border-radius: var(--radius);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; font-weight: 800; color: white;
            margin: 0 auto 20px;
            box-shadow: 0 8px 24px var(--brand-glow);
        }
        .login-title {
            text-align: center; font-size: 24px; font-weight: 800;
            letter-spacing: -0.02em; margin-bottom: 6px;
        }
        .login-subtitle {
            text-align: center; font-size: 14px; color: var(--text-tertiary);
            margin-bottom: 28px;
        }
        label {
            display: block; font-size: 13px; font-weight: 600;
            color: var(--text-secondary); margin-bottom: 5px;
        }
        input[type="email"], input[type="password"], input[type="text"] {
            width: 100%; background: var(--surface-2); border: 1px solid var(--border);
            border-radius: var(--radius-sm); padding: 11px 14px; color: var(--text-primary);
            font-size: 14px; font-family: var(--font-sans);
            transition: all 0.15s var(--ease); outline: none;
            margin-bottom: 16px;
        }
        input:focus { border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-glow); }
        input::placeholder { color: var(--text-tertiary); }
        .remember-row {
            display: flex; align-items: center; gap: 8px; margin-bottom: 20px; font-size: 13px;
            color: var(--text-secondary);
        }
        input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--brand); }
        .btn-login {
            width: 100%; padding: 12px; background: var(--brand); color: white;
            border: none; border-radius: var(--radius-sm); font-size: 15px; font-weight: 700;
            cursor: pointer; transition: all 0.15s var(--ease); letter-spacing: 0.01em;
        }
        .btn-login:hover { background: var(--brand-dim); box-shadow: 0 6px 20px var(--brand-glow); }
        .btn-login:active { transform: scale(0.98); }
        .alert {
            padding: 12px 14px; border-radius: var(--radius-sm); margin-bottom: 16px;
            font-size: 13px; display: flex; align-items: center; gap: 8px;
        }
        .alert-error { background: var(--danger-dim); color: #fca5a5; border: 1px solid rgba(239,68,68,0.2); }
        .alert-success { background: #166534; color: #86efac; border: 1px solid rgba(34,197,94,0.2); }
        .footer-links {
            text-align: center; margin-top: 20px; font-size: 13px; color: var(--text-tertiary);
        }
        .footer-links a { color: var(--brand-light); text-decoration: none; font-weight: 500; }
        .footer-links a:hover { color: var(--brand); }
    </style>
</head>
<body>
<div class="bg-grid"></div>
<div class="bg-glow"></div>

<div class="login-card">
    <div class="login-logo">M</div>
    <div class="login-title">MediaServer</div>
    <div class="login-subtitle">Professional streaming platform</div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">
            @foreach($errors->all() as $e){{ $e }}<br>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label>Email address</label>
        <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus>

        <label>Password</label>
        <input type="password" name="password" placeholder="Enter your password" required>

        <div class="remember-row">
            <input type="checkbox" name="remember" id="remember">
            <label for="remember" style="margin-bottom:0;cursor:pointer;">Keep me signed in</label>
        </div>

        <button type="submit" class="btn-login">Sign In</button>
    </form>

    <div class="footer-links">
        <a href="{{ route('register') }}">Create account</a>
        &nbsp;&bull;&nbsp;
        <a href="{{ route('pricing') }}">View plans</a>
    </div>
</div>
</body>
</html>
