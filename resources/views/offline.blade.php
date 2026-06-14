<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#FF6600">
  <title>Offline — {{ config('app.name') }}</title>
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
      text-align: center;
    }
    .btn-primary { background: #FF6600; color: #fff; }
    .btn-primary:disabled { background: #ccc; cursor: not-allowed; }
    .btn-secondary { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
    .actions { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }
    .sync-log {
      background: #1f2937;
      color: #d1d5db;
      border-radius: 8px;
      padding: 12px;
      font-size: 12px;
      font-family: monospace;
      max-height: 150px;
      overflow-y: auto;
      margin-top: 12px;
      display: none;
    }
    .footer {
      text-align: center;
      padding: 16px;
      font-size: 12px;
      color: #9ca3af;
    }
    #connection-status { font-size: 12px; margin-top: 8px; }
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
        <span class="label">Last sync</span>
        <span class="value" id="last-sync">—</span>
      </div>
      <div class="status-row">
        <span class="label">Pending transactions</span>
        <span class="value" id="pending-sales">0</span>
      </div>
      <div class="status-row">
        <span class="label">Pending operations</span>
        <span class="value" id="pending-ops">0</span>
      </div>
    </div>

    <div class="status-card">
      <h2>Cached Data</h2>
      <div class="status-row">
        <span class="label">Products</span>
        <span class="value" id="cached-products">0</span>
      </div>
      <div class="status-row">
        <span class="label">Categories</span>
        <span class="value" id="cached-categories">0</span>
      </div>
      <div class="status-row">
        <span class="label">Members</span>
        <span class="value" id="cached-members">0</span>
      </div>
      <div class="status-row">
        <span class="label">Payment methods</span>
        <span class="value" id="cached-payment">0</span>
      </div>
    </div>

    <div class="actions">
      <button class="btn btn-primary" id="retry-btn" disabled>Retry Connection</button>
      <button class="btn btn-secondary" id="view-cached-btn">View Cached Products</button>
      <button class="btn btn-secondary" id="refresh-btn">Refresh Data</button>
    </div>

    <div class="sync-log" id="sync-log"></div>
  </div>

  <div class="footer">
    {{ config('app.name') }} — Offline Mode
  </div>

  <script>
  (function() {
    var db = null;
    var DB_NAME = 'zonakasir_offline';
    var DB_VERSION = 2;

    function openDB() {
      return new Promise(function(resolve, reject) {
        var req = indexedDB.open(DB_NAME, DB_VERSION);
        req.onsuccess = function(e) { resolve(e.target.result); };
        req.onerror = function(e) { reject(e.target.error); };
        req.onupgradeneeded = function(e) {
          var db = e.target.result;
          if (!db.objectStoreNames.contains('products')) {
            db.createObjectStore('products', { keyPath: 'id' });
          }
          if (!db.objectStoreNames.contains('categories')) {
            db.createObjectStore('categories', { keyPath: 'id' });
          }
          if (!db.objectStoreNames.contains('members')) {
            db.createObjectStore('members', { keyPath: 'id' });
          }
          if (!db.objectStoreNames.contains('payment_methods')) {
            db.createObjectStore('payment_methods', { keyPath: 'id' });
          }
          if (!db.objectStoreNames.contains('about')) {
            db.createObjectStore('about', { keyPath: 'id' });
          }
          if (!db.objectStoreNames.contains('settings')) {
            db.createObjectStore('settings', { keyPath: 'key' });
          }
          if (!db.objectStoreNames.contains('pending_sales')) {
            var ps = db.createObjectStore('pending_sales', { keyPath: 'temp_id' });
            ps.createIndex('status', 'status');
          }
          if (!db.objectStoreNames.contains('queued_operations')) {
            var qo = db.createObjectStore('queued_operations', { keyPath: 'op_id' });
            qo.createIndex('type', 'type');
            qo.createIndex('status', 'status');
          }
          if (!db.objectStoreNames.contains('api_cache')) {
            db.createObjectStore('api_cache', { keyPath: 'url' });
          }
          if (!db.objectStoreNames.contains('meta')) {
            db.createObjectStore('meta', { keyPath: 'key' });
          }
        };
      });
    }

    function countStore(storeName) {
      return new Promise(function(resolve) {
        try {
          var tx = db.transaction(storeName, 'readonly');
          var req = tx.objectStore(storeName).count();
          req.onsuccess = function() { resolve(req.result); };
          req.onerror = function() { resolve(0); };
        } catch(e) { resolve(0); }
      });
    }

    function getAll(storeName) {
      return new Promise(function(resolve) {
        try {
          var tx = db.transaction(storeName, 'readonly');
          var req = tx.objectStore(storeName).getAll();
          req.onsuccess = function() { resolve(req.result); };
          req.onerror = function() { resolve([]); };
        } catch(e) { resolve([]); }
      });
    }

    async function refreshStats() {
      if (!db) return;
      document.getElementById('cached-products').textContent = await countStore('products');
      document.getElementById('cached-categories').textContent = await countStore('categories');
      document.getElementById('cached-members').textContent = await countStore('members');
      document.getElementById('cached-payment').textContent = await countStore('payment_methods');

      try {
        var sales = await getAll('pending_sales');
        document.getElementById('pending-sales').textContent = sales.filter(function(s) { return s.status === 'pending'; }).length;
      } catch(e) {
        document.getElementById('pending-sales').textContent = '0';
      }

      try {
        var ops = await getAll('queued_operations');
        document.getElementById('pending-ops').textContent = ops.filter(function(o) { return o.status === 'pending'; }).length;
      } catch(e) {
        document.getElementById('pending-ops').textContent = '0';
      }

      try {
        var tx = db.transaction('meta', 'readonly');
        var req = tx.objectStore('meta').get('last_prefetch');
        req.onsuccess = function() {
          var val = req.result?.value;
          document.getElementById('last-sync').textContent = val ? new Date(val).toLocaleString() : '—';
        };
      } catch(e) {}
    }

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

    function log(msg) {
      var el = document.getElementById('sync-log');
      el.style.display = 'block';
      var line = document.createElement('div');
      line.textContent = '[' + new Date().toLocaleTimeString() + '] ' + msg;
      el.appendChild(line);
      el.scrollTop = el.scrollHeight;
    }

    openDB().then(function(database) {
      db = database;
      refreshStats();
      updateConnection();
    });

    window.addEventListener('online', function() {
      updateConnection();
      log('Connection restored — syncing...');
      document.getElementById('conn-dot').className = 'dot dot-yellow';
      document.getElementById('conn-status').textContent = 'Reconnecting...';

      setTimeout(function() {
        refreshStats();
        updateConnection();
        log('Sync complete');
      }, 2000);
    });

    window.addEventListener('offline', function() {
      updateConnection();
      log('Connection lost');
    });

    document.getElementById('retry-btn').addEventListener('click', function() {
      if (navigator.onLine) {
        log('Retrying...');
        window.location.reload();
      }
    });

    document.getElementById('view-cached-btn').addEventListener('click', function() {
      if (!db) return;
      getAll('products').then(function(products) {
        if (products.length === 0) {
          alert('No cached products available');
          return;
        }
        var html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Products — Offline</title>';
        html += '<style>body{font-family:sans-serif;padding:16px;max-width:600px;margin:0 auto;}';
        html += '.p{border:1px solid #e5e7eb;border-radius:8px;padding:12px;margin-bottom:8px;}';
        html += '.p h3{font-size:14px;margin-bottom:4px;} .p span{color:#6b7280;font-size:12px;}';
        html += 'h1{font-size:18px;margin-bottom:12px;} .back{color:#FF6600;text-decoration:none;font-size:13px;}</style></head>';
        html += '<body><a class="back" href="/offline">&larr; Back</a><h1>Cached Products</h1>';
        products.forEach(function(p) {
          html += '<div class="p"><h3>' + (p.name || '—') + '</h3><span>' + (p.sku || p.barcode || '') + ' &bull; Rp ' + (p.selling_price || 0).toLocaleString() + '</span></div>';
        });
        html += '</body></html>';

        var blob = new Blob([html], { type: 'text/html' });
        var url = URL.createObjectURL(blob);
        window.location.href = url;
      });
    });

    document.getElementById('refresh-btn').addEventListener('click', function() {
      if (!navigator.onLine) {
        alert('Cannot refresh while offline');
        return;
      }
      log('Refreshing data from server...');
      window.location.reload();
    });
  })();
  </script>
</body>
</html>
