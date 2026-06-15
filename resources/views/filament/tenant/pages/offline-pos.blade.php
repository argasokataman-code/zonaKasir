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
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #1f2937; min-height: 100vh; overscroll-behavior-y: contain; }

    /* Header */
    .header { background: {{ $colorPrimary }}; color: #fff; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
    .header h1 { font-size: 16px; font-weight: 700; }
    .header .sync-badge { background: rgba(255,255,255,0.2); border-radius: 20px; padding: 4px 12px; font-size: 11px; display: flex; align-items: center; gap: 4px; cursor: pointer; }
    .header .sync-badge.syncing { animation: pulse 1s infinite; }
    .header .offline-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
    .header .offline-dot.online { background: #22c55e; }
    .header .offline-dot.offline { background: #ef4444; }

    /* Container */
    .container { display: flex; flex-direction: column; height: calc(100vh - 52px); }
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
    .product-card .img .low-stock { position: absolute; top: 6px; left: 6px; background: #f59e0b; color: #fff; padding: 1px 6px; border-radius: 10px; font-size: 10px; font-weight: 700; }
    .product-card .info { padding: 8px; }
    .product-card .info .sku { font-size: 10px; color: #9ca3af; }
    .product-card .info .name { font-size: 13px; font-weight: 600; line-height: 1.3; margin: 2px 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .product-card .info .price { font-size: 14px; font-weight: 700; color: {{ $colorPrimary }}; }

    /* Empty state */
    .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px; color: #9ca3af; }
    .empty-state p { font-size: 14px; }

    /* Cart toggle */
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
    .cart-panel .cart-header .clear-btn { background: none; border: none; color: #ef4444; font-size: 13px; cursor: pointer; }
    .cart-items { overflow-y: auto; flex: 1; padding: 8px 16px; max-height: 40vh; }
    .cart-item { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
    .cart-item:last-child { border-bottom: none; }
    .cart-item .item-info { flex: 1; min-width: 0; }
    .cart-item .item-info .item-name { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cart-item .item-info .item-price { font-size: 12px; color: {{ $colorPrimary }}; font-weight: 600; }
    .cart-item .item-info .item-discount { font-size: 11px; color: #ef4444; }
    .cart-item .qty-ctrl { display: flex; align-items: center; gap: 4px; }
    .cart-item .qty-ctrl button { width: 28px; height: 28px; border-radius: 50%; border: none; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; cursor: pointer; }
    .cart-item .qty-ctrl .qty-btn-minus { background: #f3f4f6; color: #374151; }
    .cart-item .qty-ctrl .qty-btn-plus { background: {{ $colorPrimary }}; color: #fff; }
    .cart-item .qty-ctrl .qty-btn-danger { background: #fef2f2; color: #ef4444; }
    .cart-item .qty-ctrl .qty-val { width: 24px; text-align: center; font-size: 14px; font-weight: 600; }
    .cart-item .item-total { font-size: 13px; font-weight: 700; color: #1f2937; min-width: 60px; text-align: right; }
    .cart-item .discount-input { width: 80px; padding: 2px 6px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 11px; text-align: right; }

    /* Cart footer */
    .cart-footer { border-top: 1px solid #e5e7eb; padding: 12px 16px; padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px)); }
    .cart-footer .row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px; }
    .cart-footer .row.total { font-size: 16px; font-weight: 700; margin-top: 4px; padding-top: 4px; border-top: 1px solid #e5e7eb; }
    .cart-footer .grand-total { color: {{ $colorPrimary }}; }
    .cart-footer .pay-btn { width: 100%; background: {{ $colorPrimary }}; color: #fff; border: none; padding: 14px; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; margin-top: 8px; }

    /* Detail section in cart */
    .cart-detail { padding: 8px 16px; border-top: 1px solid #f3f4f6; }
    .cart-detail .detail-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; font-size: 13px; cursor: pointer; }
    .cart-detail .detail-row:hover { background: #f9fafb; }
    .cart-detail input, .cart-detail select, .cart-detail textarea { width: 100%; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; margin-top: 4px; }
    .cart-detail textarea { min-height: 40px; resize: none; }

    /* Modal overlay */
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

    /* Payment method grid */
    .payment-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin-bottom: 12px; }
    .payment-grid .pm-btn { padding: 8px 4px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 11px; font-weight: 500; text-align: center; cursor: pointer; transition: all 0.15s; background: #fff; color: #374151; }
    .payment-grid .pm-btn.active { border-color: {{ $colorPrimary }}; background: {{ $colorPrimary }}; color: #fff; }
    .payment-grid .pm-btn:active { transform: scale(0.96); }

    /* Numpad */
    .numpad { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin-top: 8px; }
    .numpad button { padding: 12px 0; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; background: #f3f4f6; color: #374151; transition: background 0.1s; }
    .numpad button:active { background: #d1d5db; }
    .numpad .btn-wide { grid-column: span 2; }
    .numpad .btn-primary { background: {{ $colorPrimary }}; color: #fff; }
    .numpad .btn-primary:active { background: #e55a00; }
    .numpad .btn-no-change { grid-column: span 4; background: #e5e7eb; color: #374151; }
    .numpad .btn-backspace { background: #fef2f2; color: #ef4444; }

    /* Suggested payments */
    .suggested-payments { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .suggested-payments button { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 20px; font-size: 12px; font-weight: 500; cursor: pointer; background: #fff; color: #374151; }
    .suggested-payments button:active { background: {{ $colorPrimary }}; color: #fff; border-color: {{ $colorPrimary }}; }

    /* Table grid */
    .table-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
    .table-grid .table-btn { padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px; text-align: center; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.15s; }
    .table-grid .table-btn.active { border-color: {{ $colorPrimary }}; background: {{ $colorPrimary }}; color: #fff; }

    /* Success modal */
    .success-content { text-align: center; padding: 20px 0; }
    .success-content .check { color: #22c55e; font-size: 64px; margin-bottom: 12px; }
    .success-content .change-label { font-size: 14px; color: #6b7280; margin-top: 8px; }
    .success-content .change-amount { font-size: 28px; font-weight: 700; color: {{ $colorPrimary }}; }

    /* Receipt preview */
    .receipt-preview { font-family: 'Courier New', Courier, monospace; font-size: 12px; line-height: 1.5; padding: 16px; background: #fff; max-width: 320px; margin: 0 auto; border: 1px solid #e5e7eb; }
    .receipt-preview .line { letter-spacing: 0.15em; text-align: center; margin: 8px 0; }

    /* Toast */
    .toast { position: fixed; top: 64px; left: 50%; transform: translateX(-50%); z-index: 9999; padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 500; color: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.2); display: none; white-space: nowrap; }
    .toast.success { background: #22c55e; }
    .toast.error { background: #ef4444; }
    .toast.info { background: #3b82f6; }
    .toast.show { display: block; animation: toastIn 0.3s ease; }

    /* QR Scanner */
    #qr-reader { border: none !important; }
    #qr-reader__dashboard_section { padding: 0.75rem !important; }
    #qr-reader__dashboard_section_csr button { background-color: {{ $colorPrimary }} !important; border: none !important; color: #fff !important; padding: 0.5rem 1rem !important; border-radius: 0.5rem !important; font-weight: 600 !important; font-size: 0.875rem !important; }

    .offline-badge { background: #fef3c7; color: #92400e; font-size: 11px; padding: 2px 8px; border-radius: 10px; margin-left: 8px; display: none; }
    .offline-badge.show { display: inline-block; }

    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.5} }
    @keyframes toastIn { 0%{opacity:0;transform:translateX(-50%) translateY(-10px)} 100%{opacity:1;transform:translateX(-50%) translateY(0)} }

    /* Dark mode */
    @media (prefers-color-scheme: dark) {
      body { background: #111827; color: #f3f4f6; }
      .product-card { background: #1f2937; border-color: #374151; }
      .product-card .info .name { color: #f3f4f6; }
      .product-card .info .sku { color: #9ca3af; }
      .product-card .img { background: #374151; }
      .category-btn { background: #374151; color: #d1d5db; }
      .category-btn.active { background: {{ $colorPrimary }}; color: #fff; }
      .search-bar input { background: #1f2937; color: #f3f4f6; border-color: #4b5563; }
      .cart-panel { background: #1f2937; }
      .cart-panel .cart-header { border-color: #374151; }
      .cart-panel .cart-header h2 { color: #f3f4f6; }
      .cart-item { border-color: #374151; background: #1f2937; }
      .cart-item .item-info .item-name { color: #f3f4f6; }
      .cart-footer { border-color: #374151; background: #1f2937; }
      .cart-footer .row { color: #d1d5db; }
      .cart-detail { border-color: #374151; background: #1f2937; }
      .cart-detail .detail-row { color: #d1d5db; }
      .modal-content { background: #1f2937; color: #f3f4f6; }
      .modal-content h2 { color: #f3f4f6; }
      .form-group label { color: #9ca3af; }
      .form-group select, .form-group input, .form-group textarea { background: #374151; color: #f3f4f6; border-color: #4b5563; }
      .numpad button { background: #374151; color: #f3f4f6; }
      .numpad button:active { background: #4b5563; }
      .numpad .btn-no-change { background: #4b5563; color: #f3f4f6; }
      .numpad .btn-backspace { background: #7f1d1d; color: #fca5a5; }
      .payment-grid .pm-btn { background: #374151; color: #d1d5db; border-color: #4b5563; }
      .payment-grid .pm-btn.active { background: {{ $colorPrimary }}; color: #fff; border-color: {{ $colorPrimary }}; }
      .suggested-payments button { background: #374151; color: #d1d5db; border-color: #4b5563; }
      .suggested-payments button:active { background: {{ $colorPrimary }}; color: #fff; }
      .modal-actions .btn-cancel { background: #374151; color: #d1d5db; }
      .table-grid .table-btn { background: #374151; color: #d1d5db; border-color: #4b5563; }
      .table-grid .table-btn.active { background: {{ $colorPrimary }}; color: #fff; border-color: {{ $colorPrimary }}; }
      .cart-item .discount-input { background: #374151; color: #f3f4f6; border-color: #4b5563; }
      #pay-amount-display { background: #374151; color: #f3f4f6; border-color: #4b5563; }
      .empty-state { color: #6b7280; }
      .qty-btn-minus { background: #374151 !important; color: #d1d5db !important; }
      .qty-btn-plus { background: {{ $colorPrimary }} !important; color: #fff !important; }
      .qty-btn-danger { background: #7f1d1d !important; color: #fca5a5 !important; }
      #voucher-select { background: #374151; color: #f3f4f6; border-color: #4b5563; }
    }
  </style>
</head>
<body>

  <!-- Header -->
  <div class="header">
    <div style="display:flex;align-items:center;gap:8px;">
      <button onclick="window.location.href='/member'" style="background:rgba(255,255,255,0.2);border:none;color:#fff;padding:4px 10px;border-radius:6px;font-size:13px;cursor:pointer;">&larr;</button>
      <h1>Offline POS</h1>
      <span class="offline-dot" id="conn-dot"></span>
      <span class="offline-badge" id="offline-badge">OFFLINE</span>
    </div>
    <div style="display:flex;align-items:center;gap:8px;">
      <button onclick="openScanner()" style="background:rgba(255,255,255,0.2);border:none;color:#fff;padding:6px 10px;border-radius:6px;font-size:13px;cursor:pointer;">&#x1f4f7;</button>
      <div class="sync-badge" id="sync-badge-header" onclick="syncNow()">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 11-6.22-8.56"/><path d="M21 3v6h-6"/></svg>
        <span id="sync-text">Sync</span>
      </div>
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
        <input type="text" id="search-input" placeholder="Search products (name, SKU, barcode)..." oninput="renderProducts()">
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
        <button class="clear-btn" onclick="clearCart()">{{ __('Clear') }}</button>
      </div>
      <div class="cart-items" id="cart-items"></div>

      <!-- Detail section -->
      <div class="cart-detail">
        <div class="detail-row" onclick="toggleSection('note-section')">
          <span>{{ __('Note') }}</span>
          <span id="note-preview">—</span>
        </div>
        <div id="note-section" style="display:none;">
          <textarea id="cart-note" placeholder="Add note..." oninput="updateNote()"></textarea>
        </div>

        @if(config('setting.business_type') === 'fnb')
        <div class="detail-row" onclick="openTableModal()">
          <span>{{ __('Table') }}</span>
          <span id="table-preview">—</span>
        </div>
        @endif

        <div class="detail-row" onclick="toggleSection('discount-section')">
          <span>{{ __('Discount') }}</span>
          <span id="cart-discount-preview">—</span>
        </div>
        <div id="discount-section" style="display:none;">
          <input type="number" id="cart-discount" placeholder="0" min="0" oninput="updateCartDiscount()" inputmode="numeric">
        </div>

        <div class="detail-row" onclick="toggleSection('voucher-section')">
          <span>{{ __('Voucher') }}</span>
          <span id="voucher-preview">—</span>
        </div>
        <div id="voucher-section" style="display:none;">
          <select id="voucher-select" onchange="selectVoucher()" style="width:100%;padding:6px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;margin-top:4px;">
            <option value="">— {{ __("None") }} —</option>
          </select>
        </div>
      </div>

      <!-- Totals -->
      <div class="cart-footer">
        <div class="row"><span>{{ __('Subtotal') }}</span><span id="cart-subtotal">Rp 0</span></div>
        <div class="row" id="discount-row" style="display:none;"><span>{{ __('Discount') }}</span><span id="cart-discount-display" style="color:#ef4444;">(Rp 0)</span></div>
        <div class="row total"><span>{{ __('Total') }}</span><span class="grand-total" id="cart-total">Rp 0</span></div>
        <button class="pay-btn" onclick="openPaymentModal()">{{ __('Proceed to Payment') }}</button>
      </div>
    </div>
  </div>

  <!-- Payment Modal -->
  <div class="modal-overlay" id="payment-modal">
    <div class="backdrop" onclick="closePaymentModal()"></div>
    <div class="modal-content">
      <h2>{{ __('Payment') }}</h2>

      <!-- Payment methods grid -->
      <div class="payment-grid" id="payment-methods-grid"></div>

      <!-- Due date for credit -->
      <div class="form-group" id="due-date-group" style="display:none;">
        <label>{{ __('Due Date') }}</label>
        <input type="date" id="payment-due-date">
      </div>

      <!-- Totals summary -->
      <div style="background:#f3f4f6;border-radius:8px;padding:10px 12px;margin-bottom:12px;">
        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
          <span>{{ __('Subtotal') }}</span><span id="pm-subtotal">Rp 0</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;" id="pm-discount-row" class="hidden">
          <span>{{ __('Discount') }}</span><span id="pm-discount" style="color:#ef4444;">(Rp 0)</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:700;border-top:1px solid #e5e7eb;padding-top:4px;">
          <span>{{ __('Total') }}</span><span style="color:{{ $colorPrimary }};" id="pm-total">Rp 0</span>
        </div>
      </div>

      <!-- Pay amount display -->
      <input id="pay-amount-display" readonly
        style="width:100%;padding:12px;border:2px solid #d1d5db;border-radius:8px;font-size:18px;font-weight:700;text-align:right;background:#fff;margin-bottom:8px;"
        placeholder="0">

      <!-- Change display -->
      <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:12px;">
        <span>{{ __('Change') }}</span>
        <span id="pm-change" style="font-weight:700;color:#22c55e;">Rp 0</span>
      </div>

      <!-- Suggested payments -->
      <div class="suggested-payments" id="suggested-payments"></div>

      <!-- Numpad -->
      <div class="numpad">
        <button onclick="numpadAppend('1')">1</button>
        <button onclick="numpadAppend('2')">2</button>
        <button onclick="numpadAppend('3')">3</button>
        <button class="btn-backspace" onclick="numpadBackspace()">&#9003;</button>
        <button onclick="numpadAppend('4')">4</button>
        <button onclick="numpadAppend('5')">5</button>
        <button onclick="numpadAppend('6')">6</button>
        <button class="btn-no-change" onclick="numpadExact()">{{ __('Exact Amount') }}</button>
        <button onclick="numpadAppend('7')">7</button>
        <button onclick="numpadAppend('8')">8</button>
        <button onclick="numpadAppend('9')">9</button>
        <button id="pay-btn" class="btn-primary" onclick="confirmPayment()" style="grid-row:span 2;display:flex;align-items:center;justify-content:center;font-size:14px;">{{ __('Pay') }}</button>
        <button onclick="numpadAppend('0')" class="btn-wide">0</button>
        <button onclick="numpadAppend('00')">00</button>
      </div>

      <div class="modal-actions" style="margin-top:12px;">
        <button class="btn-cancel" onclick="closePaymentModal()">{{ __('Cancel') }}</button>
      </div>
    </div>
  </div>

  <!-- Success Modal -->
  <div class="modal-overlay" id="success-modal">
    <div class="backdrop"></div>
    <div class="modal-content">
      <div class="success-content">
        <div class="check">&#10004;</div>
        <p style="font-size:18px;font-weight:600;">{{ __('Success') }}</p>
        <div class="change-label">{{ __('Change') }}</div>
        <div class="change-amount" id="success-change">Rp 0</div>
      </div>
      <div class="modal-actions">
        <button class="btn-confirm" onclick="showReceiptPreview()">&#x1f5a8; {{ __('Print') }}</button>
        <button class="btn-cancel" onclick="closeSuccessModal()">{{ __('Close') }}</button>
      </div>
    </div>
  </div>

  <!-- Receipt Preview Modal -->
  <div class="modal-overlay" id="receipt-modal">
    <div class="backdrop" onclick="closeReceiptModal()"></div>
    <div class="modal-content" style="max-height:80vh;overflow-y:auto;">
      <h2>{{ __('Receipt Preview') }}</h2>
      <div class="receipt-preview" id="receipt-content"></div>
      <div class="modal-actions">
        <button class="btn-cancel" onclick="closeReceiptModal()">{{ __('Close') }}</button>
        <button class="btn-confirm" onclick="printReceipt()">&#x1f5a8; {{ __('Print') }}</button>
      </div>
    </div>
  </div>

  <!-- Table Selection Modal -->
  <div class="modal-overlay" id="table-modal">
    <div class="backdrop" onclick="closeTableModal()"></div>
    <div class="modal-content">
      <h2>{{ __('Select Table') }}</h2>
      <div class="table-grid" id="table-grid"></div>
      <div class="modal-actions">
        <button class="btn-cancel" onclick="closeTableModal()">{{ __('Cancel') }}</button>
        <button class="btn-confirm" onclick="saveTableSelection()">{{ __('Save') }}</button>
      </div>
    </div>
  </div>

  <!-- Scanner Modal -->
  <div class="modal-overlay" id="scanner-modal">
    <div class="backdrop" onclick="closeScanner()"></div>
    <div class="modal-content">
      <h2>{{ __('Scan Barcode') }}</h2>
      <div id="qr-reader"></div>
      <div class="modal-actions">
        <button class="btn-cancel" onclick="closeScanner()">{{ __('Close') }}</button>
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
  var tables = [];
  var vouchers = [];
  var about = null;
  var settings = {};
  var cart = {}; // { productId: { id, name, price, qty, discount_price } }
  var selectedCategory = null;
  var cartNote = '';
  var selectedTableId = null;
  var selectedVoucherCode = null;
  var cartDiscount = 0;
  var selectedPaymentMethodId = null;
  var pendingSaleData = null; // for receipt
  var displayValue = '';
  var currencySymbol = 'Rp';
  var DB_NAME = 'zonakasir_offline';
  var DB_VERSION = 2;

  // ─── Init ───────────────────────────────────────────────────
  openDB().then(function() {
    loadData();
    updateOnlineStatus();
    updateSyncBadge();
  }).catch(function(err) {
    showToast('Storage error: ' + (err.message || err), 'error');
  });

  window.addEventListener('online', function() { updateOnlineStatus(); syncNow(); });
  window.addEventListener('offline', updateOnlineStatus);

  // ─── IndexedDB ──────────────────────────────────────────────
  function openDB() {
    return new Promise(function(resolve, reject) {
      var req = indexedDB.open(DB_NAME, DB_VERSION);
      req.onsuccess = function(e) { db = e.target.result; resolve(db); };
      req.onerror = function(e) { reject(e.target.error); };
      req.onupgradeneeded = function(e) {
        var d = e.target.result;
        ['products','categories','members','payment_methods','about','tables','vouchers'].forEach(function(n) {
          if (!d.objectStoreNames.contains(n)) d.createObjectStore(n, { keyPath: 'id' });
        });
        ['pending_sales','queued_operations','api_cache'].forEach(function(n) {
          if (!d.objectStoreNames.contains(n)) d.createObjectStore(n, { keyPath: n === 'pending_sales' ? 'temp_id' : n === 'api_cache' ? 'url' : 'op_id' });
        });
        if (!d.objectStoreNames.contains('settings')) d.createObjectStore('settings', { keyPath: 'key' });
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

  function getSetting(key) {
    return new Promise(function(resolve) {
      try {
        var tx = db.transaction('settings', 'readonly');
        var req = tx.objectStore('settings').get(key);
        req.onsuccess = function() { resolve(req.result ? req.result.value : null); };
        req.onerror = function() { resolve(null); };
      } catch(e) { resolve(null); }
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
    getAll('products').then(function(p) { products = p; renderCategories(); renderProducts(); });
    getAll('categories').then(function(c) { categories = c; renderCategories(); });
    getAll('members').then(function(m) { members = m; });
    getAll('payment_methods').then(function(pm) { paymentMethods = pm; renderPaymentMethods(); });
    getAll('tables').then(function(t) { tables = t; });
    getAll('vouchers').then(function(v) { vouchers = v; renderVoucherOptions(); });
    getAll('about').then(function(a) { about = a[0] || null; });
    getSetting('currency').then(function(c) { if (c) currencySymbol = c === 'IDR' ? 'Rp' : c; });
    getSetting('default_tax').then(function(t) { if (t) settings.default_tax = parseFloat(t); });
  }

  // ─── Format ────────────────────────────────────────────────
  function formatPrice(n) {
    n = parseInt(n) || 0;
    return currencySymbol + ' ' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  function esc(s) {
    if (s == null) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }

  // ─── Render ────────────────────────────────────────────────
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
      el.innerHTML = '<div class="empty-state"><p>' + (q ? 'No products found' : 'No cached products. Go online first.') + '</p></div>';
      return;
    }

    var html = '';
    filtered.forEach(function(p) {
      var inCart = cart[p.id];
      var qty = inCart ? inCart.qty : 0;
      var stock = (p.stock_calculate !== undefined ? p.stock_calculate : p.stock || 0);
      var outOfStock = !p.is_non_stock && stock <= 0;
      var lowStock = !p.is_non_stock && !outOfStock && stock > 0 && stock <= (settings.minimum_stock_nofication || 10);

      html += '<div class="product-card" onclick="' + (outOfStock ? '' : 'addToCart(' + p.id + ')') + '">';
      html += '<div class="img">';
      if (p.hero_images_url && typeof p.hero_images_url === 'string') {
        html += '<img src="' + esc(p.hero_images_url) + '" alt="' + esc(p.name) + '" loading="lazy">';
      }
      if (outOfStock) html += '<div class="out-of-stock">Out of Stock</div>';
      if (lowStock) html += '<div class="low-stock">' + stock + ' {{ __("Stock") }}</div>';
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
  window.addToCart = function(productId) {
    var p = products.find(function(x) { return x.id === productId; });
    if (!p) return;
    if (!cart[productId]) {
      cart[productId] = { id: productId, name: p.name, price: p.selling_price_calculate || p.selling_price || 0, qty: 0, discount_price: 0 };
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
    cartNote = '';
    selectedTableId = null;
    selectedVoucherCode = null;
    cartDiscount = 0;
    document.getElementById('cart-note').value = '';
    document.getElementById('note-preview').textContent = '—';
    document.getElementById('cart-discount').value = '';
    document.getElementById('cart-discount-preview').textContent = '—';
    document.getElementById('table-preview').textContent = '—';
    document.getElementById('voucher-preview').textContent = '—';
    if (document.getElementById('voucher-select')) document.getElementById('voucher-select').value = '';
    updateCartUI();
  };

  window.setItemDiscount = function(productId, value) {
    if (!cart[productId]) return;
    var num = Math.max(0, parseInt(value) || 0);
    cart[productId].discount_price = num;
    updateCartUI();
  };

  function getCartTotal() {
    return Object.values(cart).reduce(function(sum, c) { return sum + (c.price * c.qty); }, 0);
  }

  function getCartTotalDiscount() {
    return Object.values(cart).reduce(function(sum, c) { return sum + (c.discount_price || 0); }, 0);
  }

  function getGrandTotal() {
    return Math.max(0, getCartTotal() - getCartTotalDiscount() - cartDiscount);
  }

  function updateCartUI() {
    var cartData = Object.values(cart);
    var totalQty = cartData.reduce(function(s, c) { return s + c.qty; }, 0);

    document.getElementById('cart-count').textContent = totalQty;
    document.getElementById('cart-subtotal').textContent = formatPrice(getCartTotal());
    document.getElementById('cart-total').textContent = formatPrice(getGrandTotal());

    // Discount row
    var totalDisc = getCartTotalDiscount() + cartDiscount;
    var discRow = document.getElementById('discount-row');
    if (totalDisc > 0) {
      discRow.style.display = 'flex';
      document.getElementById('cart-discount-display').textContent = '(' + formatPrice(totalDisc) + ')';
    } else {
      discRow.style.display = 'none';
    }

    renderProducts(); // refresh cart qty badges

    // Cart items
    var el = document.getElementById('cart-items');
    if (cartData.length === 0) {
      el.innerHTML = '<div style="text-align:center;padding:40px 16px;color:#9ca3af;font-size:14px;">Cart is empty</div>';
      return;
    }

    var html = '';
    cartData.forEach(function(c) {
      html += '<div class="cart-item">';
      html += '<div class="item-info">';
      html += '<div class="item-name">' + esc(c.name) + '</div>';
      html += '<div class="item-price">' + formatPrice(c.price) + '</div>';
      if (c.discount_price > 0) {
        html += '<div class="item-discount">-' + formatPrice(c.discount_price) + '</div>';
      }
      html += '<input class="discount-input" type="number" placeholder="{{ __("Discount") }}" value="' + (c.discount_price || '') + '" onchange="setItemDiscount(' + c.id + ', this.value)" inputmode="numeric" style="margin-top:4px;">';
      html += '</div>';
      html += '<div class="qty-ctrl">';
      html += '<button class="qty-btn-minus" onclick="removeFromCart(' + c.id + ')">-</button>';
      html += '<span class="qty-val">' + c.qty + '</span>';
      html += '<button class="qty-btn-plus" onclick="addToCart(' + c.id + ')">+</button>';
      html += '<button class="qty-btn-danger" onclick="removeAllFromCart(' + c.id + ')">&times;</button>';
      html += '</div>';
      html += '<div class="item-total">' + formatPrice(c.price * c.qty - (c.discount_price || 0)) + '</div>';
      html += '</div>';
    });
    el.innerHTML = html;
  }

  // ─── Detail Sections ───────────────────────────────────────
  window.toggleSection = function(sectionId) {
    var el = document.getElementById(sectionId);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
  };

  window.updateNote = function() {
    cartNote = document.getElementById('cart-note').value;
    document.getElementById('note-preview').textContent = cartNote || '—';
  };

  window.updateCartDiscount = function() {
    cartDiscount = Math.max(0, parseInt(document.getElementById('cart-discount').value) || 0);
    document.getElementById('cart-discount-preview').textContent = cartDiscount > 0 ? formatPrice(cartDiscount) : '—';
    updateCartUI();
  };

  window.selectVoucher = function() {
    var sel = document.getElementById('voucher-select');
    selectedVoucherCode = sel.value || null;
    document.getElementById('voucher-preview').textContent = selectedVoucherCode || '—';
  };

  function renderVoucherOptions() {
    var sel = document.getElementById('voucher-select');
    if (!sel || vouchers.length === 0) return;
    var html = '<option value="">— {{ __("None") }} —</option>';
    vouchers.forEach(function(v) {
      html += '<option value="' + esc(v.code) + '">' + esc(v.code) + ' — ' + esc(v.name) + '</option>';
    });
    sel.innerHTML = html;
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
  function renderPaymentMethods() {
    var el = document.getElementById('payment-methods-grid');
    var html = '';
    paymentMethods.forEach(function(pm) {
      html += '<div class="pm-btn" data-id="' + pm.id + '" onclick="selectPaymentMethod(' + pm.id + ')">' + esc(pm.name) + '</div>';
    });
    el.innerHTML = html;
    if (paymentMethods.length > 0) {
      selectPaymentMethod(paymentMethods[0].id);
    }
  }

  window.selectPaymentMethod = function(pmId) {
    selectedPaymentMethodId = pmId;
    var pm = paymentMethods.find(function(p) { return p.id === pmId; });
    document.querySelectorAll('.pm-btn').forEach(function(btn) {
      btn.classList.toggle('active', parseInt(btn.dataset.id) === pmId);
    });
    // Show/hide due date
    var dueGroup = document.getElementById('due-date-group');
    dueGroup.style.display = (pm && pm.is_credit) ? 'block' : 'none';
  };

  window.openPaymentModal = function() {
    var cartData = Object.values(cart);
    if (cartData.length === 0) { showToast('Cart is empty', 'error'); return; }
    closeCart();

    var total = getGrandTotal();
    displayValue = total.toString();

    document.getElementById('pm-subtotal').textContent = formatPrice(getCartTotal());

    var totalItemDisc = getCartTotalDiscount() + cartDiscount;
    var discRow = document.getElementById('pm-discount-row');
    if (totalItemDisc > 0) {
      discRow.style.removeProperty('display');
      document.getElementById('pm-discount').textContent = '(' + formatPrice(totalItemDisc) + ')';
    } else {
      discRow.style.display = 'none';
    }

    document.getElementById('pm-total').textContent = formatPrice(total);
    updatePayDisplay();
    generateSuggestedPayments(total);
    document.getElementById('payment-modal').classList.add('show');
  };

  window.closePaymentModal = function() {
    document.getElementById('payment-modal').classList.remove('show');
  };

  function generateSuggestedPayments(totalPrice) {
    var denominations = [500, 1000, 2000, 5000, 10000, 20000, 50000, 100000];
    var suggestions = [];
    for (var i = 0; i < denominations.length; i++) {
      var suggestion = Math.ceil(totalPrice / denominations[i]) * denominations[i];
      if (suggestions.indexOf(suggestion) === -1) suggestions.push(suggestion);
    }
    suggestions.sort(function(a, b) { return a - b; });

    var el = document.getElementById('suggested-payments');
    var html = '';
    suggestions.forEach(function(s) {
      html += '<button onclick="setPayAmount(' + s + ')">' + formatPrice(s) + '</button>';
    });
    el.innerHTML = html;
  }

  window.setPayAmount = function(amount) {
    displayValue = amount.toString();
    updatePayDisplay();
  };

  // ─── Numpad ────────────────────────────────────────────────
  window.numpadAppend = function(char) {
    displayValue += char;
    updatePayDisplay();
  };

  window.numpadBackspace = function() {
    displayValue = displayValue.slice(0, -1);
    updatePayDisplay();
  };

  window.numpadExact = function() {
    displayValue = getGrandTotal().toString();
    updatePayDisplay();
  };

  function updatePayDisplay() {
    var num = parseInt(displayValue) || 0;
    var total = getGrandTotal();
    var change = num - total;

    document.getElementById('pay-amount-display').value = formatPrice(num);
    var changeEl = document.getElementById('pm-change');
    changeEl.textContent = formatPrice(change > 0 ? change : 0);
    changeEl.style.color = change >= 0 ? '#22c55e' : '#ef4444';
  }

  // ─── Confirm Payment ──────────────────────────────────────
  window.confirmPayment = function() {
    var cartData = Object.values(cart);
    if (cartData.length === 0) { showToast('Cart is empty', 'error'); return; }

    var total = getGrandTotal();
    var payRaw = parseInt(displayValue) || 0;

    if (payRaw < total) {
      showToast('Insufficient payment amount', 'error');
      return;
    }

    var productsPayload = cartData.map(function(c) {
      return { product_id: c.id, qty: c.qty, price: c.price * c.qty, discount_price: c.discount_price || 0 };
    });

    var sale = {
      products: productsPayload,
      total_price: getCartTotal(),
      total_qty: cartData.reduce(function(s, c) { return s + c.qty; }, 0),
      payed_money: payRaw,
      money_changes: payRaw - total,
      friend_price: false,
      note: cartNote || null,
      discount_price: cartDiscount,
      table_id: selectedTableId || null,
      voucher: selectedVoucherCode || null,
      due_date: document.getElementById('payment-due-date') ? document.getElementById('payment-due-date').value || null : null,
    };

    var memberId = document.getElementById('payment-member') ? document.getElementById('payment-member').value : '';
    if (memberId) sale.member_id = parseInt(memberId);
    if (selectedPaymentMethodId) sale.payment_method_id = parseInt(selectedPaymentMethodId);

    // Save for receipt
    pendingSaleData = Object.assign({}, sale, {
      selling_details: productsPayload.map(function(p) {
        var prod = products.find(function(x) { return x.id === p.product_id; });
        return Object.assign({}, p, { product: { name: prod ? prod.name : 'Product' } });
      }),
      user: { name: 'Cashier' },
      payment_method: paymentMethods.find(function(pm) { return pm.id === selectedPaymentMethodId; }) || { name: 'Cash' },
      table: selectedTableId ? tables.find(function(t) { return t.id === selectedTableId; }) : null,
    });

    var payBtn = document.getElementById('pay-btn');
    if (payBtn) { payBtn.disabled = true; payBtn.textContent = 'Saving...'; }

    putPendingSale(sale).then(function() {
      closePaymentModal();
      var change = payRaw - total;

      // Show success modal
      document.getElementById('success-change').textContent = formatPrice(change);
      document.getElementById('success-modal').classList.add('show');

      // Reset cart
      cart = {};
      cartNote = '';
      selectedTableId = null;
      selectedVoucherCode = null;
      cartDiscount = 0;
      var vs = document.getElementById('voucher-select');
      if (vs) vs.value = '';
      document.getElementById('voucher-preview').textContent = '—';
      updateCartUI();
      updateSyncBadge();
    }).catch(function(err) {
      showToast('Failed to save: ' + err.message, 'error');
    }).finally(function() {
      var payBtn = document.getElementById('pay-btn');
      if (payBtn) { payBtn.disabled = false; payBtn.textContent = '{{ __("Pay") }}'; }
    });
  };

  // ─── Success Modal ────────────────────────────────────────
  window.closeSuccessModal = function() {
    document.getElementById('success-modal').classList.remove('show');
  };

  // ─── Receipt ──────────────────────────────────────────────
  window.showReceiptPreview = function() {
    closeSuccessModal();
    if (!pendingSaleData) return;

    var line = '─'.repeat(32);
    var h = '';

    if (about) {
      h += '<div style="text-align:center;font-weight:700;font-size:14px;">' + esc(about.shop_name) + '</div>';
      if (about.shop_location) h += '<div style="text-align:center;font-size:11px;">' + esc(about.shop_location) + '</div>';
    }
    h += '<div class="line">' + line + '</div>';
    h += '<div style="display:flex;justify-content:space-between;"><span>Cashier</span><span>' + esc(pendingSaleData.user.name) + '</span></div>';
    if (pendingSaleData.table) h += '<div style="display:flex;justify-content:space-between;"><span>Table</span><span>' + esc(pendingSaleData.table.number) + '</span></div>';
    h += '<div style="display:flex;justify-content:space-between;"><span>Payment</span><span>' + esc(pendingSaleData.payment_method.name) + '</span></div>';
    h += '<div class="line">' + line + '</div>';

    pendingSaleData.selling_details.forEach(function(d) {
      var qty = d.qty || 1;
      var ppu = d.price ? d.price / qty : 0;
      h += '<div style="display:flex;justify-content:space-between;"><span>' + esc(d.product.name) + '</span><span>' + formatPrice(Math.round(ppu)) + ' x ' + qty + '</span></div>';
      if (d.discount_price > 0) h += '<div style="text-align:right;">(' + formatPrice(d.discount_price) + ')</div>';
      h += '<div style="text-align:right;font-weight:600;">' + formatPrice(d.price || 0) + '</div>';
    });

    h += '<div class="line">' + line + '</div>';
    h += '<div style="display:flex;justify-content:space-between;"><span>{{ __("Subtotal") }}</span><span>' + formatPrice(pendingSaleData.total_price) + '</span></div>';
    if (pendingSaleData.discount_price > 0) {
      h += '<div style="display:flex;justify-content:space-between;"><span>{{ __("Discount") }}</span><span>(' + formatPrice(pendingSaleData.discount_price) + ')</span></div>';
    }
    var grandTotal = pendingSaleData.total_price - (pendingSaleData.discount_price || 0);
    h += '<div style="display:flex;justify-content:space-between;font-weight:700;"><span>{{ __("Total") }}</span><span>' + formatPrice(grandTotal) + '</span></div>';
    h += '<div class="line">' + line + '</div>';
    h += '<div style="display:flex;justify-content:space-between;"><span>{{ __("Paid") }}</span><span>' + formatPrice(pendingSaleData.payed_money) + '</span></div>';
    h += '<div style="display:flex;justify-content:space-between;"><span>{{ __("Change") }}</span><span>' + formatPrice(pendingSaleData.money_changes) + '</span></div>';
    if (pendingSaleData.note) h += '<div style="text-align:center;margin-top:6px;font-size:11px;">Note: ' + esc(pendingSaleData.note) + '</div>';
    h += '<div style="font-size:10px;margin-top:8px;text-align:center;">copy</div>';

    document.getElementById('receipt-content').innerHTML = h;
    document.getElementById('receipt-modal').classList.add('show');
  };

  window.closeReceiptModal = function() {
    document.getElementById('receipt-modal').classList.remove('show');
  };

  window.printReceipt = function() {
    closeReceiptModal();
    if (!pendingSaleData) return;

    // Try USB printer
    if (typeof window.printToUSBPrinter === 'function') {
      var line = '--------------------------------';
      var text = '';
      if (about) {
        text += about.shop_name + '\n';
        if (about.shop_location) text += about.shop_location + '\n';
      }
      text += line + '\n';
      text += 'Cashier: ' + pendingSaleData.user.name + '\n';
      if (pendingSaleData.table) text += 'Table: ' + pendingSaleData.table.number + '\n';
      text += 'Payment: ' + pendingSaleData.payment_method.name + '\n';
      text += line + '\n';
      pendingSaleData.selling_details.forEach(function(d) {
        var qty = d.qty || 1;
        var ppu = d.price ? Math.round(d.price / qty) : 0;
        text += d.product.name + '\n';
        text += '  ' + formatPrice(ppu) + ' x ' + qty + ' = ' + formatPrice(d.price) + '\n';
      });
      text += line + '\n';
      var grandTotal = pendingSaleData.total_price - (pendingSaleData.discount_price || 0);
      text += 'Total: ' + formatPrice(grandTotal) + '\n';
      text += 'Paid: ' + formatPrice(pendingSaleData.payed_money) + '\n';
      text += 'Change: ' + formatPrice(pendingSaleData.money_changes) + '\n';
      text += line + '\n';
      if (pendingSaleData.note) text += 'Note: ' + pendingSaleData.note + '\n';
      window.printToUSBPrinter(text);
      showToast('Printing...', 'info');
    } else {
      showToast('USB printer not available', 'error');
    }
  };

  // ─── Table Selection ──────────────────────────────────────
  window.openTableModal = function() {
    var el = document.getElementById('table-grid');
    if (tables.length === 0) {
      el.innerHTML = '<p style="grid-column:1/5;text-align:center;color:#9ca3af;">No tables cached</p>';
    } else {
      var html = '';
      tables.forEach(function(t) {
        html += '<div class="table-btn' + (selectedTableId === t.id ? ' active' : '') + '" onclick="pickTable(' + t.id + ', \'' + esc(t.number) + '\')">' + esc(t.number) + '</div>';
      });
      el.innerHTML = html;
    }
    document.getElementById('table-modal').classList.add('show');
  };

  window.closeTableModal = function() { document.getElementById('table-modal').classList.remove('show'); };

  window.pickTable = function(id, number) {
    selectedTableId = id;
    document.getElementById('table-preview').textContent = number;
    document.querySelectorAll('.table-btn').forEach(function(btn) {
      btn.classList.toggle('active', btn.textContent.trim() === number);
    });
  };

  window.saveTableSelection = function() { closeTableModal(); };

  // ─── Barcode Scanner ──────────────────────────────────────
  window.openScanner = function() {
    if (typeof Html5Qrcode === 'undefined') {
      var script = document.createElement('script');
      script.src = '/js/app/html5-qrcode.min.js';
      script.onload = function() { startScanner(); };
      script.onerror = function() {
        // Fallback to CDN if local not cached
        var fallback = document.createElement('script');
        fallback.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
        fallback.onload = function() { startScanner(); };
        fallback.onerror = function() { showToast('Scanner library not available offline', 'error'); };
        document.head.appendChild(fallback);
      };
      document.head.appendChild(script);
    } else {
      startScanner();
    }
  };

  var html5QrCode = null;
  function startScanner() {
    document.getElementById('scanner-modal').classList.add('show');
    if (!html5QrCode) {
      html5QrCode = new Html5Qrcode('qr-reader');
    }
    html5QrCode.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: { width: 250, height: 150 } },
      function(decodedText) {
        // Find product by barcode or SKU
        var product = products.find(function(p) { return p.barcode === decodedText || p.sku === decodedText; });
        if (product) {
          addToCart(product.id);
          showToast('Added: ' + product.name, 'success');
        } else {
          showToast('Product not found: ' + decodedText, 'error');
        }
      },
      function() {} // ignore errors
    ).catch(function(err) {
      showToast('Camera error: ' + err, 'error');
    });
  }

  window.closeScanner = function() {
    document.getElementById('scanner-modal').classList.remove('show');
    if (html5QrCode) {
      html5QrCode.stop().catch(function() {});
    }
  };

  // Stop camera when page becomes hidden
  document.addEventListener('visibilitychange', function() {
    if (document.hidden && html5QrCode) closeScanner();
  });

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
        // Refresh master data
        refreshMasterData();
        return;
      }

      var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

      var tx = db.transaction('pending_sales', 'readonly');
      var req = tx.objectStore('pending_sales').getAll();
      req.onsuccess = function() {
        var pending = (req.result || []).filter(function(s) { return s.status === 'pending'; });
        var done = 0, fail = 0;

        pending.forEach(function(sale) {
          fetch('/api/sync/submit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            credentials: 'same-origin',
            body: JSON.stringify({
              products: sale.products,
              total_price: sale.total_price,
              total_qty: sale.total_qty,
              payed_money: sale.payed_money,
              money_changes: sale.money_changes,
              friend_price: sale.friend_price,
              note: sale.note,
              discount_price: sale.discount_price,
              table_id: sale.table_id,
              member_id: sale.member_id,
              payment_method_id: sale.payment_method_id,
              voucher: sale.voucher
            }),
          }).then(function(r) {
            if (r.ok) {
              var tx2 = db.transaction('pending_sales', 'readwrite');
              sale.status = 'synced';
              sale.synced_at = new Date().toISOString();
              tx2.objectStore('pending_sales').put(sale);
              done++;
            } else { fail++; }
          }).catch(function() { fail++; }).finally(function() {
            if (done + fail >= pending.length) {
              el.classList.remove('syncing');
              text.textContent = 'Sync (' + done + ' ok)';
              showToast(done + '/' + (done + fail) + ' synced', fail > 0 ? 'error' : 'success');
              updateSyncBadge();
              if (done > 0) refreshMasterData();
              setTimeout(function() { text.textContent = 'Sync'; }, 5000);
            }
          });
        });
      };
    });
  };

  function refreshMasterData() {
    if (!navigator.onLine) return;
    var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    if (!csrfToken) return;

    fetch('/api/sync/data', {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
      credentials: 'same-origin',
    }).then(function(r) { return r.ok ? r.json() : null; }).then(function(resp) {
      if (!resp) return;
      var data = resp.data || resp;
      var isDelta = data.is_delta === true;

      function upsertStore(name, items) {
        if (!Array.isArray(items) || items.length === 0) return;
        try {
          var tx = db.transaction(name, 'readwrite');
          if (!isDelta) tx.objectStore(name).clear();
          items.forEach(function(item) { tx.objectStore(name).put(item); });
        } catch(e) {}
      }

      upsertStore('products', data.products);
      upsertStore('categories', data.categories);
      upsertStore('members', data.members);
      upsertStore('payment_methods', data.payment_methods);
      upsertStore('tables', data.tables);
      upsertStore('vouchers', data.vouchers);

      if (data.about) {
        try { var tx = db.transaction('about', 'readwrite'); tx.objectStore('about').clear(); tx.objectStore('about').put(data.about); } catch(e) {}
      }
      if (data.settings) {
        try { var tx = db.transaction('settings', 'readwrite'); for (var k in data.settings) tx.objectStore('settings').put({ key: k, value: data.settings[k] }); } catch(e) {}
      }

      try {
        var tx = db.transaction('meta', 'readwrite');
        var ts = new Date().toISOString();
        tx.objectStore('meta').put({ key: 'last_prefetch', value: ts });
        tx.objectStore('meta').put({ key: 'last_sync_at', value: ts });
      } catch(e) {}

      loadData(); // reload
    }).catch(function() {});
  }

  function updateSyncBadge() {
    countPendingSales().then(function(count) {
      var el = document.getElementById('sync-text');
      if (el) {
        if (count > 0) {
          el.textContent = count + ' pending';
        } else {
          el.textContent = 'Synced';
        }
      }
    });
  }

  // ─── Online Status ─────────────────────────────────────────
  function updateOnlineStatus() {
    var badge = document.getElementById('offline-badge');
    var dot = document.getElementById('conn-dot');
    if (navigator.onLine) {
      if (badge) badge.classList.remove('show');
      if (dot) { dot.className = 'offline-dot online'; }
    } else {
      if (badge) badge.classList.add('show');
      if (dot) { dot.className = 'offline-dot offline'; }
    }
  }

  // ─── Toast ─────────────────────────────────────────────────
  function showToast(msg, type) {
    var el = document.getElementById('toast');
    el.textContent = msg;
    el.className = 'toast ' + (type || 'success') + ' show';
    setTimeout(function() { el.classList.remove('show'); }, 3000);
  }

  updateSyncBadge();
})();
</script>
</body>
</html>
