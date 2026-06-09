<?php

use function Livewire\Volt\layout;
use function Livewire\Volt\state;

layout('livewire.components.layouts.guest');

$menu = [
    [
        'title' => 'Analisis Penjualan',
        'description' => 'Lihat analisis penjualan toko anda secara real-time dengan grafik yang mudah dipahami.',
        'image' => '/assets/images/dashboard.png',
    ],
    [
        'title' => 'Stok Management',
        'description' => 'Kelola stok barang di toko anda dengan mudah dan akurat.',
        'image' => '/assets/images/stock-management.png',
    ],
    [
        'title' => 'Kalulator Pembayaran',
        'description' => 'Hitung pembayaran secara manual atau otomatis dengan kalkulator yang cerdas.',
        'image' => '/assets/images/calculator-payment.png',
    ],
];

$prices = [
    [
        'title' => 'Pribadi',
        'description' => 'Gunakan zonaKasir gratis dengan server anda sendiri.',
        'price' => 'IDR 0',
        'button' => 'Mulai Gratis',
        'route' => '',
        'includes' => ['Semua fitur gratis', 'Self-hosted'],
        'excludes' => [
            'Maintenance', 'Dukungan 24 jam', 'Backup data', 'Pembaharuan fitur',
        ],
    ],
    [
        'title' => 'Server Kami',
        'description' => 'Dengan server kami, dapatkan fitur lebih lengkap dan dukungan penuh.',
        'price' => 'IDR 50.000',
        'button' => 'Hubungi Kami',
        'route' => '',
        'includes' => [
            'Semua fitur gratis', 'Gratis pemasangan', 'Maintenance',
            'Dukungan 24 jam', 'Backup data', 'Pembaharuan fitur',
        ],
        'excludes' => [],
    ],
];

$mainFeatures = [
    [
        'title' => 'Gratis & Open Source',
        'description' => 'Gunakan zonaKasir secara gratis tanpa biaya apapun.',
        'icon' => 'free',
    ],
    [
        'title' => 'Self-Hosted',
        'description' => 'Deploy ke server anda sendiri, kendali penuh atas data anda.',
        'icon' => 'server',
    ],
    [
        'title' => 'Multi Platform',
        'description' => 'Akses dari Android dan Web kapan saja, di mana saja.',
        'icon' => 'platform',
    ],
    [
        'title' => 'Configurable',
        'description' => 'Sesuaikan zonaKasir dengan kebutuhan bisnis anda.',
        'icon' => 'config',
    ],
];

state([
    'menu' => $menu,
    'prices' => $prices,
    'mainFeatures' => $mainFeatures,
]);

?>

