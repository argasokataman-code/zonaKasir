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
