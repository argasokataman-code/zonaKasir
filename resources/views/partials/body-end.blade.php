@php
  // ─── Read shared expiry from body-start (avoids double query) ──
  $_expiry = app()->has('_expiry_status') ? app('_expiry_status') : null;
  $_isExpired = $_expiry ? $_expiry['isExpired'] : false;
  $_plans = [];

  if ($_isExpired) {
    $_plans = \App\Models\Plan::where('is_active', true)->where('price_monthly', '>', 0)->orderBy('price_monthly')->get()->toArray();
  }

  // ─── Welcome modal data ──────────────────────────────────
  $_welcomeType = session('welcome_type', '');
  $_welcomeData = session('welcome_data', []);
  $_user = auth()->user();
@endphp

{{-- Expired Overlay --}}
@if($_isExpired && request()->is('member*') && !request()->is('member/subscription*'))
<div
  x-data="expiredOverlay()"
  x-init="init()"
  class="fixed inset-0 z-[9999] flex items-center justify-center"
  style="display: none;"
>
  {{-- Backdrop blur --}}
  <div class="absolute inset-0 bg-white/70 backdrop-blur-md"></div>

  {{-- Overlay content — scrollable on all devices --}}
  <div class="relative z-10 w-full h-full flex flex-col items-center px-4 py-6 sm:px-6 md:px-8 overflow-y-auto overflow-x-hidden overscroll-contain">
    {{-- Header --}}
    <div class="text-center mb-4 sm:mb-6 shrink-0">
      <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-red-100 mb-3">
        <svg class="w-7 h-7 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
      </div>
      <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-1">Masa Trial Telah Habis</h2>
      <p class="text-xs sm:text-sm text-gray-500 mb-3">Pilih paket di bawah untuk melanjutkan menggunakan aplikasi</p>
      <div class="flex items-center justify-center gap-3">
        <a href="{{ config('app.url') }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600 hover:text-gray-900 transition-colors">
          <span>Kunjungi Website</span>
          <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
        </a>
        <span class="text-gray-300">|</span>
        <form action="{{ url('/member/logout') }}" method="POST" class="inline">
          @csrf
          <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600 hover:text-red-600 transition-colors cursor-pointer">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
            <span>Keluar</span>
          </button>
        </form>
      </div>
    </div>

    {{-- Plans grid --}}
    <div class="w-full shrink-0 pb-8">
      <div class="grid gap-3 sm:gap-4 justify-items-center mx-auto"
           style="grid-template-columns: 1fr; max-width: 1200px;">
        @foreach($_plans as $plan)
        <div
          x-data="{ open: false }"
          class="bg-white rounded-[8px] shadow-lg flex flex-col relative border border-gray-200 w-full max-w-[360px]"
        >
          @if(($plan['is_popular'] ?? false) && $plan['price_monthly'] > 0)
          <div class="absolute top-0 right-0 bg-gray-900 text-white text-[8px] font-mono font-bold uppercase tracking-widest px-3 py-1 rounded-bl-[4px] rounded-tr-[7px]">
            Popular
          </div>
          @endif

          <div class="p-4 sm:p-5 flex flex-col h-full">
            <div>
              <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">
                {{ $plan['max_stores'] > 10 ? 'Enterprise' : ($plan['max_stores'] > 1 ? 'Bisnis' : 'Pemula') }}
              </span>
              <h3 class="font-sans font-bold text-sm sm:text-base text-gray-900">{{ $plan['name'] }}</h3>
            </div>

            <div class="py-3 my-3 border-y border-gray-100">
              @if(($plan['is_on_premise'] ?? false))
                <span class="font-mono text-xl font-black text-gray-900">Custom</span>
                <span class="text-[9px] text-gray-500 font-bold block uppercase tracking-wider mt-0.5">Self-Hosted</span>
              @elseif(($plan['price_monthly'] ?? 0) === 0)
                <span class="font-mono text-2xl font-black text-gray-900">Gratis</span>
                <span class="text-[9px] text-gray-500 font-bold block uppercase tracking-wider mt-0.5">Selamanya</span>
              @else
                <span class="font-mono text-xl sm:text-2xl font-black text-gray-900">Rp {{ number_format($plan['price_monthly'], 0, ',', '.') }}</span>
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

            <div class="mt-auto pt-3 border-t border-gray-100 relative" x-data="{ showBilling: false }">
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
                    target="_blank"
                    onclick="event.stopPropagation();"
                    class="w-full text-left px-3 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer flex items-center justify-between"
                  >
                    <span>Bulanan</span>
                    <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                  </a>
                  @if(($plan['price_yearly'] ?? 0) > 0)
                  <a
                    href="{{ url('/member/subscription?plan_id=' . $plan['id'] . '&billing=yearly') }}"
                    target="_blank"
                    onclick="event.stopPropagation();"
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

