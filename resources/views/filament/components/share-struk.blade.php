<div class="flex flex-col items-center gap-4 py-4">
    <div class="qr-wrap bg-white p-3 rounded-xl border" style="max-width: 180px;">
        {!! QrCode::size(150)->generate($record->shareUrl()) !!}
    </div>

    <div class="text-center space-y-1">
        <p class="text-sm font-semibold">@lang('Share struk digital')</p>
        <p class="text-xs text-gray-500">@lang('Scan QR atau buka link untuk lihat struk ini di HP customer')</p>
    </div>

    <div class="w-full flex gap-2">
        <input
            type="text"
            readonly
            value="{{ $record->shareUrl() }}"
            class="w-full text-xs border border-gray-300 rounded-lg px-3 py-2 bg-gray-50"
            id="share-struk-url"
        />
        <button
            type="button"
            onclick="navigator.clipboard.writeText(document.getElementById('share-struk-url').value); this.textContent = '{{ __('Tersalin') }}';"
            class="shrink-0 text-xs font-semibold text-white bg-primary-600 hover:bg-primary-500 rounded-lg px-4 py-2"
        >
            @lang('Salin')
        </button>
    </div>

    <p class="text-[11px] text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-1.5 text-center">
        <x-heroicon-s-wifi class="inline h-3 w-3 mr-1" />
        @lang('Butuh internet untuk dibuka customer')
    </p>
</div>
