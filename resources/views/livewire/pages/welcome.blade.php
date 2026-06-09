<?php

use function Livewire\Volt\layout;
use function Livewire\Volt\state;

layout('livewire.components.layouts.guest');

$menu = [
    [
        'title' => 'Analisis Penjualan',
        'description' => 'Lihat analisis penjualan toko anda secara real-time dengan grafik yang mudah dipahami.',
    ],
    [
        'title' => 'Stok Management',
        'description' => 'Kelola stok barang di toko anda dengan mudah dan akurat.',
    ],
    [
        'title' => 'Kalulator Pembayaran',
        'description' => 'Hitung pembayaran secara manual atau otomatis dengan kalkulator yang cerdas.',
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
      <div class="absolute inset-0 opacity-[0.03] animate-grid-move" style="background-image: linear-gradient(rgba(255,102,0,0.5) 1px, transparent 1px), linear-gradient(90deg, rgba(255,102,0,0.5) 1px, transparent 1px); background-size: 50px 50px;"></div>
      <div class="absolute top-1/4 left-1/4 w-1 h-1 bg-zonakasir-primary/40 rounded-full animate-float-particle"></div>
      <div class="absolute top-1/3 right-1/3 w-1.5 h-1.5 bg-zonakasir-primary/30 rounded-full animate-float-particle-delayed"></div>
      <div class="absolute bottom-1/4 left-1/3 w-1 h-1 bg-zonakasir-primary/50 rounded-full animate-float-particle"></div>
      <div class="absolute top-2/3 right-1/4 w-2 h-2 bg-zonakasir-primary/20 rounded-full animate-float-particle-delayed"></div>
    </div>

    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-32 lg:py-40 w-full text-center"
         x-data="{ shown: false }"
         x-init="setTimeout(() => shown = true, 200)"
         x-show="shown"
         x-transition:enter="transition ease-out duration-1000"
         x-transition:enter-start="opacity-0 translate-y-12"
         x-transition:enter-end="opacity-100 translate-y-0">

      <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/10 rounded-full px-5 py-2.5 mb-8 animate-fade-in-up" style="animation-delay: 0.1s">
        <span class="relative flex h-2.5 w-2.5">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
        </span>
        <span class="text-white/90 text-sm font-medium">Open Source & 100% Gratis</span>
      </div>

      <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-extrabold text-white leading-[1.1] tracking-tight">
        <span class="block animate-fade-in-up" style="animation-delay: 0.2s">Kelola Toko</span>
        <span class="block text-transparent bg-clip-text bg-gradient-to-r from-zonakasir-primary to-orange-400 animate-fade-in-up" style="animation-delay: 0.3s">Lebih Mudah</span>
        <span class="block animate-fade-in-up" style="animation-delay: 0.4s">dengan zonaKasir</span>
      </h1>

      <p class="mt-6 sm:mt-8 text-base sm:text-lg lg:text-xl text-gray-400 max-w-2xl mx-auto leading-relaxed animate-fade-in-up" style="animation-delay: 0.5s">
        Aplikasi point of sale (POS) open-source yang membantu anda mengelola penjualan, stok, dan keuangan toko dengan mudah dan efisien.
      </p>

      <div class="mt-8 sm:mt-10 flex flex-col sm:flex-row gap-4 justify-center animate-fade-in-up" style="animation-delay: 0.6s">
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

      <div class="mt-12 sm:mt-16 grid grid-cols-3 gap-6 sm:gap-8 max-w-md mx-auto animate-fade-in-up" style="animation-delay: 0.7s">
        <div class="text-center group">
          <p class="text-2xl sm:text-3xl font-extrabold text-white group-hover:text-zonakasir-primary transition-colors duration-300">100%</p>
          <p class="text-xs sm:text-sm text-gray-500 mt-1">Gratis</p>
        </div>
        <div class="text-center border-x border-white/10 px-4 group">
          <p class="text-2xl sm:text-3xl font-extrabold text-white group-hover:text-zonakasir-primary transition-colors duration-300">Open</p>
          <p class="text-xs sm:text-sm text-gray-500 mt-1">Source</p>
        </div>
        <div class="text-center group">
          <p class="text-2xl sm:text-3xl font-extrabold text-white group-hover:text-zonakasir-primary transition-colors duration-300">Multi</p>
          <p class="text-xs sm:text-sm text-gray-500 mt-1">Platform</p>
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

  {{-- Device Mockup Section --}}
  <section class="relative bg-gradient-to-b from-gray-900 to-gray-800 py-16 sm:py-20 lg:py-24 overflow-hidden">
    {{-- Background decorations --}}
    <div class="absolute inset-0 overflow-hidden">
      <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-zonakasir-primary/10 rounded-full blur-[100px]"></div>
    </div>

    <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8"
         x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
         x-transition:enter="transition ease-out duration-1000"
         x-transition:enter-start="opacity-0 translate-y-12"
         x-transition:enter-end="opacity-100 translate-y-0">

      {{-- Section Label --}}
      <div class="text-center mb-10 sm:mb-14">
        <span class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-full px-4 py-1.5 text-sm font-medium text-white/80">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          Tersedia di Web & Mobile
        </span>
      </div>

      {{-- Mockups Container --}}
      <div class="relative flex justify-center items-end gap-4 sm:gap-8 lg:gap-12">

        {{-- Floating Notification Left --}}
        <div class="hidden lg:block absolute left-0 top-8 z-10 animate-float-card">
          <div class="bg-white/95 backdrop-blur-sm rounded-2xl p-4 shadow-2xl border border-gray-100">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-green-500 rounded-xl flex items-center justify-center shadow-lg shadow-green-500/30">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
              <div>
                <p class="text-xs font-bold text-gray-800">Transaksi Berhasil</p>
                <p class="text-[10px] text-green-600 font-semibold">+Rp 150.000</p>
              </div>
            </div>
          </div>
        </div>

        {{-- Laptop Mockup: Cashier V1 Desktop --}}
        <div class="relative w-full max-w-2xl lg:max-w-3xl animate-float-card">
          <div class="absolute -inset-6 bg-gradient-to-br from-zonakasir-primary/15 to-orange-600/10 rounded-[2.5rem] blur-3xl animate-pulse-slow"></div>
          <div class="relative bg-gray-800 rounded-t-2xl sm:rounded-t-3xl p-2 sm:p-3 pb-0 shadow-2xl">
            <div class="bg-white rounded-t-lg sm:rounded-t-xl overflow-hidden aspect-[16/10]">
              {{-- Browser Chrome --}}
              <div class="bg-gray-100 px-3 sm:px-4 py-1.5 sm:py-2 flex items-center gap-2 border-b border-gray-200">
                <div class="flex gap-1">
                  <div class="w-2 h-2 sm:w-2.5 sm:h-2.5 rounded-full bg-red-400"></div>
                  <div class="w-2 h-2 sm:w-2.5 sm:h-2.5 rounded-full bg-yellow-400"></div>
                  <div class="w-2 h-2 sm:w-2.5 sm:h-2.5 rounded-full bg-green-400"></div>
                </div>
                <div class="flex-1 bg-white rounded h-4 sm:h-5 ml-2 flex items-center px-2 border border-gray-200">
                  <span class="text-[7px] sm:text-[9px] text-gray-400">admin.zonakasir.com/member/cashier</span>
                </div>
              </div>
              {{-- App Content: Filament Layout --}}
              <div class="flex h-[calc(100%-2rem)] sm:h-[calc(100%-2.25rem)]">
                {{-- Filament Sidebar --}}
                <div class="hidden sm:flex w-[18%] bg-white border-r border-gray-200 flex-col py-1.5 px-1">
                  <div class="flex items-center gap-1.5 px-2 py-1.5 mb-2">
                    <div class="w-4 h-4 bg-zonakasir-primary rounded flex items-center justify-center">
                      <span class="text-white text-[5px] font-bold">ZK</span>
                    </div>
                    <span class="text-gray-900 text-[7px] font-bold">zonaKasir</span>
                  </div>
                  {{-- Nav Items --}}
                  @foreach([
                    ['label' => 'Dashboard', 'active' => false, 'icon' => '📊'],
                    ['label' => 'Cashier', 'active' => true, 'icon' => '🛒'],
                    ['label' => 'Selling', 'active' => false, 'icon' => '📋'],
                    ['label' => 'Member', 'active' => false, 'icon' => '👤'],
                    ['label' => 'Product', 'active' => false, 'icon' => '📦'],
                    ['label' => 'Category', 'active' => false, 'icon' => '🏷'],
                  ] as $nav)
                  <div class="flex items-center gap-1.5 px-2 py-1 rounded-md text-[7px] {{ $nav['active'] ? 'bg-zonakasir-primary text-white font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                    <span class="text-[8px]">{{ $nav['icon'] }}</span>
                    <span>{{ $nav['label'] }}</span>
                  </div>
                  @endforeach
                  <div class="mt-auto border-t border-gray-100 pt-1 px-2">
                    <div class="flex items-center gap-1 text-[7px] text-gray-400">
                      <span>⚙</span>
                      <span>Setting</span>
                    </div>
                  </div>
                </div>
                {{-- Main: Product Grid + Cart --}}
                <div class="flex-1 flex bg-gray-50 overflow-hidden">
                  {{-- Product Grid (left 2/3) --}}
                  <div class="w-2/3 p-1.5 sm:p-2">
                    {{-- Search Bar --}}
                    <div class="bg-white rounded-lg border border-gray-200 h-5 sm:h-6 flex items-center px-2 mb-2">
                      <svg class="w-2.5 h-2.5 text-gray-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                      <span class="text-[7px] text-gray-400">Search (SKU, name, barcode)</span>
                    </div>
                    {{-- Product Cards Grid --}}
                    <div class="grid grid-cols-3 gap-1.5">
                      @php
                      $products = [
                        ['name' => 'Indomie Goreng', 'price' => 'Rp 3.500', 'stock' => 48, 'bg' => 'from-yellow-300 to-orange-300', 'emoji' => '🍜'],
                        ['name' => 'Teh Botol Sosro', 'price' => 'Rp 4.000', 'stock' => 24, 'bg' => 'from-green-300 to-emerald-400', 'emoji' => '🍵'],
                        ['name' => 'Rokok Sampoerna', 'price' => 'Rp 28.000', 'stock' => 12, 'bg' => 'from-red-300 to-rose-400', 'emoji' => '🚬'],
                        ['name' => 'Le Minerale 600ml', 'price' => 'Rp 4.500', 'stock' => 36, 'bg' => 'from-blue-300 to-cyan-400', 'emoji' => '💧'],
                        ['name' => 'Pocari Sweat', 'price' => 'Rp 7.000', 'stock' => 18, 'bg' => 'from-cyan-300 to-sky-400', 'emoji' => '🧃'],
                        ['name' => 'Chitato 68g', 'price' => 'Rp 12.500', 'stock' => 8, 'bg' => 'from-purple-300 to-violet-400', 'emoji' => '🥔'],
                      ];
                      @endphp
                      @foreach($products as $pi => $p)
                      <div class="bg-white rounded-lg border border-gray-100 overflow-hidden shadow-sm">
                        <div class="bg-gradient-to-br {{ $p['bg'] }} h-8 sm:h-10 flex items-center justify-center relative">
                          <span class="text-lg sm:text-xl drop-shadow-sm">{{ $p['emoji'] }}</span>
                        </div>
                        <div class="p-1 sm:p-1.5">
                          <div class="text-[7px] sm:text-[8px] font-bold text-zonakasir-primary">{{ $p['price'] }}</div>
                          <div class="text-[6px] sm:text-[7px] font-semibold text-gray-800 leading-tight mt-0.5">{{ $p['name'] }}</div>
                          <div class="text-[5px] sm:text-[6px] text-gray-400 mt-0.5">Stock: {{ $p['stock'] }}</div>
                        </div>
                      </div>
                      @endforeach
                    </div>
                  </div>
                  {{-- Cart Sidebar (right 1/3) --}}
                  <div class="w-1/3 bg-white border-l border-gray-200 flex flex-col">
                    {{-- Cart Header --}}
                    <div class="px-1.5 sm:px-2 pt-1.5 sm:pt-2 pb-1 border-b border-gray-100">
                      <div class="text-[8px] sm:text-[10px] font-semibold text-gray-800">Orders details</div>
                      <div class="text-[5px] text-gray-400 mt-0.5">Cashier: Admin</div>
                    </div>
                    {{-- Cart Items --}}
                    <div class="flex-1 overflow-hidden px-1.5 sm:px-2 py-1 space-y-1">
                      @php
                      $cartItems = [
                        ['name' => 'Indomie Goreng', 'qty' => 3, 'price' => 'Rp 10.500'],
                        ['name' => 'Teh Botol Sosro', 'qty' => 2, 'price' => 'Rp 8.000'],
                        ['name' => 'Rokok Sampoerna', 'qty' => 1, 'price' => 'Rp 28.000'],
                      ];
                      @endphp
                      @foreach($cartItems as $ci => $cart)
                      <div class="border border-gray-100 rounded-md p-1 sm:p-1.5">
                        <div class="flex justify-between items-start">
                          <span class="text-[6px] sm:text-[7px] font-semibold text-gray-800 leading-tight">{{ $cart['name'] }}</span>
                          <span class="text-[6px] sm:text-[7px] font-semibold text-zonakasir-primary">{{ $cart['price'] }}</span>
                        </div>
                        <div class="flex items-center gap-1 mt-1">
                          <div class="w-3 h-3 sm:w-3.5 sm:h-3.5 rounded bg-gray-100 flex items-center justify-center text-[6px] text-gray-400">−</div>
                          <span class="text-[6px] sm:text-[7px] font-semibold w-3 text-center">{{ $cart['qty'] }}</span>
                          <div class="w-3 h-3 sm:w-3.5 sm:h-3.5 rounded bg-zonakasir-primary flex items-center justify-center text-[6px] text-white">+</div>
                          <div class="ml-auto w-3 h-3 sm:w-3.5 sm:h-3.5 rounded bg-red-50 flex items-center justify-center text-[5px] text-red-400">🗑</div>
                        </div>
                      </div>
                      @endforeach
                    </div>
                    {{-- Cart Detail --}}
                    <div class="px-1.5 sm:px-2 py-1 border-t border-gray-100 space-y-0.5">
                      <div class="flex justify-between text-[6px] sm:text-[7px]">
                        <span class="text-gray-500">Member</span>
                        <span class="text-gray-400">No Member</span>
                      </div>
                      <div class="flex justify-between text-[6px] sm:text-[7px]">
                        <span class="text-gray-500">Discount</span>
                        <span class="text-gray-400">-</span>
                      </div>
                    </div>
                    {{-- Cart Total --}}
                    <div class="px-1.5 sm:px-2 py-1 border-t border-gray-100 bg-gray-50 space-y-0.5">
                      <div class="flex justify-between text-[6px] sm:text-[7px]">
                        <span class="text-gray-500">Sub total</span>
                        <span class="font-bold text-zonakasir-primary">Rp 46.500</span>
                      </div>
                      <div class="flex justify-between text-[6px] sm:text-[7px]">
                        <span class="text-gray-500">Tax 10%</span>
                        <span class="font-bold text-zonakasir-primary">Rp 4.650</span>
                      </div>
                      <div class="border-t border-gray-200 pt-0.5 flex justify-between text-[7px] sm:text-[8px]">
                        <span class="font-bold text-gray-800">Total</span>
                        <span class="font-bold text-zonakasir-primary">Rp 51.150</span>
                      </div>
                    </div>
                    {{-- Pay Button --}}
                    <div class="px-1.5 sm:px-2 pb-1.5 sm:pb-2 pt-1">
                      <div class="bg-zonakasir-primary rounded-md py-1 sm:py-1.5 text-center text-[7px] sm:text-[8px] font-bold text-white shadow-md">
                        Proceed to payment
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-700 h-3 sm:h-4 rounded-b-xl sm:rounded-b-2xl mx-6 sm:mx-12 shadow-inner"></div>
          <div class="bg-gray-600 h-1 sm:h-1.5 rounded-b-lg sm:rounded-b-xl mx-16 sm:mx-28"></div>
        </div>

        {{-- Phone Mockup: POS V2 Mobile --}}
        <div class="relative -ml-8 sm:ml-0 z-20 animate-float-card-delayed">
          <div class="relative">
            <div class="absolute -inset-3 bg-gradient-to-br from-zonakasir-primary/20 to-orange-600/10 rounded-[2.5rem] blur-2xl"></div>
            <div class="relative bg-gray-900 rounded-[2rem] sm:rounded-[2.5rem] p-1.5 sm:p-2 shadow-2xl w-[130px] sm:w-[180px] lg:w-[200px]">
              <div class="bg-white rounded-[1.5rem] sm:rounded-[2rem] overflow-hidden aspect-[9/19] flex flex-col">
                {{-- Top Bar --}}
                <div class="bg-white px-2 pt-1.5 sm:pt-2 pb-1 border-b border-gray-100 shadow-sm">
                  <div class="flex items-center justify-between mb-1">
                    <div class="flex items-center gap-1">
                      <svg class="w-2 h-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                      <span class="text-[8px] sm:text-[10px] font-semibold text-gray-900">Cashier</span>
                    </div>
                    <div class="w-3 h-3 rounded bg-gray-100 flex items-center justify-center">
                      <div class="space-y-0.5">
                        <div class="w-2 h-[1px] bg-gray-400"></div>
                        <div class="w-2 h-[1px] bg-gray-400"></div>
                        <div class="w-1.5 h-[1px] bg-gray-400"></div>
                      </div>
                    </div>
                  </div>
                  {{-- Search --}}
                  <div class="bg-gray-100 rounded-md h-4 flex items-center px-1.5 gap-1">
                    <svg class="w-2 h-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span class="text-[5px] sm:text-[6px] text-gray-400">Cari produk...</span>
                    <div class="ml-auto w-3 h-3 rounded bg-zonakasir-primary flex items-center justify-center">
                      <svg class="w-1.5 h-1.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    </div>
                  </div>
                  {{-- Category Tabs --}}
                  <div class="flex gap-2 mt-1.5 overflow-hidden">
                    @foreach(['All', 'Makanan', 'Minuman', 'Rokok'] as $ci => $cat)
                    <span class="text-[5px] sm:text-[6px] whitespace-nowrap pb-0.5 {{ $ci === 0 ? 'text-zonakasir-primary border-b border-zonakasir-primary font-semibold' : 'text-gray-400' }}">{{ $cat }}</span>
                    @endforeach
                  </div>
                </div>
                {{-- Product List --}}
                <div class="flex-1 bg-gray-50 overflow-hidden p-1 space-y-1">
                  @php
                  $mobileProducts = [
                    ['name' => 'Indomie Goreng', 'desc' => 'Mie instan rasa ayam bawang', 'price' => 'Rp 3.500', 'bg' => 'from-yellow-300 to-orange-300', 'emoji' => '🍜'],
                    ['name' => 'Teh Botol Sosro', 'desc' => 'Teh dalam kemasan 450ml', 'price' => 'Rp 4.000', 'bg' => 'from-green-300 to-emerald-400', 'emoji' => '🍵'],
                    ['name' => 'Rokok Sampoerna', 'desc' => 'Rokok mild 16 batang', 'price' => 'Rp 28.000', 'bg' => 'from-red-300 to-rose-400', 'emoji' => '🚬'],
                    ['name' => 'Le Minerale', 'desc' => 'Air mineral 600ml', 'price' => 'Rp 4.500', 'bg' => 'from-blue-300 to-cyan-400', 'emoji' => '💧'],
                    ['name' => 'Pocari Sweat', 'desc' => 'Minuman isotonik 350ml', 'price' => 'Rp 7.000', 'bg' => 'from-cyan-300 to-sky-400', 'emoji' => '🧃'],
                  ];
                  @endphp
                  @foreach($mobileProducts as $mp)
                  <div class="bg-white rounded-lg p-1 flex items-center gap-1.5 shadow-sm">
                    <div class="bg-gradient-to-br {{ $mp['bg'] }} w-8 h-8 sm:w-10 sm:h-10 rounded-lg flex-shrink-0 flex items-center justify-center">
                      <span class="text-sm sm:text-base">{{ $mp['emoji'] }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="text-[6px] sm:text-[7px] font-semibold text-gray-900 truncate">{{ $mp['name'] }}</div>
                      <div class="text-[4px] sm:text-[5px] text-gray-400 truncate">{{ $mp['desc'] }}</div>
                      <div class="text-[4px] text-gray-400 mt-0.5">Total Price</div>
                      <div class="text-[5px] sm:text-[6px] font-semibold text-zonakasir-primary">{{ $mp['price'] }}</div>
                    </div>
                    <div class="w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-zonakasir-primary flex items-center justify-center flex-shrink-0">
                      <span class="text-white text-[8px] sm:text-[10px] font-bold leading-none">+</span>
                    </div>
                  </div>
                  @endforeach
                </div>
                {{-- Bottom Cart Pill --}}
                <div class="p-1 bg-white border-t border-gray-100">
                  <div class="bg-zonakasir-primary rounded-full px-2 py-1 sm:py-1.5 flex items-center justify-between">
                    <span class="text-white text-[6px] sm:text-[7px] font-semibold">3 Items</span>
                    <span class="text-white text-[6px] sm:text-[7px] font-bold">Rp 51.150</span>
                    <div class="w-4 h-4 sm:w-5 sm:h-5 rounded-full border border-white/40 flex items-center justify-center">
                      <svg class="w-2 h-2 sm:w-2.5 sm:h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                  </div>
                </div>
              </div>
              {{-- Notch --}}
              <div class="absolute top-2 sm:top-3 left-1/2 -translate-x-1/2 w-8 sm:w-12 h-1.5 sm:h-2 bg-gray-900 rounded-full"></div>
            </div>
          </div>
        </div>

        {{-- Floating Notification Right --}}
        <div class="hidden lg:block absolute right-0 bottom-16 z-30 animate-float-card-delayed">
          <div class="bg-white/95 backdrop-blur-sm rounded-2xl p-4 shadow-2xl border border-gray-100">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-gradient-to-br from-zonakasir-primary to-orange-600 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/30">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
              </div>
              <div>
                <p class="text-xs font-bold text-gray-800">Penjualan Hari Ini</p>
                <p class="text-[10px] text-zonakasir-primary font-semibold">Rp 2.500.000</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- About Section --}}
  <section id="tentang" class="py-20 sm:py-24 lg:py-32 bg-white relative overflow-hidden">
    <div class="absolute top-0 right-0 w-96 h-96 bg-zonakasir-primary/5 rounded-full blur-[100px]"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center"
           x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
           x-transition:enter="transition ease-out duration-1000"
           x-transition:enter-start="opacity-0 translate-y-8"
           x-transition:enter-end="opacity-100 translate-y-0">
        <div>
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
        <div class="relative group">
          <div class="absolute inset-0 bg-gradient-to-br from-zonakasir-primary/10 to-orange-100 rounded-3xl transform rotate-3 scale-105 animate-float-slow"></div>
          <div class="absolute inset-0 bg-gradient-to-tl from-orange-500/5 to-transparent rounded-3xl transform -rotate-2 scale-[1.02]"></div>
          {{-- Dashboard Mockup --}}
          <div class="relative rounded-3xl shadow-2xl hover:shadow-3xl transition-shadow duration-500 w-full bg-gray-50 overflow-hidden aspect-[4/3] border border-gray-200">
            <div class="flex h-full">
              {{-- Sidebar --}}
              <div class="w-[22%] bg-white border-r border-gray-200 flex flex-col py-2 px-1.5">
                <div class="flex items-center gap-1.5 px-1.5 py-1 mb-3">
                  <div class="w-5 h-5 bg-zonakasir-primary rounded-md flex items-center justify-center">
                    <span class="text-white text-[6px] font-bold">ZK</span>
                  </div>
                  <span class="text-gray-900 text-[8px] font-bold">zonaKasir</span>
                </div>
                @foreach([
                  ['label' => 'Dashboard', 'active' => true],
                  ['label' => 'Cashier', 'active' => false],
                  ['label' => 'Selling', 'active' => false],
                  ['label' => 'Product', 'active' => false],
                  ['label' => 'Report', 'active' => false],
                ] as $nav)
                <div class="flex items-center gap-1.5 px-2 py-1 rounded-md text-[7px] {{ $nav['active'] ? 'bg-zonakasir-primary text-white font-semibold' : 'text-gray-500' }}">
                  <div class="w-1 h-1 rounded-full {{ $nav['active'] ? 'bg-white' : 'bg-gray-300' }}"></div>
                  {{ $nav['label'] }}
                </div>
                @endforeach
              </div>
              {{-- Main Content --}}
              <div class="flex-1 p-2.5 overflow-hidden">
                <div class="text-[10px] font-bold text-gray-900 mb-2">Dashboard</div>
                {{-- Stat Cards --}}
                <div class="grid grid-cols-3 gap-1.5 mb-2">
                  <div class="bg-white rounded-xl p-2 border border-gray-100 shadow-sm">
                    <div class="text-[5px] text-gray-400 mb-0.5">Today Total Revenue</div>
                    <div class="text-[10px] font-bold text-gray-900">2.5M</div>
                    <div class="flex items-center gap-0.5 mt-0.5">
                      <svg class="w-2 h-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                      <span class="text-[5px] text-green-500 font-medium">15%</span>
                    </div>
                  </div>
                  <div class="bg-white rounded-xl p-2 border border-gray-100 shadow-sm">
                    <div class="text-[5px] text-gray-400 mb-0.5">Sales Today</div>
                    <div class="text-[10px] font-bold text-gray-900">128</div>
                    <div class="text-[5px] text-gray-400 mt-0.5">transactions</div>
                  </div>
                  <div class="bg-white rounded-xl p-2 border border-gray-100 shadow-sm">
                    <div class="text-[5px] text-gray-400 mb-0.5">Discount Today</div>
                    <div class="text-[10px] font-bold text-gray-900">125K</div>
                    <div class="text-[5px] text-gray-400 mt-0.5">total discount</div>
                  </div>
                </div>
                {{-- Chart --}}
                <div class="bg-white rounded-xl p-2 border border-gray-100 shadow-sm mb-2">
                  <div class="flex justify-between items-center mb-1.5">
                    <span class="text-[7px] font-semibold text-gray-700">Penjualan Mingguan</span>
                    <span class="text-[5px] px-1 py-0.5 bg-zonakasir-primary/10 text-zonakasir-primary rounded">Minggu ini</span>
                  </div>
                  <div class="flex items-end gap-1 h-16">
                    @foreach([30, 45, 35, 60, 42, 75, 55] as $i => $h)
                    <div class="flex-1 flex flex-col items-center gap-0.5">
                      <div class="w-full rounded-t {{ $i === 5 ? 'bg-zonakasir-primary' : 'bg-zonakasir-primary/20' }}" style="height: {{ $h }}%"></div>
                      <span class="text-[4px] text-gray-400">{{ ['Sen','Sel','Rab','Kam','Jum','Sab','Min'][$i] }}</span>
                    </div>
                    @endforeach
                  </div>
                </div>
                {{-- Best Selling --}}
                <div class="bg-white rounded-xl p-2 border border-gray-100 shadow-sm">
                  <span class="text-[7px] font-semibold text-gray-700">Best Selling Today</span>
                  <div class="mt-1 space-y-1">
                    @foreach([['name' => 'Indomie Goreng', 'qty' => '48', 'emoji' => '🍜', 'bg' => 'bg-yellow-100'], ['name' => 'Teh Botol Sosro', 'qty' => '32', 'emoji' => '🍵', 'bg' => 'bg-green-100'], ['name' => 'Rokok Sampoerna', 'qty' => '24', 'emoji' => '🚬', 'bg' => 'bg-red-100']] as $bp)
                    <div class="flex justify-between items-center py-0.5 border-b border-gray-50 last:border-0">
                      <div class="flex items-center gap-1">
                        <div class="w-4 h-4 rounded {{ $bp['bg'] }} flex items-center justify-center text-[6px]">{{ $bp['emoji'] }}</div>
                        <span class="text-[6px] text-gray-700">{{ $bp['name'] }}</span>
                      </div>
                      <span class="text-[6px] text-zonakasir-primary font-semibold">{{ $bp['qty'] }} sold</span>
                    </div>
                    @endforeach
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- Features Section --}}
  <section id="fitur" class="py-20 sm:py-24 lg:py-32 bg-gradient-to-b from-gray-50 to-white relative overflow-hidden">
    <div class="absolute bottom-0 left-0 w-96 h-96 bg-zonakasir-primary/5 rounded-full blur-[100px]"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-16 sm:mb-20" x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
           x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="inline-flex items-center gap-2 bg-zonakasir-primary/10 rounded-full px-4 py-1.5 mb-4">
          <span class="w-1.5 h-1.5 bg-zonakasir-primary rounded-full"></span>
          <span class="text-zonakasir-primary text-sm font-semibold">Fitur Unggulan</span>
        </div>
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900">Kenapa Pilih zonaKasir?</h2>
        <p class="mt-4 text-gray-600 max-w-2xl mx-auto text-base sm:text-lg">zonaKasir hadir dengan fitur-fitur terbaik untuk membantu bisnis anda berkembang.</p>
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8"
           x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
           x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        @foreach($mainFeatures as $index => $feature)
        <div class="group bg-white rounded-3xl p-6 sm:p-8 shadow-sm hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 border border-gray-100 hover:border-zonakasir-primary/20 relative overflow-hidden"
             x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
             x-transition:enter="transition ease-out duration-700"
             x-transition:enter-start="opacity-0 translate-y-12"
             x-transition:enter-end="opacity-100 translate-y-0"
             style="transition-delay: {{ $index * 100 }}ms">
          <div class="absolute inset-0 bg-gradient-to-br from-zonakasir-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
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
      <div class="text-center mb-16 sm:mb-20" x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
           x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-8" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="inline-flex items-center gap-2 bg-zonakasir-primary/10 rounded-full px-4 py-1.5 mb-4">
          <span class="w-1.5 h-1.5 bg-zonakasir-primary rounded-full"></span>
          <span class="text-zonakasir-primary text-sm font-semibold">Menu Utama</span>
        </div>
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900">Fitur Lengkap untuk Bisnis Anda</h2>
        <p class="mt-4 text-gray-600 max-w-2xl mx-auto text-base sm:text-lg">Kelola semua aspek bisnis anda dalam satu aplikasi.</p>
      </div>

      <div class="space-y-20 sm:space-y-24 lg:space-y-32">
        @foreach($menu as $index => $item)
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-16 items-center"
             x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
             x-transition:enter="transition ease-out duration-1000"
             x-transition:enter-start="opacity-0 translate-y-12"
             x-transition:enter-end="opacity-100 translate-y-0">
          <div class="{{ $index % 2 === 1 ? 'lg:order-2 lg:text-right' : '' }} text-center lg:text-left">
            <div class="inline-flex items-center gap-2 bg-zonakasir-primary/10 rounded-full px-4 py-1.5 mb-4">
              <span class="w-1.5 h-1.5 bg-zonakasir-primary rounded-full animate-pulse"></span>
              <span class="text-zonakasir-primary text-sm font-semibold">Fitur {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
            </div>
            <h3 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-gray-900 mb-4">{{ $item['title'] }}</h3>
            <p class="text-gray-600 text-base sm:text-lg leading-relaxed max-w-lg mx-auto lg:mx-0">{{ $item['description'] }}</p>
          </div>
          <div class="{{ $index % 2 === 1 ? 'lg:order-1' : '' }} relative group">
            <div class="absolute inset-0 bg-gradient-to-br from-zonakasir-primary/10 to-orange-100 rounded-3xl transform {{ $index % 2 === 0 ? '-rotate-3' : 'rotate-3' }} scale-105 group-hover:scale-110 group-hover:rotate-0 transition-all duration-700"></div>
            <div class="absolute inset-0 bg-gradient-to-tl from-orange-500/5 to-transparent rounded-3xl transform {{ $index % 2 === 0 ? 'rotate-2' : '-rotate-2' }} scale-[1.01]"></div>
            {{-- CSS Mockup for each menu item --}}
            @if($index === 0)
            {{-- Analisis Penjualan: Real Dashboard Report --}}
            <div class="relative rounded-3xl shadow-xl group-hover:shadow-2xl transition-all duration-500 w-full bg-gray-50 overflow-hidden aspect-[4/3] border border-gray-200">
              <div class="flex h-full">
                {{-- Sidebar --}}
                <div class="w-[22%] bg-white border-r border-gray-200 flex flex-col py-2 px-1.5">
                  <div class="flex items-center gap-1.5 px-1.5 py-1 mb-3">
                    <div class="w-4 h-4 bg-zonakasir-primary rounded flex items-center justify-center">
                      <span class="text-white text-[5px] font-bold">ZK</span>
                    </div>
                    <span class="text-gray-900 text-[7px] font-bold">zonaKasir</span>
                  </div>
                  @foreach(['Dashboard' => true, 'Cashier' => false, 'Selling' => false, 'Report' => false] as $nav => $active)
                  <div class="flex items-center gap-1.5 px-2 py-1 rounded-md text-[6px] {{ $active ? 'bg-zonakasir-primary text-white font-semibold' : 'text-gray-500' }}">
                    <div class="w-1 h-1 rounded-full {{ $active ? 'bg-white' : 'bg-gray-300' }}"></div>
                    {{ $nav }}
                  </div>
                  @endforeach
                </div>
                {{-- Main --}}
                <div class="flex-1 p-2 overflow-hidden">
                  <div class="text-[8px] font-bold text-gray-900 mb-1.5">Dashboard</div>
                  <div class="grid grid-cols-3 gap-1 mb-1.5">
                    <div class="bg-white rounded-lg p-1.5 border border-gray-100">
                      <div class="text-[4px] text-gray-400">Revenue</div>
                      <div class="text-[7px] font-bold text-gray-900">2.5M</div>
                      <div class="text-[4px] text-green-500">↑ 15%</div>
                    </div>
                    <div class="bg-white rounded-lg p-1.5 border border-gray-100">
                      <div class="text-[4px] text-gray-400">Sales</div>
                      <div class="text-[7px] font-bold text-gray-900">128</div>
                    </div>
                    <div class="bg-white rounded-lg p-1.5 border border-gray-100">
                      <div class="text-[4px] text-gray-400">Discount</div>
                      <div class="text-[7px] font-bold text-gray-900">125K</div>
                    </div>
                  </div>
                  <div class="bg-white rounded-lg p-2 border border-gray-100 mb-1.5">
                    <div class="text-[6px] font-semibold text-gray-700 mb-1">Penjualan Bulanan</div>
                    <div class="flex items-end gap-1 h-14">
                      @foreach([30, 50, 40, 65, 45, 80, 55, 70, 60, 85, 75, 90] as $i => $h)
                      <div class="flex-1 flex flex-col items-center">
                        <div class="w-full rounded-t {{ $i === 11 ? 'bg-zonakasir-primary' : 'bg-zonakasir-primary/20' }}" style="height: {{ $h }}%"></div>
                      </div>
                      @endforeach
                    </div>
                    <div class="flex justify-between mt-0.5">
                      @foreach(['J','F','M','A','M','J','J','A','S','O','N','D'] as $m)
                      <span class="text-[3px] text-gray-400">{{ $m }}</span>
                      @endforeach
                    </div>
                  </div>
                  <div class="bg-white rounded-lg p-2 border border-gray-100">
                    <div class="text-[6px] font-semibold text-gray-700 mb-1">Top Products</div>
                    @foreach([['Indomie', '48'], ['Teh Botol', '32'], ['Sampoerna', '24']] as $bp)
                    <div class="flex justify-between items-center py-0.5">
                      <span class="text-[5px] text-gray-600">{{ $bp[0] }}</span>
                      <span class="text-[5px] text-zonakasir-primary font-semibold">{{ $bp[1] }} sold</span>
                    </div>
                    @endforeach
                  </div>
                </div>
              </div>
            </div>
            @elseif($index === 1)
            {{-- Stok Management: Filament Product Table --}}
            <div class="relative rounded-3xl shadow-xl group-hover:shadow-2xl transition-all duration-500 w-full bg-gray-50 overflow-hidden aspect-[4/3] border border-gray-200">
              <div class="flex h-full">
                {{-- Sidebar --}}
                <div class="w-[22%] bg-white border-r border-gray-200 flex flex-col py-2 px-1.5">
                  <div class="flex items-center gap-1.5 px-1.5 py-1 mb-3">
                    <div class="w-4 h-4 bg-zonakasir-primary rounded flex items-center justify-center">
                      <span class="text-white text-[5px] font-bold">ZK</span>
                    </div>
                    <span class="text-gray-900 text-[7px] font-bold">zonaKasir</span>
                  </div>
                  @foreach(['Dashboard' => false, 'Product' => true, 'Category' => false] as $nav => $active)
                  <div class="flex items-center gap-1.5 px-2 py-1 rounded-md text-[6px] {{ $active ? 'bg-zonakasir-primary text-white font-semibold' : 'text-gray-500' }}">
                    <div class="w-1 h-1 rounded-full {{ $active ? 'bg-white' : 'bg-gray-300' }}"></div>
                    {{ $nav }}
                  </div>
                  @endforeach
                </div>
                {{-- Main --}}
                <div class="flex-1 p-2 overflow-hidden">
                  <div class="text-[8px] font-bold text-gray-900 mb-1.5">Products</div>
                  {{-- Search + Create --}}
                  <div class="flex gap-1 mb-1.5">
                    <div class="flex-1 bg-white border border-gray-200 rounded-md h-4 flex items-center px-1.5">
                      <svg class="w-2 h-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                      <span class="text-[5px] text-gray-400 ml-0.5">Search products...</span>
                    </div>
                    <div class="bg-zonakasir-primary rounded-md h-4 px-2 flex items-center">
                      <span class="text-[5px] text-white font-semibold">+ New</span>
                    </div>
                  </div>
                  {{-- Table --}}
                  <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    {{-- Header --}}
                    <div class="grid grid-cols-5 gap-0.5 px-2 py-1 bg-gray-50 border-b border-gray-200">
                      <span class="text-[4px] font-semibold text-gray-500 col-span-2">Product</span>
                      <span class="text-[4px] font-semibold text-gray-500">SKU</span>
                      <span class="text-[4px] font-semibold text-gray-500">Stock</span>
                      <span class="text-[4px] font-semibold text-gray-500 text-right">Price</span>
                    </div>
                    {{-- Rows --}}
                    @php
                    $stockProducts = [
                      ['name' => 'Indomie Goreng', 'sku' => 'IND-001', 'stock' => '48', 'price' => 'Rp 3.500', 'stockColor' => 'text-green-600', 'emoji' => '🍜', 'bg' => 'bg-yellow-100'],
                      ['name' => 'Teh Botol Sosro', 'sku' => 'TBS-002', 'stock' => '24', 'price' => 'Rp 4.000', 'stockColor' => 'text-green-600', 'emoji' => '🍵', 'bg' => 'bg-green-100'],
                      ['name' => 'Rokok Sampoerna', 'sku' => 'RKS-003', 'stock' => '3', 'price' => 'Rp 28.000', 'stockColor' => 'text-red-600', 'emoji' => '🚬', 'bg' => 'bg-red-100'],
                      ['name' => 'Le Minerale', 'sku' => 'LEM-004', 'stock' => '36', 'price' => 'Rp 4.500', 'stockColor' => 'text-green-600', 'emoji' => '💧', 'bg' => 'bg-blue-100'],
                      ['name' => 'Pocari Sweat', 'sku' => 'POS-005', 'stock' => '18', 'price' => 'Rp 7.000', 'stockColor' => 'text-green-600', 'emoji' => '🧃', 'bg' => 'bg-cyan-100'],
                      ['name' => 'Chitato 68g', 'sku' => 'CHI-006', 'stock' => '8', 'price' => 'Rp 12.500', 'stockColor' => 'text-yellow-600', 'emoji' => '🥔', 'bg' => 'bg-purple-100'],
                    ];
                    @endphp
                    @foreach($stockProducts as $sp)
                    <div class="grid grid-cols-5 gap-0.5 px-2 py-1 border-b border-gray-50 items-center">
                      <div class="flex items-center gap-1 col-span-2">
                        <div class="w-4 h-4 rounded {{ $sp['bg'] }} flex-shrink-0 flex items-center justify-center text-[6px]">{{ $sp['emoji'] }}</div>
                        <span class="text-[5px] font-medium text-gray-800 truncate">{{ $sp['name'] }}</span>
                      </div>
                      <span class="text-[5px] text-gray-500">{{ $sp['sku'] }}</span>
                      <span class="text-[5px] font-medium {{ $sp['stockColor'] }}">{{ $sp['stock'] }}</span>
                      <span class="text-[5px] font-semibold text-gray-800 text-right">{{ $sp['price'] }}</span>
                    </div>
                    @endforeach
                  </div>
                </div>
              </div>
            </div>
            @else
            {{-- Kalkulator Pembayaran: Payment Modal --}}
            <div class="relative rounded-3xl shadow-xl group-hover:shadow-2xl transition-all duration-500 w-full bg-gray-800/90 overflow-hidden aspect-[4/3] flex items-center justify-center">
              {{-- Modal --}}
              <div class="bg-white rounded-xl w-[85%] shadow-2xl overflow-hidden">
                {{-- Modal Header --}}
                <div class="px-3 py-2 border-b border-gray-100 flex items-center justify-between">
                  <span class="text-[7px] font-bold text-gray-900">Payment</span>
                  <div class="w-3 h-3 rounded-full bg-gray-200 flex items-center justify-center">
                    <span class="text-[5px] text-gray-500">×</span>
                  </div>
                </div>
                <div class="flex gap-2 p-2">
                  {{-- Left: Payment Form --}}
                  <div class="flex-1">
                    {{-- Payment Methods --}}
                    <div class="text-[5px] text-gray-500 mb-1">Payment Method</div>
                    <div class="grid grid-cols-4 gap-0.5 mb-2">
                      @foreach(['Cash', 'QRIS', 'Debit', 'Credit'] as $pm)
                      <div class="{{ $pm === 'Cash' ? 'bg-zonakasir-primary text-white' : 'bg-gray-100 text-gray-600' }} rounded py-1 text-center text-[5px] font-medium">{{ $pm }}</div>
                      @endforeach
                    </div>
                    {{-- Paid Amount --}}
                    <div class="text-[5px] text-gray-500 mb-0.5">Paid Amount</div>
                    <div class="bg-white border border-gray-200 rounded-md px-2 py-1 text-right text-[8px] font-bold text-gray-900 mb-1.5">
                      Rp 55.000
                    </div>
                    {{-- Shortcut Buttons --}}
                    <div class="grid grid-cols-4 gap-0.5 mb-1.5">
                      @foreach(['50K', '100K', '200K'] as $amt)
                      <div class="bg-gray-100 rounded py-0.5 text-center text-[5px] text-gray-600">{{ $amt }}</div>
                      @endforeach
                      <div class="bg-gray-100 rounded py-0.5 text-center text-[5px] text-gray-600">Pass</div>
                    </div>
                    {{-- Numpad --}}
                    <div class="grid grid-cols-3 gap-0.5">
                      @foreach(['1','2','3','4','5','6','7','8','9','.','0','⌫'] as $k)
                      <div class="bg-gray-100 rounded py-1 text-center text-[6px] text-gray-700 hover:bg-gray-200">{{ $k }}</div>
                      @endforeach
                    </div>
                    <div class="mt-1 bg-zonakasir-primary rounded py-1.5 text-center text-[7px] font-bold text-white">Bayar</div>
                  </div>
                  {{-- Right: Order Summary --}}
                  <div class="w-2/5 bg-gray-50 rounded-lg p-2 hidden sm:block">
                    <div class="text-[6px] font-bold text-gray-700 mb-1">Order Summary</div>
                    @foreach([['Indomie x3', 'Rp 10.500'], ['Teh Botol x2', 'Rp 8.000'], ['Sampoerna x1', 'Rp 28.000']] as $oi)
                    <div class="flex justify-between py-0.5 border-b border-gray-100">
                      <span class="text-[5px] text-gray-600">{{ $oi[0] }}</span>
                      <span class="text-[5px] text-zonakasir-primary font-semibold">{{ $oi[1] }}</span>
                    </div>
                    @endforeach
                    <div class="border-t border-gray-200 mt-1 pt-1 space-y-0.5">
                      <div class="flex justify-between text-[5px]">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="text-gray-800">Rp 46.500</span>
                      </div>
                      <div class="flex justify-between text-[5px]">
                        <span class="text-gray-500">Tax 10%</span>
                        <span class="text-gray-800">Rp 4.650</span>
                      </div>
                      <div class="flex justify-between text-[6px] font-bold">
                        <span class="text-gray-900">Total</span>
                        <span class="text-zonakasir-primary">Rp 51.150</span>
                      </div>
                      <div class="flex justify-between text-[6px] font-bold">
                        <span class="text-gray-900">Change</span>
                        <span class="text-green-600">Rp 3.850</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            @endif
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- Pricing Section --}}
  <section id="harga" class="py-20 sm:py-24 lg:py-32 bg-gradient-to-b from-gray-50 to-white relative overflow-hidden">
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-zonakasir-primary/5 rounded-full blur-[150px]"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
         x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
         x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
      <div class="text-center mb-16 sm:mb-20">
        <div class="inline-flex items-center gap-2 bg-zonakasir-primary/10 rounded-full px-4 py-1.5 mb-4">
          <span class="w-1.5 h-1.5 bg-zonakasir-primary rounded-full"></span>
          <span class="text-zonakasir-primary text-sm font-semibold">Harga</span>
        </div>
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900">Pilih Paket yang Sesuai</h2>
        <p class="mt-4 text-gray-600 max-w-2xl mx-auto text-base sm:text-lg">Mulai gratis atau upgrade untuk fitur lebih lengkap.</p>
      </div>

      <div class="grid md:grid-cols-2 gap-6 lg:gap-8 max-w-4xl mx-auto">
        @foreach($prices as $index => $price)
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm hover:shadow-2xl transition-all duration-500 border {{ $index === 1 ? 'border-2 border-zonakasir-primary relative ring-4 ring-zonakasir-primary/10' : 'border-gray-100 hover:border-zonakasir-primary/20' }} relative overflow-hidden group"
             x-data="{ shown: false }" x-intersect:enter="shown = true" x-show="shown"
             x-transition:enter="transition ease-out duration-700"
             x-transition:enter-start="opacity-0 translate-y-12"
             x-transition:enter-end="opacity-100 translate-y-0"
             style="transition-delay: {{ $index * 150 }}ms">
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
