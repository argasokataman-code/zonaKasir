@php
  $license = null;
  try {
    $license = app(\App\Services\LicenseService::class)->getActiveLicense(tenant('id'));
  } catch (\Throwable) {}
@endphp

@if($license)
@php
  $days = $license->daysLeft();
  if ($days > 30) {
    $barColor = 'bg-green-500';
    $textColor = 'text-green-600 dark:text-green-400';
    $bgColor = 'bg-green-50 dark:bg-green-500/10';
  } elseif ($days > 7) {
    $barColor = 'bg-yellow-500';
    $textColor = 'text-yellow-600 dark:text-yellow-400';
    $bgColor = 'bg-yellow-50 dark:bg-yellow-500/10';
  } else {
    $barColor = 'bg-red-500';
    $textColor = 'text-red-600 dark:text-red-400';
    $bgColor = 'bg-red-50 dark:bg-red-500/10';
  }
@endphp
<div class="px-3 py-2">
  <div class="rounded-xl {{ $bgColor }} p-3 text-sm">
    <div class="flex items-center justify-between mb-1.5">
      <span class="font-semibold text-gray-900 dark:text-gray-200">
        {{ ucfirst($license->plan) }}
      </span>
      <span class="{{ $textColor }} text-xs font-bold">
        {{ $days }}d left
      </span>
    </div>
    <div class="h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
      @php $pct = min(100, max(0, ($days / 365) * 100)); @endphp
      <div class="h-full {{ $barColor }} rounded-full transition-all" style="width: {{ $pct }}%"></div>
    </div>
    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
      Expires: {{ $license->expires_at?->format('d M Y') }}
    </p>
  </div>
</div>
@endif
