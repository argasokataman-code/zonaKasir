<!doctype html>
<html class="scroll-smooth">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ZonaKasir adalah platform point of sale (POS) berbasis cloud untuk bisnis Indonesia — kasir, stok, dan laporan dalam satu aplikasi.">
    <meta name="keywords" content="POS, point of sale, kasir, aplikasi kasir, manajemen stok, Indonesia">
    <meta name="author" content="ZonaKasir">
    <link rel="shortcut icon" type="image/svg+xml" href="/assets/logo/icon.svg">

    <!-- Open Graph tags for social media -->
    <meta property="og:title" content="ZonaKasir - Platform Point of Sale (POS) untuk Bisnis Indonesia">
    <meta property="og:description" content="ZonaKasir adalah platform point of sale (POS) berbasis cloud untuk bisnis Indonesia — kasir, stok, dan laporan dalam satu aplikasi.">
    <meta property="og:url" content="{{ env('APP_URL') }}">
    <meta property="og:image" content="{{ env('APP_URL') }}/assets/logo/logo.svg">
    <meta property="og:type" content="website">

    <!-- Twitter Card tags for Twitter -->
    <meta name="twitter:card" content="ZonaKasir - Platform Point of Sale (POS) untuk Bisnis Indonesia">
    <meta name="twitter:site" content="@yourtwitterhandle">
    <meta name="twitter:title" content="ZonaKasir - Platform Point of Sale (POS) untuk Bisnis Indonesia">
    <meta name="twitter:description" content="ZonaKasir adalah platform point of sale (POS) berbasis cloud untuk bisnis Indonesia — kasir, stok, dan laporan dalam satu aplikasi.">
    <meta name="twitter:image" content="{{ env('APP_URL') }}/assets/logo/logo.svg">

    <title>{{ config('app.name', 'Laravel') }}</title>
    @filamentStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
  </head>
  <body>
    {{ $slot }}
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
      });
    </script>
    @livewireScripts
    @filamentScripts

  </body>
</html>


