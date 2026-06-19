{{-- ═══════════════════════════════════════════════════════════════
     OFFLINE MODE: product grid, cart, payment
     ═══════════════════════════════════════════════════════════════ --}}

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

        <template x-if="offlineCart[product.id] && offlineCart[product.id].qty > 0">
          <span class="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-zonakasir-primary text-xs font-bold text-white shadow-sm" x-text="offlineCart[product.id].qty"></span>
        </template>
      </div>

      <div class="flex flex-1 flex-col justify-between p-3 min-w-0">
        <div class="min-w-0">
          <p class="text-xs font-medium text-gray-500 dark:text-gray-400 truncate" x-text="product.sku"></p>
          <h3 class="mt-0.5 text-sm font-semibold leading-tight text-gray-900 dark:text-white line-clamp-2 break-words" x-text="product.name"></h3>
        </div>
        <div class="mt-2 flex flex-wrap items-center gap-x-1 min-w-0">
          <span class="text-sm font-bold text-zonakasir-primary whitespace-nowrap" x-text="'Rp ' + (product.selling_price_calculate || product.selling_price || 0).toLocaleString('id-ID')"></span>
          <div class="flex items-center justify-end min-h-[44px] min-w-0">
            <template x-if="!offlineCart[product.id] || offlineCart[product.id].qty === 0">
              <button @click="offlineAddToCart(product.id)"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-zonakasir-primary text-white transition-colors hover:bg-zonakasir-primary/90">
                <x-heroicon-o-plus class="h-4 w-4" />
              </button>
            </template>
            <template x-if="offlineCart[product.id] && offlineCart[product.id].qty > 0">
              <div class="flex items-center gap-0.5">
                <button @click="offlineRemoveFromCart(product.id)"
                  class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-600 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                  <x-heroicon-o-minus-small class="h-4 w-4" />
                </button>
                <span class="w-7 text-center text-sm font-semibold text-zonakasir-primary shrink-0" x-text="offlineCart[product.id].qty"></span>
                <button @click="offlineAddToCart(product.id)"
                  class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-zonakasir-primary text-white transition-colors hover:bg-zonakasir-primary/90">
                  <x-heroicon-o-plus-small class="h-4 w-4" />
                </button>
              </div>
            </template>
          </div>
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

{{-- Offline: cart toggle button (mobile only) --}}
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