{{-- Welcome Modal --}}
@if($_user && $_welcomeType && !$_user->welcomed_at)
<style>
  @keyframes welcomeBounce {
    0% { transform: scale(0.3); opacity: 0; }
    50% { transform: scale(1.08); }
    70% { transform: scale(0.95); }
    100% { transform: scale(1); opacity: 1; }
  }
  @keyframes slideUp {
    from { transform: translateY(40px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
  }
  @keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
  }
  .welcome-card {
    animation: welcomeBounce 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
  }
  .welcome-step {
    animation: slideUp 0.4s ease forwards;
    opacity: 0;
  }
  .welcome-step:nth-child(1) { animation-delay: 0.3s; }
  .welcome-step:nth-child(2) { animation-delay: 0.5s; }
  .welcome-step:nth-child(3) { animation-delay: 0.7s; }
</style>

<canvas id="welcome-confetti-canvas" style="position:fixed;inset:0;z-index:9999;pointer-events:none;"></canvas>

<div
  x-data="welcomeModal()"
  x-init="init()"
  class="fixed inset-0 z-[9998] flex items-center justify-center"
  style="background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);"
>
  <div class="welcome-card max-w-lg w-full mx-4 bg-white rounded-2xl shadow-2xl overflow-hidden">
    <div class="relative px-8 pt-10 pb-6 text-center"
         style="background: linear-gradient(135deg, #FF6600, #FF8C38, #FF6600); background-size: 200% 200%; animation: gradientShift 3s ease infinite;">
      <div class="text-5xl mb-3">
        @if($_welcomeType === 'trial') 🎉 @elseif($_welcomeType === 'paid') 🏆 @endif
      </div>
      <h2 class="text-2xl font-bold text-white">
        @if($_welcomeType === 'trial') Selamat Datang! @elseif($_welcomeType === 'paid') Pembayaran Berhasil! 🎉 @endif
      </h2>
      <p class="text-white/80 mt-1 text-sm">
        @if($_welcomeType === 'trial') Akun trial kamu sudah aktif @elseif($_welcomeType === 'paid') Nikmati semua fitur premium @endif
      </p>
    </div>

    <div class="px-8 py-6 space-y-4">
      @if($_welcomeType === 'trial')
        <div class="bg-orange-50 border border-orange-200 rounded-xl px-4 py-3 flex items-center gap-3">
          <span class="text-2xl">⏳</span>
          <div class="text-sm">
            <p class="font-semibold text-orange-800">Masa Trial 7 Hari</p>
            <p class="text-orange-600">Berakhir: <strong>{{ now()->addDays(7)->format('d M Y') }}</strong></p>
          </div>
        </div>

        <div class="space-y-2">
          <p class="text-sm font-semibold text-gray-700">Langkah cepat memulai:</p>
          <div class="welcome-step flex items-start gap-3 p-3 rounded-xl bg-gray-50">
            <span class="w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-bold text-sm shrink-0">1</span>
            <div class="text-sm">
              <p class="font-semibold text-gray-800">Lengkapi Profil Toko</p>
              <p class="text-gray-500 text-xs">Atur nama, logo, alamat, dan kontak</p>
            </div>
          </div>
          <div class="welcome-step flex items-start gap-3 p-3 rounded-xl bg-gray-50">
            <span class="w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-bold text-sm shrink-0">2</span>
            <div class="text-sm">
              <p class="font-semibold text-gray-800">Tambah Produk Pertama</p>
              <p class="text-gray-500 text-xs">Mulai katalog produk kamu</p>
            </div>
          </div>
          <div class="welcome-step flex items-start gap-3 p-3 rounded-xl bg-gray-50">
            <span class="w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-bold text-sm shrink-0">3</span>
            <div class="text-sm">
              <p class="font-semibold text-gray-800">Atur Metode Pembayaran</p>
              <p class="text-gray-500 text-xs">Cash, QRIS, Transfer, dll</p>
            </div>
          </div>
        </div>

        <div class="text-center pt-2">
          <p class="text-xs text-gray-400">🔓 Upgrade kapan saja untuk fitur tanpa batas</p>
        </div>

      @elseif($_welcomeType === 'paid')
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 flex items-center gap-3">
          <span class="text-2xl">📦</span>
          <div class="text-sm">
            <p class="font-semibold text-emerald-800">{{ $_welcomeData['plan_name'] ?? 'Paket' }}</p>
            <p class="text-emerald-600">
              @if(($_welcomeData['billing'] ?? 'monthly') === 'yearly')
                Rp {{ number_format($_welcomeData['price_yearly'] ?? 0, 0, ',', '.') }}/tahun
              @else
                Rp {{ number_format($_welcomeData['price_monthly'] ?? 0, 0, ',', '.') }}/bulan
              @endif
            </p>
          </div>
        </div>

        @if(!empty($_welcomeData['features']))
        <div>
          <p class="text-sm font-semibold text-gray-700 mb-2">Fitur yang kamu dapatkan:</p>
          <div class="grid grid-cols-2 gap-2">
            @foreach($_welcomeData['features'] as $feature)
              <div class="flex items-center gap-2 text-sm text-gray-600">
                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ $feature }}</span>
              </div>
            @endforeach
          </div>
        </div>
        @endif

        <div class="text-xs text-gray-400 flex items-center gap-1 justify-center pt-1">
          <span>✅</span>
          <span>Aktif sampai: <strong>{{ $_welcomeData['ends_at'] ?? '-' }}</strong></span>
        </div>
      @endif
    </div>

    <div class="px-8 py-4 border-t border-gray-100 flex gap-3">
      <button
        type="button"
        x-on:click="dismiss()"
        class="flex-1 px-4 py-2.5 rounded-xl font-semibold text-sm transition-all duration-200 cursor-pointer"
        style="background: linear-gradient(135deg, #FF6600, #FF8C38); color: white;"
      >
        @if($_welcomeType === 'trial') Mulai Sekarang @elseif($_welcomeType === 'paid') Ke Dashboard @endif
      </button>
    </div>
  </div>
