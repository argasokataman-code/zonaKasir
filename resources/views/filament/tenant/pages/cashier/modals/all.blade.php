{{-- ═══════════════════════════════════════════════════════════════
     MODALS: edit-detail, proceed-the-payment, success, quick-member,
     table-select, qr-scanner, offline-payment-confirm
     ═══════════════════════════════════════════════════════════════ --}}

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
                   x-text="paymentMethod.name">
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
              @include('filament.tenant.pages.cashier.partials.total')
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
          @include('filament.tenant.pages.cashier.partials.items')
        </div>
        {{-- Mobile: collapsible cart summary --}}
        <details class="md:hidden mt-2 border rounded-lg">
          <summary class="cursor-pointer px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
            {{ __('Order items') }} ({{ count($cartItems) }})
          </summary>
          <div class="px-3 pb-2">
            @include('filament.tenant.pages.cashier.partials.items')
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
