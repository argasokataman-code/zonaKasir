@php
  $user = auth()->user();
  $type = session('welcome_type', '');
  $data = session('welcome_data', []);
@endphp

@if($user && $type && ! $user->welcomed_at)
<style>
  @keyframes welcomeBounce {
    0% { transform: scale(0.3); opacity: 0; }
    50% { transform: scale(1.08); }
    70% { transform: scale(0.95); }
    100% { transform: scale(1); opacity: 1; }
  }
  @keyframes confettiFall {
    0% { transform: translateY(-100vh) rotate(0deg); opacity: 1; }
    100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
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
  .confetti-piece {
    position: fixed;
    width: 10px;
    height: 10px;
    top: -10px;
    z-index: 10000;
    animation: confettiFall linear forwards;
    pointer-events: none;
  }
</style>

<canvas id="welcome-confetti-canvas" style="position:fixed;inset:0;z-index:9999;pointer-events:none;"></canvas>

<div
  x-data="welcomeModal()"
  x-init="init()"
  class="fixed inset-0 z-[9998] flex items-center justify-center"
  style="background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);"
>
  <div class="welcome-card max-w-lg w-full mx-4 bg-white rounded-2xl shadow-2xl overflow-hidden">
    {{-- Header gradient --}}
    <div class="relative px-8 pt-10 pb-6 text-center"
         style="background: linear-gradient(135deg, #FF6600, #FF8C38, #FF6600); background-size: 200% 200%; animation: gradientShift 3s ease infinite;">
      <div class="text-5xl mb-3">
        @if($type === 'trial')
          🎉
        @elseif($type === 'paid')
          🏆
        @endif
      </div>
      <h2 class="text-2xl font-bold text-white">
        @if($type === 'trial')
          Selamat Datang!
        @elseif($type === 'paid')
          Pembayaran Berhasil! 🎉
        @endif
      </h2>
      <p class="text-white/80 mt-1 text-sm">
        @if($type === 'trial')
          Akun trial kamu sudah aktif
        @elseif($type === 'paid')
          Nikmati semua fitur premium
        @endif
      </p>
    </div>

    {{-- Body --}}
    <div class="px-8 py-6 space-y-4">
      @if($type === 'trial')
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
          <p class="text-xs text-gray-400">
            🔓 Upgrade kapan saja untuk fitur tanpa batas
          </p>
        </div>

      @elseif($type === 'paid')
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 flex items-center gap-3">
          <span class="text-2xl">📦</span>
          <div class="text-sm">
            <p class="font-semibold text-emerald-800">{{ $data['plan_name'] ?? 'Paket' }}</p>
            <p class="text-emerald-600">
              @if(($data['billing'] ?? 'monthly') === 'yearly')
                Rp {{ number_format($data['price_yearly'] ?? 0, 0, ',', '.') }}/tahun
              @else
                Rp {{ number_format($data['price_monthly'] ?? 0, 0, ',', '.') }}/bulan
              @endif
            </p>
          </div>
        </div>

        @if(!empty($data['features']))
        <div>
          <p class="text-sm font-semibold text-gray-700 mb-2">Fitur yang kamu dapatkan:</p>
          <div class="grid grid-cols-2 gap-2">
            @foreach($data['features'] as $feature)
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
          <span>Aktif sampai: <strong>{{ $data['ends_at'] ?? '-' }}</strong></span>
        </div>
      @endif
    </div>

    {{-- Footer --}}
    <div class="px-8 py-4 border-t border-gray-100 flex gap-3">
      <button
        type="button"
        x-on:click="dismiss()"
        class="flex-1 px-4 py-2.5 rounded-xl font-semibold text-sm transition-all duration-200 cursor-pointer"
        style="background: linear-gradient(135deg, #FF6600, #FF8C38); color: white;"
        x-on:mouseover="style.transform = 'scale(1.02)'"
        x-on:mouseout="style.transform = 'scale(1)'"
      >
        @if($type === 'trial')
          Mulai Sekarang
        @elseif($type === 'paid')
          Ke Dashboard
        @endif
      </button>
    </div>
  </div>
</div>

<script>
function welcomeModal() {
  return {
    init() {
      // Canvas confetti
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

        // Slow down after 2 seconds
        if (frame > 120) {
          const remaining = pieces.filter(p => p.y < canvas.height + 50);
          if (remaining.length === 0) return;
        }

        requestAnimationFrame(animate);
      };
      animate();

      // Auto-stop canvas after 3 seconds
      setTimeout(() => {
        const c = document.getElementById('welcome-confetti-canvas');
        if (c) c.style.display = 'none';
      }, 3000);
    },
    dismiss() {
      const el = this.$el.closest('[x-data]');
      if (el) el.remove();
      document.getElementById('welcome-confetti-canvas')?.remove();

      // Mark as welcomed
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
