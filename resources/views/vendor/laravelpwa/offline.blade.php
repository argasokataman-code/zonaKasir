<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#FF6600">
  <title>Offline — {{ config('app.name') }}</title>
  <link rel="manifest" href="{{ route('laravelpwa.manifest') }}">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #f8fafc;
      color: #1f2937;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    .header {
      background: #FF6600;
      color: #fff;
      padding: 20px;
      text-align: center;
    }
    .header h1 { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
    .header p { font-size: 13px; opacity: 0.85; }
    .content { flex: 1; padding: 16px; max-width: 600px; margin: 0 auto; width: 100%; }
    .status-card {
      background: #fff;
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 12px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }
    .status-card h2 { font-size: 14px; color: #6b7280; margin-bottom: 12px; font-weight: 600; }
    .status-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      border-bottom: 1px solid #f3f4f6;
      font-size: 14px;
    }
    .status-row:last-child { border-bottom: none; }
    .status-row .label { color: #6b7280; }
    .status-row .value { font-weight: 600; }
    .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
    .dot-red { background: #ef4444; }
    .dot-green { background: #22c55e; }
    .dot-yellow { background: #f59e0b; }
    .btn {
      display: inline-block;
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      border: none;
      text-decoration: none;
    }
    .btn-primary { background: #FF6600; color: #fff; }
    .btn-primary:disabled { background: #ccc; cursor: not-allowed; }
    .btn-secondary { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
    .actions { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }
    .footer { text-align: center; padding: 16px; font-size: 12px; color: #9ca3af; }
  </style>
</head>
<body>
  <div class="header">
    <h1>{{ config('app.name') }}</h1>
    <p>You are currently offline</p>
  </div>

  <div class="content">
    <div class="status-card">
      <h2>Connection Status</h2>
      <div class="status-row">
        <span class="label"><span class="dot dot-red" id="conn-dot"></span>Network</span>
        <span class="value" id="conn-status">Offline</span>
      </div>
      <div class="status-row">
        <span class="label">Pending transactions</span>
        <span class="value" id="pending-count">0</span>
      </div>
    </div>

    <div class="actions">
      <button class="btn btn-primary" id="retry-btn" disabled>Retry Connection</button>
      <button class="btn btn-secondary" onclick="window.location.href='/member'">Go to App</button>
    </div>
  </div>

  <div class="footer">{{ config('app.name') }} — Offline Mode</div>

  <script>
  (function() {
    function updateConnection() {
      var dot = document.getElementById('conn-dot');
      var status = document.getElementById('conn-status');
      var retryBtn = document.getElementById('retry-btn');
      if (navigator.onLine) {
        dot.className = 'dot dot-green';
        status.textContent = 'Online';
        retryBtn.disabled = false;
      } else {
        dot.className = 'dot dot-red';
        status.textContent = 'Offline';
        retryBtn.disabled = true;
      }
    }

    function countPending() {
      try {
        var req = indexedDB.open('zonakasir_offline', 2);
        req.onsuccess = function(e) {
          var db = e.target.result;
          if (!db.objectStoreNames.contains('pending_sales')) return;
          var tx = db.transaction('pending_sales', 'readonly');
          var count = 0;
          tx.objectStore('pending_sales').openCursor().onsuccess = function(ev) {
            var cursor = ev.target.result;
            if (cursor) {
              if (cursor.value.status === 'pending') count++;
              cursor.continue();
            } else {
              document.getElementById('pending-count').textContent = count;
            }
          };
        };
      } catch(e) {}
    }

    updateConnection();
    countPending();

    window.addEventListener('online', function() {
      updateConnection();
      setTimeout(function() { window.location.reload(); }, 1500);
    });
    window.addEventListener('offline', updateConnection);

    document.getElementById('retry-btn').addEventListener('click', function() {
      if (navigator.onLine) window.location.reload();
    });
  })();
  </script>
</body>
</html>
