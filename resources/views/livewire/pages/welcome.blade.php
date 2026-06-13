<?php

use function Livewire\Volt\layout;
use function Livewire\Volt\state;

layout('livewire.components.layouts.guest');

// Landing locale: ?lang=en|id, remembered in session, Indonesian by default
$locale = request()->query('lang');
if (! in_array($locale, ['id', 'en'], true)) {
    $locale = session('landing_locale', 'id');
}
session(['landing_locale' => $locale]);
app()->setLocale($locale);

state(['locale' => $locale]);

?>

<div class="overflow-hidden antialiased"
     x-data="{
       init() {
         const obs = new IntersectionObserver((entries) => {
           entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
         }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
         this.$el.querySelectorAll('.reveal').forEach(el => obs.observe(el));
       }
     }">

  {{-- Navbar --}}
  <nav class="fixed top-0 left-0 right-0 z-50 transition-all duration-300"
       x-data="{ scrolled: false, mobileMenu: false }"
       x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 50 }, { passive: true })"
       :class="scrolled ? 'bg-white/95 backdrop-blur shadow-lg shadow-black/5 py-1' : 'bg-transparent py-3'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-14">
        <a href="/" class="flex items-center gap-2.5">
          <img src="{{ asset('assets/logo/logo.svg') }}" class="h-8" alt="ZonaKasir">
          <span class="text-lg font-bold tracking-tight transition-colors"
                :class="scrolled ? 'text-gray-900' : 'text-white'">ZonaKasir</span>
        </a>

        <div class="hidden md:flex items-center gap-1">
          @foreach([__('landing.nav_about') => '#tentang', __('landing.nav_features') => '#fitur', __('landing.nav_menu') => '#menu', __('landing.nav_pricing') => '#harga'] as $label => $href)
          <a href="{{ $href }}" class="px-4 py-2 font-medium rounded-full transition-colors"
             :class="scrolled ? 'text-gray-600 hover:text-zonakasir-primary' : 'text-white/80 hover:text-white'">
            {{ $label }}
          </a>
          @endforeach

          {{-- Language switcher --}}
          <div class="flex items-center rounded-full p-0.5 ml-2 border"
               :class="scrolled ? 'border-gray-200 bg-gray-50' : 'border-white/20 bg-white/10'">
            <a href="?lang=id"
               class="px-2.5 py-1 rounded-full text-xs font-bold {{ $locale === 'id' ? 'bg-zonakasir-primary text-white' : 'text-gray-400' }}">ID</a>
            <a href="?lang=en"
               class="px-2.5 py-1 rounded-full text-xs font-bold {{ $locale === 'en' ? 'bg-zonakasir-primary text-white' : 'text-gray-400' }}">EN</a>
          </div>

          <a href="{{ route('auth.register') }}"
             class="ml-3 bg-zonakasir-primary text-white px-6 py-2.5 rounded-full font-medium transition-colors hover:bg-orange-600">
            {{ __('landing.nav_register') }}
          </a>
        </div>

        <div class="md:hidden flex items-center gap-2">
          <div class="flex items-center rounded-full p-0.5 border"
               :class="scrolled ? 'border-gray-200 bg-gray-50' : 'border-white/20 bg-white/10'">
            <a href="?lang=id" class="px-2 py-0.5 rounded-full text-xs font-bold {{ $locale === 'id' ? 'bg-zonakasir-primary text-white' : 'text-gray-400' }}">ID</a>
            <a href="?lang=en" class="px-2 py-0.5 rounded-full text-xs font-bold {{ $locale === 'en' ? 'bg-zonakasir-primary text-white' : 'text-gray-400' }}">EN</a>
          </div>
          <button class="w-10 h-10 flex items-center justify-center rounded-full"
                  :class="scrolled ? 'text-gray-900' : 'text-white'"
                  x-on:click="mobileMenu = !mobileMenu" aria-label="Menu">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path x-show="!mobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
              <path x-show="mobileMenu" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    {{-- Mobile menu --}}
    <div x-show="mobileMenu" x-cloak x-transition.opacity.duration.150ms
         class="md:hidden absolute top-full left-4 right-4 mt-2 bg-white shadow-2xl rounded-2xl overflow-hidden border border-gray-100">
      <div class="p-4 space-y-1">
        @foreach([__('landing.nav_about') => '#tentang', __('landing.nav_features') => '#fitur', __('landing.nav_menu') => '#menu', __('landing.nav_pricing') => '#harga'] as $label => $href)
        <a href="{{ $href }}" class="block px-4 py-3 text-gray-700 hover:text-zonakasir-primary hover:bg-zonakasir-primary/5 rounded-xl font-medium"
           x-on:click="mobileMenu = false">
          {{ $label }}
        </a>
        @endforeach
        <div class="pt-2 px-4 pb-1">
          <a href="{{ route('auth.register') }}" class="block bg-zonakasir-primary text-white text-center py-3 rounded-xl font-medium">
            {{ __('landing.nav_register') }}
          </a>
        </div>
      </div>
    </div>
  </nav>

  {{-- Hero --}}
  <section class="relative bg-gray-950 overflow-hidden">
    {{-- Static, GPU-cheap backdrop: two glows + faint grid --}}
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
      <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[700px] h-[420px] rounded-full"
           style="background: radial-gradient(closest-side, rgba(255,102,0,0.22), transparent 70%)"></div>
      <div class="absolute bottom-0 -left-40 w-[480px] h-[480px] rounded-full"
           style="background: radial-gradient(closest-side, rgba(255,102,0,0.10), transparent 70%)"></div>
      <div class="absolute inset-0 opacity-[0.04]"
           style="background-image: linear-gradient(rgba(255,255,255,.6) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.6) 1px, transparent 1px); background-size: 56px 56px; mask-image: radial-gradient(ellipse 80% 60% at 50% 35%, black, transparent)"></div>
    </div>

    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-28 sm:pt-36 w-full text-center">
      <div class="reveal inline-flex items-center gap-2 bg-white/[0.07] border border-white/10 rounded-full px-5 py-2 mb-8">
        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-400"></span>
        <span class="text-white/80 text-sm font-medium">{{ __('landing.hero_badge') }}</span>
      </div>

      <h1 class="reveal reveal-delay-1 text-4xl sm:text-6xl lg:text-7xl font-extrabold text-white leading-[1.05] tracking-tight">
        {{ __('landing.hero_title_1') }}
        <span class="block text-transparent bg-clip-text bg-gradient-to-r from-orange-400 via-zonakasir-primary to-orange-600">{{ __('landing.hero_title_2') }}</span>
        <span class="block text-white/90">{{ __('landing.hero_title_3') }}</span>
      </h1>

      <p class="reveal reveal-delay-2 mt-7 text-base sm:text-lg lg:text-xl text-gray-400 max-w-2xl mx-auto leading-relaxed">
        {{ __('landing.hero_subtitle') }}
      </p>

      <div class="reveal reveal-delay-3 mt-10 flex flex-col sm:flex-row gap-4 justify-center">
        <a href="{{ route('auth.register') }}"
           class="group bg-gradient-to-r from-zonakasir-primary to-orange-600 text-white px-9 py-4 rounded-2xl font-semibold text-lg shadow-xl shadow-orange-600/25 transition-all hover:shadow-2xl hover:shadow-orange-600/40 hover:brightness-110 flex items-center justify-center gap-3">
          <span>{{ __('landing.hero_cta') }}</span>
          <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
          </svg>
        </a>
        <a href="#fitur"
           class="border border-white/15 bg-white/[0.04] text-white px-9 py-4 rounded-2xl font-semibold text-lg transition-colors hover:bg-white/10 flex items-center justify-center gap-3">
          <span>{{ __('landing.hero_see_features') }}</span>
          <svg class="w-5 h-5 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
          </svg>
        </a>
      </div>

      {{-- Glass info chips --}}
      <div class="reveal reveal-delay-4 mt-12 flex flex-wrap justify-center gap-3 sm:gap-4">
        @foreach([
          ['t' => __('landing.stat_cloud'), 's' => __('landing.stat_cloud_sub'), 'icon' => 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z'],
          ['t' => __('landing.stat_realtime'), 's' => __('landing.stat_realtime_sub'), 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
          ['t' => __('landing.stat_multi') . ' ' . __('landing.stat_multi_sub'), 's' => 'Web · Android', 'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
        ] as $chip)
        <div class="flex items-center gap-3 bg-white/[0.05] border border-white/10 rounded-2xl px-5 py-3 text-left">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-orange-500/30 to-orange-500/5 border border-orange-500/20 flex items-center justify-center">
            <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $chip['icon'] }}"/>
            </svg>
          </div>
          <div>
            <p class="text-white font-bold text-sm leading-tight whitespace-nowrap">{{ $chip['t'] }}</p>
            <p class="text-gray-500 text-xs leading-tight whitespace-nowrap">{{ $chip['s'] }}</p>
          </div>
        </div>
        @endforeach
      </div>

      {{-- App preview folded into the hero --}}
      <div class="relative mt-14 sm:mt-20 pb-16 sm:pb-24">
        <div class="absolute -inset-x-8 bottom-0 top-1/3 rounded-[3rem]"
             style="background: radial-gradient(closest-side, rgba(255,102,0,0.12), transparent 75%)" aria-hidden="true"></div>
        <div class="relative w-full max-w-3xl mx-auto">
          <div class="relative bg-gray-800 rounded-t-2xl p-2 sm:p-3 pb-0 shadow-2xl ring-1 ring-white/10">
            <div class="bg-white rounded-t-xl overflow-hidden aspect-[16/10]">
              <div class="bg-gray-100 px-3 py-1.5 flex items-center gap-2 border-b border-gray-200">
                <div class="flex gap-1">
                  <div class="w-2.5 h-2.5 rounded-full bg-red-400"></div>
                  <div class="w-2.5 h-2.5 rounded-full bg-yellow-400"></div>
                  <div class="w-2.5 h-2.5 rounded-full bg-green-400"></div>
                </div>
                <div class="flex-1 bg-white rounded h-5 ml-2 flex items-center px-2 border border-gray-200">
                  <span class="text-[9px] text-gray-400">toko-anda.zonakasir.com/member/cashier</span>
                </div>
              </div>
              <div class="flex h-[calc(100%-2.25rem)] bg-gray-50">
                <div class="w-2/3 p-2">
                  <div class="bg-white rounded-lg border border-gray-200 h-6 flex items-center px-2 mb-2">
                    <svg class="w-2.5 h-2.5 text-gray-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span class="text-[8px] text-gray-400">Search (SKU, name, barcode)</span>
                  </div>
                  <div class="grid grid-cols-3 gap-1.5">
                    @foreach([
                      ['name' => 'Indomie Goreng', 'price' => 'Rp 3.500', 'bg' => 'from-yellow-300 to-orange-300', 'icon' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 8h18M8 8V5M16 8V5"/><line x1="7" y1="11" x2="7" y2="15"/><line x1="10" y1="11" x2="14" y2="15"/><circle cx="17" cy="13" r="2"/>'],
                      ['name' => 'Teh Botol', 'price' => 'Rp 4.000', 'bg' => 'from-green-300 to-emerald-400', 'icon' => '<rect x="7" y="2" width="10" height="18" rx="3"/><rect x="9" y="4" width="6" height="4" rx="1"/><circle cx="12" cy="11" r="3" fill="white" opacity="0.35"/>'],
                      ['name' => 'Kopi Susu', 'price' => 'Rp 18.000', 'bg' => 'from-amber-300 to-orange-400', 'icon' => '<path d="M5 3h3l2 4H5z"/><rect x="8" y="7" width="8" height="9" rx="2"/><path d="M8 8a4 5 0 0 0 8 0"/><rect x="7" y="6" width="10" height="2" rx="1"/>'],
                      ['name' => 'Le Minerale', 'price' => 'Rp 4.500', 'bg' => 'from-blue-300 to-cyan-400', 'icon' => '<rect x="7" y="2" width="10" height="15" rx="3"/><path d="M9 6l3-3 3 3"/><path d="M9 9l3-3 3 3"/><circle cx="12" cy="12" r="2" fill="white" opacity="0.35"/>'],
                      ['name' => 'Pocari Sweat', 'price' => 'Rp 7.000', 'bg' => 'from-cyan-300 to-sky-400', 'icon' => '<rect x="6" y="3" width="12" height="16" rx="2"/><rect x="8" y="5" width="8" height="3" rx="1"/><rect x="8" y="10" width="8" height="2" rx="0.5" fill="white" opacity="0.3"/><rect x="8" y="13" width="5" height="2" rx="0.5" fill="white" opacity="0.2"/>'],
                      ['name' => 'Chitato 68g', 'price' => 'Rp 12.500', 'bg' => 'from-purple-300 to-violet-400', 'icon' => '<rect x="3" y="4" width="18" height="16" rx="2"/><rect x="3" y="4" width="18" height="5" rx="2"/><circle cx="12" cy="12" r="4" fill="white" opacity="0.3"/><circle cx="10" cy="10" r="1.5" fill="white" opacity="0.4"/>'],
                    ] as $p)
                    <div class="bg-white rounded-lg border border-gray-100 overflow-hidden shadow-sm">
                      <div class="bg-gradient-to-br {{ $p['bg'] }} h-9 sm:h-11 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white/80" fill="none" stroke="white" stroke-width="1.5" viewBox="0 0 24 24">{!! $p['icon'] !!}</svg>
                      </div>
                      <div class="p-1.5">
                        <div class="text-[8px] font-bold text-zonakasir-primary">{{ $p['price'] }}</div>
                        <div class="text-[7px] font-semibold text-gray-800 leading-tight mt-0.5">{{ $p['name'] }}</div>
                      </div>
                    </div>
                    @endforeach
                  </div>
                </div>
                <div class="w-1/3 bg-white border-l border-gray-200 flex flex-col">
                  <div class="px-2 pt-2 pb-1 border-b border-gray-100">
                    <div class="text-[10px] font-semibold text-gray-800">Orders details</div>
                  </div>
                  <div class="flex-1 px-2 py-1 space-y-1">
                    @foreach([
                      ['name' => 'Indomie Goreng ×3', 'price' => 'Rp 10.500'],
                      ['name' => 'Teh Botol ×2', 'price' => 'Rp 8.000'],
                      ['name' => 'Kopi Susu ×1', 'price' => 'Rp 18.000'],
                    ] as $cart)
                    <div class="border border-gray-100 rounded-md p-1.5">
                      <div class="flex justify-between">
                        <span class="text-[7px] font-semibold text-gray-800">{{ $cart['name'] }}</span>
                        <span class="text-[7px] font-semibold text-zonakasir-primary">{{ $cart['price'] }}</span>
                      </div>
                    </div>
                    @endforeach
                  </div>
                  <div class="px-2 py-1.5 border-t border-gray-100 bg-gray-50">
                    <div class="flex justify-between text-[8px]">
                      <span class="font-bold text-gray-800">Total</span>
                      <span class="font-bold text-zonakasir-primary">Rp 36.500</span>
                    </div>
                  </div>
                  <div class="px-2 pb-2 pt-1">
                    <div class="bg-zonakasir-primary rounded-md py-1.5 text-center text-[8px] font-bold text-white">
                      Proceed to payment
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-700 h-3 sm:h-4 rounded-b-2xl mx-8 sm:mx-12"></div>
        </div>
        <p class="relative mt-6 text-center">
          <span class="inline-flex items-center gap-2 text-sm font-medium text-white/60">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            {{ __('landing.mockup_badge') }}
          </span>
        </p>
      </div>
    </div>
  </section>

  {{-- About --}}
  <section id="tentang" class="scroll-mt-20 py-16 sm:py-24 bg-white reveal">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid lg:grid-cols-2 gap-10 lg:gap-20 items-center">
        <div>
          <div class="inline-flex items-center gap-2 bg-zonakasir-primary/10 rounded-full px-4 py-1.5 mb-4">
            <span class="w-1.5 h-1.5 bg-zonakasir-primary rounded-full"></span>
            <span class="text-zonakasir-primary text-sm font-semibold">{{ __('landing.about_badge') }}</span>
          </div>
          <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900 leading-tight">
            {{ __('landing.about_title_1') }}
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-zonakasir-primary to-orange-500">{{ __('landing.about_title_2') }}</span>
            {{ __('landing.about_title_3') }}
          </h2>
          <p class="mt-6 text-gray-600 text-base sm:text-lg leading-relaxed">
            {{ __('landing.about_text') }}
          </p>
          <div class="mt-8 grid grid-cols-2 gap-4">
            <div class="flex items-center gap-3 p-3 rounded-2xl">
              <div class="w-12 h-12 bg-gradient-to-br from-zonakasir-primary/10 to-orange-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-zonakasir-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
              </div>
              <div>
                <p class="font-bold text-gray-900">{{ __('landing.about_fast') }}</p>
                <p class="text-xs sm:text-sm text-gray-500">{{ __('landing.about_fast_sub') }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3 p-3 rounded-2xl">
              <div class="w-12 h-12 bg-gradient-to-br from-zonakasir-primary/10 to-orange-50 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-zonakasir-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
              </div>
              <div>
                <p class="font-bold text-gray-900">{{ __('landing.about_safe') }}</p>
                <p class="text-xs sm:text-sm text-gray-500">{{ __('landing.about_safe_sub') }}</p>
              </div>
            </div>
          </div>
        </div>

        {{-- Dashboard mockup (static) --}}
        <div class="relative">
          <div class="absolute inset-0 bg-gradient-to-br from-zonakasir-primary/10 to-orange-100 rounded-3xl"></div>
          <div class="relative rounded-3xl shadow-2xl w-full bg-gray-50 overflow-hidden aspect-[4/3] border border-gray-200">
            <div class="flex h-full">
              <div class="hidden sm:flex w-[22%] bg-white border-r border-gray-200 flex-col py-2 px-1.5">
                <div class="flex items-center gap-1.5 px-1.5 py-1 mb-3">
                  <div class="w-5 h-5 bg-zonakasir-primary rounded-md flex items-center justify-center">
                    <span class="text-white text-[6px] font-bold">ZK</span>
                  </div>
                  <span class="text-gray-900 text-[8px] font-bold">ZonaKasir</span>
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
              <div class="flex-1 p-2.5">
                <div class="text-[10px] font-bold text-gray-900 mb-2">Dashboard</div>
                <div class="grid grid-cols-3 gap-1.5 mb-2">
                  <div class="bg-white rounded-xl p-2 border border-gray-100 shadow-sm">
                    <div class="text-[5px] text-gray-400 mb-0.5">Today Total Revenue</div>
                    <div class="text-[10px] font-bold text-gray-900">2.5M</div>
                  </div>
                  <div class="bg-white rounded-xl p-2 border border-gray-100 shadow-sm">
                    <div class="text-[5px] text-gray-400 mb-0.5">Sales Today</div>
                    <div class="text-[10px] font-bold text-gray-900">128</div>
                  </div>
                  <div class="bg-white rounded-xl p-2 border border-gray-100 shadow-sm">
                    <div class="text-[5px] text-gray-400 mb-0.5">Discount Today</div>
                    <div class="text-[10px] font-bold text-gray-900">125K</div>
                  </div>
                </div>
                <div class="bg-white rounded-xl p-2 border border-gray-100 shadow-sm">
                  <div class="flex items-end gap-1 h-24">
                    @foreach([30, 45, 35, 60, 42, 75, 55] as $i => $h)
                    <div class="flex-1 h-full flex flex-col justify-end">
                      <div class="w-full rounded-t {{ $i === 5 ? 'bg-zonakasir-primary' : 'bg-zonakasir-primary/20' }}" style="height: {{ $h }}%"></div>
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

  {{-- Features --}}
  <section id="fitur" class="scroll-mt-20 py-16 sm:py-24 bg-gray-50 reveal">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12 sm:mb-16">
        <div class="inline-flex items-center gap-2 bg-zonakasir-primary/10 rounded-full px-4 py-1.5 mb-4">
          <span class="w-1.5 h-1.5 bg-zonakasir-primary rounded-full"></span>
          <span class="text-zonakasir-primary text-sm font-semibold">{{ __('landing.features_badge') }}</span>
        </div>
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900">{{ __('landing.features_title') }}</h2>
        <p class="mt-4 text-gray-600 max-w-2xl mx-auto text-base sm:text-lg">{{ __('landing.features_subtitle') }}</p>
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5 lg:gap-7">
        @foreach([
          ['title' => __('landing.feature_cloud_title'), 'desc' => __('landing.feature_cloud_desc'), 'icon' => 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z'],
          ['title' => __('landing.feature_platform_title'), 'desc' => __('landing.feature_platform_desc'), 'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
          ['title' => __('landing.feature_report_title'), 'desc' => __('landing.feature_report_desc'), 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
          ['title' => __('landing.feature_support_title'), 'desc' => __('landing.feature_support_desc'), 'icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z'],
        ] as $feature)
        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-sm border border-gray-100 transition-all hover:shadow-xl hover:-translate-y-1 hover:border-orange-200">
          <div class="w-14 h-14 bg-gradient-to-br from-zonakasir-primary to-orange-500 rounded-2xl flex items-center justify-center mb-5 shadow-lg shadow-orange-500/25">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $feature['icon'] }}"/>
            </svg>
          </div>
          <h3 class="text-lg font-bold text-gray-900">{{ $feature['title'] }}</h3>
          <p class="mt-2 text-sm text-gray-600 leading-relaxed">{{ $feature['desc'] }}</p>
        </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- Modules --}}
  <section id="menu" class="scroll-mt-20 py-16 sm:py-24 bg-white reveal">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12 sm:mb-16">
        <div class="inline-flex items-center gap-2 bg-zonakasir-primary/10 rounded-full px-4 py-1.5 mb-4">
          <span class="w-1.5 h-1.5 bg-zonakasir-primary rounded-full"></span>
          <span class="text-zonakasir-primary text-sm font-semibold">{{ __('landing.menu_badge') }}</span>
        </div>
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900">{{ __('landing.menu_title') }}</h2>
      </div>

      <div class="grid md:grid-cols-3 gap-6 lg:gap-8">
        @foreach([
          ['title' => __('landing.menu_analysis_title'), 'desc' => __('landing.menu_analysis_desc'), 'icon' => 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V5a2 2 0 00-2-2H6a2 2 0 00-2 2v14a2 2 0 002 2z'],
          ['title' => __('landing.menu_stock_title'), 'desc' => __('landing.menu_stock_desc'), 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
          ['title' => __('landing.menu_cashier_title'), 'desc' => __('landing.menu_cashier_desc'), 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
        ] as $m)
        <div class="bg-gray-50 rounded-3xl p-7 sm:p-8 border border-gray-100 transition-all hover:bg-white hover:shadow-xl">
          <div class="w-14 h-14 bg-zonakasir-primary rounded-2xl flex items-center justify-center mb-5 shadow-lg shadow-orange-500/20">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $m['icon'] }}"/>
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-900">{{ $m['title'] }}</h3>
          <p class="mt-3 text-gray-600 leading-relaxed">{{ $m['desc'] }}</p>
        </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- Pricing --}}
  <section id="harga" class="scroll-mt-20 py-16 sm:py-24 bg-gray-50 reveal">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12 sm:mb-16">
        <div class="inline-flex items-center gap-2 bg-zonakasir-primary/10 rounded-full px-4 py-1.5 mb-4">
          <span class="w-1.5 h-1.5 bg-zonakasir-primary rounded-full"></span>
          <span class="text-zonakasir-primary text-sm font-semibold">{{ __('landing.pricing_badge') }}</span>
        </div>
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900">{{ __('landing.pricing_title') }}</h2>
        <p class="mt-4 text-gray-600 max-w-2xl mx-auto text-base sm:text-lg">{{ __('landing.pricing_subtitle') }}</p>
      </div>

      <div x-data="{ annual: false }">
      {{-- Billing toggle --}}
      <div class="flex justify-center mb-10">
        <div class="inline-flex items-center bg-white border border-gray-200 rounded-full p-1 shadow-sm">
          <button type="button" x-on:click="annual = false"
                  class="px-5 py-2 rounded-full text-sm font-semibold transition-colors"
                  :class="annual ? 'text-gray-500' : 'bg-zonakasir-primary text-white'">
            {{ __('landing.billing_monthly') }}
          </button>
          <button type="button" x-on:click="annual = true"
                  class="px-5 py-2 rounded-full text-sm font-semibold transition-colors flex items-center gap-2"
                  :class="annual ? 'bg-zonakasir-primary text-white' : 'text-gray-500'">
            {{ __('landing.billing_annual') }}
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                  :class="annual ? 'bg-white/20 text-white' : 'bg-green-100 text-green-700'">{{ __('landing.save_two_months') }}</span>
          </button>
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-6 lg:gap-8 items-stretch">
        {{-- Starter --}}
        <div class="bg-white rounded-3xl p-7 sm:p-9 border border-gray-200 flex flex-col">
          <h3 class="text-xl font-bold text-gray-900">{{ __('landing.plan_starter') }}</h3>
          <p class="mt-2 text-sm text-gray-500">{{ __('landing.plan_starter_desc') }}</p>
          <div class="mt-5 flex items-baseline gap-1" x-show="!annual">
            <span class="text-4xl font-extrabold text-gray-900">IDR 25.000</span>
            <span class="text-gray-400 font-medium">{{ __('landing.price_month') }}</span>
          </div>
          <div class="mt-5 flex items-baseline gap-1" x-show="annual" x-cloak>
            <span class="text-4xl font-extrabold text-gray-900">IDR 249.000</span>
            <span class="text-gray-400 font-medium">{{ __('landing.price_year') }}</span>
          </div>
          <ul class="mt-7 space-y-3 flex-1">
            @foreach([__('landing.plan_starter_1'), __('landing.plan_starter_2'), __('landing.plan_starter_3'), __('landing.plan_starter_4'), __('landing.plan_starter_5')] as $inc)
            <li class="flex items-center gap-3 text-sm text-gray-700">
              <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              {{ $inc }}
            </li>
            @endforeach
          </ul>
          <a href="{{ route('auth.register') }}" class="mt-8 block text-center border-2 border-zonakasir-primary text-zonakasir-primary py-3.5 rounded-2xl font-semibold transition-colors hover:bg-zonakasir-primary hover:text-white">
            {{ __('landing.plan_cta') }}
          </a>
        </div>

        {{-- Business --}}
        <div class="relative bg-gray-900 rounded-3xl p-7 sm:p-9 flex flex-col shadow-2xl">
          <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-zonakasir-primary text-white text-xs font-bold px-4 py-1.5 rounded-full whitespace-nowrap">{{ __('landing.plan_popular') }}</span>
          <h3 class="text-xl font-bold text-white">{{ __('landing.plan_business') }}</h3>
          <p class="mt-2 text-sm text-gray-400">{{ __('landing.plan_business_desc') }}</p>
          <div class="mt-5 flex items-baseline gap-1" x-show="!annual">
            <span class="text-4xl font-extrabold text-white">IDR 49.000</span>
            <span class="text-gray-500 font-medium">{{ __('landing.price_month') }}</span>
          </div>
          <div class="mt-5 flex items-baseline gap-1" x-show="annual" x-cloak>
            <span class="text-4xl font-extrabold text-white">IDR 490.000</span>
            <span class="text-gray-500 font-medium">{{ __('landing.price_year') }}</span>
          </div>
          <ul class="mt-7 space-y-3 flex-1">
            @foreach([__('landing.plan_business_1'), __('landing.plan_business_2'), __('landing.plan_business_3'), __('landing.plan_business_4'), __('landing.plan_business_5')] as $inc)
            <li class="flex items-center gap-3 text-sm text-gray-300">
              <svg class="w-5 h-5 text-zonakasir-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              {{ $inc }}
            </li>
            @endforeach
          </ul>
          <a href="{{ route('auth.register') }}" class="mt-8 block text-center bg-zonakasir-primary text-white py-3.5 rounded-2xl font-semibold transition-colors hover:bg-orange-600">
            {{ __('landing.plan_cta') }}
          </a>
        </div>
      </div>
      </div>
    </div>
  </section>

  {{-- FAQ --}}
  <section id="faq" class="scroll-mt-20 py-16 sm:py-24 bg-white reveal">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12 sm:mb-16">
        <div class="inline-flex items-center gap-2 bg-zonakasir-primary/10 rounded-full px-4 py-1.5 mb-4">
          <span class="w-1.5 h-1.5 bg-zonakasir-primary rounded-full"></span>
          <span class="text-zonakasir-primary text-sm font-semibold">{{ __('landing.faq_badge') }}</span>
        </div>
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900">{{ __('landing.faq_title') }}</h2>
        <p class="mt-4 text-gray-600 max-w-2xl mx-auto text-base sm:text-lg">{{ __('landing.faq_subtitle') }}</p>
      </div>

      <div class="space-y-3" x-data="{ open: null }">
        @foreach([
          ['q' => __('landing.faq_q1'), 'a' => __('landing.faq_a1')],
          ['q' => __('landing.faq_q2'), 'a' => __('landing.faq_a2')],
          ['q' => __('landing.faq_q3'), 'a' => __('landing.faq_a3')],
          ['q' => __('landing.faq_q4'), 'a' => __('landing.faq_a4')],
          ['q' => __('landing.faq_q5'), 'a' => __('landing.faq_a5')],
          ['q' => __('landing.faq_q6'), 'a' => __('landing.faq_a6')],
        ] as $i => $faq)
        <div class="border border-gray-200 rounded-2xl overflow-hidden transition-colors"
             :class="open === {{ $i }} ? 'border-zonakasir-primary/30 bg-orange-50/30' : 'bg-white hover:border-gray-300'">
          <button type="button" x-on:click="open = open === {{ $i }} ? null : {{ $i }}"
                  class="w-full flex items-center justify-between px-6 py-5 text-left">
            <span class="text-base sm:text-lg font-semibold text-gray-900 pr-4">{{ $faq['q'] }}</span>
            <span class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300"
                  :class="open === {{ $i }} ? 'bg-zonakasir-primary text-white rotate-180' : 'bg-gray-100 text-gray-500'">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </span>
          </button>
          <div x-show="open === {{ $i }}"
               x-transition:enter="transition ease-out duration-300"
               x-transition:enter-start="opacity-0 -translate-y-2"
               x-transition:enter-end="opacity-100 translate-y-0"
               x-transition:leave="transition ease-in duration-200"
               x-transition:leave-start="opacity-100 translate-y-0"
               x-transition:leave-end="opacity-0 -translate-y-2"
               x-cloak>
            <div class="px-6 pb-5 text-gray-600 leading-relaxed text-sm sm:text-base">{{ $faq['a'] }}</div>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- CTA --}}
  <section class="py-16 sm:py-24 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 reveal">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white leading-tight">{{ __('landing.cta_title') }}</h2>
      <p class="mt-5 text-gray-400 text-base sm:text-lg">{{ __('landing.cta_subtitle') }}</p>
      <a href="{{ route('auth.register') }}"
         class="mt-9 inline-flex items-center gap-3 bg-zonakasir-primary text-white px-9 py-4 rounded-2xl font-semibold text-lg transition-colors hover:bg-orange-600">
        <span>{{ __('landing.cta_button') }}</span>
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
        </svg>
      </a>
    </div>
  </section>

  {{-- Footer --}}
  <footer class="bg-gray-900 pt-14 sm:pt-18 pb-8 border-t border-white/5 reveal">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-10 lg:gap-14">
        {{-- Brand --}}
        <div class="sm:col-span-2 lg:col-span-1">
          <div class="flex items-center gap-2.5 mb-4">
            <img src="{{ asset('assets/logo/logo.svg') }}" class="h-8" alt="ZonaKasir">
            <span class="text-white font-bold text-lg">ZonaKasir</span>
          </div>
          <p class="text-gray-400 text-sm leading-relaxed mb-5">{{ __('landing.footer_desc') }}</p>
          <div class="flex items-center gap-3">
            @foreach(['whatsapp', 'email'] as $contact)
            <a href="#" class="w-9 h-9 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-gray-400 hover:bg-zonakasir-primary hover:text-white hover:border-zonakasir-primary transition-colors">
              @if($contact === 'whatsapp')
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
              @else
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              @endif
            </a>
            @endforeach
          </div>
        </div>

        {{-- Links --}}
        <div>
          <h4 class="text-white font-semibold mb-4 text-sm">{{ __('landing.footer_links_title') }}</h4>
          <ul class="space-y-2.5">
            @foreach([
              __('landing.nav_about') => '#tentang',
              __('landing.nav_features') => '#fitur',
              __('landing.nav_menu') => '#menu',
              __('landing.nav_pricing') => '#harga',
              __('landing.faq_badge') => '#faq',
            ] as $label => $href)
            <li><a href="{{ $href }}" class="text-gray-400 hover:text-white text-sm transition-colors">{{ $label }}</a></li>
            @endforeach
          </ul>
        </div>

        {{-- Contact --}}
        <div>
          <h4 class="text-white font-semibold mb-4 text-sm">{{ __('landing.footer_contact_title') }}</h4>
          <ul class="space-y-2.5">
            <li class="flex items-center gap-2.5 text-gray-400 text-sm">
              <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              zonakasirapp@gmail.com
            </li>
            <li class="flex items-center gap-2.5 text-gray-400 text-sm">
              <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              Yogyakarta, Indonesia
            </li>
            <li class="flex items-center gap-2.5 text-gray-400 text-sm">
              <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              {{ __('landing.footer_hours') }}
            </li>
          </ul>
        </div>

        {{-- Legal --}}
        <div>
          <h4 class="text-white font-semibold mb-4 text-sm">{{ __('landing.footer_legal_title') }}</h4>
          <ul class="space-y-2.5">
            <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">{{ __('landing.footer_privacy') }}</a></li>
            <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">{{ __('landing.footer_terms') }}</a></li>
            <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">{{ __('landing.footer_refund') }}</a></li>
          </ul>
        </div>
      </div>

      {{-- Bottom bar --}}
      <div class="mt-12 pt-6 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-3">
        <p class="text-gray-600 text-sm">© {{ date('Y') }} ZonaKasir. {{ __('landing.footer_rights') }}</p>
        <p class="text-gray-600 text-xs">{{ __('landing.footer_made_with') }} &hearts; {{ __('landing.footer_made_in') }}</p>
      </div>
    </div>
  </footer>
</div>
