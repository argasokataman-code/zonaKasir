@php
  use Filament\Facades\Filament;
  use App\Features\{PaymentShortcutButton, SellingTax, Discount};

@endphp
<div class="" x-data="{
  cartOpen: false,
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
  }
}" x-init="
  // PWA check: redirect to network error page if offline in regular browser
  if (!navigator.onLine && !isPWA) {
    window.location.href = '/network-error';
    return;
  }
  window.addEventListener('online', () => { isOffline = false; });
  window.addEventListener('offline', () => {
    isOffline = true;
    if (isPWA) { loadOfflineData(); }
    else { window.location.href = '/network-error'; }
  });
  if (isPWA && !navigator.onLine) loadOfflineData();
">
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

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-x-4">
    <div class="col-span-1 lg:col-span-2 pb-24 lg:pb-0">
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
      <div x-show="!isOffline" class="grid grid-cols-2 gap-2 sm:gap-3 sm:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" wire:loading.class="opacity-60">
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
              @php $cartQty = $cartItems->first(fn ($i) => $i->product_id === $product->id)?->qty ?? 0; @endphp
              @if ($cartQty > 0)
                <span class="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-zonakasir-primary text-xs font-bold text-white shadow-sm">{{ $cartQty }}</span>
              @endif
            </div>

            {{-- Info --}}
            <div class="flex flex-1 flex-col justify-between p-3">
              <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $product->sku }}</p>
                <h3 class="mt-0.5 text-sm font-semibold leading-tight text-gray-900 dark:text-white line-clamp-2">{{ $product->name }}</h3>
              </div>
              <div class="mt-2 flex items-center justify-between">
                <span class="text-sm font-bold text-zonakasir-primary">{{ price_format($product->sellingPriceCalculate) }}</span>
                @if ($cartQty === 0)
                  <button wire:click="addCart({{ $product->id }})" wire:loading.attr="disabled"
                    class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full bg-zonakasir-primary text-white transition-colors hover:bg-zonakasir-primary/90 disabled:opacity-50">
                    <x-heroicon-o-plus class="h-5 w-5" />
                  </button>
                @else
                  <div class="flex items-center gap-1">
                    <button wire:click="reduceCart({{ $product->id }})" wire:loading.attr="disabled"
                      class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full bg-gray-100 text-gray-600 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                      <x-heroicon-o-minus-small class="h-5 w-5" />
                    </button>
                    <span class="w-8 text-center text-sm font-semibold text-zonakasir-primary">{{ $cartQty }}</span>
                    <button wire:click="addCart({{ $product->id }})" wire:loading.attr="disabled"
                      class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full bg-zonakasir-primary text-white transition-colors hover:bg-zonakasir-primary/90 disabled:opacity-50">
                      <x-heroicon-o-plus-small class="h-5 w-5" />
                    </button>
                  </div>
                @endif
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

      {{-- Offline Product Cards Grid --}}
      <div x-show="isOffline && isPWA" x-cloak class="grid grid-cols-2 gap-2 sm:gap-3 sm:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        {{-- Offline Search --}}
        <div class="col-span-full mb-2">
          <div class="relative">
            <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
            <input type="text" x-model="offlineSearch" placeholder="{{ __('Search products offline...') }}"
              class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 focus:border-zonakasir-primary focus:outline-none focus:ring-1 focus:ring-zonakasir-primary dark:border-gray-600 dark:bg-gray-800 dark:text-white">
          </div>
        </div>

        {{-- Offline Categories --}}
        <div class="col-span-full mb-2 flex gap-2 overflow-x-auto">
          <button @click="offlineSelectedCategory = null"
            class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-medium transition-colors"
            :class="offlineSelectedCategory === null ? 'bg-zonakasir-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300'">
            {{ __('All') }}
          </button>
          <template x-for="cat in offlineCategories" :key="cat.id">
            <button @click="offlineSelectedCategory = cat.id"
              class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-medium transition-colors"
              :class="offlineSelectedCategory === cat.id ? 'bg-zonakasir-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300'"
              x-text="cat.name"></button>
          </template>
        </div>

        {{-- Offline Product Cards --}}
        <template x-for="product in filteredOfflineProducts" :key="product.id">
          <div class="group relative flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition-all hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
            <div class="relative aspect-[4/3] overflow-hidden bg-gray-100 dark:bg-gray-700">
              <template x-if="product.hero_images_url">
                <img :src="product.hero_images_url" :alt="product.name" class="h-full w-full object-cover transition-transform group-hover:scale-105">
              </template>
              <template x-if="!product.hero_images_url">
                <div class="flex h-full items-center justify-center text-gray-400">
                  <x-heroicon-o-photo class="h-10 w-10" />
                </div>
              </template>

              {{-- Stock badge --}}
              <template x-if="!product.is_non_stock && (product.stock_calculate !== undefined ? product.stock_calculate : product.stock || 0) <= 0">
                <div class="absolute inset-0 flex items-center justify-center bg-black/50">
                  <span class="rounded-md bg-red-600 px-2 py-1 text-xs font-bold text-white">{{ __('Out of stock') }}</span>
                </div>
              </template>
              <template x-if="!product.is_non_stock && (product.stock_calculate !== undefined ? product.stock_calculate : product.stock || 0) > 0 && (product.stock_calculate !== undefined ? product.stock_calculate : product.stock || 0) <= 10">
                <span class="absolute left-2 top-2 rounded-md bg-amber-500 px-1.5 py-0.5 text-xs font-bold text-white shadow-sm" x-text="(product.stock_calculate !== undefined ? product.stock_calculate : product.stock || 0) + ' {{ __("Stock") }}'"></span>
              </template>
              <template x-if="!product.is_non_stock && (product.stock_calculate !== undefined ? product.stock_calculate : product.stock || 0) > 10">
                <span class="absolute left-2 top-2 rounded-md bg-zonakasir-primary px-1.5 py-0.5 text-xs font-bold text-white shadow-sm" x-text="(product.stock_calculate !== undefined ? product.stock_calculate : product.stock || 0) + ' {{ __("Stock") }}'"></span>
              </template>

              {{-- Cart quantity badge --}}
              <template x-if="offlineCart[product.id] && offlineCart[product.id].qty > 0">
                <span class="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-zonakasir-primary text-xs font-bold text-white shadow-sm" x-text="offlineCart[product.id].qty"></span>
              </template>
            </div>

            <div class="flex flex-1 flex-col justify-between p-3">
              <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400" x-text="product.sku"></p>
                <h3 class="mt-0.5 text-sm font-semibold leading-tight text-gray-900 dark:text-white line-clamp-2" x-text="product.name"></h3>
              </div>
              <div class="mt-2 flex items-center justify-between">
                <span class="text-sm font-bold text-zonakasir-primary" x-text="'Rp ' + (product.selling_price_calculate || product.selling_price || 0).toLocaleString('id-ID')"></span>
                <template x-if="!offlineCart[product.id] || offlineCart[product.id].qty === 0">
                  <button @click="offlineAddToCart(product.id)"
                    class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full bg-zonakasir-primary text-white transition-colors hover:bg-zonakasir-primary/90">
                    <x-heroicon-o-plus class="h-5 w-5" />
                  </button>
                </template>
                <template x-if="offlineCart[product.id] && offlineCart[product.id].qty > 0">
                  <div class="flex items-center gap-1">
                    <button @click="offlineRemoveFromCart(product.id)"
                      class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full bg-gray-100 text-gray-600 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                      <x-heroicon-o-minus-small class="h-5 w-5" />
                    </button>
                    <span class="w-8 text-center text-sm font-semibold text-zonakasir-primary" x-text="offlineCart[product.id].qty"></span>
                    <button @click="offlineAddToCart(product.id)"
                      class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full bg-zonakasir-primary text-white transition-colors hover:bg-zonakasir-primary/90">
                      <x-heroicon-o-plus-small class="h-5 h-5" />
                    </button>
                  </div>
                </template>
              </div>
            </div>
          </div>
        </template>

        {{-- Offline empty state --}}
        <template x-if="filteredOfflineProducts.length === 0">
          <div class="col-span-full flex flex-col items-center justify-center py-16 text-gray-400">
            <x-heroicon-o-cube class="h-16 w-16" />
            <p class="mt-2 text-lg font-medium">{{ __('No cached products') }}</p>
            <p class="text-sm">{{ __('Go online first to sync data') }}</p>
          </div>
        </template>
      </div>
    </div>

    {{-- Offline: cart toggle button --}}
    <div x-show="isOffline && isPWA" class="fixed bottom-0 left-0 right-0 z-50 border-t bg-white px-3 pb-[env(safe-area-inset-bottom)] pt-3 shadow-lg dark:border-gray-800 dark:bg-gray-900 lg:hidden">
      <button @click="cartOpen = true" class="flex w-full items-center justify-between rounded-lg bg-zonakasir-primary px-4 py-3 min-h-[48px] text-white">
        <span class="font-semibold">{{ __('View Cart') }}</span>
        <span class="flex items-center gap-2">
          <span x-text="offlineCartCount" class="rounded-full bg-white/20 px-2 py-0.5 text-sm"></span>
          <x-heroicon-o-chevron-up class="h-5 w-5" />
        </span>
      </button>
    </div>

    {{-- Offline: cart panel --}}
    <div x-show="isOffline && isPWA && cartOpen" x-cloak class="fixed inset-x-0 bottom-0 z-50 max-h-[85vh] overflow-y-auto rounded-t-2xl bg-white shadow-2xl transition-transform duration-300 dark:bg-gray-900">
      <div class="flex items-center justify-between border-b p-3 dark:border-gray-800">
        <p class="text-base font-semibold">{{ __('Offline Cart') }}</p>
        <button @click="cartOpen = false" class="rounded-full p-1 hover:bg-gray-100 dark:hover:bg-gray-800">
          <x-heroicon-o-x-mark class="h-5 w-5" />
        </button>
      </div>

      <div class="p-3 space-y-3">
        {{-- Cart items --}}
        <template x-if="offlineCartCount === 0">
          <p class="text-center text-gray-400 py-8">{{ __('Cart is empty') }}</p>
        </template>

        <template x-for="(item, productId) in offlineCart" :key="productId">
          <div class="flex items-center gap-3 py-2 border-b border-gray-100 dark:border-gray-800">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="item.name"></p>
              <p class="text-xs text-zonakasir-primary font-semibold" x-text="'Rp ' + item.price.toLocaleString('id-ID')"></p>
            </div>
            <div class="flex items-center gap-1">
              <button @click="offlineRemoveFromCart(parseInt(productId))"
                class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                <x-heroicon-o-minus-small class="h-4 w-4" />
              </button>
              <span class="w-8 text-center text-sm font-semibold" x-text="item.qty"></span>
              <button @click="offlineAddToCart(parseInt(productId))"
                class="w-8 h-8 rounded-full bg-zonakasir-primary flex items-center justify-center text-white">
                <x-heroicon-o-plus-small class="h-4 w-4" />
              </button>
            </div>
            <p class="text-sm font-bold text-gray-900 dark:text-white w-24 text-right" x-text="'Rp ' + (item.price * item.qty).toLocaleString('id-ID')"></p>
          </div>
        </template>

        {{-- Offline cart summary --}}
        <div x-show="offlineCartCount > 0" class="border-t pt-3 dark:border-gray-800">
          <div class="flex justify-between text-sm mb-1">
            <span class="text-gray-500">{{ __('Subtotal') }}</span>
            <span class="font-semibold" x-text="'Rp ' + offlineCartSubtotal.toLocaleString('id-ID')"></span>
          </div>
          <div class="flex justify-between text-lg font-bold mb-3">
            <span>{{ __('Total') }}</span>
            <span class="text-zonakasir-primary" x-text="'Rp ' + offlineCartSubtotal.toLocaleString('id-ID')"></span>
          </div>
          <button @click="paymentModalOpen = true"
            class="w-full rounded-lg bg-zonakasir-primary py-3 text-white font-semibold text-sm hover:bg-zonakasir-primary/90 transition-colors">
            {{ __('Proceed to Payment') }}
          </button>
          <p class="text-xs text-gray-400 text-center mt-2">{{ __('Payment will sync when online') }}</p>
        </div>
      </div>
    </div>

    {{-- Offline: payment modal --}}
    <div x-show="isOffline && isPWA && paymentModalOpen" x-cloak class="fixed inset-0 z-[100] flex items-end justify-center bg-black/50 sm:items-center">
      <div class="w-full max-w-md rounded-t-2xl bg-white p-4 shadow-xl dark:bg-gray-900 sm:rounded-2xl" @click.outside="paymentModalOpen = false">
        <h3 class="text-lg font-semibold mb-1">{{ __('Offline Payment') }}</h3>
        <p class="text-xs text-amber-600 bg-amber-50 rounded-lg px-3 py-2 mb-4 font-medium">
          ⚠️ {{ __('QRIS & digital payment unavailable offline. Cash only.') }}
        </p>

        <div class="space-y-2 mb-4">
          <div class="flex justify-between text-sm">
            <span class="text-gray-500">{{ __('Total') }}</span>
            <span class="font-bold" x-text="'Rp ' + offlineCartSubtotal.toLocaleString('id-ID')"></span>
          </div>
        </div>

        {{-- Payment method: cash only --}}
        <div class="mb-4">
          <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 block">{{ __('Payment Method') }}</label>
          <div class="flex gap-2">
            <button @click="offlinePaymentMethod = 'cash'"
              class="flex-1 rounded-lg border-2 py-3 text-sm font-semibold transition-all"
              :class="offlinePaymentMethod === 'cash' ? 'border-zonakasir-primary bg-zonakasir-primary text-white' : 'border-gray-200 bg-white text-gray-600 dark:border-gray-700 dark:bg-gray-800'">
              💵 {{ __('Cash') }}
            </button>
            <button disabled class="flex-1 rounded-lg border-2 border-gray-100 bg-gray-50 py-3 text-sm font-semibold text-gray-300 cursor-not-allowed dark:border-gray-800 dark:bg-gray-900">
              📱 QRIS
              <span class="block text-[10px] font-normal">{{ __('Offline') }}</span>
            </button>
            <button disabled class="flex-1 rounded-lg border-2 border-gray-100 bg-gray-50 py-3 text-sm font-semibold text-gray-300 cursor-not-allowed dark:border-gray-800 dark:bg-gray-900">
              💳 {{ __('Card') }}
              <span class="block text-[10px] font-normal">{{ __('Offline') }}</span>
            </button>
          </div>
        </div>

        <div class="flex gap-2">
          <button @click="paymentModalOpen = false"
            class="flex-1 rounded-lg border border-gray-300 py-3 text-sm font-semibold dark:border-gray-600">
            {{ __('Cancel') }}
          </button>
          <button @click="saveOfflineSale()"
            class="flex-1 rounded-lg bg-zonakasir-primary py-3 text-sm font-semibold text-white hover:bg-zonakasir-primary/90">
            {{ __('Save Offline') }}
          </button>
        </div>
      </div>
    </div>

    {{-- Mobile: cart toggle button with scan --}}
    <div class="fixed bottom-0 left-0 right-0 z-50 border-t bg-white px-3 pb-[env(safe-area-inset-bottom)] pt-3 shadow-lg dark:border-gray-800 dark:bg-gray-900 lg:hidden"
      x-show="!cartOpen">
      <div class="flex gap-2">
        <button @click="cartOpen = true"
          class="flex flex-1 items-center justify-between rounded-lg bg-zonakasir-primary px-4 py-3 min-h-[48px] text-white">
          <span class="font-semibold">{{ __('View Cart') }}</span>
          <span class="flex items-center gap-2">
            <span x-text="$wire.cartItems ? $wire.cartItems.length : 0" class="rounded-full bg-white/20 px-2 py-0.5 text-sm"></span>
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
    <div class="fixed inset-x-0 bottom-0 z-50 max-h-[85vh] overflow-y-auto rounded-t-2xl bg-white shadow-2xl transition-transform duration-300 dark:bg-gray-900 lg:inset-auto lg:right-0 lg:top-0 lg:h-screen lg:w-[40%] xl:w-1/3 lg:rounded-none lg:shadow-none lg:translate-y-0"
      x-bind:class="cartOpen ? 'translate-y-0' : 'translate-y-full lg:!translate-y-0'"
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
        <div class="max-h-[40%] min-h-32 overflow-auto" wire:loading.class="opacity-20"
          wire:target="addCart,reduceCart,deleteCart,addDiscountPricePerItem,addCartUsingScanner">
          @forelse($cartItems as $item)
            <div class="mb-2 rounded-lg border bg-white px-3 py-2 dark:border-gray-900 dark:bg-gray-900"
              id="{{ $item->id }}" key="{{ rand() }}">
              <div class="grid items-center space-x-3">
                <div class="flex justify-between">
                  <p class="font-semibold text-sm"> {{ $item->product->name }}</p>
                  <p class="font-semibold text-sm text-zonakasir-primary">{{ $item->price_format_money }}</p>
                </div>
              </div>
              <div class="grid grid-cols-2 items-center space-y-1 py-1 text-right">
                <div class="col-span-2">
                  @feature(Discount::class)
                    <div class="mb-1 flex justify-end">
                      <x-filament::input.wrapper class="w-1/2">
                        <x-filament::input type="text" id="{{ $item->product->name }}-{{ $item->id }}"
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
                <x-filament::input.wrapper class="w-16" x-data="cart">
                  <x-filament::input type="text"
                    id="{{ $item->product->name }}-{{ $item->id }}-qty-{{ rand() }}"
                    data-value="{{ $item->qty }}" value="{{ $item->qty }}"
                    x-on:keyup.debounce.500ms="(e) => add('{{ $item->product_id }}', e.target.value)"
                    placeholder="{{ __('Discount') }}" class="w-1/2 text-right text-sm" inputMode="numeric" />
                </x-filament::input.wrapper>
                <button class="rounded-lg !bg-gray-100 px-2 py-0.5"
                  x-on:click="$wire.reduceCart({{ $item->product_id }});" wire:loading.attr="disabled">
                  <x-heroicon-o-minus-small class="h-3.5 w-3.5 !text-green-900" />
                </button>
                <button class="rounded-lg !bg-danger-100 px-2 py-0.5" wire:click="deleteCart({{ $item->id }})"
                  wire:loading.attr="disabled">
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
  {{-- modal --}}
