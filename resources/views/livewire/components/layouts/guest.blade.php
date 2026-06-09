<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="zonaKasir merupakan aplikasi point of sale (POS) yang memudahkan pengelolaan bisnis Anda. unduh secara gratis">
    <meta name="keywords" content="POS, open-source, gratis, free, murah">
    <meta name="author" content="zonaKasir">
    <link rel="shortcut icon" type="image/svg+xml" href="/assets/logo/icon.svg">

    <!-- Open Graph tags for social media -->
    <meta property="og:title" content="zonaKasir - Aplikasi Point of Sale (POS) Gratis">
    <meta property="og:description" content="zonaKasir merupakan aplikasi point of sale (POS) yang memudahkan pengelolaan bisnis Anda. unduh secara gratis">
    <meta property="og:url" content="{{ env('APP_URL') }}">
    <meta property="og:image" content="{{ env('APP_URL') }}/assets/logo/logo.svg">
    <meta property="og:type" content="website">

    <!-- Twitter Card tags for Twitter -->
    <meta name="twitter:card" content="zonaKasir - Aplikasi Point of Sale (POS) Gratis">
    <meta name="twitter:site" content="@yourtwitterhandle">
    <meta name="twitter:title" content="zonaKasir - Aplikasi Point of Sale (POS) Gratis">
    <meta name="twitter:description" content="zonaKasir merupakan aplikasi point of sale (POS) yang memudahkan pengelolaan bisnis Anda. unduh secara gratis">
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


