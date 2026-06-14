@php
  $user = auth()->user();
  $isExpired = false;

  if ($user && $user->tenant_id) {
      $blockedSub = \App\Models\Subscription::where('tenant_id', $user->tenant_id)
          ->whereIn('status', ['expired', 'past_due'])
          ->latest()
          ->first();

      if ($blockedSub) {
          $isExpired = true;
      } else {
          $subscription = \App\Models\Subscription::where('tenant_id', $user->tenant_id)
              ->whereIn('status', ['trialing', 'active'])
              ->latest()
              ->first();

          if ($subscription) {
              if ($subscription->status === 'trialing' && $subscription->trial_ends_at && $subscription->trial_ends_at->isPast()) {
                  $isExpired = true;
              } elseif ($subscription->status === 'active' && $subscription->ends_at && $subscription->ends_at->isPast()) {
                  $isExpired = true;
              }
          } else {
              $hasAny = \App\Models\Subscription::where('tenant_id', $user->tenant_id)->exists();
              if (!$hasAny) {
                  $isExpired = true;
              }
          }
      }
  }
@endphp

@if($isExpired && request()->is('member*') && !request()->is('member/subscription*'))
<style>
  .fi-layout,
  .fi-sidebar,
  .fi-main,
  .fi-topbar,
  [class*="fi-layout"] {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
    position: absolute !important;
    width: 0 !important;
    height: 0 !important;
    overflow: hidden !important;
  }
</style>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.fi-layout, .fi-sidebar, .fi-main, .fi-topbar').forEach(function(el) {
      el.remove();
    });
  });
</script>
@endif
