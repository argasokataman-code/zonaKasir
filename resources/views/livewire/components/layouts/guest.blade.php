<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="zonaKasir merupakan aplikasi point of sale (POS) yang memudahkan pengelolaan bisnis Anda. unduh secara gratis">
    <meta name="keywords" content="POS, open-source, gratis, free, murah">
    <meta name="author" content="zonaKasir">
    <link rel="shortcut icon" type="image/svg+xml" href="/assets/logo/icon.svg">

    <meta property="og:title" content="zonaKasir - Aplikasi Point of Sale (POS) Gratis">
    <meta property="og:description" content="zonaKasir merupakan aplikasi point of sale (POS) yang memudahkan pengelolaan bisnis Anda. unduh secara gratis">
    <meta property="og:url" content="{{ env('APP_URL') }}">
    <meta property="og:image" content="{{ asset('assets/logo/logo.svg') }}">
    <meta property="og:type" content="website">

    <meta name="twitter:card" content="zonaKasir - Aplikasi Point of Sale (POS) Gratis">
    <meta name="twitter:site" content="@yourtwitterhandle">
    <meta name="twitter:title" content="zonaKasir - Aplikasi Point of Sale (POS) Gratis">
    <meta name="twitter:description" content="zonaKasir merupakan aplikasi point of sale (POS) yang memudahkan pengelolaan bisnis Anda. unduh secara gratis">
    <meta name="twitter:image" content="{{ asset('assets/logo/logo.svg') }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    @filamentStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
  </head>
  <body class="font-sans antialiased bg-[#F4F4F2] text-[#1A1A1A]">
    {{ $slot }}
    @livewireScripts
    @filamentScripts
  </body>
</html>


