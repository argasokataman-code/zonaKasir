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
                  :class="cartDetail['payment_method_id'] == paymentMethod.id ? 'bg-primary-600 text-white' :
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
                class="w-full rounded-xl border-2 bg-gray-50 p-4 pb-6 text-right text-2xl font-bold text-gray-900 focus:outline-none dark:bg-gray-800 dark:text-white @error('payed_money') border-red-500 @else border-gray-200 dark:border-gray-600 focus:border-primary-500 @enderror"
                x-ref="payedMoney" inputMode="none" tabindex="0" @keydown="handleKeydown($event)">
              <span class="absolute bottom-1 right-4 text-xs text-gray-400" x-show="rawValue > 0" x-cloak>
                {{ __('Change') }}: <span class="font-semibold text-primary-600" x-text="moneyFormat(changeAmount > 0 ? changeAmount : 0)"></span>
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
                    class="text-xs font-semibold text-primary-600 hover:underline">
                    + {{ __('Add member') }}
                  </button>
                </div>
                <select wire:model="cartDetail.member_id"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
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
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
              </div>
              {{-- DP / Partial payment --}}
              <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Down payment (optional)') }}</label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">Rp</span>
                  <input type="text" wire:model.live="cartDetail.payed_money"
                    class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-right text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
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
                {{-- Partial / DP toggle (open bill F&B) — bayar sebagian, sisanya menyusul --}}
                <button type="button"
                  x-show="!paymentMethods.find(p => p.id == cartDetail.payment_method_id)?.is_credit"
                  class="flex min-h-[48px] w-full items-center justify-center gap-x-2 rounded-xl bg-gray-100 p-3 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-gray-200 transition-all hover:bg-gray-200 active:scale-95 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700"
                  :class="cartDetail.allow_partial ? 'ring-2 ring-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : ''"
                  x-on:click="cartDetail.allow_partial = !cartDetail.allow_partial; $wire.set('cartDetail.allow_partial', cartDetail.allow_partial);">
                  <x-heroicon-o-wallet class="h-5 w-5" />
                  <span x-text="cartDetail.allow_partial ? '{{ __('Partial active') }}' : '{{ __('Partial / DP') }}'"></span>
                </button>
                {{-- Split Bill --}}
                <button type="button"
                  x-show="!paymentMethods.find(p => p.id == cartDetail.payment_method_id)?.is_credit && cartCount > 0"
                  class="flex min-h-[48px] w-full items-center justify-center gap-x-2 rounded-xl bg-gray-100 p-3 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-gray-200 transition-all hover:bg-gray-200 active:scale-95 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700"
                  wire:click="openSplitBillModal()"
                  x-on:click="
                    setTimeout(() => {
                      try {
                        const raw = document.getElementById('split-cart-data')?.textContent || '[]';
                        const parsed = JSON.parse(raw);
                        $wire.splitBillCartItemsJson = raw;
                        $dispatch('open-split-bill-with-data', {items: parsed});
                      } catch(e) {}
                    }, 300);
                  ">
                  <x-heroicon-o-scissors class="h-5 w-5" />
                  {{ __('Split Bill') }}
                </button>
                {{-- Pay / Confirm Piutang --}}
                <button wire:loading.attr="disabled" wire:target="proceedThePayment" type="submit"
                  class="flex min-h-[48px] w-full items-center justify-center gap-x-2 rounded-xl bg-primary-600 p-3 text-base font-bold text-white shadow-lg shadow-primary-600/30 transition-all hover:brightness-110 active:scale-95 disabled:opacity-50">
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

  {{-- ═══ SPLIT BILL MODAL ═══ --}}
  {{-- Hidden data source — Livewire re-renders this on every round-trip --}}
  @php
    $splitCartData = $cartItems->map(fn($item) => [
      'id' => $item->id,
      'product_id' => $item->product_id,
      'name' => $item->product->name ?? 'Unknown',
      'qty' => $item->qty,
      'price' => $item->price,
      'discount_price' => $item->discount_price ?? 0,
      'unit_price' => $item->qty > 0 ? round($item->price / $item->qty, 2) : 0,
      'assigned_group' => -1,
    ])->values()->toArray();
  @endphp
  <script type="application/json" id="split-cart-data">{!! json_encode($splitCartData) !!}</script>

  <x-filament::modal id="split-bill-modal" width="5xl" :close-by-clicking-away="false" :close-by-escaping="false">
    <x-slot name="heading">
      {{ __('Split Bill') }}
    </x-slot>

    <div x-data="{
      groupCount: 2,
      groups: [],
      draggedItemId: null,
      dragOverGroup: null,
      cartItems: [],
      totalAll: @js($total_price),
      formatPrice(val) { return new Intl.NumberFormat('id-ID', {style:'currency',currency:'IDR',minimumFractionDigits:0}).format(val); },
      setGroupCount(n) {
        const labels = ['A','B','C','D','E','F'];
        const colors = ['bg-blue-500','bg-green-500','bg-amber-500','bg-purple-500','bg-red-500','bg-cyan-500'];
        this.groupCount = n;
        while (this.groups.length < n) {
          const i = this.groups.length;
          this.groups.push({ label: 'Group ' + labels[i], items: [], color: colors[i] });
        }
        this.groups.length = n;
        this.rebalance();
      },
      rebalance() {
        const n = this.groupCount;
        const unassigned = this.cartItems.filter(i => i.assigned_group < 0 || i.assigned_group >= n);
        unassigned.forEach((item, idx) => { item.assigned_group = idx % n; });
        this.groups.forEach((g, i) => { g.items = this.cartItems.filter(item => item.assigned_group === i); });
        this.syncPayload();
      },
      moveItem(itemId, targetGroup) {
        const item = this.cartItems.find(i => i.id === itemId);
        if (item && targetGroup >= 0 && targetGroup < this.groupCount) {
          item.assigned_group = targetGroup;
          this.groups.forEach((g, i) => { g.items = this.cartItems.filter(item => item.assigned_group === i); });
          this.syncPayload();
        }
      },
      onDragStart(e, itemId) {
        this.draggedItemId = itemId;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', itemId);
        e.target.classList.add('opacity-50', 'scale-95');
      },
      onDragEnd(e) {
        this.draggedItemId = null;
        this.dragOverGroup = null;
        e.target.classList.remove('opacity-50', 'scale-95');
        document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
      },
      onDragOver(e, gIdx) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        this.dragOverGroup = gIdx;
      },
      onDragLeave(e) {
        this.dragOverGroup = null;
      },
      onDrop(e, gIdx) {
        e.preventDefault();
        this.dragOverGroup = null;
        if (this.draggedItemId !== null) {
          this.moveItem(this.draggedItemId, gIdx);
          this.draggedItemId = null;
        }
      },
      renameGroup(gIdx, newName) {
        if (this.groups[gIdx]) {
          this.groups[gIdx].label = newName || ('Group ' + ['A','B','C','D','E','F'][gIdx]);
          this.syncPayload();
        }
      },
      resetAll() {
        this.groups.forEach((g, i) => {
          g.label = 'Group ' + ['A','B','C','D','E','F'][i];
        });
        this.rebalance();
      },
      groupTotal(groupIdx) {
        return this.groups[groupIdx] ? this.groups[groupIdx].items.reduce((sum, item) => sum + item.price - item.discount_price, 0) : 0;
      },
      splitPayload: '[]',
      syncPayload() {
        this.splitPayload = JSON.stringify(
          this.groups.filter((g, i) => i < this.groupCount).map(g => ({
            label: g.label,
            items: g.items.map(item => ({ detail_id: item.id, qty: item.qty }))
          }))
        );
      },
      init() {
        this.setGroupCount(2);
        window.addEventListener('open-split-bill-with-data', (e) => {
          this.cartItems = e.detail.items || [];
          this.setGroupCount(this.groupCount);
          this.$dispatch('open-modal', {id: 'split-bill-modal'});
        });
      },
    }">
      <style>
        .drag-over { background: rgba(59, 130, 246, 0.1) !important; border-color: rgb(59, 130, 246) !important; }
        [draggable='true'] { cursor: grab; }
        [draggable='true']:active { cursor: grabbing; }
      </style>

      {{-- Group count selector --}}
      <div class="mb-4">
        <p class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('How many groups?') }}</p>
        <div class="flex gap-2">
          <template x-for="n in 6" :key="n">
            <button type="button" @click="setGroupCount(n)"
              class="flex h-10 w-10 items-center justify-center rounded-lg text-sm font-bold transition-all"
              :class="groupCount === n
                ? 'bg-primary-600 text-white shadow-lg shadow-primary-600/30'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400'"
              x-text="n"></button>
          </template>
        </div>
      </div>

      {{-- Unassigned items (items not yet assigned) --}}
      <template x-if="cartItems.filter(i => i.assigned_group < 0 || i.assigned_group >= groupCount).length > 0">
        <div class="mb-4 rounded-xl border-2 border-dashed border-gray-300 p-3 dark:border-gray-600"
          @dragover.prevent="onDragOver($event, -1)"
          @dragleave="onDragLeave($event)"
          @drop.prevent="onDrop($event, -1)">
          <div class="mb-2 flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Unassigned Items') }}</span>
            <span class="text-[10px] text-gray-400">{{ __('Drag to group or select below') }}</span>
          </div>
          <div class="space-y-1">
            <template x-for="item in cartItems.filter(i => i.assigned_group < 0 || i.assigned_group >= groupCount)" :key="item.id">
              <div class="flex items-center justify-between rounded-lg bg-white/80 px-2 py-1.5 text-xs dark:bg-gray-800/80"
                draggable="true"
                @dragstart="onDragStart($event, item.id)"
                @dragend="onDragEnd($event)">
                <div class="min-w-0 flex-1">
                  <p class="truncate font-medium text-gray-800 dark:text-white" x-text="item.name"></p>
                  <p class="text-gray-500" x-text="item.qty + 'x ' + formatPrice(item.price)"></p>
                </div>
                <select @change="moveItem(item.id, parseInt($event.target.value)); $event.target.value = ''"
                  @click.stop
                  class="rounded border border-gray-300 bg-white px-2 py-1 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                  <option value="">{{ __('Move to...') }}</option>
                  <template x-for="(g, i) in groups.filter((_, i) => i < groupCount)" :key="i">
                    <option :value="i" x-text="g.label"></option>
                  </template>
                </select>
              </div>
            </template>
          </div>
        </div>
      </template>

      {{-- Groups grid --}}
      <div class="mb-4 grid gap-3"
        :class="groupCount <= 2 ? 'grid-cols-' + groupCount : groupCount <= 3 ? 'grid-cols-3' : 'grid-cols-2 sm:grid-cols-3'">
        <template x-for="(group, gIdx) in groups" :key="gIdx">
          <div class="rounded-xl border-2 border-dashed p-3 transition-all min-h-[100px]"
            :class="[group.color.replace('bg-', 'border-') + '/30', dragOverGroup === gIdx ? 'drag-over' : '']"
            x-show="gIdx < groupCount"
            @dragover.prevent="onDragOver($event, gIdx)"
            @dragleave="onDragLeave($event)"
            @drop.prevent="onDrop($event, gIdx)">
            {{-- Group header --}}
            <div class="mb-2 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold text-white flex-shrink-0"
                  :class="group.color" x-text="group.label.replace('Group ', '')"></span>
                <input type="text" :value="group.label"
                  @change="renameGroup(gIdx, $event.target.value)"
                  @blur="if(!$event.target.value) renameGroup(gIdx, 'Group ' + ['A','B','C','D','E','F'][gIdx])"
                  class="w-20 text-sm font-semibold text-gray-800 bg-transparent border-b border-transparent hover:border-gray-300 focus:border-primary-500 focus:outline-none dark:text-white dark:hover:border-gray-600 dark:focus:border-primary-400 truncate"
                  :placeholder="'Group ' + ['A','B','C','D','E','F'][gIdx]" />
              </div>
              <span class="text-xs font-bold text-primary-600 flex-shrink-0" x-text="formatPrice(groupTotal(gIdx))"></span>
            </div>
            {{-- Items in group --}}
            <div class="min-h-[60px] space-y-1">
              <template x-for="item in group.items" :key="item.id">
                <div class="flex items-center justify-between rounded-lg bg-white/80 px-2 py-1.5 text-xs dark:bg-gray-800/80"
                  draggable="true"
                  @dragstart="onDragStart($event, item.id)"
                  @dragend="onDragEnd($event)">
                  <div class="min-w-0 flex-1">
                    <p class="truncate font-medium text-gray-800 dark:text-white" x-text="item.name"></p>
                    <p class="text-gray-500" x-text="item.qty + 'x'"></p>
                  </div>
                  <div class="flex items-center gap-1">
                    <span class="w-16 text-right font-semibold text-gray-700 dark:text-gray-300" x-text="formatPrice(item.price - item.discount_price)"></span>
                    <select @change="moveItem(item.id, parseInt($event.target.value)); $event.target.value = ''"
                      @click.stop
                      class="rounded border border-gray-300 bg-white px-1.5 py-0.5 text-[10px] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                      <option value="">{{ __('Move') }}</option>
                      <template x-for="(g, i) in groups.filter((_, i) => i < groupCount && i !== gIdx)" :key="i">
                        <option :value="i" x-text="g.label"></option>
                      </template>
                    </select>
                  </div>
                </div>
              </template>
              <template x-if="group.items.length === 0">
                <p class="py-4 text-center text-xs text-gray-400">{{ __('Drop items here') }}</p>
              </template>
            </div>
          </div>
        </template>
      </div>

      {{-- Summary --}}
      <div class="mb-4 rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
        <div class="flex items-center justify-between text-sm">
          <span class="font-medium text-gray-600 dark:text-gray-400">{{ __('Total Bill') }}</span>
          <span class="font-bold text-gray-900 dark:text-white" x-text="formatPrice(totalAll)"></span>
        </div>
        <div class="mt-1 flex items-center justify-between text-sm">
          <span class="font-medium text-gray-600 dark:text-gray-400">{{ __('Groups') }}</span>
          <span class="font-bold text-primary-600" x-text="groupCount"></span>
        </div>
      </div>
      <input type="hidden" id="split-payload-input" x-ref="splitPayload" :value="splitPayload" />
    </div>

    <x-slot name="footer">
      <div class="flex gap-2">
        <x-filament::button color="gray" x-on:click="$dispatch('close-modal', {id: 'split-bill-modal'})">
          {{ __('Cancel') }}
        </x-filament::button>
        <x-filament::button color="danger" x-on:click="resetAll()">
          {{ __('Reset') }}
        </x-filament::button>
        <x-filament::button wire:loading.attr="disabled"
          x-on:click="
            const payload = document.getElementById('split-payload-input');
            $wire.setSplitConfig(JSON.parse(payload ? payload.value : '[]'));
            $dispatch('close-modal', {id: 'split-bill-modal'});
          ">
          {{ __('Confirm Split') }}
        </x-filament::button>
      </div>
    </x-slot>
  </x-filament::modal>

  {{-- ═══ SPLIT GROUPS PAYMENT MODAL ═══ --}}
  <x-filament::modal id="split-groups-payment" width="4xl" :close-by-clicking-away="false" :close-by-escaping="false">
    <x-slot name="heading">
      {{ __('Pay Per Group') }}
    </x-slot>

    <div x-data="{
      splitGroups: [],
      splitSellingId: null,
      payingGroupId: null,
      payingAmount: 0,
      payingMethodId: null,
      formatPrice(val) { return new Intl.NumberFormat('id-ID', {style:'currency',currency:'IDR',minimumFractionDigits:0}).format(val); },
    }"
    x-on:split-bill-created.window="
      splitGroups = event.detail.groups;
      splitSellingId = event.detail.sellingId;
      payingMethodId = paymentMethods.length ? paymentMethods[0].id : null;
      $dispatch('open-modal', {id: 'split-groups-payment'});
    "
    x-on:split-group-paid.window="
      const g = splitGroups.find(g => g.id === event.detail.splitGroupId);
      if (g) { g.status = 'paid'; g.remaining = 0; g.paid_total = g.total; }
      if (event.detail.allPaid) {
        $dispatch('close-modal', {id: 'split-groups-payment'});
        $dispatch('open-modal', {id: 'success-modal'});
        if (typeof document.getElementById('changes') !== 'undefined') {
          document.getElementById('changes').innerHTML = formatPrice(0);
        }
      }
    ">
      <div class="space-y-3">
        <template x-for="(group, gIdx) in splitGroups" :key="group.id">
          <div class="rounded-xl border p-4 transition-all"
            :class="group.status === 'paid' ? 'border-green-300 bg-green-50 dark:border-green-800 dark:bg-green-900/20' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800'">
            <div class="mb-2 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-gray-800 dark:text-white" x-text="group.label"></span>
                <template x-if="group.status === 'paid'">
                  <span class="rounded-full bg-green-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ __('PAID') }}</span>
                </template>
              </div>
              <span class="text-sm font-bold" :class="group.status === 'paid' ? 'text-green-600' : 'text-primary-600'" x-text="formatPrice(group.total)"></span>
            </div>
            <div class="mb-2 space-y-1">
              <template x-for="item in group.items" :key="item.name">
                <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400">
                  <span x-text="item.qty + 'x ' + item.name"></span>
                  <span x-text="formatPrice(item.price)"></span>
                </div>
              </template>
            </div>
            <template x-if="group.status !== 'paid'">
              <div class="flex items-center gap-2 border-t border-gray-100 pt-2 dark:border-gray-700">
                <select x-model="payingMethodId"
                  class="flex-1 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-800 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                  <template x-for="pm in paymentMethods" :key="pm.id">
                    <option :value="pm.id" x-text="pm.name"></option>
                  </template>
                </select>
                <button type="button"
                  class="rounded-lg bg-primary-600 px-4 py-1.5 text-xs font-bold text-white hover:bg-primary-700"
                  x-on:click="$wire.call('paySplitGroup', group.id, payingMethodId, group.total)">
                  {{ __('Pay') }} <span x-text="formatPrice(group.total)"></span>
                </button>
              </div>
            </template>
          </div>
        </template>
      </div>
    </div>
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
          class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
          placeholder="{{ __('Member name') }}" />
        @error('newMemberName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Phone / Contact') }}</label>
        <input type="text" wire:model="newMemberPhone"
          class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
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
    <x-slot name="heading">
      <p>{{ __('Choose the table') }}</p>
    </x-slot>
    <div class="grid grid-cols-3 gap-3 md:grid-cols-4">
      @foreach ($tableOption as $table)
        <div x-on:click="$wire.cartDetail['table_id'] = {{ $table->id }};"
          class="flex flex-col items-center justify-center rounded-xl border-2 px-4 py-3 text-sm font-semibold transition-all hover:scale-105 dark:text-white"
          :class="$wire.cartDetail['table_id'] == {{ $table->id }} ? 'border-primary-600 bg-primary-600 text-white' : ({{ $table->is_open ? 'true' : 'false' }} ? 'border-danger-400 bg-danger-50 text-danger-700 dark:bg-danger-900/30 dark:text-danger-300' : 'border-primary-500 dark:bg-gray-900')">
          <span x-text="{{ $table->number }}">{{ $table->number }}</span>
          @if($table->zone || $table->capacity)
            <span class="text-[10px] font-normal opacity-75">
              {{ $table->zone }}{{ $table->zone && $table->capacity ? ' · ' : '' }}{{ $table->capacity ? $table->capacity.' pax' : '' }}
            </span>
          @endif
        </div>
      @endforeach
    </div>
    <x-slot name="footer">
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
