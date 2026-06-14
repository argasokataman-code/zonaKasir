<div>
  <div class="max-w-2xl mx-auto grid grid-cols-1 justify-center items-center h-screen">
    <div class="space-y-6">
      <div class="flex justify-center items-center">
        <img src="{{ asset('assets/logo/logo.svg') }}" class="w-20 h-24" alt="Logo">
      </div>
      <div class="w-full">
        <form>
          {{ $this->form }}

          @if(config('turnstile.enabled') && config('turnstile.site_key'))
            <div class="mt-4 flex justify-center">
              <div class="cf-turnstile" data-sitekey="{{ config('turnstile.site_key') }}" data-callback="onTurnstileSuccess"></div>
            </div>
            @error('turnstile')
              <p class="mt-1 text-sm text-red-600 text-center">{{ $message }}</p>
            @enderror
          @endif

        </form>
        <x-filament-actions::modals />

        <div class="mt-4">
          <div class="relative">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-2 bg-white text-gray-500">or</span>
            </div>
          </div>
          <div class="mt-4">
            <a href="{{ route('google.redirect') }}"
               class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
              <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
              </svg>
              Sign up with Google
            </a>
          </div>
        </div>
      </div>
    </div>
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
