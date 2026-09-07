{{-- Offline fallback: standalone, zero backend dependency so it always renders. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f172a">
    <title>Mining ERP - Offline</title>
    <link rel="icon" href="/icons/icon-192.png">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px;
            background: #f1f5f9; color: #1e293b; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; }
        .card { width: 100%; max-width: 420px; background: #fff; border: 1px solid #e2e8f0;
            border-radius: 16px; padding: 32px 28px; text-align: center; box-shadow: 0 8px 30px rgb(15 23 42 / .08); }
        .mark { width: 64px; height: 64px; border-radius: 16px; margin: 0 auto 16px; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        p { font-size: 14px; color: #64748b; margin: 0 0 20px; line-height: 1.6; }
        button { min-height: 44px; padding: 0 24px; border: 0; border-radius: 10px; cursor: pointer;
            background: #0f172a; color: #fff; font-size: 14px; font-weight: 600; }
        button:active { transform: scale(.98); }
        @media (prefers-color-scheme: dark) {
            body { background: #080d16; color: #e2e8f0; }
            .card { background: #141c2b; border-color: #263246; }
            p { color: #94a3b8; }
            button { background: #f59e0b; color: #0f172a; }
        }
    </style>
</head>
<body>
    <div class="card">
        <img class="mark" src="/icons/icon-192.png" alt="Mining ERP" width="64" height="64">
        <h1>Anda sedang offline</h1>
        <p>Tidak ada koneksi ke server Mining ERP. Data yang sudah tersimpan aman — coba lagi setelah koneksi pulih.</p>
        <button type="button" onclick="window.location.reload()">Coba Lagi</button>
    </div>
</body>
</html>
