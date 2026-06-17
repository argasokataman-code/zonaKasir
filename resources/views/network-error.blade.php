<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Koneksi Terputus — {{ config('app.name') }}</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #f8fafc;
      color: #1f2937;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .card {
      background: #fff;
      border-radius: 16px;
      padding: 40px 32px;
      text-align: center;
      max-width: 400px;
      width: 100%;
      box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    }
    .icon {
      width: 64px;
      height: 64px;
      background: #FEF3C7;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      font-size: 32px;
    }
    h1 {
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 8px;
    }
    p {
      font-size: 14px;
      color: #6b7280;
      line-height: 1.6;
      margin-bottom: 24px;
    }
    .btn {
      display: inline-block;
      background: #FF6600;
      color: #fff;
      border: none;
      padding: 12px 24px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }
    .btn:hover { background: #E55A00; }
    .hint {
      margin-top: 16px;
      font-size: 12px;
      color: #9ca3af;
    }
    .pwa-hint {
      display: none;
      margin-top: 12px;
      padding: 12px;
      background: #F0FDF4;
      border-radius: 8px;
      font-size: 12px;
      color: #166534;
    }
    .web-hint {
      display: block;
      margin-top: 12px;
      padding: 12px;
      background: #F0FDF4;
      border-radius: 8px;
      font-size: 12px;
      color: #166534;
    }
    @media (display-mode: standalone) {
      .web-hint { display: none !important; }
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="icon">📡</div>
    <h1>Koneksi Terputus</h1>
    <p>Periksa koneksi internet kamu, lalu coba lagi.</p>
    <button class="btn" onclick="window.location.reload()">Coba Lagi</button>
    <p class="hint">Pastikan WiFi atau data seluler aktif.</p>

    <div class="pwa-hint web-hint">
      <p style="margin:0;color:#166534;font-size:12px;">
        💡 <strong>Tip:</strong> Install ZonaKasir sebagai PWA untuk tetap bisa jualan saat offline.
      </p>
    </div>
  </div>

  <script>
    // Auto-retry when back online
    window.addEventListener('online', function() {
      window.location.reload();
    });
  </script>
</body>
</html>
