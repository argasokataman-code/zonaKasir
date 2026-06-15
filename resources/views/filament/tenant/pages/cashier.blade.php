@php
  use Filament\Facades\Filament;
  use App\Features\{PaymentShortcutButton, SellingTax, Discount};

@endphp
<div class="" x-data="{ cartOpen: false, isOffline: !navigator.onLine }" x-init="window.addEventListener('online', () => isOffline = false); window.addEventListener('offline', () => isOffline = true);">
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
      <div class="grid grid-cols-2 gap-2 sm:gap-3 sm:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" wire:loading.class="opacity-60">
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
                @elseif ($stock < Setting::get('minimum_stock_nofication', 10))
                  <span class="absolute left-2 top-2 rounded-md bg-amber-500 px-1.5 py-0.5 text-xs font-bold text-white shadow-sm">{{ $stock }} {{ __('Stock') }}</span>
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
            </div>
            <x-filament::input.wrapper
              x-show="paymentMethods.filter((pm) => pm.is_credit)[0]?.id == cartDetail['payment_method_id']"
              :valid="!$errors->has('due_date')" class="mb-2">
              <x-slot name="prefix">
                {{ __('Due date') }}
              </x-slot>
              <x-filament::input type="date" wire:model="cartDetail.due_date" />
            </x-filament::input.wrapper>
            <div class="mb-4">
              @include('filament.tenant.pages.cashier.total')
            </div>
            <div x-show="paymentMethods.find(p => p.id == cartDetail.payment_method_id)?.payment_type == 'cash' || !paymentMethods.find(p => p.id == cartDetail.payment_method_id)?.payment_type">
            @error('payed_money')
              <span class="error text-danger-500">{{ $message }}</span>
            @enderror
            <input id="display"
              class="@error('payed_money') 'border-danger-500' @enderror w-full rounded-md border border-gray-300 bg-white p-2 text-right text-base text-black dark:bg-gray-900 dark:text-white md:text-lg"
              focus :disabled="isTouchScreen" x-mask:dynamic="$money($input)" x-on:keyup="changes" x-ref="payedMoney"
              inputMode="numeric">
            <div class="mt-4 grid grid-cols-3 gap-2" id="calculator-button-shortcut">
            </div>
            <div class="mt-2 grid grid-cols-3 gap-2" id="calculator-button">
              <button type="button" class="col-span-3 rounded-md bg-gray-300 p-1.5 text-sm hover:bg-gray-400 md:p-2 md:text-lg"
                x-on:click="append('no_changes')">{{ __('No change') }}</button>
              <button type="button" class="rounded-md bg-gray-300 p-1.5 text-sm hover:bg-gray-400 md:p-2 md:text-lg"
                x-on:click="append(7)">7</button>
              <button type="button" class="rounded-md bg-gray-300 p-1.5 text-sm hover:bg-gray-400 md:p-2 md:text-lg"
                x-on:click="append(8)">8</button>
              <button type="button" class="rounded-md bg-gray-300 p-1.5 text-sm hover:bg-gray-400 md:p-2 md:text-lg"
                x-on:click="append(9)">9</button>
              <button type="button" class="rounded-md bg-gray-300 p-1.5 text-sm hover:bg-gray-400 md:p-2 md:text-lg"
                x-on:click="append(4)">4</button>
              <button type="button" class="rounded-md bg-gray-300 p-1.5 text-sm hover:bg-gray-400 md:p-2 md:text-lg"
                x-on:click="append(5)">5</button>
              <button type="button" class="rounded-md bg-gray-300 p-1.5 text-sm hover:bg-gray-400 md:p-2 md:text-lg"
                x-on:click="append(6)">6</button>
              <button type="button" class="rounded-md bg-gray-300 p-1.5 text-sm hover:bg-gray-400 md:p-2 md:text-lg"
                x-on:click="append(1)">1</button>
              <button type="button" class="rounded-md bg-gray-300 p-1.5 text-sm hover:bg-gray-400 md:p-2 md:text-lg"
                x-on:click="append(2)">2</button>
              <button type="button" class="rounded-md bg-gray-300 p-1.5 text-sm hover:bg-gray-400 md:p-2 md:text-lg"
                x-on:click="append(3)">3</button>
              <button type="button" class="rounded-md bg-gray-300 p-1.5 text-sm hover:bg-gray-400 md:p-2 md:text-lg"
                x-on:click="append('.')">.</button>
              <button type="button" class="rounded-md bg-gray-300 p-1.5 text-sm hover:bg-gray-400 md:p-2 md:text-lg"
                x-on:click="append(0)">0</button>
              <button type="button"
                class="flex items-center justify-center rounded-md bg-gray-300 p-1.5 text-sm hover:bg-gray-400 md:p-2 md:text-lg"
                x-on:click="append('backspace')">
                <x-filament::icon icon="heroicon-o-backspace" class="h-4 w-4 text-gray-500 dark:text-white md:h-5 md:w-5" />
              </button>
            </div>
            </div>
            <div class="mt-2 grid grid-cols-3 gap-2">
                <button wire:loading.attr="disabled" type="submit"
                  class="flex w-full items-center justify-center gap-x-2 rounded-md bg-zonakasir-primary p-2 text-sm text-white hover:brightness-110 md:text-lg">
                  <div wire:loading>
                    <x-filament::loading-indicator class="h-5 w-5" />
                  </div>
                  {{ __('Pay it') }}
                </button>
                <button wire:click="dispatch('close-modal', {id: 'proceed-the-payment'});" type="button"
                  class="flex w-full items-center justify-center gap-x-2 rounded-md bg-gray-300 p-2 text-sm md:text-lg">
                  {{ __('Close') }}
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
      -webkit-overflow-scrolling: touch;
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
    snapScript.setAttribute('data-client-key', @js(config('midtrans.client_key')));
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
        .text('-------------------------------')
        .table(['@lang('Payed money')', moneyFormat(selling.payed_money)])
        .table(['@lang('Change')', moneyFormat(selling.money_changes)])
        .align('center');
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
      h += `<div style="display:flex;justify-content:space-between"><span>Payed money</span><span>${moneyFormat(selling.payed_money)}</span></div>`;
      h += `<div style="display:flex;justify-content:space-between"><span>Change</span><span>${moneyFormat(selling.money_changes)}</span></div>`;
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
        isTouchScreen() {
          return ('ontouchstart' in window) ||
            (navigator.maxTouchPoints > 0) ||
            (navigator.msMaxTouchPoints > 0);
        },
        displayValue: '',
        paymentMethods: $wire.entangle('paymentMethods'),
        cartDetail: @js($cartDetail),
        subtotal: $wire.entangle('total_price'),
        shortcut(number) {
          this.$refs.payedMoney.value = moneyFormat(number);
          this.changes();
          return;
        },
        append(number) {
          if (number == 'no_changes') {
            this.$refs.payedMoney.value = moneyFormat(this.subtotal);
            this.changes();
            return;
          }
          if (number == 'backspace') {
            this.displayValue = this.displayValue.slice(0, -1);
            this.$refs.payedMoney.value = moneyFormat(this.displayValue);
            this.changes();
            return;
          }
          this.displayValue += number;
          this.$refs.payedMoney.value = moneyFormat(this.displayValue);
          this.changes();
        },
        selectPayment(method) {
          this.cartDetail['payment_method_id'] = method.id;
          $wire.setPaymentMethodId(method.id);

          // Midtrans types that don't need calculator: directly proceed payment
          var midtransTypes = [/*'credit_card',*/ 'debit_card', 'gopay', 'shopeepay', 'qris', 'bank_transfer', 'indomaret', 'alfamart', 'kredivo', 'akulaku'];
          if (midtransTypes.includes(method.payment_type)) {
            // Small delay to allow Livewire to sync payment_method_id
            setTimeout(() => { $wire.proceedThePayment(); }, 100);
          }
        },
        changes() {
          let val = this.$refs.payedMoney.value || '';
          let numericValue = val.replace(/\D/g, '');
          let num = parseInt(numericValue, 10);
          num = isNaN(num) ? 0 : num;

          this.displayValue = num > 0 ? num.toString() : '';

          $wire.cartDetail['money_changes'] = num - (this.subtotal);
          $wire.cartDetail['payed_money'] = num;

          if (this.$refs.moneyChanges) {
            this.$refs.moneyChanges.textContent = moneyFormat($wire.cartDetail['money_changes']);
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

    function generateButton(totalPrice) {
      const shortcutSuggestion = generateSuggestedPayments(totalPrice);
      let calculatorBtn = document.getElementById('calculator-button-shortcut');
      calculatorBtn.innerHTML = '';

      for (let suggestion of shortcutSuggestion) {
        const button = document.createElement('button');
        button.textContent = numberFormat(suggestion);
        button.setAttribute('type', 'button')
        button.setAttribute('x-on:click', `shortcut(${suggestion})`);
        button.className = 'bg-gray-300 hover:bg-gray-400 p-2 rounded-md text-lg';
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
