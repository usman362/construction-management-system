<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Restricted Access</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 36px; max-width: 380px; width: 100%; box-shadow: 0 20px 50px rgba(0,0,0,0.4); }
        h1 { font-size: 20px; margin-bottom: 6px; color: #f1f5f9; }
        .sub { font-size: 13px; color: #94a3b8; margin-bottom: 24px; }
        label { display: block; font-size: 12px; font-weight: 600; color: #cbd5e1; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.05em; }
        input { width: 100%; padding: 10px 12px; background: #0f172a; border: 1px solid #475569; border-radius: 6px; color: #f1f5f9; font-size: 14px; margin-bottom: 16px; }
        input:focus { outline: none; border-color: #3b82f6; }
        button { width: 100%; padding: 11px; background: #2563eb; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        .error { background: #7f1d1d; color: #fecaca; padding: 10px 12px; border-radius: 6px; font-size: 13px; margin-bottom: 16px; }
        .footer { font-size: 11px; color: #64748b; text-align: center; margin-top: 18px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Restricted Access</h1>
        <p class="sub">This site is currently password-protected.</p>

        @if($error ?? null)
            <div class="error">{{ $error }}</div>
        @endif

        <form method="POST" action="{{ $redirectTo }}">
            {{-- No @csrf needed: this middleware runs before session/CSRF
                 middleware, so the token wouldn't generate. The POST is
                 fully intercepted by SiteGate before VerifyCsrfToken runs. --}}
            <input type="hidden" name="_gate" value="1">
            <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">

            <label for="gate_username">Username</label>
            <input type="text" name="gate_username" id="gate_username" required autofocus autocomplete="off">

            <label for="gate_password">Password</label>
            <input type="password" name="gate_password" id="gate_password" required autocomplete="off">

            <button type="submit">Enter Site</button>
        </form>

        <p class="footer">If you've been given access, the credentials were shared with you separately.</p>
    </div>
</body>
</html>
