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
  {{-- Navbar --}}
  <nav class="fixed top-0 left-0 right-0 z-50 transition-all duration-300"
       x-data="{ scrolled: false }"
       x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 20 })"
       :class="scrolled ? 'bg-white/90 backdrop-blur-md shadow-lg' : 'bg-transparent'">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <div class="flex items-center justify-between h-20">
        <a href="/" class="flex items-center gap-3 group">
          <img src="{{ asset('assets/logo/logo.svg') }}" class="h-10 transition-transform duration-300 group-hover:scale-110" alt="zonaKasir">
          <span class="text-xl font-bold transition-colors duration-300"
                :class="scrolled ? 'text-gray-900' : 'text-white'">zonaKasir</span>
        </a>
        <div class="hidden md:flex items-center gap-6">
          <a href="#fitur" class="font-medium transition-colors duration-300 hover:text-zonakasir-primary"
             :class="scrolled ? 'text-gray-600' : 'text-white/80'">Fitur</a>
          <a href="#menu" class="font-medium transition-colors duration-300 hover:text-zonakasir-primary"
             :class="scrolled ? 'text-gray-600' : 'text-white/80'">Menu</a>
          <a href="#harga" class="font-medium transition-colors duration-300 hover:text-zonakasir-primary"
             :class="scrolled ? 'text-gray-600' : 'text-white/80'">Harga</a>
          <a href="{{ route('auth.register') }}"
             class="bg-zonakasir-primary text-white px-6 py-2.5 rounded-full font-medium hover:bg-orange-600 transition-all duration-300 hover:shadow-lg hover:shadow-orange-500/30">
            Daftar Sekarang
          </a>
        </div>
        <button class="md:hidden" :class="scrolled ? 'text-gray-900' : 'text-white'" x-on:click="mobileMenu = !mobileMenu">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
      </div>
    </div>
    {{-- Mobile Menu --}}
    <div x-show="mobileMenu" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-4"
         class="md:hidden bg-white shadow-xl rounded-b-2xl mx-4" x-cloak>
      <div class="px-6 py-4 space-y-3">
        <a href="#fitur" class="block py-2 text-gray-700 hover:text-zonakasir-primary" x-on:click="mobileMenu = false">Fitur</a>
        <a href="#menu" class="block py-2 text-gray-700 hover:text-zonakasir-primary" x-on:click="mobileMenu = false">Menu</a>
        <a href="#harga" class="block py-2 text-gray-700 hover:text-zonakasir-primary" x-on:click="mobileMenu = false">Harga</a>
        <a href="{{ route('auth.register') }}" class="block bg-zonakasir-primary text-white text-center py-3 rounded-full font-medium">Daftar Sekarang</a>
      </div>
    </div>
  </nav>

  {{-- Hero Section --}}
  <section class="relative min-h-screen flex items-center bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 overflow-hidden">
    {{-- Animated Background --}}
    <div class="absolute inset-0 overflow-hidden">
      <div class="absolute -top-40 -right-40 w-96 h-96 bg-zonakasir-primary/20 rounded-full blur-3xl animate-float"></div>
      <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-zonakasir-primary/10 rounded-full blur-3xl animate-float-delayed"></div>
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-zonakasir-primary/5 rounded-full blur-3xl animate-pulse-slow"></div>
      {{-- Grid Pattern --}}
      <div class="absolute inset-0 opacity-5" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23FF6600&quot; fill-opacity=&quot;0.4&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-6 lg:px-8 py-32 lg:py-0">
      <div class="grid lg:grid-cols-2 gap-12 items-center">
        {{-- Left Content --}}
        <div class="text-center lg:text-left" x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 100)" x-show="shown" x-transition:enter="transition ease-out duration-1000" x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0">
          <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full px-4 py-2 mb-6">
            <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
            <span class="text-white/80 text-sm font-medium">Open Source & Gratis</span>
          </div>
          <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-white leading-tight">
            Kelola Toko
            <span class="text-zonakasir-primary"> Lebih Mudah</span>
            <br>dengan zonaKasir
          </h1>
          <p class="mt-6 text-lg text-gray-400 max-w-xl mx-auto lg:mx-0">
            Aplikasi point of sale (POS) open-source yang membantu anda mengelola penjualan, stok, dan keuangan toko dengan mudah dan efisien.
          </p>
          <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
            <a href="{{ route('auth.register') }}"
               class="group bg-zonakasir-primary text-white px-8 py-4 rounded-full font-semibold text-lg hover:bg-orange-600 transition-all duration-300 hover:shadow-xl hover:shadow-orange-500/30 flex items-center justify-center gap-2">
              Mulai Gratis
              <svg class="w-5 h-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
              </svg>
            </a>
            <a href="#fitur"
               class="border border-white/30 text-white px-8 py-4 rounded-full font-semibold text-lg hover:bg-white/10 transition-all duration-300 flex items-center justify-center gap-2">
              Lihat Fitur
            </a>
          </div>
          <div class="mt-10 flex items-center gap-8 justify-center lg:justify-start">
            <div class="text-center">
              <p class="text-2xl font-bold text-white">100%</p>
              <p class="text-sm text-gray-400">Gratis</p>
            </div>
            <div class="w-px h-12 bg-white/20"></div>
            <div class="text-center">
              <p class="text-2xl font-bold text-white">Open</p>
              <p class="text-sm text-gray-400">Source</p>
            </div>
            <div class="w-px h-12 bg-white/20"></div>
            <div class="text-center">
              <p class="text-2xl font-bold text-white">Multi</p>
              <p class="text-sm text-gray-400">Platform</p>
            </div>
          </div>
        </div>

        {{-- Right Content - Animated Phone Mockup --}}
        <div class="relative flex justify-center lg:justify-end" x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 300)" x-show="shown" x-transition:enter="transition ease-out duration-1000 delay-200" x-transition:enter-start="opacity-0 translate-x-8" x-transition:enter-end="opacity-100 translate-x-0">
          <div class="relative">
            {{-- Floating Card 1 --}}
            <div class="absolute -left-16 top-20 bg-white rounded-2xl p-4 shadow-2xl z-10 animate-float-card hidden lg:block">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                  <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-800">Transaksi Berhasil</p>
                  <p class="text-xs text-gray-500">+Rp 150.000</p>
                </div>
              </div>
            </div>

            {{-- Floating Card 2 --}}
            <div class="absolute -right-12 bottom-32 bg-white rounded-2xl p-4 shadow-2xl z-10 animate-float-card-delayed hidden lg:block">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                  <svg class="w-5 h-5 text-zonakasir-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                  </svg>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-800">Penjualan Hari Ini</p>
                  <p class="text-xs text-gray-500">Rp 2.500.000</p>
                </div>
              </div>
            </div>

            {{-- Phone Image --}}
            <div class="relative z-20">
              <div class="absolute inset-0 bg-gradient-to-br from-zonakasir-primary/20 to-orange-600/20 rounded-[3rem] blur-2xl"></div>
              <img src="{{ asset('assets/images/cashier-transaction-1.png') }}" class="relative h-[28rem] md:h-[32rem] drop-shadow-2xl" alt="zonaKasir POS">
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Scroll Indicator --}}
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
      <svg class="w-6 h-6 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
      </svg>
    </div>
  </section>

  {{-- About Section --}}
  <section id="tentang" class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <div class="grid lg:grid-cols-2 gap-16 items-center">
        <div x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 -translate-x-8" x-transition:enter-end="opacity-100 translate-x-0">
          <span class="text-zonakasir-primary font-semibold text-sm uppercase tracking-wider">Tentang Kami</span>
          <h2 class="mt-4 text-3xl md:text-4xl font-bold text-gray-900 leading-tight">
            Solusi POS <span class="text-zonakasir-primary">Open Source</span> untuk Bisnis Anda
          </h2>
          <p class="mt-6 text-gray-600 text-lg leading-relaxed">
            zonaKasir adalah aplikasi point of sale open-source yang dirancang untuk memudahkan pengelolaan bisnis anda.
            Dengan fitur lengkap dan antarmuka yang intuitif, zonaKasir membantu anda mencatat penjualan, mengelola stok,
            dan memantau keuangan toko secara real-time.
          </p>
          <div class="mt-8 grid grid-cols-2 gap-6">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 bg-zonakasir-primary/10 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-zonakasir-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
              </div>
              <div>
                <p class="font-semibold text-gray-900">Cepat</p>
                <p class="text-sm text-gray-500">Transaksi instan</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 bg-zonakasir-primary/10 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-zonakasir-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
              </div>
              <div>
                <p class="font-semibold text-gray-900">Aman</p>
                <p class="text-sm text-gray-500">Data terenkripsi</p>
              </div>
            </div>
          </div>
        </div>
        <div class="relative" x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown" x-transition:enter="transition ease-out duration-700 delay-200" x-transition:enter-start="opacity-0 translate-x-8" x-transition:enter-end="opacity-100 translate-x-0">
          <div class="absolute inset-0 bg-gradient-to-br from-zonakasir-primary/10 to-orange-100 rounded-3xl transform rotate-3 scale-105"></div>
          <img src="{{ asset('assets/images/dashboard.png') }}" class="relative rounded-3xl shadow-2xl" alt="zonaKasir Dashboard">
        </div>
      </div>
    </div>
  </section>

  {{-- Features Section --}}
  <section id="fitur" class="py-24 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <div class="text-center mb-16" x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
        <span class="text-zonakasir-primary font-semibold text-sm uppercase tracking-wider">Fitur Unggulan</span>
        <h2 class="mt-4 text-3xl md:text-4xl font-bold text-gray-900">Kenapa Pilih zonaKasir?</h2>
        <p class="mt-4 text-gray-600 max-w-2xl mx-auto">zonaKasir hadir dengan fitur-fitur terbaik untuk membantu bisnis anda berkembang.</p>
      </div>

      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
        @foreach($mainFeatures as $index => $feature)
        <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-xl transition-all duration-500 hover:-translate-y-2 group"
             x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
             x-transition:enter="transition ease-out duration-700"
             x-transition:enter-start="opacity-0 translate-y-8"
             x-transition:enter-end="opacity-100 translate-y-0"
             style="transition-delay: {{ $index * 100 }}ms">
          <div class="w-14 h-14 bg-zonakasir-primary/10 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-zonakasir-primary group-hover:scale-110 transition-all duration-300">
            @if($feature['icon'] === 'free')
            <svg class="w-7 h-7 text-zonakasir-primary group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            @elseif($feature['icon'] === 'server')
            <svg class="w-7 h-7 text-zonakasir-primary group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
            </svg>
            @elseif($feature['icon'] === 'platform')
            <svg class="w-7 h-7 text-zonakasir-primary group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            @else
            <svg class="w-7 h-7 text-zonakasir-primary group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            @endif
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $feature['title'] }}</h3>
          <p class="text-gray-600">{{ $feature['description'] }}</p>
        </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- Menu Section --}}
  <section id="menu" class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <div class="text-center mb-16" x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
        <span class="text-zonakasir-primary font-semibold text-sm uppercase tracking-wider">Menu Utama</span>
        <h2 class="mt-4 text-3xl md:text-4xl font-bold text-gray-900">Fitur Lengkap untuk Bisnis Anda</h2>
        <p class="mt-4 text-gray-600 max-w-2xl mx-auto">Kelola semua aspek bisnis anda dalam satu aplikasi.</p>
      </div>

      <div class="space-y-24">
        @foreach($menu as $index => $item)
        <div class="grid lg:grid-cols-2 gap-12 items-center {{ $index % 2 === 1 ? 'lg:flex-row-reverse' : '' }}"
             x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
             x-transition:enter="transition ease-out duration-700"
             x-transition:enter-start="opacity-0 {{ $index % 2 === 0 ? '-translate-x-8' : 'translate-x-8' }}"
             x-transition:enter-end="opacity-100 translate-x-0">
          <div class="{{ $index % 2 === 1 ? 'lg:order-2' : '' }}">
            <div class="inline-flex items-center gap-2 bg-zonakasir-primary/10 rounded-full px-4 py-1.5 mb-4">
              <span class="w-2 h-2 bg-zonakasir-primary rounded-full"></span>
              <span class="text-zonakasir-primary text-sm font-medium">Fitur {{ $index + 1 }}</span>
            </div>
            <h3 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">{{ $item['title'] }}</h3>
            <p class="text-gray-600 text-lg leading-relaxed">{{ $item['description'] }}</p>
          </div>
          <div class="{{ $index % 2 === 1 ? 'lg:order-1' : '' }} relative">
            <div class="absolute inset-0 bg-gradient-to-br from-zonakasir-primary/10 to-orange-100 rounded-3xl transform {{ $index % 2 === 0 ? '-rotate-3' : 'rotate-3' }} scale-105"></div>
            <img src="{{ $item['image'] }}" class="relative rounded-3xl shadow-xl hover:shadow-2xl transition-shadow duration-500" alt="{{ $item['title'] }}">
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- Pricing Section --}}
  <section id="harga" class="py-24 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <div class="text-center mb-16" x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
        <span class="text-zonakasir-primary font-semibold text-sm uppercase tracking-wider">Harga</span>
        <h2 class="mt-4 text-3xl md:text-4xl font-bold text-gray-900">Pilih Paket yang Sesuai</h2>
        <p class="mt-4 text-gray-600 max-w-2xl mx-auto">Mulai gratis atau upgrade untuk fitur lebih lengkap.</p>
      </div>

      <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
        @foreach($prices as $index => $price)
        <div class="bg-white rounded-3xl p-8 shadow-sm hover:shadow-xl transition-all duration-500 {{ $index === 1 ? 'ring-2 ring-zonakasir-primary relative' : '' }}"
             x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
             x-transition:enter="transition ease-out duration-700"
             x-transition:enter-start="opacity-0 translate-y-8"
             x-transition:enter-end="opacity-100 translate-y-0"
             style="transition-delay: {{ $index * 150 }}ms">
          @if($index === 1)
          <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-zonakasir-primary text-white text-sm font-semibold px-4 py-1 rounded-full">
            Populer
          </div>
          @endif
          <h3 class="text-2xl font-bold text-gray-900">{{ $price['title'] }}</h3>
          <p class="mt-2 text-gray-600">{{ $price['description'] }}</p>
          <div class="mt-6 flex items-baseline gap-1">
            <span class="text-4xl font-extrabold text-gray-900">{{ $price['price'] }}</span>
            <span class="text-gray-500">/ bulan</span>
          </div>
          <a href="{{ route('auth.register') }}"
             class="mt-8 block w-full py-3 px-6 rounded-full text-center font-semibold transition-all duration-300 {{ $index === 1 ? 'bg-zonakasir-primary text-white hover:bg-orange-600 hover:shadow-lg hover:shadow-orange-500/30' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }}">
            {{ $price['button'] }}
          </a>
          <div class="mt-8">
            <p class="text-sm font-semibold text-gray-900 mb-4">Termasuk:</p>
            <ul class="space-y-3">
              @foreach($price['includes'] as $include)
              <li class="flex items-center gap-3">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="text-gray-600">{{ $include }}</span>
              </li>
              @endforeach
              @foreach($price['excludes'] as $exclude)
              <li class="flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span class="text-gray-400">{{ $exclude }}</span>
              </li>
              @endforeach
            </ul>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- CTA Section --}}
  <section class="py-24 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 relative overflow-hidden">
    <div class="absolute inset-0 overflow-hidden">
      <div class="absolute -top-40 -right-40 w-96 h-96 bg-zonakasir-primary/20 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-zonakasir-primary/10 rounded-full blur-3xl"></div>
    </div>
    <div class="relative max-w-4xl mx-auto px-6 lg:px-8 text-center" x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
      <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">Siap Memulai dengan zonaKasir?</h2>
      <p class="text-gray-400 text-lg mb-8 max-w-2xl mx-auto">Daftar sekarang dan mulai kelola toko anda dengan lebih mudah dan efisien.</p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="{{ route('auth.register') }}"
           class="group bg-zonakasir-primary text-white px-8 py-4 rounded-full font-semibold text-lg hover:bg-orange-600 transition-all duration-300 hover:shadow-xl hover:shadow-orange-500/30 flex items-center justify-center gap-2">
          Daftar Gratis Sekarang
          <svg class="w-5 h-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
          </svg>
        </a>
      </div>
    </div>
  </section>

  {{-- Footer --}}
  <footer class="bg-gray-900 text-white py-12">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <div class="grid md:grid-cols-4 gap-8">
        <div class="md:col-span-2">
          <div class="flex items-center gap-3 mb-4">
            <img src="{{ asset('assets/logo/logo.svg') }}" class="h-10" alt="zonaKasir">
            <span class="text-xl font-bold">zonaKasir</span>
          </div>
          <p class="text-gray-400 max-w-md">Aplikasi point of sale open-source yang membantu anda mengelola bisnis dengan mudah.</p>
        </div>
        <div>
          <h4 class="font-semibold mb-4">Produk</h4>
          <ul class="space-y-2 text-gray-400">
            <li><a href="#fitur" class="hover:text-zonakasir-primary transition-colors">Fitur</a></li>
            <li><a href="#harga" class="hover:text-zonakasir-primary transition-colors">Harga</a></li>
            <li><a href="{{ route('auth.register') }}" class="hover:text-zonakasir-primary transition-colors">Daftar</a></li>
          </ul>
        </div>
        <div>
          <h4 class="font-semibold mb-4">Legal</h4>
          <ul class="space-y-2 text-gray-400">
            <li><a href="#" class="hover:text-zonakasir-primary transition-colors">Kebijakan Privasi</a></li>
            <li><a href="#" class="hover:text-zonakasir-primary transition-colors">Syarat & Ketentuan</a></li>
          </ul>
        </div>
      </div>
      <div class="border-t border-gray-800 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <p class="text-gray-500 text-sm">&copy; {{ date('Y') }} zonaKasir. All rights reserved.</p>
        <div class="flex items-center gap-4">
          <span class="text-gray-500 text-sm">Made with</span>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-red-500">
            <path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z"/>
          </svg>
        </div>
      </div>
    </div>
  </footer>
</div>
