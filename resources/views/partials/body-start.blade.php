@php
  // ─── Compute expired status once, share with body-end ───
  $_isExpired = false;
  $_wallpaper = null;
  $_user = auth()->user();

  if ($_user && $_user->tenant_id) {
    $blockedSub = \App\Models\Subscription::where('tenant_id', $_user->tenant_id)
      ->whereIn('status', ['expired', 'past_due'])
      ->latest()
      ->first();

    if ($blockedSub) {
      $_isExpired = true;
    } else {
      $subscription = \App\Models\Subscription::where('tenant_id', $_user->tenant_id)
        ->whereIn('status', ['trialing', 'active'])
        ->latest()
        ->first();

      if ($subscription) {
        if ($subscription->status === 'trialing' && $subscription->trial_ends_at && $subscription->trial_ends_at->isPast()) {
          $_isExpired = true;
        } elseif ($subscription->status === 'active' && $subscription->ends_at && $subscription->ends_at->isPast()) {
          $_isExpired = true;
        }
      } else {
        $hasAny = \App\Models\Subscription::where('tenant_id', $_user->tenant_id)->exists();
        if (!$hasAny) {
          $_isExpired = true;
        }
      }
    }
  }

  // Share with body-end to avoid double query
  app()->instance('_expiry_status', ['isExpired' => $_isExpired, 'plans' => []]);

  if (!request()->is('admin*') && !$_isExpired) {
    $_about = \App\Models\Tenants\About::first();
    if ($_about?->photo) {
      try {
        $_uploadDisk = config('filesystems.upload_disk');
        $_wallpaper = \App\Models\Tenants\UploadedFile::urlFromPath($_about->photo, $_uploadDisk);
      } catch (\Throwable) {}
    }
  }
@endphp

@if($_isExpired && request()->is('member*') && !request()->is('member/subscription*'))
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

@if($_wallpaper)
<style>
  .fi-main {
    position: relative;
  }
  .fi-main::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image: url('{{ $_wallpaper }}');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    opacity: 0.06;
    pointer-events: none;
    z-index: 0;
    /* GPU optimization: promote to own layer */
    transform: translateZ(0);
    will-change: opacity;
  }
  .dark .fi-main::before {
    opacity: 0.04;
    filter: brightness(0.5);
  }
</style>
@endif

{{-- Demo donation banner --}}
@if(app()->environment('demo'))
  @include('donation-banner', ['link' => 'https://trakteer.id/sheenazien8/tip'])
@endif
