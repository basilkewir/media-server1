<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Required - MediaServer</title>
    <style>
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .box {
            text-align: center;
            padding: 2rem;
            max-width: 420px;
        }
        h1 { font-size: 1.5rem; margin-bottom: 0.5rem; }
        p { color: #94a3b8; line-height: 1.6; }
        input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid #334155;
            border-radius: 8px;
            background: #1e293b;
            color: #fff;
            font-size: 1rem;
            text-align: center;
            letter-spacing: 0.1em;
            margin: 1rem 0;
        }
        input:focus { outline: none; border-color: #3b82f6; }
        button {
            width: 100%;
            padding: 0.875rem;
            border: none;
            border-radius: 8px;
            background: #3b82f6;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover { background: #2563eb; }
        .error { color: #ef4444; margin-top: 0.75rem; font-size: 0.875rem; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Subscription Required</h1>
        <p>This stream requires a valid access code. Enter your subscription code below to continue watching.</p>

        <form id="redeem-form">
            <input type="text" id="code" name="code" placeholder="XXXX-XXXX-XXXX" maxlength="20" autocomplete="off">
            <button type="submit">Unlock Stream</button>
            <div class="error" id="error"></div>
        </form>
    </div>

    <script>
    document.getElementById('redeem-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const code = document.getElementById('code').value.trim().replace(/-/g, '');
        const errorDiv = document.getElementById('error');
        errorDiv.textContent = '';

        if (code.length < 8) {
            errorDiv.textContent = 'The code field must be at least 8 characters.';
            return;
        }

        try {
            const res = await fetch('/api/access-codes/redeem', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ code }),
            });

            const data = await res.json();

            if (data.success) {
                window.location.reload();
            } else {
                errorDiv.textContent = data.message || 'Invalid code. Please try again.';
            }
        } catch (err) {
            errorDiv.textContent = 'Network error. Please try again.';
        }
    });
    </script>
</body>
</html>
