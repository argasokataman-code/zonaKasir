@php
  use Filament\Facades\Filament;
  use App\Features\{PaymentShortcutButton, SellingTax, Discount};

@endphp
<div class="" x-data="{
  cartOpen: $persist(false).as('cashier_cartOpen'),
  isOffline: !navigator.onLine,
  isPWA: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
  offlineProducts: [],
  offlineCategories: [],
  offlineCart: {},
  offlineSelectedCategory: null,
  offlineSearch: '',
  offlineDb: null,
  paymentModalOpen: false,
  selectedPaymentMethod: null,
  offlineCartNote: '',
  offlineCartDiscount: 0,
  offlinePaymentMethod: 'cash',
  syncProgress: 0,
  syncStatus: '',
  showSyncSplash: false,
  // Cart badge qty map — product_id => qty (optimistic, updated instantly)
   cartQty: {{ Js::from(collect($cartItems)->pluck('qty', 'product_id')->toArray()) }},
   debounceTimers: {},

   // Instant add — update badge instantly, sync server in background
   instantAdd(productId) {
     this.cartQty[productId] = (this.cartQty[productId] || 0) + 1;
     clearTimeout(this.debounceTimers[productId]);
     this.debounceTimers[productId] = setTimeout(() => {
       if (navigator.onLine) {
         $wire.addCart(productId, { _bulk: this.cartQty[productId] });
       }
     }, 300);
   },

   instantReduce(productId) {
     if ((this.cartQty[productId] || 0) > 0) {
       this.cartQty[productId]--;
       if (this.cartQty[productId] <= 0) delete this.cartQty[productId];
     }
     clearTimeout(this.debounceTimers[productId]);
     this.debounceTimers[productId] = setTimeout(() => {
       if (navigator.onLine) {
         const qty = this.cartQty[productId] || 0;
         $wire.addCart(productId, { _bulk: qty });
       }
     }, 300);
   },

   // Sync badge from server after Livewire action completes
   handleCartDataUpdated(event) {
     // Livewire v3 detail = [ {cartItems} ]; fallback for bare {cartItems}
     const raw = event.detail;
     const data = Array.isArray(raw) ? raw[0] : raw;
     this.cartQty = data?.cartItems || {};
   },

   async initOfflineDb() {
    if (this.offlineDb) return this.offlineDb;
    return new Promise((resolve, reject) => {
      const req = indexedDB.open('zonakasir_offline', 2);
      req.onsuccess = (e) => { this.offlineDb = e.target.result; resolve(this.offlineDb); };
      req.onerror = (e) => reject(e.target.error);
      req.onupgradeneeded = (e) => {
        const db = e.target.result;
        ['products','categories','members','payment_methods','about','settings','pending_sales','meta'].forEach(n => {
          if (!db.objectStoreNames.contains(n)) db.createObjectStore(n, { keyPath: n === 'pending_sales' ? 'temp_id' : n === 'settings' ? 'key' : n === 'meta' ? 'key' : 'id' });
        });
      };
    });
  },

  async loadOfflineData() {
    try {
      const db = await this.initOfflineDb();
      const tx = db.transaction('products', 'readonly');
      const req = tx.objectStore('products').getAll();
      req.onsuccess = () => { this.offlineProducts = req.result || []; };
    } catch(e) { console.error('[Offline] Load products error:', e); }
    try {
      const db = await this.initOfflineDb();
      const tx = db.transaction('categories', 'readonly');
      const req = tx.objectStore('categories').getAll();
      req.onsuccess = () => { this.offlineCategories = req.result || []; };
    } catch(e) { console.error('[Offline] Load categories error:', e); }
  },

  get filteredOfflineProducts() {
    let filtered = this.offlineProducts;
    if (this.offlineSelectedCategory) filtered = filtered.filter(p => p.category_id === this.offlineSelectedCategory);
    if (this.offlineSearch) {
      const q = this.offlineSearch.toLowerCase();
      filtered = filtered.filter(p => (p.name && p.name.toLowerCase().includes(q)) || (p.sku && p.sku.toLowerCase().includes(q)) || (p.barcode && p.barcode.toLowerCase().includes(q)));
    }
    return filtered;
  },

  offlineAddToCart(productId) {
    const p = this.offlineProducts.find(x => x.id === productId);
    if (!p || (!p.is_non_stock && (p.stock_calculate !== undefined ? p.stock_calculate : p.stock || 0) <= 0)) return;
    if (!this.offlineCart[productId]) this.offlineCart[productId] = { id: productId, name: p.name, price: p.selling_price_calculate || p.selling_price || 0, qty: 0, discount_price: 0 };
    this.offlineCart[productId].qty++;
  },

  offlineRemoveFromCart(productId) {
    if (!this.offlineCart[productId]) return;
    this.offlineCart[productId].qty--;
    if (this.offlineCart[productId].qty <= 0) delete this.offlineCart[productId];
  },

  get offlineCartCount() {
    return Object.values(this.offlineCart).reduce((sum, item) => sum + item.qty, 0);
  },

  get offlineCartSubtotal() {
    return Object.values(this.offlineCart).reduce((sum, item) => sum + (item.price * item.qty), 0);
  },

  async saveOfflineSale() {
    const db = await this.initOfflineDb();
    const tx = db.transaction('pending_sales', 'readwrite');
    const entry = {
      temp_id: 'offline_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
      products: Object.values(this.offlineCart),
      total_price: this.offlineCartSubtotal - this.offlineCartDiscount,
      total_qty: this.offlineCartCount,
      payed_money: 0,
      money_changes: 0,
      discount_price: this.offlineCartDiscount,
      note: this.offlineCartNote,
      payment_method_id: this.selectedPaymentMethod,
      status: 'pending',
      created_at: new Date().toISOString(),
      synced: false
    };
    tx.objectStore('pending_sales').put(entry);
    this.offlineCart = {};
    this.offlineCartDiscount = 0;
    this.offlineCartNote = '';
    this.paymentModalOpen = false;
  },

  async runStartupSync() {
    if (!this.isPWA || !navigator.onLine) return;

    // Init DB first — fail gracefully if unavailable
    let db;
    try {
      db = await this.initOfflineDb();
    } catch(e) {
      console.error('[PWA] IndexedDB unavailable, skipping sync:', e);
      return;
    }

    // Check if data is already fresh (< 30 min old)
    try {
      const tx = db.transaction('meta', 'readonly');
      const req = tx.objectStore('meta').get('last_prefetch');
      const lastPrefetch = await new Promise(r => { req.onsuccess = () => r(req.result); req.onerror = () => r(null); });
      if (lastPrefetch && lastPrefetch.value) {
        const age = Date.now() - new Date(lastPrefetch.value).getTime();
        if (age < 30 * 60 * 1000) return; // fresh, skip sync
      }
    } catch(e) {}

    // Show splash
    this.showSyncSplash = true;
    this.syncProgress = 0;
    this.syncStatus = 'Syncing data...';

    try {
      // Single Livewire call — uses session auth + tenant context
      const data = await $wire.call('getOfflineSyncData');
      this.syncProgress = 60;

      // Write each dataset to IndexedDB
      const stores = [
        { key: 'products', items: data.products || [] },
        { key: 'categories', items: data.categories || [] },
        { key: 'members', items: data.members || [] },
        { key: 'payment_methods', items: data.payment_methods || [] },
        { key: 'about', items: data.about ? [data.about] : [], single: true },
      ];

      for (const store of stores) {
        const tx = db.transaction(store.key, 'readwrite');
        const objStore = tx.objectStore(store.key);
        objStore.clear();
        if (store.single) {
          store.items.forEach(item => objStore.put(item));
        } else {
          store.items.forEach(item => objStore.put(item));
        }
      }

      // Save sync timestamp
      const metaTx = db.transaction('meta', 'readwrite');
      metaTx.objectStore('meta').put({ key: 'last_prefetch', value: new Date().toISOString() });

      this.syncProgress = 100;
      this.syncStatus = 'Ready!';
      await new Promise(r => setTimeout(r, 500));
      this.showSyncSplash = false;
      this.loadOfflineData();
    } catch(e) {
      console.error('[PWA] Sync failed:', e);
      this.syncStatus = 'Sync failed';
      await new Promise(r => setTimeout(r, 1000));
      this.showSyncSplash = false;
    }
  },

  init: function() {
    if (!navigator.onLine && !this.isPWA) {
      window.location.href = '/network-error';
      return;
    }
    window.addEventListener('online', () => { this.isOffline = false; });
    window.addEventListener('offline', () => {
      this.isOffline = true;
      if (this.isPWA) { this.loadOfflineData(); }
      else { window.location.href = '/network-error'; }
    });
    if (this.isPWA && !navigator.onLine) this.loadOfflineData();
    if (this.isPWA) this.runStartupSync();
  },

}" x-on:cart-data-updated.window="handleCartDataUpdated">

  {{-- ═══ SYNC SPLASH SCREEN (PWA only) ═══ --}}
  <div x-show="showSyncSplash" x-cloak
    style="position:fixed;inset:0;z-index:999999;background:#FF6600;display:flex;flex-direction:column;align-items:center;justify-content:center;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;">
    <div style="text-align:center;color:#fff;">
      {{-- Logo --}}
      <div style="font-size:48px;margin-bottom:16px;">⚡</div>
      <h1 style="font-size:24px;font-weight:700;margin-bottom:8px;">{{ config('app.name') }}</h1>
      <p style="font-size:14px;opacity:0.85;margin-bottom:32px;" x-text="syncStatus"></p>
      {{-- Progress bar --}}
      <div style="width:280px;height:6px;background:rgba(255,255,255,0.3);border-radius:3px;overflow:hidden;margin:0 auto 12px;">
        <div :style="'width:' + syncProgress + '%;height:100%;background:#fff;border-radius:3px;transition:width 0.3s ease;'"></div>
      </div>
      <p style="font-size:13px;opacity:0.7;" x-text="syncProgress + '%'"></p>
    </div>
  </div>

  {{-- Offline Mode Indicator --}}
  <div x-show="isOffline && isPWA" x-cloak
    class="mb-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 flex items-center gap-3 dark:bg-amber-900/20 dark:border-amber-800">
    <span class="text-amber-600 dark:text-amber-400">
      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
      </svg>
    </span>
    <div class="flex-1">
      <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">{{ __('Offline Mode Active') }}</p>
      <p class="text-xs text-amber-600 dark:text-amber-400">{{ __('Transactions will sync when you are back online. Cash payment only.') }}</p>
    </div>
    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
  </div>

  <div class="lg:flex">
    <div class="lg:w-[60%] pb-24 lg:pb-0">
      {{-- Mobile back button --}}
      <div class="mb-2 flex items-center gap-2 lg:hidden">
        <a href="/member/sellings"
          class="flex items-center gap-1 rounded-lg bg-gray-100 px-3 py-2 text-sm text-gray-600 dark:bg-gray-700 dark:text-gray-300">
          <x-heroicon-o-arrow-left class="h-4 w-4" />
          <span>{{ __('Back') }}</span>
        </a>
      </div>
      {{-- Search --}}
      <div class="mb-4 px-1">
        <div class="relative">
          <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
          <input type="text" wire:model.live.debounce.300ms="search"
            class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 focus:border-zonakasir-primary focus:outline-none focus:ring-1 focus:ring-zonakasir-primary dark:border-gray-600 dark:bg-gray-800 dark:text-white"
            placeholder="{{ __('Search (SKU, name, barcode)') }}">
        </div>
      </div>

      {{-- Categories --}}
      <div class="mb-4 flex gap-2 overflow-x-auto px-1">
        <button wire:click="$set('selectedCategory', null)"
          class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-medium transition-colors {{ is_null($selectedCategory) ? 'bg-zonakasir-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300' }}">
          {{ __('All') }}
        </button>
        @foreach ($categories as $category)
          <button wire:click="$set('selectedCategory', {{ $category->id }})"
            class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-medium transition-colors {{ $selectedCategory === $category->id ? 'bg-zonakasir-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300' }}">
            {{ $category->name }}
          </button>
        @endforeach
      </div>

      {{-- Product Cards Grid --}}
      <div wire:loading.class="opacity-60" wire:target="search,selectedCategory" x-show="!isOffline" class="grid grid-cols-2 gap-2 sm:gap-3 sm:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        @forelse ($products as $product)
          <div class="group relative flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition-all hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
            {{-- Image --}}
            <div class="relative aspect-[4/3] overflow-hidden bg-gray-100 dark:bg-gray-700">
              @php $heroImage = $product->heroImage; @endphp
              @if ($heroImage)
                <img src="{{ $heroImage }}" alt="{{ $product->name }}" class="h-full w-full object-cover transition-transform group-hover:scale-105">
              @else
                <div class="flex h-full items-center justify-center text-gray-400">
                  <x-heroicon-o-photo class="h-10 w-10" />
                </div>
              @endif

              {{-- Stock badge --}}
                @if (!$product->is_non_stock)
                  @php $stock = $product->stockCalculate; @endphp
                  @if ($stock <= 0)
                    <div class="absolute inset-0 flex items-center justify-center bg-black/50">
                      <span class="rounded-md bg-red-600 px-2 py-1 text-xs font-bold text-white">{{ __('Out of stock') }}</span>
                    </div>
                  @else
                    <span class="absolute left-2 top-2 rounded-md px-1.5 py-0.5 text-xs font-bold text-white shadow-sm {{ $stock < \App\Models\Tenants\Setting::get('minimum_stock_nofication', 10) ? 'bg-amber-500' : 'bg-zonakasir-primary' }}">{{ $stock }} {{ __('Stock') }}</span>
                  @endif
                @endif

              {{-- Cart quantity badge --}}
              <span x-show="cartQty[{{ $product->id }}]" x-cloak class="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-zonakasir-primary text-xs font-bold text-white shadow-sm" x-text="cartQty[{{ $product->id }}]"></span>
            </div>

            {{-- Info --}}
            <div class="flex flex-1 flex-col justify-between p-3">
              <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $product->sku }}</p>
                <h3 class="mt-0.5 text-sm font-semibold leading-tight text-gray-900 dark:text-white line-clamp-2">{{ $product->name }}</h3>
              </div>
              <div class="mt-2 flex items-center">
                <span class="text-sm font-bold text-zonakasir-primary">{{ price_format($product->sellingPriceCalculate) }}</span>
                <div class="ml-auto flex items-center justify-end min-w-[100px] min-h-[44px]">
                  <button x-show="!cartQty[{{ $product->id }}]" @click="instantAdd({{ $product->id }})"
                    class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full bg-zonakasir-primary text-white transition-colors hover:bg-zonakasir-primary/90">
                    <x-heroicon-o-plus class="h-5 w-5" />
                  </button>
                  <div x-show="cartQty[{{ $product->id }}]" x-cloak class="flex items-center gap-1">
                    <button @click="instantReduce({{ $product->id }})"
                      class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full bg-gray-100 text-gray-600 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                      <x-heroicon-o-minus-small class="h-5 w-5" />
                    </button>
                    <span class="w-8 text-center text-sm font-semibold text-zonakasir-primary" x-text="cartQty[{{ $product->id }}]"></span>
                    <button @click="instantAdd({{ $product->id }})"
                      class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full bg-zonakasir-primary text-white transition-colors hover:bg-zonakasir-primary/90">
                      <x-heroicon-o-plus-small class="h-5 w-5" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        @empty
          <div class="col-span-full flex flex-col items-center justify-center py-16 text-gray-400">
            <x-heroicon-o-cube class="h-16 w-16" />
            <p class="mt-2 text-lg font-medium">{{ __('Product not found') }}</p>
          </div>
        @endforelse
      </div>

      @include('filament.tenant.pages.cashier._offline')
    </div>

    {{-- Mobile: cart toggle button with scan --}}
    <div class="fixed bottom-0 left-0 right-0 z-50 border-t bg-white px-3 pb-[env(safe-area-inset-bottom)] pt-3 shadow-lg dark:border-gray-800 dark:bg-gray-900 lg:hidden"
      x-show="!cartOpen">
      <div class="flex gap-2">
        <button @click="cartOpen = true"
          class="flex flex-1 items-center justify-between rounded-lg bg-zonakasir-primary px-4 py-3 min-h-[48px] text-white">
          <span class="font-semibold">{{ __('View Cart') }}</span>
          <span class="flex items-center gap-2">
            <span class="rounded-full bg-white/20 px-2 py-0.5 text-sm">{{ $cartCount }}</span>
            <x-heroicon-o-chevron-up class="h-5 w-5" />
          </span>
        </button>
        <button
          x-on:click="
            if (navigator.mediaDevices?.getUserMedia) {
              navigator.mediaDevices.getUserMedia({ video: true }).then(function(stream) {
                stream.getTracks().forEach(t => t.stop());
                $dispatch('open-modal', {id: 'qr-scanner-modal'});
              }).catch(function(err) {
                new FilamentNotification().title('Camera permission denied: ' + err.message).danger().send();
              });
            } else {
              $dispatch('open-modal', {id: 'qr-scanner-modal'});
            }
          "
          type="button"
          class="flex min-h-[48px] min-w-[48px] items-center justify-center rounded-lg bg-gray-100 px-3 py-3 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
          <x-heroicon-o-qr-code class="h-6 w-6" />
        </button>
      </div>
    </div>

    {{-- Sidebar: always visible on desktop, bottom sheet on mobile --}}
    <div class="fixed inset-x-0 bottom-0 z-50 max-h-[85vh] overflow-y-auto rounded-t-2xl bg-white shadow-2xl transition-transform duration-300 dark:bg-gray-900 lg:fixed lg:left-auto lg:right-0 lg:top-0 lg:bottom-auto lg:z-auto lg:h-screen lg:w-[40%] xl:w-1/3 lg:max-h-none lg:rounded-none lg:shadow-none"
      x-bind:class="cartOpen ? 'translate-y-0' : 'translate-y-full lg:translate-y-0'"
      x-cloak>
      <div class="flex items-center justify-between border-b p-3 dark:border-gray-800 lg:hidden">
        <p class="text-base font-semibold">{{ __('Orders details') }}</p>
        <button @click="cartOpen = false" class="rounded-full p-1 hover:bg-gray-100 dark:hover:bg-gray-800">
          <x-heroicon-o-x-mark class="h-5 w-5" />
        </button>
      </div>
      <div class="h-full space-y-2 px-3 pb-24 lg:pb-10">
        <div class="flex items-center justify-between" x-data="fullscreen">
          <p class="text-lg font-semibold hidden lg:block">{{ __('Orders details') }}</p>
          <div class="flex items-center">
            <div class="flex items-center gap-x-2">
              <a href="/member/sellings"
                class="flex items-center justify-center gap-x-1 rounded-lg bg-gray-100 px-4 py-1 text-gray-500 dark:bg-gray-700 dark:text-gray-300">
                <x-heroicon-o-arrow-left class="h-4 w-4" />
                <p class="text-sm">{{ __('Back') }} </p>
              </a>

              <button
                x-on:click="
                  if (navigator.mediaDevices?.getUserMedia) {
                    navigator.mediaDevices.getUserMedia({ video: true }).then(function(stream) {
                      stream.getTracks().forEach(t => t.stop());
                      $dispatch('open-modal', {id: 'qr-scanner-modal'});
                    }).catch(function(err) {
                      new FilamentNotification().title('Camera permission denied: ' + err.message).danger().send();
                    });
                  } else {
                    $dispatch('open-modal', {id: 'qr-scanner-modal'});
                  }
                "
                type="button"
                class="rounded-full p-2 hover:bg-gray-100 dark:hover:bg-gray-800" aria-label="Scan with camera">
                <x-heroicon-o-qr-code class="h-8 w-8 text-gray-900 dark:text-gray-300" />
              </button>

            </div>
            <div class="gap-x-2">
              <x-filament::dropdown placement="top-start">
                <x-slot name="trigger">
                  <x-heroicon-o-ellipsis-vertical class="h-8 w-8 cursor-pointer text-gray-900 dark:text-gray-300" />
                </x-slot>

                <x-filament::dropdown.list>
                  <x-filament::dropdown.list.item x-on:mousedown="document.location.reload()">
                    <div class="flex gap-x-2">
                      <x-heroicon-m-arrow-path class="h-5 w-5 cursor-pointer text-gray-900 dark:text-gray-300" />
                      <p>{{ __('Reload') }} </p>
                    </div>
                  </x-filament::dropdown.list.item>

                  <x-filament::dropdown.list.item x-on:mousedown="requestFullscreen">
                    <div class="flex gap-x-2">
                      <x-heroicon-o-arrows-pointing-out
                        class="h-5 w-5 cursor-pointer text-gray-900 dark:text-gray-300" />
                      <p>{{ __('Fullscreen') }} </p>
                    </div>
                  </x-filament::dropdown.list.item>
                  <x-filament::dropdown.list.item>
                    <p class="flex gap-x-2" wire:confirm="Are you sure you want to clear all of the items?"
                      wire:click.prevent="clearCart">
                      <x-heroicon-o-trash class="h-5 w-5 cursor-pointer text-gray-900 dark:text-gray-300" />
                      <span>{{ __('Clear') }} </span>
                    </p>
                  </x-filament::dropdown.list.item>

                </x-filament::dropdown.list>
              </x-filament::dropdown>
            </div>
          </div>
        </div>
        <hr class="my-1" />
        <div class="hidden justify-between lg:flex">
          <p class="text-sm">{{ Filament::auth()->user()->cashier_name }}</p>
        </div>
        <div class="flex items-center justify-between">
          <p class="mb-1 hidden text-xl font-semibold lg:block">{{ __('Current Orders') }}</p>
          <div class="flex gap-x-1"></div>
        </div>
        <div class="max-h-[40vh] min-h-32 overflow-auto" wire:loading.class="opacity-20"
          wire:target="addCart,reduceCart,deleteCart,addDiscountPricePerItem,addCartUsingScanner">
          @forelse($cartItems as $item)
            <div class="mb-2 rounded-lg border bg-white px-3 py-2 dark:border-gray-900 dark:bg-gray-900"
              id="cart-item-{{ $item->id }}" key="cart-item-{{ $item->id }}">
              <div class="grid items-center space-x-3">
                <div class="flex justify-between">
                  <p class="font-semibold text-sm"> {{ $item->product?->name ?? __('Deleted product') }}</p>
                  <p class="font-semibold text-sm text-zonakasir-primary">{{ $item->price_format_money }}</p>
                </div>
              </div>
              <div class="grid grid-cols-2 items-center space-y-1 py-1 text-right">
                <div class="col-span-2">
                  @feature(Discount::class)
                    <div class="mb-1 flex justify-end">
                      <x-filament::input.wrapper class="w-1/2">
                        <x-filament::input type="text" id="discount-{{ $item->id }}"
                          value="{{ $item->discount_price == 0 ? '' : $item->discount_price }}"
                          wire:keyup.debounce.500ms="addDiscountPricePerItem({{ $item }}, parseFloat($event.target.value.replace(/,/g, '')))"
                          placeholder="{{ __('Discount') }}" class="w-1/2 text-right text-sm" inputMode="numeric"
                          x-mask:dynamic="$money($input)" />
                      </x-filament::input.wrapper>
                    </div>
                  @endfeature
                  @if ($item->discount_price && $item->discount_price > 0)
                    <p class="font-semibold text-sm text-zonakasir-primary">{{ $item->final_price_format }}</p>
                  @endif
                </div>
              </div>
              <div class="flex h-7 space-x-2">
                <button class="rounded-lg !bg-zonakasir-primary px-2 py-0.5 text-sm"
                  wire:click.stop="addCart( {{ $item->product_id }} )" wire:loading.attr="disabled">
                  <x-heroicon-o-plus-small class="h-3.5 w-3.5 !text-white" />
                </button>
                <x-filament::input.wrapper class="w-16">
                  <x-filament::input type="text"
                    id="cart-qty-{{ $item->id }}"
                    data-value="{{ $item->qty }}" value="{{ $item->qty }}"
                    x-on:keyup.debounce.500ms="(e) => add('{{ $item->product_id }}', e.target.value)"
                    placeholder="{{ __('Qty') }}" class="w-1/2 text-right text-sm" inputMode="numeric" />
                </x-filament::input.wrapper>
                <button class="rounded-lg !bg-gray-100 px-2 py-0.5"
                  x-on:click="$wire.reduceCart({{ $item->product_id }});" wire:loading.attr="disabled">
                  <x-heroicon-o-minus-small class="h-3.5 w-3.5 !text-green-900" />
                </button>
                <button class="rounded-lg !bg-danger-100 px-2 py-0.5" wire:click.stop="deleteCart({{ $item->product_id }})"
                  wire:loading.attr="disabled" type="button">
                  <x-heroicon-o-trash class="h-3.5 w-3.5 !text-danger-900" />
                </button>
                <livewire:price-setting :cart-item="$item" key="{{ $item->id }}" />
              </div>
            </div>
          @empty
            <div
              class="flex h-32 items-center justify-center rounded-lg border bg-white dark:border-gray-900 dark:bg-gray-900">
              <x-heroicon-o-x-mark class="hidden h-8 w-8 text-gray-900 dark:text-white lg:block" />
              <p class="text-lg text-gray-600 dark:text-white lg:text-2xl">{{ __('No item') }}</p>
            </div>
          @endforelse
        </div>
        <div>
          <div
            class="w-full rounded-lg border bg-white px-3 py-1.5 text-sm text-gray-600 dark:border-gray-900 dark:bg-gray-900 dark:text-white">
            @include('filament.tenant.pages.cashier.detail')
          </div>
        </div>
        <div>
          <div
            class="w-full rounded-lg border bg-white px-3 py-1.5 text-sm text-gray-600 dark:border-gray-900 dark:bg-gray-900 dark:text-white">
            @include('filament.tenant.pages.cashier.total')
          </div>
        </div>
        <button class="w-full rounded-lg bg-zonakasir-primary px-2 py-3 text-sm font-semibold text-white"
          x-on:mousedown="if(isOffline) { $dispatch('open-modal', {id: 'offline-payment-confirm'}); } else { cartOpen = false; $dispatch('open-modal', {id: 'proceed-the-payment'}); }">{{ __('Proceed to payment') }}</button>
      </div>
    </div>
  </div>
  @include('filament.tenant.pages.cashier._modals')
</div>

@include('filament.tenant.pages.cashier._scripts')
