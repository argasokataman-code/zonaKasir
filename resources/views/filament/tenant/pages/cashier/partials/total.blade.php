@php
use function Filament\Support\format_money;
use App\Features\{SellingTax, Discount};
@endphp

@php
// Sidebar context: always show money changes (no payment method selected yet)
$showMoneyChanges = $showMoneyChanges ?? true;
@endphp

<div class="space-y-3">
  <div class="flex justify-between">
    <p>{{ __('Sub total') }}</p>
    <p class="font-bold text-primary-600"><span x-text="moneyFormat(subTotal)">{{ price_format($sub_total) }}</span></p>
  </div>
  @feature(SellingTax::class)
    <div class="flex justify-between">
      <p>{{ __('Tax') }}</p>
      <p class="font-bold text-primary-600">{{ $tax }}%</p>
    </div>
  @endfeature
  @feature(Discount::class)
    <div class="flex justify-between">
      <p>{{ __('Discount price') }}</p>
      <p class="font-bold text-primary-600">({{ price_format($this->discount_price) }})</p>
    </div>
  @endfeature
  <hr/>
  <div class="flex justify-between">
    <p class="font-bold">{{ __('Total') }}</p>
    <p class="font-bold text-primary-600" x-ref="total" data-value="{{ $total_price }}"><span x-text="moneyFormat(totalPrice)">{{ price_format($total_price) }}</span></p>
  </div>
  @if($showMoneyChanges)
    <div class="flex justify-between"
      x-show="typeof paymentMethods === 'undefined' || paymentMethods.find(p => p.id == cartDetail.payment_method_id)?.payment_type == 'cash' || !paymentMethods.find(p => p.id == cartDetail.payment_method_id)?.['payment_type']">
      <p class="font-bold">{{ __('Money changes') }}</p>
      <p class="font-bold text-primary-600" x-ref="moneyChanges"></p>
    </div>
  @endif
</div>