</div>

<script>
function welcomeModal() {
  return {
    init() {
      const canvas = document.getElementById('welcome-confetti-canvas');
      if (!canvas) return;
      const ctx = canvas.getContext('2d');
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;

      const colors = ['#FF6600', '#FF8C38', '#FFB347', '#FFD700', '#FF4500', '#FF6347'];
      const pieces = [];
      const count = 60;

      for (let i = 0; i < count; i++) {
        pieces.push({
          x: Math.random() * canvas.width,
          y: Math.random() * canvas.height - canvas.height,
          w: Math.random() * 10 + 5,
          h: Math.random() * 6 + 3,
          color: colors[Math.floor(Math.random() * colors.length)],
          speed: Math.random() * 3 + 2,
          rotation: Math.random() * 360,
          rotSpeed: Math.random() * 10 - 5,
          opacity: Math.random() * 0.5 + 0.5,
          swing: Math.random() * 2 - 1,
          swingSpeed: Math.random() * 0.02 + 0.01,
        });
      }

      let frame = 0;
      const animate = () => {
        frame++;
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        for (const p of pieces) {
          p.y += p.speed;
          p.x += Math.sin(frame * p.swingSpeed) * p.swing;
          p.rotation += p.rotSpeed;

          if (p.y > canvas.height + 20) {
            p.y = -20;
            p.x = Math.random() * canvas.width;
          }

          ctx.save();
          ctx.translate(p.x, p.y);
          ctx.rotate(p.rotation * Math.PI / 180);
          ctx.globalAlpha = p.opacity;
          ctx.fillStyle = p.color;
          ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
          ctx.restore();
        }

        if (frame > 120) {
          const remaining = pieces.filter(p => p.y < canvas.height + 50);
          if (remaining.length === 0) return;
        }

        requestAnimationFrame(animate);
      };
      animate();

      setTimeout(() => {
        const c = document.getElementById('welcome-confetti-canvas');
        if (c) c.style.display = 'none';
      }, 3000);
    },
    dismiss() {
      const el = this.$el.closest('[x-data]');
      if (el) el.remove();
      document.getElementById('welcome-confetti-canvas')?.remove();

      fetch('/welcome/dismiss', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });
    }
  };
}
</script>
@endif
