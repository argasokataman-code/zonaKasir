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
      $bg = 'bg-red-50 border-red-200 text-red-700';
    } elseif ($daysRemaining <= 5) {
      $bg = 'bg-yellow-50 border-yellow-200 text-yellow-700';
    } else {
      $bg = 'bg-blue-50 border-blue-200 text-blue-700';
    }
  @endphp

  <div class="flex items-center justify-between gap-2 px-4 py-2 text-sm {{ $bg }} border-b">
    <span class="font-medium truncate">
      @if($daysRemaining <= 0)
        {{ __('Trial expired!') }}
      @elseif($daysRemaining == 1)
        {{ __('Trial ends tomorrow!') }}
      @else
        {{ __(':days days trial left', ['days' => $daysRemaining]) }}
      @endif
    </span>
    <a href="{{ \App\Filament\Tenant\Pages\ManageSubscription::getUrl() }}"
       class="font-semibold underline whitespace-nowrap hover:no-underline">
      {{ $daysRemaining <= 0 ? __('Subscribe') : __('Plans') }}
    </a>
  </div>
@endif
