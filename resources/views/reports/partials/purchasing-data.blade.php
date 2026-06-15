<div class="max-w-full">
  <div class="text-center space-y-2">
    <h1 class="text-3xl font-semibold">{{ __('Purchasing Report') }}</h1>
    <h3 class="text-xl">{{ $header['shop_name'] }}</h3>
  </div>
  <p class="mb-4">{{ __('Period') }}: <b>{{ $header['start_date'] }} - {{ $header['end_date'] }}</b></p>
  <div class="space-y-4">
    @foreach($reports as $key => $report)
      <div class="overflow-x-auto">
        <x-table class="w-full text-sm">
          <x-table-header>
            <x-table-row>
              <x-table-header-cell colspan="6">{{ __('Supplier') }}:</x-table-header-cell>
              <x-table-header-cell>{{ $report['supplier'] }}</x-table-header-cell>
            </x-table-row>
            <x-table-row>
              <x-table-header-cell colspan="6">{{ __('Date') }}:</x-table-header-cell>
              <x-table-header-cell>{{ $report['date'] }}</x-table-header-cell>
            </x-table-row>
          </x-table-header>
          <x-table-header>
            <x-table-header-cell class="whitespace-nowrap">{{ __('Product name') }}</x-table-header-cell>
            <x-table-header-cell class="whitespace-nowrap">{{ __('Unit') }}</x-table-header-cell>
            <x-table-header-cell class="whitespace-nowrap text-right">{{ __('Stock amount') }}</x-table-header-cell>
            <x-table-header-cell class="whitespace-nowrap text-right">{{ __('Cost per stock') }}</x-table-header-cell>
            <x-table-header-cell class="whitespace-nowrap text-right">{{ __('Total cost') }}</x-table-header-cell>
            <x-table-header-cell class="whitespace-nowrap text-right">{{ __('Price per stock') }}</x-table-header-cell>
            <x-table-header-cell class="whitespace-nowrap text-right">{{ __('Total price') }}</x-table-header-cell>
          </x-table-header>
          <tbody>
            @foreach ($report['stocks'] as $key => $stock)
              <x-table-row>
                <x-table-cell>{{ $stock['product_name'] }}</x-table-cell>
                <x-table-cell class="whitespace-nowrap">{{ $stock['product_unit'] }}</x-table-cell>
                <x-table-cell class="number whitespace-nowrap">{{ $stock['init_stock'] }}</x-table-cell>
                <x-table-cell class="number whitespace-nowrap">{{ $stock['initial_price'] }}</x-table-cell>
                <x-table-cell class="number whitespace-nowrap">{{ $stock['total_initial_price'] }}</x-table-cell>
                <x-table-cell class="number whitespace-nowrap">{{ $stock['selling_price'] }}</x-table-cell>
                <x-table-cell class="number whitespace-nowrap">{{ $stock['total_selling_price'] }}</x-table-cell>
              </x-table-row>
            @endforeach
            <x-table-row class="border-t border-gray-200 dark:border-gray-600">
              <x-table-cell colspan="4"><b>{{ __('Subtotal') }}</b></x-table-cell>
              <x-table-cell colspan="2" class="number whitespace-nowrap"><b>{{ $report['subtotal_total_initial_price'] }}</b></x-table-cell>
              <x-table-cell class="number whitespace-nowrap"><b>{{ $report['subtotal_total_selling_price'] }}</b></x-table-cell>
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
          <x-table-header-cell colspan="2" class="text-center">{{ __('Grand Total') }}</x-table-header-cell>
        </x-table-row>
        <x-table-row>
          <x-table-header-cell class="whitespace-nowrap">{{ __('Cost') }}</x-table-header-cell>
          <x-table-header-cell class="number whitespace-nowrap text-right"><b>{{ $footer['grand_total_initial_price'] }}</b></x-table-header-cell>
        </x-table-row>
        <x-table-row>
          <x-table-header-cell class="whitespace-nowrap">{{ __('Selling price') }}</x-table-header-cell>
          <x-table-header-cell class="number whitespace-nowrap text-right"><b>{{ $footer['grand_total_selling_price'] }}</b></x-table-header-cell>
        </x-table-row>
      </x-table-header>
    </x-table>
  </div>
</div>
