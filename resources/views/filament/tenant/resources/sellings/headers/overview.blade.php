@php
  use App\Features\TotalRevenueInSellingTable;
  use App\Models\Tenants\Profile;
  use App\Models\Tenants\Selling;
  use App\Models\Tenants\Setting;
  use Illuminate\Support\Carbon;
  use Illuminate\Support\Facades\DB;
  use Illuminate\Support\Number;
@endphp
@feature(TotalRevenueInSellingTable::class)
@php
  $profile = Profile::get();
  $timezone = $profile->timezone ?? 'UTC';
  $startDate = request()->input('tableFilters.date.start_date');
  $endDate = request()->input('tableFilters.date.end_date');

  if ($startDate && $endDate) {
      $start = Carbon::parse($startDate, $timezone)->startOfDay()->setTimezone('UTC');
      $end = Carbon::parse($endDate, $timezone)->endOfDay()->setTimezone('UTC');
  } else {
      $now = now($timezone);
      $start = $now->copy()->startOfDay()->setTimezone('UTC');
      $end = $now->copy()->endOfDay()->setTimezone('UTC');
  }

  $revenue = Selling::query()
      ->select(
          DB::raw('COALESCE(SUM(total_price), 0) as total_price'),
          DB::raw('COALESCE(SUM(tax_price), 0) as tax_price'),
          DB::raw('COALESCE(SUM(total_discount_per_item), 0) as total_discount_per_item'),
          DB::raw('COALESCE(SUM(discount_price), 0) as discount_price'),
          DB::raw('COALESCE(SUM(total_cost), 0) as total_cost'),
      )
      ->isPaid()
      ->whereBetween('created_at', [$start, $end])
      ->first();

  $grossProfit = $revenue->total_price - $revenue->tax_price - $revenue->total_discount_per_item - $revenue->discount_price;
  $totalRevenue = $grossProfit - $revenue->total_cost;
@endphp
<div class="p-3">
  <p class="text-sm font-medium text-gray-500 dark:text-gray-400">@lang('Total revenue')</p>
  <p class="text-xl font-semibold">{{ Number::currency($totalRevenue, Setting::get('currency', 'IDR')) }}</p>
</div>
@endfeature
