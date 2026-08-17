<div class="max-w-full">
  <div class="text-center space-y-2">
    <h1 class="text-3xl font-semibold">{{ __('Sales per Category') }}</h1>
    <h3 class="text-xl">{{ $header['shop_name'] }}</h3>
  </div>
  <p class="mb-4">{{ __('Period') }}: <b>{{ $header['start_date'] }} - {{ $header['end_date'] }}</b></p>

  <div class="overflow-x-auto">
    <x-table class="w-full text-sm">
      <x-table-header>
        <x-table-header-cell>{{ __('Category') }}</x-table-header-cell>
        <x-table-header-cell class="whitespace-nowrap text-right">{{ __('Qty') }}</x-table-header-cell>
        <x-table-header-cell class="whitespace-nowrap text-right">{{ __('Revenue') }}</x-table-header-cell>
        <x-table-header-cell class="whitespace-nowrap text-right">{{ __('Discount') }}</x-table-header-cell>
        <x-table-header-cell class="whitespace-nowrap text-right">{{ __('After Discount') }}</x-table-header-cell>
        <x-table-header-cell class="whitespace-nowrap text-right">{{ __('Cost') }}</x-table-header-cell>
        <x-table-header-cell class="whitespace-nowrap text-right">{{ __('Profit') }}</x-table-header-cell>
      </x-table-header>
      <tbody>
        @foreach($reports as $key => $report)
          <x-table-row>
            <x-table-cell class="whitespace-nowrap">{{ $report['category_name'] }}</x-table-cell>
            <x-table-cell :number="true" class="whitespace-nowrap">{{ $report['total_qty'] }}</x-table-cell>
            <x-table-cell :number="true" class="whitespace-nowrap">{{ $report['total_revenue'] }}</x-table-cell>
            <x-table-cell :number="true" class="whitespace-nowrap">{{ $report['total_discount'] }}</x-table-cell>
            <x-table-cell :number="true" class="whitespace-nowrap">{{ $report['total_after_discount'] }}</x-table-cell>
            <x-table-cell :number="true" class="whitespace-nowrap">{{ $report['total_cost'] }}</x-table-cell>
            <x-table-cell :number="true" class="whitespace-nowrap">{{ $report['total_profit'] }}</x-table-cell>
          </x-table-row>
        @endforeach
        <x-table-row>
          <x-table-cell><b>{{ __('Total') }}</b></x-table-cell>
          <x-table-cell :number="true" class="whitespace-nowrap">{{ $footer['total_qty'] }}</x-table-cell>
          <x-table-cell :number="true" class="whitespace-nowrap">{{ $footer['total_revenue'] }}</x-table-cell>
          <x-table-cell :number="true" class="whitespace-nowrap">{{ $footer['total_discount'] }}</x-table-cell>
          <x-table-cell :number="true" class="whitespace-nowrap">{{ $footer['total_after_discount'] }}</x-table-cell>
          <x-table-cell :number="true" class="whitespace-nowrap">{{ $footer['total_cost'] }}</x-table-cell>
          <x-table-cell :number="true" class="whitespace-nowrap">{{ $footer['total_profit'] }}</x-table-cell>
        </x-table-row>
      </tbody>
    </x-table>
  </div>
</div>