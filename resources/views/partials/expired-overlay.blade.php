@php
  $user = auth()->user();
  $isExpired = false;

  if ($user && $user->tenant_id) {
      $subscription = \App\Models\Subscription::where('tenant_id', $user->tenant_id)
          ->whereIn('status', ['trialing', 'active'])
          ->latest()
          ->first();

      // Check if expired (trial past or active past)
      if ($subscription) {
          if ($subscription->status === 'trialing' && $subscription->trial_ends_at && $subscription->trial_ends_at->isPast()) {
              $isExpired = true;
          } elseif ($subscription->status === 'active' && $subscription->ends_at && $subscription->ends_at->isPast()) {
              $isExpired = true;
          }
      } else {
          // No subscription at all
          $hasAny = \App\Models\Subscription::where('tenant_id', $user->tenant_id)->exists();
          if (!$hasAny) {
              $isExpired = true;
          }
      }

      $plans = $isExpired ? \App\Models\Plan::where('is_active', true)->orderBy('price_monthly')->get()->toArray() : [];
  }
@endphp

@if($isExpired)
<div
  x-data="expiredOverlay()"
  x-init="init()"
  class="fixed inset-0 z-[9999] flex items-center justify-center overflow-hidden"
  style="display: none;"
>
  {{-- Backdrop blur --}}
  <div class="absolute inset-0 bg-white/70 backdrop-blur-md"></div>

  {{-- Overlay content --}}
  <div class="relative z-10 w-full max-w-6xl max-h-full flex flex-col items-center justify-center px-6 py-8 overflow-hidden">
    {{-- Header --}}
    <div class="text-center mb-6 shrink-0">
      <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-red-100 mb-3">
        <svg class="w-7 h-7 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
      </div>
      <h2 class="text-xl font-bold text-gray-900 mb-1">Masa Trial Telah Habis</h2>
      <p class="text-sm text-gray-500 mb-3">Pilih paket di bawah untuk melanjutkan menggunakan aplikasi</p>
      <a href="{{ config('app.url') }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600 hover:text-gray-900 transition-colors">
        <span>Kunjungi Website</span>
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
      </a>
    </div>

    {{-- Plans grid --}}
    <div class="w-full min-h-0 overflow-y-auto">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($plans as $plan)
        <div
          x-data="{ open: false, showBilling: false }"
          class="bg-white rounded-[8px] shadow-lg flex flex-col relative border border-gray-200"
        >
          @if(($plan['is_popular'] ?? false) && $plan['price_monthly'] > 0)
          <div class="absolute top-0 right-0 bg-gray-900 text-white text-[8px] font-mono font-bold uppercase tracking-widest px-3 py-1 rounded-bl-[4px] rounded-tr-[7px]">
            Popular
          </div>
          @endif

          <div class="p-5 flex flex-col h-full">
            <div>
              <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">
                {{ $plan['max_stores'] > 10 ? 'Enterprise' : ($plan['max_stores'] > 1 ? 'Bisnis' : 'Pemula') }}
              </span>
              <h3 class="font-sans font-bold text-base text-gray-900">{{ $plan['name'] }}</h3>
            </div>

            <div class="py-3 my-3 border-y border-gray-100">
              @if(($plan['is_on_premise'] ?? false))
                <span class="font-mono text-xl font-black text-gray-900">Custom</span>
                <span class="text-[9px] text-gray-500 font-bold block uppercase tracking-wider mt-0.5">Self-Hosted</span>
              @elseif(($plan['price_monthly'] ?? 0) === 0)
                <span class="font-mono text-2xl font-black text-gray-900">Gratis</span>
                <span class="text-[9px] text-gray-500 font-bold block uppercase tracking-wider mt-0.5">Selamanya</span>
              @else
                <span class="font-mono text-2xl font-black text-gray-900">Rp {{ number_format($plan['price_monthly'], 0, ',', '.') }}</span>
                <span class="text-[9px] text-gray-500 font-bold block uppercase tracking-wider mt-0.5">Per Bulan</span>
                @if($plan['price_yearly'])
                <span class="text-[9px] text-gray-400 block mt-0.5">Rp {{ number_format($plan['price_yearly'], 0, ',', '.') }}/tahun</span>
                @endif
              @endif
            </div>

            <div class="text-[10px] text-gray-400 font-semibold mb-2">
              {{ $plan['max_stores'] }} outlet / {{ $plan['max_users'] }} user
            </div>

            @if(!empty($plan['features']))
            <button
              type="button"
              x-on:click="open = !open"
              class="w-full flex items-center justify-between text-[10px] font-bold text-gray-900 uppercase tracking-wider py-1.5 border-t border-gray-100 cursor-pointer hover:text-gray-600 transition-colors"
            >
              <span>Fitur ({{ count($plan['features']) }})</span>
              <svg class="w-3 h-3 transition-transform duration-200" x-bind:class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-collapse x-cloak class="overflow-hidden">
              <ul class="space-y-1.5 text-[11px] text-gray-600 font-medium py-2">
                @foreach($plan['features'] as $key => $label)
                <li class="flex items-start gap-2">
                  <span class="w-3.5 h-3.5 rounded-full bg-emerald-100 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-2 h-2 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                  </span>
                  <span>{{ is_string($label) ? $label : (is_string($key) ? $key : $label) }}</span>
                </li>
                @endforeach
              </ul>
            </div>
            @endif

            <div class="mt-auto pt-3 border-t border-gray-100 relative">
              @if(($plan['price_monthly'] ?? 0) === 0)
                <span class="block w-full text-center text-[10px] font-bold uppercase tracking-widest py-2 bg-gray-100 text-gray-500 rounded-[4px]">Gratis</span>
              @else
                <button
                  type="button"
                  x-on:click="showBilling = !showBilling"
                  class="block w-full text-center text-[10px] font-bold uppercase tracking-widest py-2 bg-gray-900 text-white rounded-[4px] hover:bg-gray-700 transition-colors cursor-pointer"
                >
                  Pilih Paket
                </button>
                <div
                  x-show="showBilling"
                  x-cloak
                  x-on:click.away="showBilling = false"
                  class="absolute bottom-full left-0 right-0 mb-1 bg-white border border-gray-200 rounded-[6px] shadow-lg overflow-hidden z-10"
                >
                  <a
                    href="{{ url('/member/subscription?plan_id=' . $plan['id'] . '&billing=monthly') }}"
                    class="w-full text-left px-3 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer flex items-center justify-between"
                  >
                    <span>Bulanan</span>
                    <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                  </a>
                  @if(($plan['price_yearly'] ?? 0) > 0)
                  <a
                    href="{{ url('/member/subscription?plan_id=' . $plan['id'] . '&billing=yearly') }}"
                    class="w-full text-left px-3 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 border-t border-gray-100 transition-colors cursor-pointer flex items-center justify-between"
                  >
                    <span>Tahunan</span>
                    <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                  </a>
                  @endif
                </div>
              @endif
            </div>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </div>