<div class="overflow-hidden">
  {{-- Loading Screen --}}
  <div x-data="{ loading: true }" x-init="setTimeout(() => loading = false, 1500)" x-show="loading"
       x-transition:leave="transition ease-in duration-500" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-[100] bg-gray-900 flex items-center justify-center">
    <div class="text-center">
      <img src="{{ asset('assets/logo/logo.svg') }}" class="h-16 mx-auto animate-bounce" alt="zonaKasir">
      <div class="mt-4 flex gap-1 justify-center">
        <div class="w-2 h-2 bg-zonakasir-primary rounded-full animate-bounce" style="animation-delay: 0ms"></div>
        <div class="w-2 h-2 bg-zonakasir-primary rounded-full animate-bounce" style="animation-delay: 150ms"></div>
        <div class="w-2 h-2 bg-zonakasir-primary rounded-full animate-bounce" style="animation-delay: 300ms"></div>
      </div>
    </div>
  </div>

  {{-- Navbar --}}
  <nav class="fixed top-0 left-0 right-0 z-50 transition-all duration-500"
       x-data="{ scrolled: false, mobileMenu: false }"
       x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 50 })"
       :class="scrolled ? 'bg-white/95 backdrop-blur-xl shadow-lg shadow-black/5 py-2' : 'bg-transparent py-4'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <a href="/" class="flex items-center gap-3 group">
          <div class="relative">
            <img src="{{ asset('assets/logo/logo.svg') }}" class="h-9 transition-all duration-500 group-hover:scale-110 group-hover:rotate-3" alt="zonaKasir">
            <div class="absolute inset-0 bg-zonakasir-primary/20 rounded-full blur-lg opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
          </div>
          <span class="text-xl font-bold transition-colors duration-500 tracking-tight"
                :class="scrolled ? 'text-gray-900' : 'text-white'">zonaKasir</span>
        </a>

        <div class="hidden md:flex items-center gap-1">
          @foreach(['Fitur' => '#fitur', 'Menu' => '#menu', 'Harga' => '#harga'] as $label => $href)
          <a href="{{ $href }}" class="relative px-4 py-2 font-medium transition-all duration-300 rounded-full group"
             :class="scrolled ? 'text-gray-600 hover:text-zonakasir-primary hover:bg-zonakasir-primary/5' : 'text-white/80 hover:text-white hover:bg-white/10'">
            {{ $label }}
            <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-0 h-0.5 bg-zonakasir-primary transition-all duration-300 group-hover:w-2/3 rounded-full"></span>
          </a>
          @endforeach
          <a href="{{ route('auth.register') }}"
             class="ml-4 bg-zonakasir-primary text-white px-6 py-2.5 rounded-full font-medium transition-all duration-300 hover:bg-orange-600 hover:shadow-lg hover:shadow-orange-500/30 hover:-translate-y-0.5 active:translate-y-0">
            Daftar Sekarang
          </a>
        </div>

        <button class="md:hidden relative w-10 h-10 flex items-center justify-center rounded-full transition-colors"
                :class="scrolled ? 'text-gray-900 hover:bg-gray-100' : 'text-white hover:bg-white/10'"
                x-on:click="mobileMenu = !mobileMenu">
          <div class="w-5 h-4 flex flex-col justify-between">
            <span class="w-full h-0.5 bg-current rounded-full transition-all duration-300 origin-center"
                  :class="mobileMenu ? 'rotate-45 translate-y-[7px]' : ''"></span>
            <span class="w-full h-0.5 bg-current rounded-full transition-all duration-300"
                  :class="mobileMenu ? 'opacity-0 scale-x-0' : ''"></span>
            <span class="w-full h-0.5 bg-current rounded-full transition-all duration-300 origin-center"
                  :class="mobileMenu ? '-rotate-45 -translate-y-[7px]' : ''"></span>
          </div>
        </button>
      </div>
    </div>

    {{-- Mobile Menu --}}
    <div x-show="mobileMenu" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
         class="md:hidden absolute top-full left-4 right-4 mt-2 bg-white/95 backdrop-blur-xl shadow-2xl rounded-2xl overflow-hidden border border-gray-100">
      <div class="p-4 space-y-1">
        @foreach(['Fitur' => '#fitur', 'Menu' => '#menu', 'Harga' => '#harga'] as $label => $href)
        <a href="{{ $href }}" class="block px-4 py-3 text-gray-700 hover:text-zonakasir-primary hover:bg-zonakasir-primary/5 rounded-xl transition-all duration-200 font-medium"
           x-on:click="mobileMenu = false">
          {{ $label }}
        </a>
        @endforeach
        <div class="pt-2 px-4">
          <a href="{{ route('auth.register') }}" class="block bg-zonakasir-primary text-white text-center py-3 rounded-xl font-medium hover:bg-orange-600 transition-all duration-200">
            Daftar Sekarang
          </a>
        </div>
      </div>
    </div>
  </nav>

  {{-- Hero Section --}}
  <section class="relative min-h-screen flex items-center bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 overflow-hidden">
    {{-- Animated Background --}}
    <div class="absolute inset-0 overflow-hidden">
      <div class="absolute -top-40 -right-40 w-[500px] h-[500px] bg-zonakasir-primary/20 rounded-full blur-[120px] animate-float"></div>
      <div class="absolute -bottom-40 -left-40 w-[500px] h-[500px] bg-zonakasir-primary/10 rounded-full blur-[120px] animate-float-delayed"></div>
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-zonakasir-primary/5 rounded-full blur-[150px] animate-pulse-slow"></div>
      {{-- Animated Grid --}}
      <div class="absolute inset-0 opacity-[0.03] animate-grid-move" style="background-image: linear-gradient(rgba(255,102,0,0.5) 1px, transparent 1px), linear-gradient(90deg, rgba(255,102,0,0.5) 1px, transparent 1px); background-size: 50px 50px;"></div>
      {{-- Floating Particles --}}
      <div class="absolute top-1/4 left-1/4 w-1 h-1 bg-zonakasir-primary/40 rounded-full animate-float-particle"></div>
      <div class="absolute top-1/3 right-1/3 w-1.5 h-1.5 bg-zonakasir-primary/30 rounded-full animate-float-particle-delayed"></div>
      <div class="absolute bottom-1/4 left-1/3 w-1 h-1 bg-zonakasir-primary/50 rounded-full animate-float-particle"></div>
      <div class="absolute top-2/3 right-1/4 w-2 h-2 bg-zonakasir-primary/20 rounded-full animate-float-particle-delayed"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-32 lg:py-0 w-full">
      <div class="grid lg:grid-cols-2 gap-12 lg:gap-8 items-center">
        {{-- Left Content --}}
        <div class="text-center lg:text-left"
             x-data="{ shown: false }"
             x-init="setTimeout(() => shown = true, 200)"
             x-show="shown"
             x-transition:enter="transition ease-out duration-1000"
             x-transition:enter-start="opacity-0 translate-y-12"
             x-transition:enter-end="opacity-100 translate-y-0">

          {{-- Badge --}}
          <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/10 rounded-full px-5 py-2.5 mb-8 animate-fade-in-up" style="animation-delay: 0.1s">
            <span class="relative flex h-2.5 w-2.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
            </span>
            <span class="text-white/90 text-sm font-medium">Open Source & 100% Gratis</span>
          </div>

          {{-- Heading --}}
          <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-extrabold text-white leading-[1.1] tracking-tight">
            <span class="block animate-fade-in-up" style="animation-delay: 0.2s">Kelola Toko</span>
            <span class="block text-transparent bg-clip-text bg-gradient-to-r from-zonakasir-primary to-orange-400 animate-fade-in-up" style="animation-delay: 0.3s">Lebih Mudah</span>
            <span class="block animate-fade-in-up" style="animation-delay: 0.4s">dengan zonaKasir</span>
          </h1>

          {{-- Description --}}
          <p class="mt-6 sm:mt-8 text-base sm:text-lg lg:text-xl text-gray-400 max-w-xl mx-auto lg:mx-0 leading-relaxed animate-fade-in-up" style="animation-delay: 0.5s">
            Aplikasi point of sale (POS) open-source yang membantu anda mengelola penjualan, stok, dan keuangan toko dengan mudah dan efisien.
          </p>

          {{-- CTA Buttons --}}
          <div class="mt-8 sm:mt-10 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start animate-fade-in-up" style="animation-delay: 0.6s">
            <a href="{{ route('auth.register') }}"
               class="group relative bg-zonakasir-primary text-white px-8 py-4 rounded-2xl font-semibold text-lg overflow-hidden transition-all duration-300 hover:shadow-2xl hover:shadow-orange-500/30 hover:-translate-y-1 active:translate-y-0 flex items-center justify-center gap-3">
              <div class="absolute inset-0 bg-gradient-to-r from-orange-600 to-orange-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
              <span class="relative">Mulai Gratis</span>
              <svg class="relative w-5 h-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
              </svg>
            </a>
            <a href="#fitur"
               class="relative border border-white/20 text-white px-8 py-4 rounded-2xl font-semibold text-lg overflow-hidden transition-all duration-300 hover:bg-white/10 hover:border-white/40 hover:-translate-y-1 active:translate-y-0 flex items-center justify-center gap-3 backdrop-blur-sm">
              <span>Lihat Fitur</span>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
              </svg>
            </a>
          </div>

          {{-- Stats --}}
          <div class="mt-12 sm:mt-16 grid grid-cols-3 gap-6 sm:gap-8 max-w-md mx-auto lg:mx-0 animate-fade-in-up" style="animation-delay: 0.7s">
            <div class="text-center lg:text-left group">
              <p class="text-2xl sm:text-3xl font-extrabold text-white group-hover:text-zonakasir-primary transition-colors duration-300">100%</p>
              <p class="text-xs sm:text-sm text-gray-500 mt-1">Gratis</p>
            </div>
            <div class="text-center lg:text-left border-x border-white/10 px-4 group">
              <p class="text-2xl sm:text-3xl font-extrabold text-white group-hover:text-zonakasir-primary transition-colors duration-300">Open</p>
              <p class="text-xs sm:text-sm text-gray-500 mt-1">Source</p>
            </div>
            <div class="text-center lg:text-left group">
              <p class="text-2xl sm:text-3xl font-extrabold text-white group-hover:text-zonakasir-primary transition-colors duration-300">Multi</p>
              <p class="text-xs sm:text-sm text-gray-500 mt-1">Platform</p>
            </div>
          </div>
        </div>

        {{-- Right Content - Phone Mockup --}}
        <div class="relative flex justify-center lg:justify-end"
             x-data="{ shown: false }"
             x-init="setTimeout(() => shown = true, 400)"
             x-show="shown"
             x-transition:enter="transition ease-out duration-1000 delay-200"
             x-transition:enter-start="opacity-0 scale-95 translate-x-12"
             x-transition:enter-end="opacity-100 scale-100 translate-x-0">
          <div class="relative w-full max-w-sm lg:max-w-none">
            {{-- Glow Effect --}}
            <div class="absolute inset-0 bg-gradient-to-br from-zonakasir-primary/30 to-orange-600/20 rounded-[3rem] blur-3xl animate-pulse-slow"></div>

            {{-- Floating Notification 1 --}}
            <div class="absolute -left-6 sm:-left-16 top-16 sm:top-20 bg-white/95 backdrop-blur-sm rounded-2xl p-3 sm:p-4 shadow-2xl z-10 animate-float-card hidden sm:block border border-gray-100">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-green-400 to-green-500 rounded-xl flex items-center justify-center shadow-lg shadow-green-500/30">
                  <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                  </svg>
                </div>
                <div>
                  <p class="text-xs sm:text-sm font-bold text-gray-800">Transaksi Berhasil</p>
                  <p class="text-[10px] sm:text-xs text-green-600 font-semibold">+Rp 150.000</p>
                </div>
              </div>
            </div>

            {{-- Floating Notification 2 --}}
            <div class="absolute -right-4 sm:-right-12 bottom-28 sm:bottom-32 bg-white/95 backdrop-blur-sm rounded-2xl p-3 sm:p-4 shadow-2xl z-10 animate-float-card-delayed hidden sm:block border border-gray-100">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-zonakasir-primary to-orange-600 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/30">
                  <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                  </svg>
                </div>
                <div>
                  <p class="text-xs sm:text-sm font-bold text-gray-800">Penjualan Hari Ini</p>
                  <p class="text-[10px] sm:text-xs text-zonakasir-primary font-semibold">Rp 2.500.000</p>
                </div>
              </div>
            </div>

            {{-- Floating Notification 3 - Mobile Only --}}
            <div class="absolute -left-2 sm:-left-6 bottom-16 sm:bottom-20 bg-white/95 backdrop-blur-sm rounded-xl p-2.5 shadow-xl z-10 animate-float-card hidden sm:hidden border border-gray-100">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-gradient-to-br from-green-400 to-green-500 rounded-lg flex items-center justify-center">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                  </svg>
                </div>
                <div>
                  <p class="text-[10px] font-bold text-gray-800">Berhasil</p>
                  <p class="text-[8px] text-green-600 font-semibold">+Rp 150K</p>
                </div>
              </div>
            </div>

            {{-- Phone Image --}}
            <div class="relative z-20">
              <img src="{{ asset('assets/images/cashier-transaction-1.png') }}" class="relative h-[20rem] sm:h-[26rem] md:h-[30rem] lg:h-[32rem] drop-shadow-2xl hover:scale-[1.02] transition-transform duration-700" alt="zonaKasir POS">
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Scroll Indicator --}}
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-fade-in-up" style="animation-delay: 1s">
      <div class="flex flex-col items-center gap-2 text-white/40">
        <span class="text-xs font-medium tracking-widest uppercase">Scroll</span>
        <div class="w-6 h-10 border-2 border-white/20 rounded-full flex justify-center p-1">
          <div class="w-1.5 h-3 bg-white/40 rounded-full animate-scroll-indicator"></div>
        </div>
      </div>
    </div>
  </section>

  {{-- About Section --}}
  <section id="tentang" class="py-20 sm:py-24 lg:py-32 bg-white relative overflow-hidden">
    <div class="absolute top-0 right-0 w-96 h-96 bg-zonakasir-primary/5 rounded-full blur-[100px]"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
        {{-- Text --}}
        <div x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
             x-transition:enter="transition ease-out duration-1000"
             x-transition:enter-start="opacity-0 -translate-x-12"
             x-transition:enter-end="opacity-100 translate-x-0">
          <div class="inline-flex items-center gap-2 bg-zonakasir-primary/10 rounded-full px-4 py-1.5 mb-4">
            <span class="w-1.5 h-1.5 bg-zonakasir-primary rounded-full"></span>
            <span class="text-zonakasir-primary text-sm font-semibold">Tentang Kami</span>
          </div>
          <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900 leading-tight">
            Solusi POS
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-zonakasir-primary to-orange-500">Open Source</span>
            untuk Bisnis Anda
          </h2>
          <p class="mt-6 text-gray-600 text-base sm:text-lg leading-relaxed">
            zonaKasir adalah aplikasi point of sale open-source yang dirancang untuk memudahkan pengelolaan bisnis anda.
            Dengan fitur lengkap dan antarmuka yang intuitif, zonaKasir membantu anda mencatat penjualan, mengelola stok,
            dan memantau keuangan toko secara real-time.
          </p>
          <div class="mt-8 grid grid-cols-2 gap-4 sm:gap-6">
            <div class="group flex items-center gap-3 p-3 sm:p-4 rounded-2xl hover:bg-zonakasir-primary/5 transition-all duration-300">
              <div class="w-12 h-12 bg-gradient-to-br from-zonakasir-primary/10 to-orange-50 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                <svg class="w-6 h-6 text-zonakasir-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
              </div>
              <div>
                <p class="font-bold text-gray-900">Cepat</p>
                <p class="text-xs sm:text-sm text-gray-500">Transaksi instan</p>
              </div>
            </div>
            <div class="group flex items-center gap-3 p-3 sm:p-4 rounded-2xl hover:bg-zonakasir-primary/5 transition-all duration-300">
              <div class="w-12 h-12 bg-gradient-to-br from-zonakasir-primary/10 to-orange-50 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                <svg class="w-6 h-6 text-zonakasir-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
              </div>
              <div>
                <p class="font-bold text-gray-900">Aman</p>
                <p class="text-xs sm:text-sm text-gray-500">Data terenkripsi</p>
              </div>
            </div>
          </div>
        </div>
        {{-- Image --}}
        <div class="relative" x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
             x-transition:enter="transition ease-out duration-1000 delay-200"
             x-transition:enter-start="opacity-0 translate-x-12"
             x-transition:enter-end="opacity-100 translate-x-0">
          <div class="absolute inset-0 bg-gradient-to-br from-zonakasir-primary/10 to-orange-100 rounded-3xl transform rotate-3 scale-105 animate-float-slow"></div>
          <div class="absolute inset-0 bg-gradient-to-tl from-orange-500/5 to-transparent rounded-3xl transform -rotate-2 scale-[1.02]"></div>
          <img src="{{ asset('assets/images/dashboard.png') }}" class="relative rounded-3xl shadow-2xl hover:shadow-3xl transition-shadow duration-500 w-full" alt="zonaKasir Dashboard">
        </div>
      </div>
    </div>
  </section>

  {{-- Features Section --}}
  <section id="fitur" class="py-20 sm:py-24 lg:py-32 bg-gradient-to-b from-gray-50 to-white relative overflow-hidden">
    <div class="absolute bottom-0 left-0 w-96 h-96 bg-zonakasir-primary/5 rounded-full blur-[100px]"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      {{-- Header --}}
      <div class="text-center mb-16 sm:mb-20" x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
           x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="inline-flex items-center gap-2 bg-zonakasir-primary/10 rounded-full px-4 py-1.5 mb-4">
          <span class="w-1.5 h-1.5 bg-zonakasir-primary rounded-full"></span>
          <span class="text-zonakasir-primary text-sm font-semibold">Fitur Unggulan</span>
        </div>
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900">Kenapa Pilih zonaKasir?</h2>
        <p class="mt-4 text-gray-600 max-w-2xl mx-auto text-base sm:text-lg">zonaKasir hadir dengan fitur-fitur terbaik untuk membantu bisnis anda berkembang.</p>
      </div>

      {{-- Feature Cards --}}
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
        @foreach($mainFeatures as $index => $feature)
        <div class="group bg-white rounded-3xl p-6 sm:p-8 shadow-sm hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 border border-gray-100 hover:border-zonakasir-primary/20 relative overflow-hidden"
             x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
             x-transition:enter="transition ease-out duration-700"
             x-transition:enter-start="opacity-0 translate-y-12"
             x-transition:enter-end="opacity-100 translate-y-0"
             style="transition-delay: {{ $index * 100 }}ms">
          {{-- Hover Gradient --}}
          <div class="absolute inset-0 bg-gradient-to-br from-zonakasir-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
          {{-- Icon --}}
          <div class="relative w-14 h-14 sm:w-16 sm:h-16 bg-gradient-to-br from-zonakasir-primary/10 to-orange-50 rounded-2xl flex items-center justify-center mb-5 group-hover:from-zonakasir-primary group-hover:to-orange-500 group-hover:scale-110 group-hover:rotate-3 transition-all duration-500 shadow-sm group-hover:shadow-lg group-hover:shadow-orange-500/25">
            @if($feature['icon'] === 'free')
            <svg class="w-7 h-7 sm:w-8 sm:h-8 text-zonakasir-primary group-hover:text-white transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            @elseif($feature['icon'] === 'server')
            <svg class="w-7 h-7 sm:w-8 sm:h-8 text-zonakasir-primary group-hover:text-white transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
            </svg>
            @elseif($feature['icon'] === 'platform')
            <svg class="w-7 h-7 sm:w-8 sm:h-8 text-zonakasir-primary group-hover:text-white transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            @else
            <svg class="w-7 h-7 sm:w-8 sm:h-8 text-zonakasir-primary group-hover:text-white transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            @endif
          </div>
          {{-- Content --}}
          <h3 class="relative text-lg sm:text-xl font-bold text-gray-900 mb-2">{{ $feature['title'] }}</h3>
          <p class="relative text-gray-600 text-sm sm:text-base leading-relaxed">{{ $feature['description'] }}</p>
        </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- Menu Section --}}
  <section id="menu" class="py-20 sm:py-24 lg:py-32 bg-white relative overflow-hidden">
    <div class="absolute top-1/2 right-0 w-96 h-96 bg-zonakasir-primary/5 rounded-full blur-[100px] -translate-y-1/2"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      {{-- Header --}}
      <div class="text-center mb-16 sm:mb-20" x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
           x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="inline-flex items-center gap-2 bg-zonakasir-primary/10 rounded-full px-4 py-1.5 mb-4">
          <span class="w-1.5 h-1.5 bg-zonakasir-primary rounded-full"></span>
          <span class="text-zonakasir-primary text-sm font-semibold">Menu Utama</span>
        </div>
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900">Fitur Lengkap untuk Bisnis Anda</h2>
        <p class="mt-4 text-gray-600 max-w-2xl mx-auto text-base sm:text-lg">Kelola semua aspek bisnis anda dalam satu aplikasi.</p>
      </div>

      {{-- Menu Items --}}
      <div class="space-y-20 sm:space-y-24 lg:space-y-32">
        @foreach($menu as $index => $item)
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-16 items-center"
             x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
             x-transition:enter="transition ease-out duration-1000"
             x-transition:enter-start="opacity-0 {{ $index % 2 === 0 ? '-translate-x-12' : 'translate-x-12' }}"
             x-transition:enter-end="opacity-100 translate-x-0">
          {{-- Text --}}
          <div class="{{ $index % 2 === 1 ? 'lg:order-2 lg:text-right' : '' }} text-center lg:text-left">
            <div class="inline-flex items-center gap-2 bg-zonakasir-primary/10 rounded-full px-4 py-1.5 mb-4">
              <span class="w-1.5 h-1.5 bg-zonakasir-primary rounded-full animate-pulse"></span>
              <span class="text-zonakasir-primary text-sm font-semibold">Fitur {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
            </div>
            <h3 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-gray-900 mb-4">{{ $item['title'] }}</h3>
            <p class="text-gray-600 text-base sm:text-lg leading-relaxed max-w-lg mx-auto lg:mx-0">{{ $item['description'] }}</p>
          </div>
          {{-- Image --}}
          <div class="{{ $index % 2 === 1 ? 'lg:order-1' : '' }} relative group">
            <div class="absolute inset-0 bg-gradient-to-br from-zonakasir-primary/10 to-orange-100 rounded-3xl transform {{ $index % 2 === 0 ? '-rotate-3' : 'rotate-3' }} scale-105 group-hover:scale-110 group-hover:rotate-0 transition-all duration-700"></div>
            <div class="absolute inset-0 bg-gradient-to-tl from-orange-500/5 to-transparent rounded-3xl transform {{ $index % 2 === 0 ? 'rotate-2' : '-rotate-2' }} scale-[1.01]"></div>
            <img src="{{ $item['image'] }}" class="relative rounded-3xl shadow-xl group-hover:shadow-2xl transition-all duration-500 w-full" alt="{{ $item['title'] }}">
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- Pricing Section --}}
  <section id="harga" class="py-20 sm:py-24 lg:py-32 bg-gradient-to-b from-gray-50 to-white relative overflow-hidden">
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-zonakasir-primary/5 rounded-full blur-[150px]"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      {{-- Header --}}
      <div class="text-center mb-16 sm:mb-20" x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
           x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="inline-flex items-center gap-2 bg-zonakasir-primary/10 rounded-full px-4 py-1.5 mb-4">
          <span class="w-1.5 h-1.5 bg-zonakasir-primary rounded-full"></span>
          <span class="text-zonakasir-primary text-sm font-semibold">Harga</span>
        </div>
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900">Pilih Paket yang Sesuai</h2>
        <p class="mt-4 text-gray-600 max-w-2xl mx-auto text-base sm:text-lg">Mulai gratis atau upgrade untuk fitur lebih lengkap.</p>
      </div>

      {{-- Pricing Cards --}}
      <div class="grid md:grid-cols-2 gap-6 lg:gap-8 max-w-4xl mx-auto">
        @foreach($prices as $index => $price)
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm hover:shadow-2xl transition-all duration-500 border {{ $index === 1 ? 'border-2 border-zonakasir-primary relative ring-4 ring-zonakasir-primary/10' : 'border-gray-100 hover:border-zonakasir-primary/20' }} relative overflow-hidden group"
             x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
             x-transition:enter="transition ease-out duration-700"
             x-transition:enter-start="opacity-0 translate-y-12"
             x-transition:enter-end="opacity-100 translate-y-0"
             style="transition-delay: {{ $index * 150 }}ms">
          {{-- Hover Gradient --}}
          <div class="absolute inset-0 bg-gradient-to-br from-zonakasir-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

          @if($index === 1)
          <div class="absolute -top-px left-0 right-0 h-1 bg-gradient-to-r from-zonakasir-primary to-orange-500"></div>
          <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-gradient-to-r from-zonakasir-primary to-orange-500 text-white text-sm font-bold px-5 py-1.5 rounded-full shadow-lg shadow-orange-500/30">
            Populer
          </div>
          @endif

          <div class="relative">
            <h3 class="text-2xl font-bold text-gray-900">{{ $price['title'] }}</h3>
            <p class="mt-2 text-gray-600">{{ $price['description'] }}</p>
            <div class="mt-6 flex items-baseline gap-1">
              <span class="text-4xl sm:text-5xl font-extrabold text-gray-900">{{ $price['price'] }}</span>
              <span class="text-gray-500">/ bulan</span>
            </div>
            <a href="{{ route('auth.register') }}"
               class="mt-8 block w-full py-3.5 px-6 rounded-2xl text-center font-semibold transition-all duration-300 hover:-translate-y-0.5 active:translate-y-0 {{ $index === 1 ? 'bg-gradient-to-r from-zonakasir-primary to-orange-500 text-white hover:shadow-lg hover:shadow-orange-500/30' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }}">
              {{ $price['button'] }}
            </a>
            <div class="mt-8">
              <p class="text-sm font-semibold text-gray-900 mb-4">Termasuk:</p>
              <ul class="space-y-3">
                @foreach($price['includes'] as $include)
                <li class="flex items-center gap-3">
                  <div class="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                  </div>
                  <span class="text-gray-600">{{ $include }}</span>
                </li>
                @endforeach
                @foreach($price['excludes'] as $exclude)
                <li class="flex items-center gap-3">
                  <div class="w-5 h-5 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </div>
                  <span class="text-gray-400">{{ $exclude }}</span>
                </li>
                @endforeach
              </ul>
            </div>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- CTA Section --}}
  <section class="py-20 sm:py-24 lg:py-32 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 relative overflow-hidden">
    <div class="absolute inset-0 overflow-hidden">
      <div class="absolute -top-40 -right-40 w-[500px] h-[500px] bg-zonakasir-primary/20 rounded-full blur-[120px] animate-float"></div>
      <div class="absolute -bottom-40 -left-40 w-[500px] h-[500px] bg-zonakasir-primary/10 rounded-full blur-[120px] animate-float-delayed"></div>
      <div class="absolute inset-0 opacity-[0.03]" style="background-image: linear-gradient(rgba(255,255,255,0.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 50px 50px;"></div>
    </div>
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center" x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
         x-transition:enter="transition ease-out duration-1000" x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0">
      <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white mb-6">Siap Memulai dengan zonaKasir?</h2>
      <p class="text-gray-400 text-base sm:text-lg lg:text-xl mb-10 max-w-2xl mx-auto">Daftar sekarang dan mulai kelola toko anda dengan lebih mudah dan efisien.</p>
      <a href="{{ route('auth.register') }}"
         class="group relative inline-flex items-center gap-3 bg-gradient-to-r from-zonakasir-primary to-orange-500 text-white px-10 py-5 rounded-2xl font-bold text-lg transition-all duration-300 hover:shadow-2xl hover:shadow-orange-500/30 hover:-translate-y-1 active:translate-y-0 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-orange-600 to-orange-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        <span class="relative">Daftar Gratis Sekarang</span>
        <svg class="relative w-5 h-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
        </svg>
      </a>
    </div>
  </section>

  {{-- Footer --}}
  <footer class="bg-gray-900 text-white pt-16 pb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
        <div class="sm:col-span-2 lg:col-span-1">
          <div class="flex items-center gap-3 mb-4">
            <img src="{{ asset('assets/logo/logo.svg') }}" class="h-10" alt="zonaKasir">
            <span class="text-xl font-bold">zonaKasir</span>
          </div>
          <p class="text-gray-400 text-sm leading-relaxed max-w-xs">Aplikasi point of sale open-source yang membantu anda mengelola bisnis dengan mudah.</p>
        </div>
        <div>
          <h4 class="font-bold mb-4 text-white">Produk</h4>
          <ul class="space-y-3 text-gray-400 text-sm">
            <li><a href="#fitur" class="hover:text-zonakasir-primary transition-colors duration-200 hover:translate-x-1 inline-block">Fitur</a></li>
            <li><a href="#harga" class="hover:text-zonakasir-primary transition-colors duration-200 hover:translate-x-1 inline-block">Harga</a></li>
            <li><a href="{{ route('auth.register') }}" class="hover:text-zonakasir-primary transition-colors duration-200 hover:translate-x-1 inline-block">Daftar</a></li>
          </ul>
        </div>
        <div>
          <h4 class="font-bold mb-4 text-white">Legal</h4>
          <ul class="space-y-3 text-gray-400 text-sm">
            <li><a href="#" class="hover:text-zonakasir-primary transition-colors duration-200 hover:translate-x-1 inline-block">Kebijakan Privasi</a></li>
            <li><a href="#" class="hover:text-zonakasir-primary transition-colors duration-200 hover:translate-x-1 inline-block">Syarat & Ketentuan</a></li>
          </ul>
        </div>
      </div>
      <div class="border-t border-gray-800 mt-12 pt-8 flex flex-col sm:flex-row justify-between items-center gap-4">
        <p class="text-gray-500 text-sm">&copy; {{ date('Y') }} zonaKasir. All rights reserved.</p>
        <div class="flex items-center gap-2 text-gray-500 text-sm">
          <span>Made with</span>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-red-500 animate-heartbeat">
            <path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z"/>
          </svg>
        </div>
      </div>
    </div>
  </footer>
</div>
