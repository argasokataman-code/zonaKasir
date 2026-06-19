@php
  use Filament\Facades\Filament;
  use App\Features\{PaymentShortcutButton, SellingTax, Discount};

@endphp
<div x-data="cashier" x-on:cart-data-updated.window="handleCartDataUpdated">

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
      <div x-show="!isOffline" class="mb-4 px-1">
        <div class="relative">
          <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
          <input type="text" wire:model.live.debounce.300ms="search"
            class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 focus:border-zonakasir-primary focus:outline-none focus:ring-1 focus:ring-zonakasir-primary dark:border-gray-600 dark:bg-gray-800 dark:text-white"
            placeholder="{{ __('Search (SKU, name, barcode)') }}">
        </div>
      </div>

      {{-- Categories --}}
      <div x-show="!isOffline" class="mb-4 flex gap-2 overflow-x-auto px-1">
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
            <div class="flex flex-1 flex-col justify-between p-2 min-w-0">
              <div class="min-w-0">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 truncate">{{ $product->sku }}</p>
                <h3 class="mt-0.5 text-xs font-semibold leading-tight text-gray-900 dark:text-white line-clamp-2 break-words">{{ $product->name }}</h3>
              </div>
              <div class="mt-1 flex items-center justify-between gap-1 min-w-0">
                <span class="text-xs font-bold text-zonakasir-primary truncate">{{ price_format($product->sellingPriceCalculate) }}</span>
                <div class="flex items-center shrink-0">
                  <button x-show="!cartQty[{{ $product->id }}]" @click="instantAdd({{ $product->id }})"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zonakasir-primary text-white">
                    <x-heroicon-o-plus class="h-3.5 w-3.5" />
                  </button>
                  <div x-show="cartQty[{{ $product->id }}]" x-cloak class="flex items-center gap-px">
                    <button @click="instantReduce({{ $product->id }})"
                      class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                      <x-heroicon-o-minus-small class="h-3.5 w-3.5" />
                    </button>
                    <span class="w-6 text-center text-xs font-semibold text-zonakasir-primary shrink-0" x-text="cartQty[{{ $product->id }}]"></span>
                    <button @click="instantAdd({{ $product->id }})"
                      class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zonakasir-primary text-white">
                      <x-heroicon-o-plus-small class="h-3.5 w-3.5" />
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

      @include('filament.tenant.pages.cashier.pwa.ui')
    </div>

    {{-- Mobile: cart toggle button with scan --}}
    <div class="fixed bottom-0 left-0 right-0 z-50 border-t bg-white px-3 pb-[env(safe-area-inset-bottom)] pt-3 shadow-lg dark:border-gray-800 dark:bg-gray-900 lg:hidden"
      x-show="!cartOpen && !isOffline">
      <div class="flex gap-2">
        <button @click="cartOpen = true"
          class="flex flex-1 items-center justify-between rounded-lg bg-zonakasir-primary px-4 py-3 min-h-[48px] text-white">
          <span class="font-semibold">{{ __('View Cart') }}</span>
          <span class="flex items-center gap-2">
            <span class="rounded-full bg-white/20 px-2 py-0.5 text-sm" x-text="cartCount">{{ $cartCount }}</span>
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
    <div x-show="!isOffline" class="fixed inset-x-0 bottom-0 z-50 max-h-[85vh] overflow-y-auto rounded-t-2xl bg-white shadow-2xl transition-transform duration-300 dark:bg-gray-900 lg:fixed lg:left-auto lg:right-0 lg:top-0 lg:bottom-auto lg:z-auto lg:h-screen lg:w-[40%] xl:w-1/3 lg:max-h-none lg:rounded-none lg:shadow-none"
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
            <div wire:key="cart-item-{{ $item->id }}" class="mb-2 rounded-lg border bg-white px-3 py-2 dark:border-gray-900 dark:bg-gray-900">
              <div class="flex justify-between items-center">
                <div class="min-w-0 flex-1">
                  <p class="font-semibold text-sm truncate"> {{ $item->product?->name ?? __('Deleted product') }}</p>
                  <p class="text-xs text-zonakasir-primary font-semibold">{{ $item->price_format_money }}</p>
                </div>
                <div class="flex items-center gap-1 shrink-0 ml-2">
                  <button class="flex h-7 w-7 items-center justify-center rounded-lg bg-zonakasir-primary text-white text-sm font-bold"
                    wire:click.stop="addCart( {{ $item->product_id }} )" wire:loading.attr="disabled">+</button>
                  <input type="text" value="{{ $item->qty }}"
                    x-on:keyup.debounce.500ms="(e) => add('{{ $item->product_id }}', e.target.value)"
                    class="w-10 rounded border border-gray-300 px-1 py-0.5 text-center text-xs dark:border-gray-600 dark:bg-gray-800"
                    inputMode="numeric" />
                  <button class="flex h-7 w-7 items-center justify-center rounded-lg bg-gray-100 text-gray-600 text-sm font-bold dark:bg-gray-700"
                    x-on:click="$wire.reduceCart({{ $item->product_id }});" wire:loading.attr="disabled">−</button>
                  <button class="flex h-7 w-7 items-center justify-center rounded-lg bg-red-100 text-red-600 text-sm relative"
                    x-on:click="$el.closest('.mb-2').style.display='none'"
                    wire:click.stop="deleteCart({{ $item->product_id }})"
                    wire:loading.class="opacity-50"
                    wire:target="deleteCart({{ $item->product_id }})"
                    type="button">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                  </button>
                  @if($item->product && $item->product->priceUnits()->exists())
                    <livewire:price-setting :cart-item="$item" wire:key="ps-{{ $item->id }}" />
                  @endif
                </div>
              </div>
              @feature(Discount::class)
                @if($item->discount_price > 0)
                  <div class="mt-1 text-right">
                    <input type="text" value="{{ $item->discount_price }}"
                      wire:keyup.debounce.500ms="addDiscountPricePerItem({{ $item }}, parseFloat($event.target.value.replace(/,/g, '')))"
                      placeholder="{{ __('Discount') }}"
                      class="w-24 rounded border border-gray-300 px-2 py-0.5 text-right text-xs dark:border-gray-600 dark:bg-gray-800"
                      inputMode="numeric" x-mask:dynamic="$money($input)" />
                    <p class="text-xs font-semibold text-zonakasir-primary">{{ $item->final_price_format }}</p>
                  </div>
                @endif
              @endfeature
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
            @include('filament.tenant.pages.cashier.partials.detail')
          </div>
        </div>
        <div>
          <div
            class="w-full rounded-lg border bg-white px-3 py-1.5 text-sm text-gray-600 dark:border-gray-900 dark:bg-gray-900 dark:text-white">
            @include('filament.tenant.pages.cashier.partials.total')
          </div>
        </div>
        <button class="w-full rounded-lg bg-zonakasir-primary px-2 py-3 text-sm font-semibold text-white"
          x-on:mousedown="cartOpen = false; $dispatch('open-modal', {id: 'proceed-the-payment'});">{{ __('Proceed to payment') }}</button>
      </div>
    </div>
  </div>
  @include('filament.tenant.pages.cashier.modals.all')
</div>

@include('filament.tenant.pages.cashier.scripts')