<x-filament::modal id="edit-detail" width="2xl">
    <form wire:submit.prevent="storeCart">
      <x-slot name="heading">
        <p id="titleEditDetail">{{ __('Edit detail') }}</p>
      </x-slot>
      {{ $this->storeCartForm }}
      <x-filament::button type="submit" class="mt-10">
        {{ __('Save') }}
      </x-filament::button>
    </form>
  </x-filament::modal>
  <x-filament::modal id="proceed-the-payment" width="5xl">
    <form wire:submit.prevent="proceedThePayment">
      <div class="my-2 grid gap-4 md:grid-cols-2">
        <div x-data="detail">
          <div class="rounded-lg">
            <div class="mb-4 grid grid-cols-2 gap-2 md:grid-cols-4">
              <template x-for="paymentMethod in paymentMethods">
                <div
                  x-on:click="selectPayment(paymentMethod)"
                  class="flex cursor-pointer justify-center rounded-md border-none px-3 py-2 text-xs hover:scale-105 dark:text-white md:text-sm"
                  :class="cartDetail['payment_method_id'] == paymentMethod.id ? 'bg-zonakasir-primary text-white' :
                      'dark:bg-gray-900 bg-gray-300 '"
                  x-text="paymentMethod.name.substring(0, 8)">
                </div>
              </template>
              <template x-if="!paymentMethods.length">
                <p class="col-span-full text-center text-xs text-amber-600 font-medium">{{ __('No payment methods available') }}</p>
              </template>
              <template x-if="paymentMethods.length && !cartDetail['payment_method_id']">
                <p class="col-span-full text-center text-xs text-amber-600 font-medium">{{ __('Select a payment method above') }}</p>
              </template>
            </div>
            <div class="mb-4">
              @include('filament.tenant.pages.cashier.total')
            </div>
            @php
              $isCreditSelected = true;
            @endphp
            <div class="grid gap-3" :class="paymentMethods.find(p => p.id == cartDetail.payment_method_id)?.is_credit ? 'md:grid-cols-[1fr_1fr]' : 'grid-cols-1'">
            {{-- Calculator area --}}
            <div>
            @error('payed_money')
              <span class="error text-danger-500">{{ $message }}</span>
            @enderror

            {{-- Payment input display --}}
            <div class="relative mb-3">
              <input id="display" readonly autofocus
                class="w-full rounded-xl border-2 bg-gray-50 p-4 pb-6 text-right text-2xl font-bold text-gray-900 focus:outline-none dark:bg-gray-800 dark:text-white @error('payed_money') border-red-500 @else border-gray-200 dark:border-gray-600 focus:border-zonakasir-primary @enderror"
                x-ref="payedMoney" inputMode="none" tabindex="0" @keydown="handleKeydown($event)">
              <span class="absolute bottom-1 right-4 text-xs text-gray-400" x-show="rawValue > 0" x-cloak>
                {{ __('Change') }}: <span class="font-semibold text-zonakasir-primary" x-text="moneyFormat(changeAmount > 0 ? changeAmount : 0)"></span>
              </span>
            </div>

            {{-- Quick amount shortcuts (populated dynamically) --}}
            <div class="mb-3 grid grid-cols-3 gap-2" id="calculator-button-shortcut">
            </div>

            {{-- Modern number pad --}}
            <div class="mb-3 grid grid-cols-3 gap-2">
              {{-- Row 1 --}}
              <button type="button" 
                class="flex min-h-[48px] items-center justify-center rounded-xl bg-white p-3 text-xl font-semibold text-gray-800 shadow-sm ring-1 ring-gray-200 transition-all hover:bg-gray-50 hover:shadow active:scale-95 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700 dark:hover:bg-gray-700"
                x-on:click="pressDigit(7)">7</button>
              <button type="button"
                class="flex min-h-[48px] items-center justify-center rounded-xl bg-white p-3 text-xl font-semibold text-gray-800 shadow-sm ring-1 ring-gray-200 transition-all hover:bg-gray-50 hover:shadow active:scale-95 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700 dark:hover:bg-gray-700"
                x-on:click="pressDigit(8)">8</button>
              <button type="button"
                class="flex min-h-[48px] items-center justify-center rounded-xl bg-white p-3 text-xl font-semibold text-gray-800 shadow-sm ring-1 ring-gray-200 transition-all hover:bg-gray-50 hover:shadow active:scale-95 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700 dark:hover:bg-gray-700"
                x-on:click="pressDigit(9)">9</button>
              {{-- Row 2 --}}
              <button type="button"
                class="flex min-h-[48px] items-center justify-center rounded-xl bg-white p-3 text-xl font-semibold text-gray-800 shadow-sm ring-1 ring-gray-200 transition-all hover:bg-gray-50 hover:shadow active:scale-95 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700 dark:hover:bg-gray-700"
                x-on:click="pressDigit(4)">4</button>
              <button type="button"
                class="flex min-h-[48px] items-center justify-center rounded-xl bg-white p-3 text-xl font-semibold text-gray-800 shadow-sm ring-1 ring-gray-200 transition-all hover:bg-gray-50 hover:shadow active:scale-95 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700 dark:hover:bg-gray-700"
                x-on:click="pressDigit(5)">5</button>
              <button type="button"
                class="flex min-h-[48px] items-center justify-center rounded-xl bg-white p-3 text-xl font-semibold text-gray-800 shadow-sm ring-1 ring-gray-200 transition-all hover:bg-gray-50 hover:shadow active:scale-95 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700 dark:hover:bg-gray-700"
                x-on:click="pressDigit(6)">6</button>
              {{-- Row 3 --}}
              <button type="button"
                class="flex min-h-[48px] items-center justify-center rounded-xl bg-white p-3 text-xl font-semibold text-gray-800 shadow-sm ring-1 ring-gray-200 transition-all hover:bg-gray-50 hover:shadow active:scale-95 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700 dark:hover:bg-gray-700"
                x-on:click="pressDigit(1)">1</button>
              <button type="button"
                class="flex min-h-[48px] items-center justify-center rounded-xl bg-white p-3 text-xl font-semibold text-gray-800 shadow-sm ring-1 ring-gray-200 transition-all hover:bg-gray-50 hover:shadow active:scale-95 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700 dark:hover:bg-gray-700"
                x-on:click="pressDigit(2)">2</button>
              <button type="button"
                class="flex min-h-[48px] items-center justify-center rounded-xl bg-white p-3 text-xl font-semibold text-gray-800 shadow-sm ring-1 ring-gray-200 transition-all hover:bg-gray-50 hover:shadow active:scale-95 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700 dark:hover:bg-gray-700"
                x-on:click="pressDigit(3)">3</button>
              {{-- Row 4 --}}
              <button type="button"
                class="flex min-h-[48px] items-center justify-center rounded-xl bg-red-50 p-3 text-lg font-semibold text-red-600 shadow-sm ring-1 ring-red-200 transition-all hover:bg-red-100 hover:shadow active:scale-95 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-800"
                x-on:click="pressClear()">C</button>
              <button type="button"
                class="flex min-h-[48px] items-center justify-center rounded-xl bg-white p-3 text-xl font-semibold text-gray-800 shadow-sm ring-1 ring-gray-200 transition-all hover:bg-gray-50 hover:shadow active:scale-95 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700 dark:hover:bg-gray-700"
                x-on:click="pressDigit(0)">0</button>
              <button type="button"
                class="flex min-h-[48px] items-center justify-center rounded-xl bg-white p-3 text-lg font-semibold text-gray-800 shadow-sm ring-1 ring-gray-200 transition-all hover:bg-gray-50 hover:shadow active:scale-95 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700 dark:hover:bg-gray-700"
                x-on:click="pressDecimal()">.</button>
            </div>
            {{-- Row 5: Backspace full-width --}}
            <button type="button"
              class="mb-3 flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl bg-gray-100 p-2 text-sm font-semibold text-gray-500 shadow-sm ring-1 ring-gray-200 transition-all hover:bg-gray-200 active:scale-95 dark:bg-gray-700 dark:text-gray-300 dark:ring-gray-600 dark:hover:bg-gray-600"
              x-on:click="pressBackspace()">
              <x-filament::icon icon="heroicon-o-backspace" class="h-4 w-4" />
              <span>{{ __('Delete') }}</span>
            </button>
            </div>
            {{-- Piutang form: sebelah calculator --}}
            <div x-show="paymentMethods.find(p => p.id == cartDetail.payment_method_id)?.is_credit"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-x-2"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 class="flex flex-col gap-3 rounded-xl border-2 border-dashed border-amber-300 bg-amber-50/50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
              <div class="flex items-center gap-2 text-sm font-semibold text-amber-700 dark:text-amber-400">
                <x-heroicon-o-exclamation-circle class="h-5 w-5" />
                <span>{{ __('Piutang / Credit') }}</span>
              </div>
              {{-- Member select --}}
              <div>
                <div class="flex items-center justify-between mb-1">
                  <label class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Member') }} <span class="text-red-500">*</span></label>
                  <button type="button" x-on:click="$dispatch('open-modal', {id: 'modal-quick-member'})"
                    class="text-xs font-semibold text-zonakasir-primary hover:underline">
                    + {{ __('Add member') }}
                  </button>
                </div>
                <select wire:model="cartDetail.member_id"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-zonakasir-primary focus:ring-1 focus:ring-zonakasir-primary dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                  <option value="">{{ __('Select member...') }}</option>
                  @foreach($members as $id => $memberName)
                    <option value="{{ $id }}">{{ $memberName }}</option>
                  @endforeach
                </select>
              </div>
              {{-- Due date --}}
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Due date') }} <span class="text-red-500">*</span></label>
                <input type="date" wire:model="cartDetail.due_date"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-zonakasir-primary focus:ring-1 focus:ring-zonakasir-primary dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
              </div>
              {{-- DP / Partial payment --}}
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Down payment (optional)') }}</label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">Rp</span>
                  <input type="text" wire:model.live="cartDetail.payed_money"
                    class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-right text-gray-900 focus:border-zonakasir-primary focus:ring-1 focus:ring-zonakasir-primary dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    placeholder="0" />
                </div>
                <p x-show="cartDetail.payed_money > 0" x-cloak class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  {{ __('Remaining:') }} <span class="font-semibold text-amber-600" x-text="moneyFormat({{ $total_price }} - (cartDetail.payed_money || 0))"></span>
                </p>
              </div>
              {{-- Info --}}
              <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Customer will pay later. Due date is required.') }}</p>
            </div>
            </div>
            <div class="mt-2 grid gap-2" :class="paymentMethods.find(p => p.id == cartDetail.payment_method_id)?.is_credit ? 'grid-cols-2' : 'grid-cols-3'">
                <div x-show="paymentMethodWarning" x-cloak x-transition class="col-span-full text-center text-sm text-danger-600 font-medium">
                    {{ __('Select a payment method first') }}
                </div>
                {{-- Exact amount button (non-piutang only) --}}
                <button type="button"
                  x-show="!paymentMethods.find(p => p.id == cartDetail.payment_method_id)?.is_credit"
                  class="flex min-h-[48px] w-full items-center justify-center gap-x-2 rounded-xl bg-amber-50 p-3 text-sm font-semibold text-amber-700 shadow-sm ring-1 ring-amber-200 transition-all hover:bg-amber-100 active:scale-95 dark:bg-amber-900/30 dark:text-amber-400 dark:ring-amber-800"
                  x-on:click="pressNoChange()">
                  <x-heroicon-o-check class="h-5 w-5" />
                  {{ __('Exact amount') }}
                </button>
                {{-- Pay / Confirm Piutang --}}
                <button wire:loading.attr="disabled" wire:target="proceedThePayment" type="submit"
                  class="flex min-h-[48px] w-full items-center justify-center gap-x-2 rounded-xl bg-zonakasir-primary p-3 text-base font-bold text-white shadow-lg shadow-zonakasir-primary/30 transition-all hover:brightness-110 active:scale-95 disabled:opacity-50">
                  <span wire:loading.remove wire:target="proceedThePayment" x-show="paymentMethods.find(p => p.id == cartDetail.payment_method_id)?.is_credit">{{ __('Confirm Piutang') }}</span>
                  <span wire:loading.remove wire:target="proceedThePayment" x-show="!paymentMethods.find(p => p.id == cartDetail.payment_method_id)?.is_credit">{{ __('Pay now') }}</span>
                  <span wire:loading wire:target="proceedThePayment">
                    <x-filament::loading-indicator class="h-5 w-5" />
                  </span>
                </button>
                {{-- Cancel --}}
                <button wire:click="dispatch('close-modal', {id: 'proceed-the-payment'});" type="button"
                  class="flex min-h-[48px] w-full items-center justify-center gap-x-2 rounded-xl bg-gray-100 p-3 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-gray-200 transition-all hover:bg-gray-200 active:scale-95 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700">
                  {{ __('Cancel') }}
                </button>
              </div>
          </div>
        </div>
        {{-- Cart items: visible on desktop, collapsible accordion on mobile --}}
        <div class="hidden md:block max-h-[80vh] overflow-y-scroll">
          @if ($errors->any())
            @foreach ($errors->all() as $error)
              <p class="error w-full text-center text-lg text-danger-500">{{ $error }}</p>
            @endforeach
          @endif
          @include('filament.tenant.pages.cashier.items')
        </div>
        {{-- Mobile: collapsible cart summary --}}
        <details class="md:hidden mt-2 border rounded-lg">
          <summary class="cursor-pointer px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
            {{ __('Order items') }} ({{ count($cartItems) }})
          </summary>
          <div class="px-3 pb-2">
            @include('filament.tenant.pages.cashier.items')
          </div>
        </details>
      </div>
    </form>
  </x-filament::modal>
  <x-filament::modal id="success-modal" width="xl" :close-by-clicking-away="false" :close-by-escaping="false">
    <div class="flex flex-col items-center justify-center">
      <x-heroicon-o-check-circle style="color: rgb(34 197 94); width: 200px" />
      <p class="">@lang('Success')</p>
      <p class="text-3xl font-bold">
        @lang('Change'):
        <span id="changes"></span>
      </p>
    </div>
    <x-slot name="footer">
      <div class="grid grid-cols-2 gap-x-2">
        <x-filament::button icon="heroicon-m-printer" id="printReceiptButton">
          {{ __('Print') }}
        </x-filament::button>
        <x-filament::button color="gray" x-on:click="$dispatch('close-modal', {id: 'success-modal'})">
          {{ __('Close') }}
        </x-filament::button>
      </div>
    </x-slot>
  </x-filament::modal>

  {{-- Quick Add Member Modal --}}
  <x-filament::modal id="modal-quick-member" width="md">
    <x-slot name="heading">
      {{ __('Quick Add Member') }}
    </x-slot>
    <div class="flex flex-col gap-4">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Name') }} <span class="text-red-500">*</span></label>
        <input type="text" wire:model="newMemberName"
          class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-zonakasir-primary focus:ring-1 focus:ring-zonakasir-primary dark:border-gray-600 dark:bg-gray-800 dark:text-white"
          placeholder="{{ __('Member name') }}" />
        @error('newMemberName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Phone / Contact') }}</label>
        <input type="text" wire:model="newMemberPhone"
          class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-zonakasir-primary focus:ring-1 focus:ring-zonakasir-primary dark:border-gray-600 dark:bg-gray-800 dark:text-white"
          placeholder="{{ __('Phone or email') }}" />
      </div>
    </div>
    <x-slot name="footer">
      <div class="flex gap-2">
        <x-filament::button wire:click="quickCreateMember" wire:loading.attr="disabled">
          {{ __('Save') }}
        </x-filament::button>
        <x-filament::button color="gray" x-on:click="$dispatch('close-modal', {id: 'modal-quick-member'})">
          {{ __('Cancel') }}
        </x-filament::button>
      </div>
    </x-slot>
  </x-filament::modal>

  @include('partials.receipt-preview')
  <x-filament::modal id="modal-selected-table" width="xl" :close-by-clicking-away="false" :close-by-escaping="false">
    <div class="grid grid-cols-4 gap-4">
      @foreach ($tableOption as $table)
        <div x-on:click="$wire.cartDetail['table_id'] = {{ $table->id }};"
          class="flex cursor-pointer justify-center rounded-md border border-zonakasir-primary px-4 py-2 text-sm hover:scale-105 dark:text-white"
          :class="$wire.cartDetail['table_id'] == {{ $table->id }} ? 'bg-zonakasir-primary text-white' : 'dark:bg-gray-900 '">
          {{ $table->number }}
        </div>
      @endforeach
    </div>
    <x-slot name="footer">
      <x-slot name="heading">
        <p id="titleEditDetail">{{ __('Choose the table') }}</p>
      </x-slot>
      <div class="grid grid-cols-2 gap-x-2">
        <x-filament::button id="saveSelectedTable"
          x-on:click="$dispatch('close-modal', {id: 'modal-selected-table'}); $wire.storeCart()">
          {{ __('Save') }}
        </x-filament::button>
        <x-filament::button color="gray" x-on:click="$dispatch('close-modal', {id: 'modal-selected-table'})">
          {{ __('Close') }}
        </x-filament::button>
      </div>
    </x-slot>
  </x-filament::modal>

  <x-filament::modal id="qr-scanner-modal" width="xl" :close-by-clicking-away="false"
    x-on:close-modal.window="if ($event.detail.id === 'qr-scanner-modal') { window.stopScanner(); }"
    class="[&_.fi-modal-content]:!max-w-[100vw] [&_.fi-modal-content]:!w-full [&_.fi-modal-content]:!h-full [&_.fi-modal-content]:!m-0 [&_.fi-modal-content]:!rounded-none lg:[&_.fi-modal-content]:!max-w-2xl lg:[&_.fi-modal-content]:!w-auto lg:[&_.fi-modal-content]:!h-auto lg:[&_.fi-modal-content]:!m-4 lg:[&_.fi-modal-content]:!rounded-xl">
    <x-slot name="heading">
      {{ __('Scan Barcode with Camera') }}
    </x-slot>

    {{-- Main container with Alpine.js state management --}}
    <div x-data="{ isLoading: false }" x-ref="scannerContainer">

      {{-- Loading spinner (hidden by default) --}}
      <div x-show="isLoading" class="flex min-h-[300px] flex-col items-center justify-center text-center">
        <svg class="h-16 w-16 animate-spin text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none"
          viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
          </circle>
          <path class="opacity-75" fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
          </path>
        </svg>
        <p class="mt-4 text-lg font-medium text-gray-600 dark:text-gray-300">
          Processing product...
        </p>
      </div>

      {{-- QR Scanner container (hidden when loading) --}}
      <div x-show="!isLoading">
        <div wire:ignore id="qr-reader" class="w-full"></div>
      </div>

    </div>

    <x-slot name="footer">
      <x-filament::button color="gray" x-on:click="$dispatch('close-modal', {id: 'qr-scanner-modal'})">
        {{ __('Close') }}
      </x-filament::button>
    </x-slot>
  </x-filament::modal>

  {{-- Offline Payment Confirmation --}}
  <x-filament::modal id="offline-payment-confirm" width="md">
    <x-slot name="heading">
      ⚠️ {{ __('You are offline') }}
    </x-slot>

    <div class="space-y-4 py-2">
      <p class="text-sm text-gray-600 dark:text-gray-300">
        {{ __('No internet connection. The transaction will be saved locally and synced when you are back online.') }}
      </p>
      <div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
        <strong>{{ __('Pending transactions:') }}</strong>
        <span id="offline-pending-count">0</span>
      </div>
    </div>

    <x-slot name="footer">
      <x-filament::button color="gray" x-on:click="$dispatch('close-modal', {id: 'offline-payment-confirm'})">
        {{ __('Cancel') }}
      </x-filament::button>
      <x-filament::button color="warning" id="offline-save-btn"
        x-on:click="
          $dispatch('close-modal', {id: 'offline-payment-confirm'});
          if(window.offlineManager && window.offlineManager.db) {
            var data = { items: $wire.cartItems.map(function(i) { return { product_id: i.product_id, qty: i.qty, price: i.price, discount_price: i.discount_price }; }), total_price: $wire.total_price, payed_money: $wire.total_price };
            window.offlineManager.addPendingSale(data).then(function() {
              $wire.clearCart();
              var pendingEl = document.getElementById('offline-pending-count');
              if(pendingEl) window.offlineManager.getPendingCount().then(function(c) { pendingEl.textContent = c; });
              new FilamentNotification().title('Transaction saved offline').success().send();
            });
          } else {
            new FilamentNotification().title('Offline storage not available').danger().send();
          }
        ">
        {{ __('Save for later sync') }}
      </x-filament::button>
    </x-slot>
  </x-filament::modal>
  <style>
    /* html5-qrcode library button & control styling */
    #qr-reader__dashboard_section_csr button,
    #qr-reader__dashboard_section_swaplink {
      background-color: #f97316 !important;
      border: none !important;
      color: #fff !important;
      padding: 0.5rem 1.25rem !important;
      border-radius: 0.5rem !important;
      font-weight: 600 !important;
      font-size: 0.875rem !important;
      cursor: pointer !important;
      transition: background-color 0.2s ease !important;
      box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1) !important;
      margin: 0.25rem 0 !important;
    }

    #qr-reader__dashboard_section_csr button:hover,
    #qr-reader__dashboard_section_swaplink:hover {
      background-color: #ea580c !important;
    }

    #qr-reader__dashboard_section {
      padding: 0.75rem !important;
      margin-top: 0.5rem !important;
    }

    #qr-reader__dashboard_section_csr {
      display: flex !important;
      flex-direction: column !important;
      gap: 0.5rem !important;
      align-items: stretch !important;
    }

    #qr-reader__dashboard_section_csr select {
      width: 100% !important;
      padding: 0.5rem 0.75rem !important;
      border-radius: 0.5rem !important;
      border: 1px solid #d1d5db !important;
      background-color: #fff !important;
      color: #111827 !important;
      font-size: 0.875rem !important;
      outline: none !important;
      transition: border-color 0.2s ease !important;
    }

    #qr-reader__dashboard_section_csr select:focus {
      border-color: #f97316 !important;
      box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.25) !important;
    }

    #qr-reader__scan_region {
      min-height: 200px !important;
      border-radius: 0.5rem !important;
      overflow: hidden !important;
    }

    #qr-reader {
      border: none !important;
    }

    #qr-reader__dashboard {
      padding: 0.5rem !important;
    }

    #qr-reader__status_line {
      font-size: 0.875rem !important;
      padding: 0.25rem 0.5rem !important;
    }

    /* Dark mode support */
    .dark #qr-reader__dashboard_section_csr select {
      background-color: #111827 !important;
      color: #f3f4f6 !important;
      border-color: #374151 !important;
    }

    .dark #qr-reader__dashboard_section_csr select:focus {
      border-color: #f97316 !important;
      box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.25) !important;
    }

    .dark #qr-reader__dashboard_section {
      background-color: #1f2937 !important;
      border-color: #374151 !important;
    }

    .dark #qr-reader__dashboard {
      background-color: #111827 !important;
    }

    .dark #qr-reader__status_line {
      color: #d1d5db !important;
    }

    .dark #qr-reader img[alt="Info icon"] {
      filter: invert(1) !important;
    }

    /* Fix dark mode swap link: transparent background keeps text readable */
    .dark #qr-reader__dashboard_section_swaplink {
      background-color: transparent !important;
      color: #f97316 !important;
      text-decoration: underline !important;
      box-shadow: none !important;
      padding: 0 !important;
    }
  </style>

  {{-- Ensure modal is above sidebar z-50 --}}
  <style>
    .fi-modal .fixed.inset-0.z-40 {
      z-index: 100 !important;
    }
    .fi-modal .fi-modal-content {
      z-index: 101 !important;
    }
  </style>

  {{-- PWA touch & scroll improvements --}}
  <style>
    /* Prevent pull-to-refresh in PWA standalone mode */
    html, body {
      overscroll-behavior-y: contain;
    }

    /* Better touch scrolling for cart & product list */
    .overflow-y-auto, .overflow-auto {
      -webkit-overflow-scrolling: auto;
      overscroll-behavior: contain;
    }

    /* Remove tap highlight on mobile */
    button, a, input, select, textarea {
      -webkit-tap-highlight-color: transparent;
    }

    /* Safe area insets for notched devices */
    .pb-safe {
      padding-bottom: env(safe-area-inset-bottom, 0px);
    }
    .pt-safe {
      padding-top: env(safe-area-inset-top, 0px);
    }

    /* Product card touch feedback */
    .group:active {
      transform: scale(0.98);
    }

    /* Bottom bar safe area */
    .bottom-safe {
      bottom: env(safe-area-inset-bottom, 0px);
    }
  </style>