</div>

<script>
function expiredOverlay() {
  return {
    init() {
      this.$el.style.display = 'flex';
      document.body.style.overflow = 'hidden';
      document.body.style.overflowX = 'hidden';
      document.documentElement.style.overflow = 'hidden';
      document.documentElement.style.overflowX = 'hidden';

      // Block all clicks outside overlay
      document.addEventListener('click', this.blockClicks, true);
      document.addEventListener('keydown', this.blockKeys, true);
    },
    blockClicks(e) {
      if (!e.target.closest('[x-data="expiredOverlay()"]')) {
        e.stopPropagation();
        e.preventDefault();
      }
    },
    blockKeys(e) {
      // Allow only scroll keys
      const allowed = ['ArrowDown', 'ArrowUp', 'ArrowLeft', 'ArrowRight', 'Space', 'PageDown', 'PageUp'];
      if (!allowed.includes(e.key) && !e.ctrlKey && !e.metaKey) {
        e.stopPropagation();
        e.preventDefault();
      }
    },
    destroy() {
      document.body.style.overflow = '';
      document.body.style.overflowX = '';
      document.documentElement.style.overflow = '';
      document.documentElement.style.overflowX = '';
      document.removeEventListener('click', this.blockClicks, true);
      document.removeEventListener('keydown', this.blockKeys, true);
    }
  }
}
</script>
@endif
