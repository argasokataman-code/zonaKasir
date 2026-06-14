@php
  $colorPrimary = '#FF6600';
  $appName = config('app.name', 'zonaKasir');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <meta name="theme-color" content="{{ $colorPrimary }}">
  <meta name="description" content="Offline Point of Sale — {{ $appName }}">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <title>Offline POS — {{ $appName }}</title>
  <link rel="manifest" href="{{ route('laravelpwa.manifest') }}">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #1f2937; min-height: 100vh; }

    /* Header */
    .header { background: {{ $colorPrimary }}; color: #fff; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
    .header h1 { font-size: 16px; font-weight: 700; }
    .header .sync-badge { background: rgba(255,255,255,0.2); border-radius: 20px; padding: 4px 12px; font-size: 11px; display: flex; align-items: center; gap: 4px; cursor: pointer; }
    .header .sync-badge.syncing { animation: pulse 1s infinite; }

    /* Container */
    .container { display: flex; flex-direction: column; height: calc(100vh - 52px); }

    /* Product section */
    .product-section { flex: 1; overflow-y: auto; padding: 8px; }

    /* Search */
    .search-bar { position: relative; margin-bottom: 8px; }
    .search-bar input { width: 100%; padding: 10px 12px 10px 36px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 14px; outline: none; background: #fff; }
    .search-bar input:focus { border-color: {{ $colorPrimary }}; box-shadow: 0 0 0 2px rgba(255,102,0,0.15); }
    .search-bar .icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; }

    /* Categories */
    .categories { display: flex; gap: 6px; overflow-x: auto; margin-bottom: 8px; padding: 2px 0; -webkit-overflow-scrolling: touch; }
    .category-btn { white-space: nowrap; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 500; border: none; cursor: pointer; transition: all 0.15s; background: #e5e7eb; color: #4b5563; flex-shrink: 0; }
    .category-btn.active { background: {{ $colorPrimary }}; color: #fff; }

    /* Product grid */
    .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px; }
    .product-card { background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); cursor: pointer; transition: transform 0.1s, box-shadow 0.1s; position: relative; }
    .product-card:active { transform: scale(0.97); }
    .product-card .img { aspect-ratio: 4/3; background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #d1d5db; font-size: 24px; position: relative; }
    .product-card .img img { width: 100%; height: 100%; object-fit: cover; }
    .product-card .img .out-of-stock { position: absolute; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 11px; font-weight: 700; }
    .product-card .img .cart-qty { position: absolute; top: 6px; right: 6px; background: {{ $colorPrimary }}; color: #fff; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; }
    .product-card .info { padding: 8px; }
    .product-card .info .sku { font-size: 10px; color: #9ca3af; }
    .product-card .info .name { font-size: 13px; font-weight: 600; line-height: 1.3; margin: 2px 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .product-card .info .price { font-size: 14px; font-weight: 700; color: {{ $colorPrimary }}; }

    /* Empty state */
    .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px; color: #9ca3af; }
    .empty-state svg { width: 48px; height: 48px; margin-bottom: 12px; }
    .empty-state p { font-size: 14px; }

    /* Cart drawer */
    .cart-toggle { position: fixed; bottom: 12px; left: 12px; right: 12px; z-index: 90; }
    .cart-toggle button { width: 100%; background: {{ $colorPrimary }}; color: #fff; border: none; padding: 14px; border-radius: 12px; font-size: 15px; font-weight: 600; display: flex; align-items: center; justify-content: space-between; cursor: pointer; box-shadow: 0 4px 12px rgba(255,102,0,0.3); }
    .cart-toggle .count { background: rgba(255,255,255,0.2); border-radius: 20px; padding: 2px 10px; font-size: 13px; }

    /* Cart panel */
    .cart-overlay { display: none; position: fixed; inset: 0; z-index: 200; }
    .cart-overlay.show { display: block; }
    .cart-overlay .backdrop { position: absolute; inset: 0; background: rgba(0,0,0,0.5); }
    .cart-panel { position: absolute; bottom: 0; left: 0; right: 0; background: #fff; border-radius: 16px 16px 0 0; max-height: 85vh; display: flex; flex-direction: column; }
    .cart-panel .handle { width: 36px; height: 4px; background: #d1d5db; border-radius: 2px; margin: 8px auto 4px; }
    .cart-panel .cart-header { display: flex; align-items: center; justify-content: space-between; padding: 8px 16px 4px; border-bottom: 1px solid #f3f4f6; }
    .cart-panel .cart-header h2 { font-size: 16px; font-weight: 600; }
    .cart-panel .cart-header button { background: none; border: none; color: #ef4444; font-size: 13px; cursor: pointer; }
    .cart-items { overflow-y: auto; flex: 1; padding: 8px 16px; }
    .cart-item { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
    .cart-item:last-child { border-bottom: none; }
    .cart-item .item-info { flex: 1; min-width: 0; }
    .cart-item .item-info .item-name { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cart-item .item-info .item-price { font-size: 12px; color: {{ $colorPrimary }}; font-weight: 600; }
    .cart-item .qty-ctrl { display: flex; align-items: center; gap: 6px; }
    .cart-item .qty-ctrl button { width: 28px; height: 28px; border-radius: 50%; border: none; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; cursor: pointer; }
    .cart-item .qty-ctrl .qty-btn-minus { background: #f3f4f6; color: #374151; }
    .cart-item .qty-ctrl .qty-btn-plus { background: {{ $colorPrimary }}; color: #fff; }
    .cart-item .qty-ctrl .qty-btn-danger { background: #fef2f2; color: #ef4444; }
    .cart-item .qty-ctrl .qty-val { width: 24px; text-align: center; font-size: 14px; font-weight: 600; }
    .cart-item .item-total { font-size: 13px; font-weight: 700; color: #1f2937; min-width: 60px; text-align: right; }

    /* Cart footer */
    .cart-footer { border-top: 1px solid #e5e7eb; padding: 12px 16px; padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px)); }
    .cart-footer .row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px; }
    .cart-footer .row.total { font-size: 16px; font-weight: 700; margin-top: 4px; padding-top: 4px; border-top: 1px solid #e5e7eb; }
    .cart-footer .grand-total { color: {{ $colorPrimary }}; }
    .cart-footer .pay-btn { width: 100%; background: {{ $colorPrimary }}; color: #fff; border: none; padding: 14px; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; margin-top: 8px; }

    /* Payment modal */
    .modal-overlay { display: none; position: fixed; inset: 0; z-index: 300; }
    .modal-overlay.show { display: block; }
    .modal-overlay .backdrop { position: absolute; inset: 0; background: rgba(0,0,0,0.5); }
    .modal-content { position: absolute; bottom: 0; left: 0; right: 0; background: #fff; border-radius: 16px 16px 0 0; max-height: 90vh; overflow-y: auto; padding: 16px; padding-bottom: calc(16px + env(safe-area-inset-bottom, 0px)); }
    .modal-content h2 { font-size: 18px; font-weight: 600; margin-bottom: 16px; }
    .form-group { margin-bottom: 12px; }
    .form-group label { display: block; font-size: 13px; font-weight: 500; color: #6b7280; margin-bottom: 4px; }
    .form-group select, .form-group input { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; background: #fff; }
    .form-group select:focus, .form-group input:focus { border-color: {{ $colorPrimary }}; }
    .form-group input[readonly] { background: #f9fafb; color: #374151; font-weight: 600; }
    .modal-actions { display: flex; gap: 8px; margin-top: 16px; }
    .modal-actions button { flex: 1; padding: 12px; border-radius: 10px; font-size: 14px; font-weight: 600; border: none; cursor: pointer; }
    .modal-actions .btn-cancel { background: #f3f4f6; color: #374151; }
    .modal-actions .btn-confirm { background: {{ $colorPrimary }}; color: #fff; }
    .modal-actions .btn-confirm:disabled { background: #d1d5db; cursor: not-allowed; }

    /* Toast */
    .toast { position: fixed; top: 64px; left: 50%; transform: translateX(-50%); z-index: 9999; padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 500; color: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.2); display: none; }
    .toast.success { background: #22c55e; }
    .toast.error { background: #ef4444; }
    .toast.show { display: block; animation: toastIn 0.3s ease; }

    /* Animations */
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.5} }
    @keyframes toastIn { 0%{opacity:0;transform:translateX(-50%) translateY(-10px)} 100%{opacity:1;transform:translateX(-50%) translateY(0)} }
    @keyframes fadeInUp { 0%{opacity:0;transform:translateY(20px)} 100%{opacity:1;transform:translateY(0)} }

    .empty-cart { text-align: center; padding: 40px 16px; color: #9ca3af; font-size: 14px; }

    /* Offline badge */
    .offline-badge { background: #fef3c7; color: #92400e; font-size: 11px; padding: 2px 8px; border-radius: 10px; margin-left: 8px; display: none; }
    .offline-badge.show { display: inline-block; }
  </style>
</head>
<body>

  <!-- Header -->
  <div class="header">
    <div style="display:flex;align-items:center;gap:8px;">
      <button onclick="window.location.href='/member'" style="background:rgba(255,255,255,0.2);border:none;color:#fff;padding:4px 10px;border-radius:6px;font-size:13px;cursor:pointer;">&larr;</button>
      <h1>Offline POS</h1>
      <span class="offline-badge" id="offline-badge">OFFLINE</span>
    </div>
    <div class="sync-badge" id="sync-badge-header" onclick="syncNow()">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 11-6.22-8.56"/><path d="M21 3v6h-6"/></svg>
      <span id="sync-text">Sync</span>
    </div>
  </div>

  <!-- Toast -->
  <div class="toast" id="toast"></div>

  <!-- Products -->
  <div class="container">
    <div class="product-section" id="product-section">
      <div class="search-bar">
        <span class="icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        </span>
        <input type="text" id="search-input" placeholder="Search products..." oninput="renderProducts()">
      </div>
      <div class="categories" id="categories-container"></div>
      <div class="product-grid" id="product-grid"></div>
    </div>
  </div>

  <!-- Cart Toggle -->
  <div class="cart-toggle" id="cart-toggle">
    <button onclick="openCart()">
      <span>{{ __('View Cart') }}</span>
      <span class="count" id="cart-count">0</span>
    </button>
  </div>

  <!-- Cart Panel -->
  <div class="cart-overlay" id="cart-overlay">
    <div class="backdrop" onclick="closeCart()"></div>
    <div class="cart-panel">
      <div class="handle"></div>
      <div class="cart-header">
        <h2>{{ __('Cart') }}</h2>
        <button onclick="clearCart()">{{ __('Clear') }}</button>
      </div>
      <div class="cart-items" id="cart-items"></div>
      <div class="cart-footer" id="cart-footer">
        <div class="row">
          <span>{{ __('Subtotal') }}</span>
          <span id="cart-subtotal">Rp 0</span>
        </div>
        <div class="row total">
          <span>{{ __('Total') }}</span>
          <span class="grand-total" id="cart-total">Rp 0</span>
        </div>
        <button class="pay-btn" onclick="openPaymentModal()">{{ __('Proceed to Payment') }}</button>
      </div>
    </div>
  </div>

  <!-- Payment Modal -->
  <div class="modal-overlay" id="payment-modal">
    <div class="backdrop" onclick="closePaymentModal()"></div>
    <div class="modal-content">
      <h2>{{ __('Payment') }}</h2>

      <div class="form-group">
        <label>{{ __('Customer') }}</label>
        <select id="payment-member"><option value="">— {{ __('Walk-in') }} —</option></select>
      </div>

      <div class="form-group">
        <label>{{ __('Payment Method') }}</label>
        <select id="payment-method"></select>
      </div>

      <div class="form-group">
        <label>{{ __('Pay Amount') }}</label>
        <input type="text" id="payment-amount" oninput="formatPaymentAmount(this)" inputmode="numeric">
      </div>

      <div class="form-group" style="margin-top:16px;">
        <label>{{ __('Change') }}</label>
        <input type="text" id="payment-change" readonly>
      </div>

      <div style="background:#f3f4f6;border-radius:8px;padding:10px 12px;margin-top:8px;">
        <div style="display:flex;justify-content:space-between;font-size:13px;">
          <span>{{ __('Total') }}</span>
          <span style="font-weight:700;color:{{ $colorPrimary }};" id="payment-total">Rp 0</span>
        </div>
      </div>

      <div class="modal-actions">
        <button class="btn-cancel" onclick="closePaymentModal()">{{ __('Cancel') }}</button>
        <button class="btn-confirm" id="pay-confirm-btn" onclick="confirmPayment()">{{ __('Save Offline') }}</button>
      </div>
    </div>
  </div>

<script>
(function() {
  // ─── State ──────────────────────────────────────────────────
  var db = null;
  var products = [];
  var categories = [];
  var members = [];
  var paymentMethods = [];
  var cart = {};
  var settings = {};
  var selectedCategory = null;
  var currencySymbol = 'Rp';
  var DB_NAME = 'zonakasir_offline';
  var DB_VERSION = 2;

  // ─── Init ───────────────────────────────────────────────────
  openDB().then(function() {
    loadData();
    updateOnlineStatus();
    updateSyncBadge();
  });

  window.addEventListener('online', function() { updateOnlineStatus(); syncNow(); });
  window.addEventListener('offline', updateOnlineStatus);

  setInterval(function() {
    if (navigator.onLine) syncNow();
  }, 30000);

  // Auto-refresh master data every 5 minutes when online
  setInterval(function() {
    if (navigator.onLine && window.refreshMasterData) {
      window.refreshMasterData();
    }
  }, 5 * 60 * 1000);

  // ─── IndexedDB ──────────────────────────────────────────────
  function openDB() {
    return new Promise(function(resolve, reject) {
      var req = indexedDB.open(DB_NAME, DB_VERSION);
      req.onsuccess = function(e) { db = e.target.result; resolve(db); };
      req.onerror = function(e) { reject(e.target.error); };
      req.onupgradeneeded = function(e) {
        var d = e.target.result;
        ['products','categories','members','payment_methods','about','settings'].forEach(function(n) {
          if (!d.objectStoreNames.contains(n)) d.createObjectStore(n, { keyPath: 'id' });
        });
        ['pending_sales','queued_operations','api_cache'].forEach(function(n) {
          if (!d.objectStoreNames.contains(n)) d.createObjectStore(n, { keyPath: n === 'pending_sales' ? 'temp_id' : n === 'api_cache' ? 'url' : 'op_id' });
        });
        if (!d.objectStoreNames.contains('meta')) d.createObjectStore('meta', { keyPath: 'key' });
      };
    });
  }

  function getAll(store) {
    return new Promise(function(resolve) {
      try {
        var tx = db.transaction(store, 'readonly');
        var req = tx.objectStore(store).getAll();
        req.onsuccess = function() { resolve(req.result || []); };
        req.onerror = function() { resolve([]); };
      } catch(e) { resolve([]); }
    });
  }

  function getMeta(key) {
    return new Promise(function(resolve) {
      try {
        var tx = db.transaction('meta', 'readonly');
        var req = tx.objectStore('meta').get(key);
        req.onsuccess = function() { resolve(req.result ? req.result.value : null); };
        req.onerror = function() { resolve(null); };
      } catch(e) { resolve(null); }
    });
  }

  function putPendingSale(sale) {
    return new Promise(function(resolve, reject) {
      var entry = {
        temp_id: 'offline_pos_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
        status: 'pending',
        created_at: new Date().toISOString(),
        synced: false,
        ...sale,
      };
      try {
        var tx = db.transaction('pending_sales', 'readwrite');
        var req = tx.objectStore('pending_sales').put(entry);
        req.onsuccess = function() { resolve(entry); };
        req.onerror = function() { reject(req.error); };
      } catch(e) { reject(e); }
    });
  }

  function countPendingSales() {
    return new Promise(function(resolve) {
      try {
        var tx = db.transaction('pending_sales', 'readonly');
        var req = tx.objectStore('pending_sales').getAll();
        req.onsuccess = function() {
          var count = (req.result || []).filter(function(s) { return s.status === 'pending'; }).length;
          resolve(count);
        };
        req.onerror = function() { resolve(0); };
      } catch(e) { resolve(0); }
    });
  }

  // ─── Load Data from IndexedDB ──────────────────────────────
  function loadData() {
    getAll('products').then(function(p) {
      products = p;
      renderCategories();
      renderProducts();
    });
    getAll('categories').then(function(c) { categories = c; renderCategories(); });
    getAll('members').then(function(m) { members = m; renderMembers(); });
    getAll('payment_methods').then(function(pm) { paymentMethods = pm; renderPaymentMethods(); });
    getMeta('currency').then(function(c) { if (c) currencySymbol = c === 'IDR' ? 'Rp' : c; });
  }

  // ─── Render Products ───────────────────────────────────────
  function renderCategories() {
    var el = document.getElementById('categories-container');
    var html = '<button class="category-btn' + (selectedCategory === null ? ' active' : '') + '" onclick="selectCategory(null)">All</button>';
    categories.forEach(function(c) {
      html += '<button class="category-btn' + (selectedCategory === c.id ? ' active' : '') + '" onclick="selectCategory(' + c.id + ')">' + esc(c.name) + '</button>';
    });
    el.innerHTML = html;
  }

  window.selectCategory = function(id) {
    selectedCategory = id;
    renderCategories();
    renderProducts();
  };

  function renderProducts() {
    var el = document.getElementById('product-grid');
    var q = (document.getElementById('search-input').value || '').toLowerCase();
    var filtered = products.filter(function(p) {
      if (selectedCategory && p.category_id !== selectedCategory) return false;
      if (q && !(p.name && p.name.toLowerCase().includes(q)) && !(p.sku && p.sku.toLowerCase().includes(q)) && !(p.barcode && p.barcode.toLowerCase().includes(q))) return false;
      return true;
    });

    if (filtered.length === 0) {
      el.innerHTML = '<div class="empty-state"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg><p>' + (q ? 'No products found' : 'No cached products. Go online first to sync data.') + '</p></div>';
      return;
    }

    var html = '';
    var cartData = getCartData();
    filtered.forEach(function(p) {
      var inCart = cartData.find(function(c) { return c.id === p.id; });
      var qty = inCart ? inCart.qty : 0;
      var stock = (p.stock_calculate !== undefined ? p.stock_calculate : p.stock || 0);
      var outOfStock = !p.is_non_stock && stock <= 0;

      html += '<div class="product-card" onclick="' + (outOfStock ? '' : 'addToCart(' + p.id + ')') + '">';
      html += '<div class="img">';
      if (p.hero_images_url && typeof p.hero_images_url === 'string' && p.hero_images_url.length > 0) {
        html += '<img src="' + escPath(p.hero_images_url) + '" alt="' + esc(p.name) + '" loading="lazy" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\';" onload="this.style.display=\'\'">';
        html += '<div style="display:none;align-items:center;justify-content:center;width:100%;height:100%;background:#f3f4f6;color:#d1d5db;font-size:24px;position:absolute;top:0;left:0;">';
        html += '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>';
        html += '</div>';
      } else {
        html += '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>';
      }
      if (outOfStock) html += '<div class="out-of-stock">Out of Stock</div>';
      if (qty > 0) html += '<div class="cart-qty">' + qty + '</div>';
      html += '</div>';
      html += '<div class="info">';
      html += '<div class="sku">' + esc(p.sku || '') + '</div>';
      html += '<div class="name">' + esc(p.name) + '</div>';
      html += '<div class="price">' + formatPrice(p.selling_price_calculate || p.selling_price || 0) + '</div>';
      html += '</div></div>';
    });
    el.innerHTML = html;
  }

  // ─── Cart Operations ───────────────────────────────────────
  function getCartData() {
    return Object.values(cart).map(function(c) { return c; });
  }

  window.addToCart = function(productId) {
    var p = products.find(function(x) { return x.id === productId; });
    if (!p) return;
    if (!cart[productId]) {
      cart[productId] = { id: productId, name: p.name, price: p.selling_price_calculate || p.selling_price || 0, qty: 0 };
    }
    cart[productId].qty++;
    updateCartUI();
  };

  window.removeFromCart = function(productId) {
    if (!cart[productId]) return;
    cart[productId].qty--;
    if (cart[productId].qty <= 0) delete cart[productId];
    updateCartUI();
  };

  window.removeAllFromCart = function(productId) {
    delete cart[productId];
    updateCartUI();
  };

  window.clearCart = function() {
    cart = {};
    updateCartUI();
  };

  function updateCartUI() {
    var cartData = getCartData();
    var totalQty = cartData.reduce(function(s, c) { return s + c.qty; }, 0);
    var subtotal = cartData.reduce(function(s, c) { return s + c.price * c.qty; }, 0);

    document.getElementById('cart-count').textContent = totalQty;
    document.getElementById('cart-subtotal').textContent = formatPrice(subtotal);
    document.getElementById('cart-total').textContent = formatPrice(subtotal);
    document.getElementById('payment-total').textContent = formatPrice(subtotal);

    renderProducts();

    // Cart items
    var el = document.getElementById('cart-items');
    if (cartData.length === 0) {
      el.innerHTML = '<div class="empty-cart">Cart is empty</div>';
      return;
    }

    var html = '';
    cartData.forEach(function(c) {
      html += '<div class="cart-item">';
      html += '<div class="item-info"><div class="item-name">' + esc(c.name) + '</div><div class="item-price">' + formatPrice(c.price) + '</div></div>';
      html += '<div class="qty-ctrl">';
      html += '<button class="qty-btn-minus" onclick="removeFromCart(' + c.id + ')">-</button>';
      html += '<span class="qty-val">' + c.qty + '</span>';
      html += '<button class="qty-btn-plus" onclick="addToCart(' + c.id + ')">+</button>';
      html += '<button class="qty-btn-danger" onclick="removeAllFromCart(' + c.id + ')">&times;</button>';
      html += '</div>';
      html += '<div class="item-total">' + formatPrice(c.price * c.qty) + '</div>';
      html += '</div>';
    });
    el.innerHTML = html;
  }

  // ─── Cart Panel ─────────────────────────────────────────────
  window.openCart = function() {
    document.getElementById('cart-overlay').classList.add('show');
    updateCartUI();
  };

  window.closeCart = function() {
    document.getElementById('cart-overlay').classList.remove('show');
  };

  // ─── Payment Modal ─────────────────────────────────────────
  function renderMembers() {
    var el = document.getElementById('payment-member');
    var html = '<option value="">— Walk-in —</option>';
    members.forEach(function(m) { html += '<option value="' + m.id + '">' + esc(m.name) + '</option>'; });
    el.innerHTML = html;
  }

  function renderPaymentMethods() {
    var el = document.getElementById('payment-method');
    var html = '';
    paymentMethods.forEach(function(pm) { html += '<option value="' + pm.id + '">' + esc(pm.name) + '</option>'; });
    el.innerHTML = html;
  }

  window.openPaymentModal = function() {
    var cartData = getCartData();
    if (cartData.length === 0) { showToast('Cart is empty', 'error'); return; }
    closeCart();
    var total = cartData.reduce(function(s, c) { return s + c.price * c.qty; }, 0);
    document.getElementById('payment-total').textContent = formatPrice(total);
    document.getElementById('payment-amount').value = formatPrice(total).replace(/[^0-9]/g, '');
    document.getElementById('payment-amount').setAttribute('data-raw', total.toString());
    document.getElementById('payment-change').value = 'Rp 0';
    document.getElementById('payment-modal').classList.add('show');
    document.getElementById('pay-confirm-btn').disabled = false;
  };

  window.closePaymentModal = function() {
    document.getElementById('payment-modal').classList.remove('show');
  };

  window.formatPaymentAmount = function(el) {
    var raw = el.value.replace(/[^0-9]/g, '');
    if (raw === '') { el.value = ''; el.setAttribute('data-raw', '0'); return; }
    el.setAttribute('data-raw', raw);
    el.value = formatPrice(parseInt(raw));

    var total = parseInt(document.getElementById('payment-total').textContent.replace(/[^0-9]/g, ''));
    var pay = parseInt(raw);
    var change = pay - total;
    document.getElementById('payment-change').value = change > 0 ? formatPrice(change) : 'Rp 0';
  };

  window.confirmPayment = function() {
    var cartData = getCartData();
    if (cartData.length === 0) { showToast('Cart is empty', 'error'); return; }

    var total = cartData.reduce(function(s, c) { return s + c.price * c.qty; }, 0);
    var memberId = document.getElementById('payment-member').value;
    var pmId = document.getElementById('payment-method').value;
    var payRaw = parseInt(document.getElementById('payment-amount').getAttribute('data-raw') || '0');

    var productsPayload = cartData.map(function(c) {
      return { product_id: c.id, qty: c.qty, price: c.price, discount_price: 0 };
    });

    var sale = {
      products: productsPayload,
      total_price: total,
      total_qty: cartData.reduce(function(s, c) { return s + c.qty; }, 0),
      payed_money: payRaw || total,
      friend_price: false,
    };
    if (memberId) sale.member_id = parseInt(memberId);
    if (pmId) sale.payment_method_id = parseInt(pmId);

    document.getElementById('pay-confirm-btn').disabled = true;
    document.getElementById('pay-confirm-btn').textContent = 'Saving...';

    putPendingSale(sale).then(function() {
      closePaymentModal();
      cart = {};
      updateCartUI();
      updateSyncBadge();
      showToast('Transaction saved offline. Will sync when online.', 'success');
      document.getElementById('pay-confirm-btn').disabled = false;
      document.getElementById('pay-confirm-btn').textContent = 'Save Offline';
    }).catch(function(err) {
      showToast('Failed to save: ' + err.message, 'error');
      document.getElementById('pay-confirm-btn').disabled = false;
      document.getElementById('pay-confirm-btn').textContent = 'Save Offline';
    });
  };

  // ─── Sync ───────────────────────────────────────────────────
  window.syncNow = function() {
    if (!navigator.onLine) { showToast('You are offline', 'error'); return; }

    var el = document.getElementById('sync-badge-header');
    var text = document.getElementById('sync-text');
    el.classList.add('syncing');
    text.textContent = 'Syncing...';

    countPendingSales().then(function(count) {
      if (count === 0) {
        el.classList.remove('syncing');
        text.textContent = 'Sync';
        return;
      }

      var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
      var token = document.querySelector('meta[name="csrf-token"]');
      var csrfToken = token ? token.getAttribute('content') : '';

      // Collect all pending sales
      var tx = db.transaction('pending_sales', 'readonly');
      var req = tx.objectStore('pending_sales').getAll();
      req.onsuccess = function() {
        var pending = (req.result || []).filter(function(s) { return s.status === 'pending'; });
        var done = 0, fail = 0;

        pending.forEach(function(sale, i) {
          fetch('/api/sync/submit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            credentials: 'same-origin',
            body: JSON.stringify(sale),
          }).then(function(r) {
            if (r.ok) {
              // Mark as synced
              var tx2 = db.transaction('pending_sales', 'readwrite');
              var s = sale;
              s.status = 'synced';
              s.synced_at = new Date().toISOString();
              tx2.objectStore('pending_sales').put(s);
              done++;
            } else {
              fail++;
            }
          }).catch(function() {
            fail++;
          }).finally(function() {
            if (done + fail >= pending.length) {
              el.classList.remove('syncing');
              text.textContent = 'Sync (' + done + ' ok)';
              var total = done + fail;
              showToast(done + '/' + total + ' synced', fail > 0 && done > 0 ? 'error' : 'success');
              updateSyncBadge();
              setTimeout(function() { text.textContent = 'Sync'; }, 5000);
            }
          });
        });
      };
    });
  };

  function updateSyncBadge() {
    countPendingSales().then(function(count) {
      var el = document.getElementById('sync-text');
      if (el) {
        if (count > 0) {
          el.textContent = count + ' pending';
          if (navigator.onLine) {
            document.getElementById('sync-badge-header').style.background = 'rgba(255,255,255,0.3)';
          } else {
            document.getElementById('sync-badge-header').style.background = 'rgba(239,68,68,0.4)';
          }
        } else {
          el.textContent = 'Synced';
          document.getElementById('sync-badge-header').style.background = 'rgba(255,255,255,0.2)';
        }
      }
    });
  }

  window.refreshMasterData = function() {
    if (!navigator.onLine) return;
    var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    if (!csrfToken) return;

    // Try delta sync by reading last_sync_at
    var syncUrl = '/api/sync/data';
    try {
      var tx = db.transaction('meta', 'readonly');
      var req = tx.objectStore('meta').get('last_sync_at');
      req.onsuccess = function() {
        if (req.result && req.result.value) {
          syncUrl += '?since=' + encodeURIComponent(req.result.value);
        }
        doFetch(syncUrl);
      };
      req.onerror = function() { doFetch(syncUrl); };
    } catch(e) { doFetch(syncUrl); }

    function doFetch(url) {
      fetch(url, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
        credentials: 'same-origin',
      }).then(function(r) { return r.ok ? r.json() : null; }).then(function(resp) {
        if (!resp) return;
        var data = resp.data || resp;
        var isDelta = data.is_delta === true;
        var count = 0;

        function upsertStore(name, items) {
          if (!Array.isArray(items) || items.length === 0) return;
          try {
            var tx = db.transaction(name, 'readwrite');
            if (!isDelta) tx.objectStore(name).clear();
            items.forEach(function(item) { tx.objectStore(name).put(item); });
            count += items.length;
          } catch(e) {}
        }

      upsertStore('products', data.products);
      upsertStore('categories', data.categories);
      upsertStore('members', data.members);
      upsertStore('payment_methods', data.payment_methods);

      // Handle deleted records (delta sync only)
      if (isDelta && data.deleted_ids) {
        for (var storeName in data.deleted_ids) {
          var ids = data.deleted_ids[storeName];
          if (Array.isArray(ids) && ids.length > 0) {
            try {
              var tx = db.transaction(storeName, 'readwrite');
              ids.forEach(function(id) {
                try { tx.objectStore(storeName).delete(id); } catch(e2) {}
              });
              count += ids.length;
            } catch(e) {}
          }
        }
      }

      // Settings
      if (data.settings) {
        try {
          var tx = db.transaction('settings', 'readwrite');
          for (var k in data.settings) {
            tx.objectStore('settings').put({ key: k, value: data.settings[k] });
          }
        } catch(e) {}
      }

      // About
      if (data.about) {
        try {
          var tx = db.transaction('about', 'readwrite');
          tx.objectStore('about').clear();
          tx.objectStore('about').put(data.about);
        } catch(e) {}
      }

      // Update timestamps
      try {
        var tx = db.transaction('meta', 'readwrite');
        var ts = new Date().toISOString();
        tx.objectStore('meta').put({ key: 'last_prefetch', value: ts });
        tx.objectStore('meta').put({ key: 'last_sync_at', value: ts });
      } catch(e) {}

      if (count > 0) {
        loadData();
        showToast('Data refreshed: ' + count + ' items', 'success');
      }
    }).catch(function() {});
  };

  // ─── Helpers ───────────────────────────────────────────────
  function formatPrice(n) {
    n = parseInt(n) || 0;
    return currencySymbol + ' ' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  function esc(s) {
    if (s == null) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function escPath(s) {
    if (s == null) return '';
    return String(s).replace(/"/g,'%22').replace(/'/g,"%27");
  }

  function showToast(msg, type) {
    var el = document.getElementById('toast');
    el.textContent = msg;
    el.className = 'toast ' + (type || 'success') + ' show';
    setTimeout(function() { el.classList.remove('show'); }, 3000);
  }

  function updateOnlineStatus() {
    var badge = document.getElementById('offline-badge');
    if (badge) badge.classList.toggle('show', !navigator.onLine);
  }

  // Update sync badge on page load
  updateSyncBadge();
})();
</script>
</body>
</html>
