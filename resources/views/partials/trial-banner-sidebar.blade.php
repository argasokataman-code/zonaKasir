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
      $daysRemaining = (int) max(0, now()->diffInDays($subscription->trial_ends_at, false));
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

  <div class="px-6 py-2 {{ $bg }} border-b flex items-center justify-between gap-4">
    <div class="flex items-center gap-2">
      <x-heroicon-o-clock class="h-4 w-4 shrink-0" />
      <span class="text-sm font-medium">Trial</span>
      <span class="text-sm opacity-75">&middot;</span>
      <span class="text-sm">{{ $daysRemaining }} hari</span>
      <span class="text-sm opacity-75">&middot;</span>
      <a href="{{ \App\Filament\Tenant\Pages\ManageSubscription::getUrl() }}"
         class="text-sm font-semibold underline hover:no-underline">
        Upgrade
      </a>
    </div>
  </div>
@endif
