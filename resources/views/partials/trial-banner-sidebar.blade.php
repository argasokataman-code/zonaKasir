@php
  $user = auth()->user();
  $isTrial = false;
  $daysRemaining = null;

  if ($user) {
    $subscription = \App\Models\Subscription::where('tenant_id', $user->tenant_id)
      ->where('status', 'trialing')
      ->latest()
      ->first();

    if ($subscription && $subscription->trial_ends_at) {
      $isTrial = true;
      $daysRemaining = max(0, now()->diffInDays($subscription->trial_ends_at, false));
    }
  }
@endphp

@if($isTrial && $daysRemaining !== null)
  @php
    if ($daysRemaining <= 2) {
      $bgClass = 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800';
      $textClass = 'text-red-800 dark:text-red-200';
      $subTextClass = 'text-red-600 dark:text-red-400';
      $btnClass = 'text-red-700 bg-red-100 hover:bg-red-200 dark:text-red-200 dark:bg-red-800 dark:hover:bg-red-700';
    } elseif ($daysRemaining <= 5) {
      $bgClass = 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800';
      $textClass = 'text-yellow-800 dark:text-yellow-200';
      $subTextClass = 'text-yellow-600 dark:text-yellow-400';
      $btnClass = 'text-yellow-700 bg-yellow-100 hover:bg-yellow-200 dark:text-yellow-200 dark:bg-yellow-800 dark:hover:bg-yellow-700';
    } else {
      $bgClass = 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800';
      $textClass = 'text-blue-800 dark:text-blue-200';
      $subTextClass = 'text-blue-600 dark:text-blue-400';
      $btnClass = 'text-blue-700 bg-blue-100 hover:bg-blue-200 dark:text-blue-200 dark:bg-blue-800 dark:hover:bg-blue-700';
    }
  @endphp

  <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
    <div class="flex items-center gap-2 p-2 rounded-lg {{ $bgClass }}">
      <div class="flex-1 min-w-0">
        <p class="text-xs font-medium {{ $textClass }} truncate">
          @if($daysRemaining <= 0)
            {{ __('Trial expired!') }}
          @elseif($daysRemaining == 1)
            {{ __('Trial ends tomorrow!') }}
          @else
            {{ __('Trial :days days left', ['days' => $daysRemaining]) }}
          @endif
        </p>
      </div>
      <a href="{{ \App\Filament\Tenant\Pages\ManageSubscription::getUrl() }}"
         class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded {{ $btnClass }}">
        {{ $daysRemaining <= 0 ? __('Subscribe') : __('Plans') }}
      </a>
    </div>
  </div>
@endif
