<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOD Manager — {{ $channel->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a; color: #f8fafc;
            display: flex; align-items: center; justify-content: center; min-height: 100vh;
        }
        .box {
            background: #1e293b; border: 1px solid #334155;
            border-radius: 12px; padding: 2.5rem; width: 100%; max-width: 420px;
        }
        .logo { font-size: 2rem; margin-bottom: 0.5rem; }
        h1 { font-size: 1.4rem; margin-bottom: 0.25rem; }
        .sub { color: #94a3b8; font-size: 0.9rem; margin-bottom: 2rem; }
        label { display: block; font-size: 0.85rem; color: #94a3b8; margin-bottom: 0.4rem; }
        input {
            width: 100%; padding: 0.75rem 1rem;
            border: 1px solid #334155; border-radius: 8px;
            background: #0f172a; color: #fff; font-size: 1rem;
            text-align: center; letter-spacing: 0.15em; margin-bottom: 1rem;
        }
        input:focus { outline: none; border-color: #3b82f6; }
        button {
            width: 100%; padding: 0.8rem; border: none; border-radius: 8px;
            background: #3b82f6; color: #fff; font-size: 1rem;
            font-weight: 600; cursor: pointer;
        }
        button:hover { background: #2563eb; }
        .error { color: #f87171; font-size: 0.85rem; margin-bottom: 1rem; padding: 0.6rem 0.8rem; background: rgba(239,68,68,0.1); border-radius: 6px; }
        .success { color: #4ade80; font-size: 0.85rem; margin-bottom: 1rem; padding: 0.6rem 0.8rem; background: rgba(34,197,94,0.1); border-radius: 6px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="logo">🎬</div>
        <h1>VOD Manager</h1>
        <p class="sub">{{ $channel->name }} — enter your access code to manage videos</p>

        @if(session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('vod-manager.login.post', $channel) }}">
            @csrf
            <label>Access Code</label>
            <input type="text" name="code" placeholder="XXXX-XXXX-XXXX"
                   maxlength="20" autocomplete="off" autofocus
                   value="{{ old('code') }}">
            <button type="submit">Unlock</button>
        </form>
    </div>
</body>
</html>
