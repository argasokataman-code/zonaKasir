@php use App\Features\Discount; @endphp
@forelse($cartItems as $item)
  <div wire:key="cart-item-{{ $item->id }}-{{ $item->qty }}" class="mb-2 rounded-lg border bg-white px-3 py-2 dark:border-gray-900 dark:bg-gray-900">
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
          wire:click.stop="deleteCart({{ $item->product_id }})"
          wire:loading.class="opacity-50"
          wire:target="deleteCart({{ $item->product_id }})"
          type="button">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
        </button>
        @if($item->product && $item->product->priceUnits->isNotEmpty())
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
  <div class="flex h-32 items-center justify-center rounded-lg border bg-white dark:border-gray-900 dark:bg-gray-900">
    <svg class="hidden h-8 w-8 text-gray-900 dark:text-white lg:block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
    </svg>
    <p class="text-lg text-gray-600 dark:text-white lg:text-2xl">{{ __('No item') }}</p>
  </div>
@endforelse
