<div class="min-h-screen bg-[#F4F4F2] flex items-center justify-center p-4 sm:p-6 relative overflow-hidden">
  {{-- Background wallpaper from landing page --}}
  <div class="absolute inset-0 z-0" style="background: linear-gradient(to bottom, transparent 0%, rgba(244,244,242,0.6) 50%, #F4F4F2 100%), url('/images/landing/retail_hero_bg_1781378962689.jpg') center/cover no-repeat; opacity: 0.4; filter: grayscale(1) contrast(1.25);"></div>

  <div class="w-full max-w-lg relative z-10">
    {{-- Brand Header --}}
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-14 h-14 rounded-xl bg-[#1A1A1A] shadow-sm mb-4">
        <span class="text-white font-black text-lg">ZK</span>
      </div>
      <h1 class="text-2xl font-bold text-[#1A1A1A] tracking-tight">Daftar ZonaKasir</h1>
      <p class="text-sm text-[#666666] mt-1.5 font-medium">Mulai uji coba gratis 7 hari. Tanpa kartu kredit.</p>
    </div>

    {{-- Form Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-[#E5E5E1] p-6 sm:p-8">
      <form>
        {{ $this->form }}

  </div>

  @if(config('turnstile.enabled') && config('turnstile.site_key'))
          <div class="mt-6 flex justify-center">
            <div class="cf-turnstile" data-sitekey="{{ config('turnstile.site_key') }}" data-callback="onTurnstileSuccess"></div>
          </div>
          @error('turnstile')
            <p class="mt-2 text-sm text-red-600 text-center font-medium">{{ $message }}</p>
          @enderror
        @endif
      </form>
      <x-filament-actions::modals />

      {{-- Divider --}}
      <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
          <div class="w-full border-t border-[#E5E5E1]"></div>
        </div>
        <div class="relative flex justify-center text-sm">
          <span class="px-3 bg-white text-[#888888] font-medium">{{ __('atau') }}</span>
        </div>
      </div>

      {{-- Google Sign In --}}
      <a href="{{ route('google.redirect') }}"
         class="flex items-center justify-center w-full px-4 py-2.5 text-sm font-semibold text-[#1A1A1A] bg-white border border-[#E5E5E1] rounded-lg hover:bg-[#F4F4F2] transition-colors shadow-sm">
        <svg class="w-5 h-5 mr-2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
          <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
          <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
          <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        Lanjutkan dengan Google
      </a>
    </div>

    {{-- Footer --}}
    <p class="text-center text-xs text-[#888888] mt-6 font-medium">
      Dengan mendaftar, Anda menyetujui
      <a href="#" class="text-[#1A1A1A] hover:underline font-semibold">Syarat & Ketentuan</a>
      dan
      <a href="#" class="text-[#1A1A1A] hover:underline font-semibold">Kebijakan Privasi</a>
    </p>
  </div>

  @if(config('turnstile.enabled') && config('turnstile.site_key'))
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <script>
      function onTurnstileSuccess(token) {
        var el = document.querySelector('[wire\\\\:id]');
        if (el) {
          Livewire.find(el.getAttribute('wire:id')).set('turnstileToken', token);
        }
      }
    </script>
  @endif
</div>
