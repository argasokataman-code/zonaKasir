<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ZonaKasir — platform point of sale (POS) berbasis cloud untuk bisnis Indonesia. Kelola penjualan, stok, dan keuangan dalam satu aplikasi.">
    <meta name="keywords" content="POS, point of sale, kasir, aplikasi kasir, manajemen stok, Indonesia">
    <meta name="author" content="ZonaKasir">
    <meta name="theme-color" content="#FF6600">

    {{-- Preconnect to asset origins --}}
    <link rel="preconnect" href="{{ config('app.url') }}" crossorigin>

    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="/assets/logo/icon.svg">
    <link rel="icon" type="image/png" href="/assets/logo/icon-32.png">

    {{-- Open Graph --}}
    <meta property="og:title" content="ZonaKasir — Platform Point of Sale (POS) untuk Bisnis Indonesia">
    <meta property="og:description" content="Aplikasi POS berbasis cloud. Kelola penjualan, stok, dan keuangan toko dengan mudah.">
    <meta property="og:url" content="{{ config('app.url') }}">
    <meta property="og:image" content="{{ config('app.url') }}/assets/logo/og-image.png">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="ZonaKasir — Platform Point of Sale (POS) untuk Bisnis Indonesia">
    <meta name="twitter:description" content="Aplikasi POS berbasis cloud. Kelola penjualan, stok, dan keuangan toko dengan mudah.">
    <meta name="twitter:image" content="{{ config('app.url') }}/assets/logo/og-image.png">

    <title>{{ config('app.name', 'ZonaKasir') }}</title>
    @filamentStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
  </head>
  <body>
    {{ $slot }}
    {{-- Alpine intersect directive for scroll animations --}}
    <script>
      document.addEventListener('alpine:init', () => {
        Alpine.directive('intersect', (el, { expression, modifiers }, { effect, evaluateLater, cleanup }) => {
          const hasEnter = modifiers.includes('enter');
          const hasLeave = modifiers.includes('leave');
          const evaluate = evaluateLater(expression);
          const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
              if (entry.isIntersecting) {
                if (hasEnter || (!hasEnter && !hasLeave)) {
                  el._x_isIntersecting = true;
                  evaluate();
                }
              } else {
                if (hasLeave || (!hasEnter && !hasLeave)) {
                  el._x_isIntersecting = false;
                  if (!hasEnter) evaluate();
                }
              }
            });
          }, { threshold: 0.1 });
          observer.observe(el);
          cleanup(() => observer.disconnect());
        });
        Alpine.data('countUp', (target, duration = 2000) => ({
          display: '0',
          init() {
            const el = this.$el;
            const parse = (raw) => parseFloat(raw.replace(/[^0-9.]/g, ''));
            const prefix = target.match(/^[^0-9]*/) || '';
            const suffix = target.match(/[^0-9.]*$/) || '';
            const end = parse(target);
            const startTime = performance.now();
            const tick = (now) => {
              const elapsed = now - startTime;
              const progress = Math.min(elapsed / duration, 1);
              const eased = 1 - Math.pow(1 - progress, 3);
              this.display = prefix + Math.floor(eased * end).toLocaleString() + suffix;
              if (progress < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
          }
        }));
      });
    </script>
    @livewireScripts
    @filamentScripts

    {{-- Deferred third-party scripts can go here --}}

  </body>
</html>


