<div class="max-w-full">
  <div class="text-center space-y-2">
    <h1 class="text-3xl font-semibold">Laporan kasir</h1>
    <h3 class="text-xl">{{ $header['shop_name'] }}</h3>
  </div>
  <p class="mb-4">{{ __('Period') }}: <b>{{ $header['start_date'] }} - {{ $header['end_date'] }}</b></p>
  <div class="space-y-4">
    @foreach($reports as $key => $report)
      <div class="overflow-x-auto">
        <x-table class="w-full text-sm">
          <x-table-header>
            <x-table-row>
              <x-table-header-cell colspan="3">{{ __('Cashier') }} : {{ $report['user'] }}</x-table-header-cell>
              <x-table-header-cell colspan="2"># {{ $report['number'] }}</x-table-header-cell>
              <x-table-header-cell colspan="2">{{ __('Date') }} : {{ $report['created_at'] }}</x-table-header-cell>
            </x-table-row>
            <x-table-row>
              <x-table-header-cell>{{ __('Items') }}</x-table-header-cell>
              <x-table-header-cell class="whitespace-nowrap text-right">{{ __('Price') }}</x-table-header-cell>
              <x-table-header-cell class="whitespace-nowrap text-right">{{ __('Cost') }}</x-table-header-cell>
              <x-table-header-cell class="whitespace-nowrap text-right">{{ __('Discount') }}</x-table-header-cell>
              <x-table-header-cell class="whitespace-nowrap text-right">{{ __('Total Harga') }}</x-table-header-cell>
              <x-table-header-cell class="whitespace-nowrap text-right">{{ __('Total Cost') }}</x-table-header-cell>
              <x-table-header-cell class="whitespace-nowrap text-right">{{ __('Total Sesudah Discount') }}</x-table-header-cell>
            </x-table-row>
          </x-table-header>
          <tbody>
            @foreach($report['transaction']['items'] as $item)
              <x-table-row>
                <x-table-cell>{{ $item['product'] }}</x-table-cell>
                <x-table-cell class="number whitespace-nowrap">{{ $item['product_price'] }} x {{ $item['quantity'] }}</x-table-cell>
                <x-table-cell class="number whitespace-nowrap">{{ $item['product_cost'] }} x {{ $item['quantity'] }}</x-table-cell>
                <x-table-cell class="number whitespace-nowrap">({{ $item['discount_price'] }})</x-table-cell>
                <x-table-cell class="number whitespace-nowrap">{{ $item['price'] }}</x-table-cell>
                <x-table-cell class="number whitespace-nowrap">{{ $item['cost'] }}</x-table-cell>
                <x-table-cell class="number whitespace-nowrap">{{ $item['total_after_discount'] }}</x-table-cell>
              </x-table-row>
            @endforeach
            <x-table-row>
              <x-table-cell colspan="3"><b>{{ __('Sub Total') }}</b></x-table-cell>
              <x-table-cell class="number whitespace-nowrap"><b>({{ $report['total']['discount'] }})</b></x-table-cell>
              <x-table-cell class="number whitespace-nowrap"><b>{{ $report['total']['gross_selling'] }}</b></x-table-cell>
              <x-table-cell class="number whitespace-nowrap"><b>{{ $report['total']['cost'] }}</b></x-table-cell>
              <x-table-cell class="number whitespace-nowrap"><b>{{ $report['total']['net_selling'] }}</b></x-table-cell>
            </x-table-row>
            <x-table-row>
              <x-table-cell colspan="6"><b>{{ __('Discount Penjualan') }}</b></x-table-cell>
              <x-table-cell class="number whitespace-nowrap"><b>({{ $report['total']['discount_selling'] }})</b></x-table-cell>
            </x-table-row>
            <x-table-row>
              <x-table-cell colspan="6"><b>Total</b></x-table-cell>
              <x-table-cell class="number whitespace-nowrap"><b>{{ $report['total']['grand_total'] }}</b></x-table-cell>
            </x-table-row>
          </tbody>
        </x-table>
      </div>
    @endforeach
  </div>

  <div class="overflow-x-auto mt-4">
    <x-table class="w-full text-sm">
      <x-table-header>
        <x-table-row>
          <x-table-header-cell colspan="8" class="text-center">{{ __('Grand Total') }}</x-table-header-cell>
        </x-table-row>
        <x-table-row>
          <x-table-header-cell class="whitespace-nowrap">{{ __('Cost') }}</x-table-header-cell>
          <x-table-header-cell class="whitespace-nowrap">{{ __('Penjualan') }}</x-table-header-cell>
          <x-table-header-cell class="whitespace-nowrap">{{ __('Discount per Penjualan') }}</x-table-header-cell>
          <x-table-header-cell class="whitespace-nowrap">{{ __('Discount per Item') }}</x-table-header-cell>
          <x-table-header-cell class="whitespace-nowrap">{{ __('Penjualan Setelah Discount') }}</x-table-header-cell>
          <x-table-header-cell class="whitespace-nowrap">{{ __('Keuntungan Kotor') }}</x-table-header-cell>
          <x-table-header-cell class="whitespace-nowrap">{{ __('Keuntungan Bersih Sebelum Diskon Penjualan') }}</x-table-header-cell>
          <x-table-header-cell class="whitespace-nowrap">{{ __('Keuntungan Bersih Setelah Diskon Penjualan') }}</x-table-header-cell>
        </x-table-row>
      </x-table-header>
      <tbody>
        <x-table-row>
          <x-table-cell class="number whitespace-nowrap"><b>{{ $footer['total_cost'] }}</b></x-table-cell>
          <x-table-cell class="number whitespace-nowrap"><b>{{ $footer['total_gross'] }}</b></x-table-cell>
          <x-table-cell class="number whitespace-nowrap"><b>{{ $footer['total_discount'] }}</b></x-table-cell>
          <x-table-cell class="number whitespace-nowrap"><b>{{ $footer['total_discount_per_item'] }}</b></x-table-cell>
          <x-table-cell class="number whitespace-nowrap"><b>{{ $footer['total_net'] }}</b></x-table-cell>
          <x-table-cell class="number whitespace-nowrap"><b>{{ $footer['total_gross_profit'] }}</b></x-table-cell>
          <x-table-cell class="number whitespace-nowrap"><b>{{ $footer['total_net_profit_before_discount_selling'] }}</b></x-table-cell>
          <x-table-cell class="number whitespace-nowrap"><b>{{ $footer['total_net_profit_after_discount_selling'] }}</b></x-table-cell>
        </x-table-row>
      </tbody>
    </x-table>
  </div>
</div>