</div>

@script()
  <script>
    window.zonakasirCurrency = @js($currency);
    window.zonakasirLocale = @js($locale);
    let selling = null;

    // Load Midtrans Snap.js
    var snapScript = document.createElement('script');
    snapScript.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
    snapScript.setAttribute('data-client-key', @js(config('midtrans.client_key') ?? ''));
    snapScript.async = false;
    document.head.appendChild(snapScript);

      // Handle Midtrans payment event from Livewire
      $wire.on('midtrans-payment', (event) => {
        var data = Array.isArray(event) ? event[0] : (event.detail || event);
        var orderId = data && data.order_id;
        var token = data && data.token;
        var redirect_url = data && data.redirect_url;
        if (!orderId || !token || !redirect_url) { console.error('Midtrans: missing data', event); return; }

        // Snap popup opens automatically with QR code for payment
        setTimeout(function() {
          if (window.snap) {
            window.snap.pay(token, {
              onSuccess: function() {
                // Confirm payment via Livewire after successful Snap payment
                $wire.call('confirmMidtransPayment', orderId).then(() => {
                  window.location.reload();
                });
              },
              onPending: function() {},
              onError: function(e) { console.error('Snap error', e); }
            });
          }
        }, 500);
      });

    $wire.on('selling-created', (event) => {
      selling = event.selling;
      $wire.dispatch('close-modal', {
        id: 'proceed-the-payment'
      });

      $wire.dispatch('open-modal', {
        id: 'success-modal',
        money_changes: selling.money_changes
      });
      setTimeout(() => {
        document.getElementById('changes').innerHTML = moneyFormat(selling.money_changes);
      }, 300);
    });
    async function doPrintReceipt() {
      let about = @js($about);
      const printerData = getPrinter();
      if (!printerData || printerData instanceof Error) {
        new FilamentNotification()
          .title('@lang('You should choose the printer first in printer setting')')
          .danger()
          .actions([
            new FilamentNotificationAction('Setting')
            .icon('heroicon-o-cog-6-tooth')
            .button()
            .url('/member/printer'),
          ])
          .send()
        return;
      }
      const printer = new Printer(printerData.printerId);
      let p = printer.font('a');
      if (about != undefined || about != null) {
        p.size(1).align('center').text(about.shop_name).size(0).text(about.shop_location);
        if (printerData.header != undefined) p.text(printerData.header);
        p.align('left').text('-------------------------------');
      }
      p.table(['@lang('Cashier')', selling.user.name])
      if (selling.table != undefined && selling.table != null) p.table(['@lang('Table')', selling.table.number])
      p.table(['@lang('Payment method')', selling.payment_method.name]);
      if (selling.member != undefined && selling.member != null) p.table(['Member', selling.member.name]);
      p.text('-------------------------------');
      selling.selling_details.forEach(d => {
        p.table([d.product.name, moneyFormat(d.price / d.qty) + ' x ' + d.qty.toString()])
        if (d.discount_price > 0) {
          p.align('right').text(`(${moneyFormat(d.discount_price)})`)
        }
        p.align('right').text(moneyFormat(d.price)).align('left')
      });
      p.text('-------------------------------');
      if ("@js(feature(SellingTax::class))" == 'true') {
        p.table(['@lang('Tax')', `${selling.tax}%`]).table(['@lang('Tax price')', moneyFormat(selling.tax_price)]);
      }
      p.table(['@lang('Subtotal')', moneyFormat(selling.total_price)])
      if ("@js(feature(Discount::class))" == 'true') {
        p.table(['@lang('Discount')', `(${moneyFormat(selling.total_discount_per_item + selling.discount_price)})`])
      }
      p.table(['@lang('Total price')', moneyFormat(selling.grand_total_price)])
        .text('-------------------------------');
      if (selling.payment_method?.is_credit) {
        if (selling.payed_money > 0) {
          p.table(['@lang('DP (Down payment)')', moneyFormat(selling.payed_money)])
           .table(['@lang('Remaining')', moneyFormat(selling.grand_total_price - selling.payed_money)]);
        } else {
          p.table(['@lang('Remaining')', moneyFormat(selling.grand_total_price)]);
        }
        if (selling.due_date) p.table(['@lang('Due date')', selling.due_date]);
      } else {
        p.table(['@lang('Payed money')', moneyFormat(selling.payed_money)])
         .table(['@lang('Change')', moneyFormat(selling.money_changes)]);
      }
      p.align('center');
      if (printerData.footer != undefined) p.text(printerData.footer);
      await p.cut().print();
    }

    function previewHtml(selling, about, printerData) {
      const line = '─'.repeat(31);
      let h = '';
      const esc = (s) => s == null ? '' : String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
      if (about) {
        h += `<div style="text-align:center;font-size:14px;font-weight:700;margin-bottom:2px">${esc(about.shop_name)}</div>`;
        if (about.shop_location) h += `<div style="text-align:center;font-size:11px;margin-bottom:2px">${esc(about.shop_location)}</div>`;
        if (printerData?.header) h += `<div style="text-align:center;font-size:11px">${esc(printerData.header)}</div>`;
      }
      h += `<div style="text-align:center;letter-spacing:.2em;margin:6px 0">${line}</div>`;
      if (selling.user?.name) h += `<div style="display:flex;justify-content:space-between"><span>Cashier</span><span>${esc(selling.user.name)}</span></div>`;
      if (selling.table?.number) h += `<div style="display:flex;justify-content:space-between"><span>Table</span><span>${esc(selling.table.number)}</span></div>`;
      if (selling.payment_method?.name) h += `<div style="display:flex;justify-content:space-between"><span>Payment</span><span>${esc(selling.payment_method.name)}</span></div>`;
      if (selling.member?.name) h += `<div style="display:flex;justify-content:space-between"><span>Member</span><span>${esc(selling.member.name)}</span></div>`;
      h += `<div style="text-align:center;letter-spacing:.2em;margin:6px 0">${line}</div>`;
      const details = selling.selling_details || selling.details || [];
      details.forEach(d => {
        const qty = d.qty || 1;
        const ppu = d.price_per_unit || (d.price ? d.price / qty : 0);
        const nm = d.product?.name || d.name || 'Product';
        h += `<div style="display:flex;justify-content:space-between"><span>${esc(nm)}</span><span>${moneyFormat(ppu)} x ${qty}</span></div>`;
        if (d.discount_price > 0) h += `<div style="text-align:right">(${moneyFormat(d.discount_price)})</div>`;
        h += `<div style="text-align:right;font-weight:600">${moneyFormat(d.price || d.total_price || 0)}</div>`;
      });
      h += `<div style="text-align:center;letter-spacing:.2em;margin:6px 0">${line}</div>`;
      if (selling.tax > 0) {
        h += `<div style="display:flex;justify-content:space-between"><span>Tax</span><span>${selling.tax}%</span></div>`;
        h += `<div style="display:flex;justify-content:space-between"><span>Tax price</span><span>${moneyFormat(selling.tax_price)}</span></div>`;
      }
      h += `<div style="display:flex;justify-content:space-between"><span>Subtotal</span><span>${moneyFormat(selling.total_price)}</span></div>`;
      const discount = (selling.total_discount_per_item || 0) + (selling.discount_price || 0);
      if (discount > 0) h += `<div style="display:flex;justify-content:space-between"><span>Discount</span><span>(${moneyFormat(discount)})</span></div>`;
      h += `<div style="display:flex;justify-content:space-between;font-weight:700"><span>Total price</span><span>${moneyFormat(selling.grand_total_price)}</span></div>`;
      h += `<div style="text-align:center;letter-spacing:.2em;margin:6px 0">${line}</div>`;
      const isCredit = selling.payment_method?.is_credit || false;
      if (isCredit) {
        h += `<div style="display:flex;justify-content:space-between"><span>Total</span><span>${moneyFormat(selling.grand_total_price)}</span></div>`;
        if (selling.payed_money > 0) {
          h += `<div style="display:flex;justify-content:space-between"><span>DP (Down payment)</span><span>${moneyFormat(selling.payed_money)}</span></div>`;
          h += `<div style="display:flex;justify-content:space-between;font-weight:700"><span>Remaining</span><span>${moneyFormat(selling.grand_total_price - selling.payed_money)}</span></div>`;
        } else {
          h += `<div style="display:flex;justify-content:space-between;font-weight:700"><span>Remaining</span><span>${moneyFormat(selling.grand_total_price)}</span></div>`;
        }
        if (selling.due_date) h += `<div style="display:flex;justify-content:space-between"><span>Due date</span><span>${esc(selling.due_date)}</span></div>`;
      } else {
        h += `<div style="display:flex;justify-content:space-between"><span>Payed money</span><span>${moneyFormat(selling.payed_money)}</span></div>`;
        h += `<div style="display:flex;justify-content:space-between"><span>Change</span><span>${moneyFormat(selling.money_changes)}</span></div>`;
      }
      if (printerData?.footer) h += `<div style="text-align:center;margin-top:4px">${esc(printerData.footer)}</div>`;
      h += `<div style="font-size:10px;margin-top:2px">copy</div>`;
      return h;
    }

    document.getElementById("printReceiptButton").addEventListener('click', async () => {
      let a = @js($about);
      const pd = getPrinter();
      const preview = previewHtml(selling, a, pd instanceof Error ? null : pd);
      document.getElementById('receiptPreviewContent').innerHTML = preview;
      $wire.dispatch('close-modal', {id: 'success-modal'});
      $wire.dispatch('open-modal', {id: 'receipt-preview-modal'});
    });

    document.addEventListener('click', async (e) => {
      if (e.target.id === 'confirmPrintButton' || e.target.closest('#confirmPrintButton')) {
        await doPrintReceipt();
        $wire.dispatch('close-modal', {id: 'receipt-preview-modal'});
      }
    });

    Alpine.data('fullscreen', () => {
      return {
        isFullscreen: false,
        requestFullscreen() {
          if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
            isFullscreen = true;
          } else {
            document.exitFullscreen();
            isFullscreen = false;
          }
        }
      }
    });
    Alpine.data('detail', () => {
      return {
        paymentMethodWarning: false,
        rawValue: 0,
        changeAmount: 0,
        maxValue: 999999999,
        decimalActive: false,
        decimalCount: 0,

        init() {
            Livewire.on('payment-method-missing', () => {
                this.paymentMethodWarning = true;
                setTimeout(() => this.paymentMethodWarning = false, 3000);
            });
            this.$watch('subtotal', (val) => {
              if (val !== undefined && val !== null) this.recalc();
            });
            document.addEventListener('shortcut-payment', (e) => {
              this.shortcut(e.detail.amount);
            });
        },
        isTouchScreen() {
          return ('ontouchstart' in window) ||
            (navigator.maxTouchPoints > 0) ||
            (navigator.msMaxTouchPoints > 0);
        },
        paymentMethods: $wire.entangle('paymentMethods'),
        cartDetail: @js($cartDetail),
        subtotal: $wire.entangle('total_price'),

        pressDigit(digit) {
          if (this.decimalActive) {
            this.decimalCount++;
            if (this.decimalCount > 2) return;
            this.rawValue = this.rawValue + digit / Math.pow(10, this.decimalCount);
            this.rawValue = parseFloat(this.rawValue.toFixed(2));
          } else {
            let next = this.rawValue * 10 + digit;
            if (next > this.maxValue) return;
            this.rawValue = next;
          }
          this.sync();
        },
        pressDecimal() {
          if (this.decimalActive) return;
          this.decimalActive = true;
          this.decimalCount = 0;
          // Ensure rawValue is float so toFixed works
          this.rawValue = parseFloat(this.rawValue.toString());
          this.sync();
        },
        pressBackspace() {
          if (this.decimalActive && this.decimalCount > 0) {
            this.decimalCount--;
            if (this.decimalCount === 0) {
              this.decimalActive = false;
              this.rawValue = Math.round(this.rawValue);
            } else {
              this.rawValue = Math.floor(this.rawValue * Math.pow(10, this.decimalCount)) / Math.pow(10, this.decimalCount);
            }
          } else if (this.decimalActive && this.decimalCount === 0) {
            this.decimalActive = false;
          } else {
            this.rawValue = Math.floor(this.rawValue / 10);
          }
          this.sync();
        },
        pressClear() {
          this.rawValue = 0;
          this.decimalActive = false;
          this.decimalCount = 0;
          this.sync();
        },
        pressNoChange() {
          let sub = this.getSubtotal();
          this.rawValue = sub;
          this.decimalActive = false;
          this.decimalCount = 0;
          this.sync();
        },
        shortcut(number) {
          let n = parseFloat(number);
          if (isNaN(n) || n < 0) return;
          if (n > this.maxValue) n = this.maxValue;
          this.rawValue = n;
          this.decimalActive = false;
          this.decimalCount = 0;
          this.sync();
        },
        getSubtotal() {
          let sub = typeof this.subtotal === 'number' ? this.subtotal : parseFloat(this.subtotal || '0');
          return isNaN(sub) ? 0 : sub;
        },
        sync() {
          this.$refs.payedMoney.value = moneyFormat(this.rawValue);
          this.recalc();
        },
        recalc() {
          let sub = this.getSubtotal();
          this.changeAmount = this.rawValue - sub;
          let safeChange = this.changeAmount > 0 ? this.changeAmount : 0;
          $wire.set('cartDetail.money_changes', safeChange);
          $wire.set('cartDetail.payed_money', this.rawValue);
          if (this.$refs.moneyChanges) {
            this.$refs.moneyChanges.textContent = moneyFormat(safeChange);
          }
        },
        handleKeydown(event) {
          let k = event.key;
          if (k >= '0' && k <= '9') { this.pressDigit(parseInt(k, 10)); event.preventDefault(); }
          else if (k === '.' || k === ',') { this.pressDecimal(); event.preventDefault(); }
          else if (k === 'Backspace') { this.pressBackspace(); event.preventDefault(); }
          else if (k === 'Delete') { this.pressClear(); event.preventDefault(); }
          else if (k === 'Escape') { $wire.dispatch('close-modal', {id: 'proceed-the-payment'}); event.preventDefault(); }
        },
        selectPayment(method) {
          this.cartDetail['payment_method_id'] = method.id;
          $wire.setPaymentMethodId(method.id);

          var midtransTypes = ['debit_card', 'gopay', 'shopeepay', 'qris', 'bank_transfer', 'indomaret', 'alfamart', 'kredivo', 'akulaku'];
          if (midtransTypes.includes(method.payment_type)) {
            setTimeout(() => { $wire.proceedThePayment(); }, 100);
          }
        }
      }
    });

    Alpine.data('cart', () => {
      return {
        add: (productId, amount) => {
          $wire.addCart(productId, {
            amount: amount ?? 0
          })
          console.log(productId, amount)
        }
      }
    })

    let barcodeData = '';
    let barcodeTimeout;
    let scannerEnabled = true;
    let modalOpened = false;
    let input;
    let index;

    function generateSuggestedPayments(totalPrice) {
      const denominations = [500, 1000, 2000, 5000, 10000, 20000, 50000, 100000];
      const suggestions = [];

      for (let denom of denominations) {
        const suggestion = Math.ceil(totalPrice / denom) * denom;
        if (!suggestions.includes(suggestion)) {
          suggestions.push(suggestion);
        }
      }

      suggestions.sort((a, b) => a - b);

      return suggestions;
    }

    let lastGeneratedTotal = null;

    function generateButton(totalPrice) {
      if (totalPrice === lastGeneratedTotal) return;
      lastGeneratedTotal = totalPrice;

      const shortcutSuggestion = generateSuggestedPayments(totalPrice);
      let calculatorBtn = document.getElementById('calculator-button-shortcut');
      if (!calculatorBtn) return;

      while (calculatorBtn.firstChild) {
        calculatorBtn.removeChild(calculatorBtn.firstChild);
      }

      for (let suggestion of shortcutSuggestion) {
        const button = document.createElement('button');
        button.textContent = numberFormat(suggestion);
        button.setAttribute('type', 'button');
        button.className = 'flex min-h-[40px] items-center justify-center rounded-xl bg-zonakasir-primary/10 p-2 text-sm font-semibold text-zonakasir-primary shadow-sm ring-1 ring-zonakasir-primary/20 transition-all hover:bg-zonakasir-primary/20 active:scale-95 dark:bg-zonakasir-primary/20 dark:text-zonakasir-primary/90 dark:ring-zonakasir-primary/30';
        button.addEventListener('click', () => {
          document.dispatchEvent(new CustomEvent('shortcut-payment', { detail: { amount: suggestion } }));
        });
        calculatorBtn.appendChild(button);
      }
    }

    $wire.on('open-modal', (event) => {

      // Initialize QR scanner when modal opens
      if (event.id === 'qr-scanner-modal') {
        // Create scanner instance only once (singleton pattern)
        if (!html5QrcodeScanner) {
          html5QrcodeScanner = new Html5QrcodeScanner(
            "qr-reader",
            {
              fps: 10,
              qrbox: { width: 300, height: 200 },
              rememberLastUsedCamera: true
            },
            false // verbose mode disabled
          );
        }
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
      }


      if (event.inputId != undefined) {
        let inputId = event.inputId;
        let title = event.title;
        let titleModal = document.getElementById("titleEditDetail");
        titleModal.innerHTML = title;
        index = event.index;
        input = document.getElementById(inputId);
        const result = [...(input.parentNode.parentNode.parentNode.parentNode.parentNode.children)].forEach((child,
          i) => {
          if (i != index) {
            child.classList.add('hidden');
          }
        });
        input.classList.remove('hidden');
      }
      let totalPrice = $refs.total.getAttribute('data-value');
      if ("@js(feature(PaymentShortcutButton::class))" == 'true') {
        generateButton(totalPrice);
      }
      if (event.id === 'proceed-the-payment') {
        setTimeout(() => {
          const el = document.getElementById('display');
          if (el) el.focus();
        }, 150);
      }
      modalOpened = true;
    });

    $wire.on('close-modal', (event) => {
      if (input != undefined) {
        let titleModal = document.getElementById("titleEditDetail");
        titleModal.innerHTML = '@lang('Edit detail')';
        const result = [...(input.parentNode.parentNode.parentNode.parentNode.parentNode.children)].forEach((child,
          i) => {
          if (i != index) {
            child.classList.remove('hidden');
          }
        });
        input.classList.add('hidden');
        input = undefined
      }
      modalOpened = false;
    });

    // QR Scanner global variables and functions
    let html5QrcodeScanner = null;
    let isScanningEnabled = true;

    /**
     * Handles successful barcode/QR code scan
     * @param {string} decodedText - The decoded string from the QR code or barcode
     */
    async function onScanSuccess(decodedText, decodedResult) {
      if (!isScanningEnabled) return;

      // Find Alpine.js component for state management
      const readerElement = document.getElementById('qr-reader');
      if (!readerElement) {
        console.error('Scanner reader element not found!');
        return;
      }

      const alpineContainer = readerElement.closest('[x-ref="scannerContainer"]');
      if (!alpineContainer || !alpineContainer._x_dataStack) {
        console.error('Could not find the Alpine.js scanner container.');
        return;
      }
      const alpineComponent = alpineContainer._x_dataStack[0];

      // Disable scanning and show loading spinner
      isScanningEnabled = false;
      alpineComponent.isLoading = true;

      console.log(`Scan result: ${decodedText}`);

      // Process product and wait for Livewire to complete
      await $wire.call('addCartUsingScanner', decodedText);

      // Hide loading spinner
      alpineComponent.isLoading = false;

      // Show success notification
      new FilamentNotification()
        .title('Product added')
        .success()
        .duration(3000)
        .send();

      // Re-enable scanning after cooldown period
      setTimeout(() => {
        isScanningEnabled = true;
      }, 1000);
    }

    /**
     * Handles scan failure (empty implementation)
     */
    function onScanFailure(error) {
      // Intentionally empty - failures are handled silently
    }

    /**
     * Safely stops the camera scanner
     */
    window.stopScanner = () => {
      if (html5QrcodeScanner && html5QrcodeScanner.getState() === Html5QrcodeScannerState.SCANNING) {
        html5QrcodeScanner.clear().then(() => {
          console.log('QR Code scanner stopped successfully.');
        }).catch(err => {
          // Ignore errors during rapid closing
        });
      }
    };
    // Physical barcode scanner support (keyboard input)
    document.addEventListener('keypress', (event) => {
      if (modalOpened || !scannerEnabled) {
        return;
      }

      if (barcodeTimeout) {
        clearTimeout(barcodeTimeout);
      }

      if (event.key === 'Enter') {
        console.log('Barcode scanned:', barcodeData);
        $wire.addCartUsingScanner(barcodeData);

        barcodeData = '';
        scannerEnabled = false;

        // Re-enable scanner after processing
        setTimeout(() => {
          scannerEnabled = true;
        }, 1000);
      } else {
        barcodeData += event.key;
      }

      // Clear barcode data if no input for 500ms
      barcodeTimeout = setTimeout(() => {
        barcodeData = '';
      }, 500);
    });
  </script>
@endscript
