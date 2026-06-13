<div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center p-4">
  <div class="w-full max-w-md">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      {{-- Header --}}
      <div class="bg-gradient-to-r from-zonakasir-primary to-orange-600 px-6 py-8 text-center">
        <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
          </svg>
        </div>
        <h2 class="text-xl font-bold text-white">License Required</h2>
        @if($this->license)
          <p class="text-white/70 text-sm mt-1">
            @if($this->license->plan === 'trial')
              Your {{ $this->license->daysLeft() }} day free trial has ended.
            @else
              Your {{ $this->license->plan }} license has expired.
            @endif
          </p>
        @else
          <p class="text-white/70 text-sm mt-1">No active license found for this store.</p>
        @endif
      </div>

      {{-- Body --}}
      <div class="p-6">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-5">
          Enter a license key to activate or extend your subscription. Contact us via WhatsApp to purchase a license.
        </p>

        <form wire:submit="activate">
          {{ $this->form }}

          <div class="mt-5 space-y-3">
            <button type="submit"
              class="w-full bg-zonakasir-primary text-white py-3 rounded-xl font-semibold hover:bg-orange-600 transition-colors">
              Activate License
            </button>
            <a href="https://wa.me/6281234567890" target="_blank" rel="noopener"
              class="w-full flex items-center justify-center gap-2 bg-green-500 text-white py-3 rounded-xl font-semibold hover:bg-green-600 transition-colors">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347"/></svg>
              Chat via WhatsApp
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
