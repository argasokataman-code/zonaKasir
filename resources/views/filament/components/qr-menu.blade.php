<div class="flex flex-col items-center gap-4 py-4">
    <div class="qr-wrap bg-white p-3 rounded-xl border" style="max-width: 180px;">
        {!! QrCode::size(150)->generate($about->menuUrl()) !!}
    </div>

    <div class="text-center space-y-1">
        <p class="text-sm font-semibold">@lang('Menu digital kafe')</p>
        <p class="text-xs text-gray-500">@lang('Print & tempel QR ini di meja. Tambah ?table=NOMOR utk per-meja.')</p>
    </div>

    <div class="w-full flex gap-2">
        <input
            type="text"
            readonly
            value="{{ $about->menuUrl() }}"
            class="w-full text-xs border border-gray-300 rounded-lg px-3 py-2 bg-gray-50"
            id="menu-digital-url"
        />
        <button
            type="button"
            onclick="navigator.clipboard.writeText(document.getElementById('menu-digital-url').value); this.textContent = '{{ __('Tersalin') }}';"
            class="shrink-0 text-xs font-semibold text-white bg-primary-600 hover:bg-primary-500 rounded-lg px-4 py-2"
        >
            @lang('Salin')
        </button>
    </div>

    <div class="w-full">
        <label class="text-xs font-semibold text-gray-600 block mb-1">@lang('Per meja (opsional)')</label>
        <select
            class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white"
            onchange="var v=this.value; var base='{{ $about->menuUrl() }}'; document.getElementById('menu-digital-url').value = v ? base + '?table=' + v : base;"
        >
            <option value="">@lang('Semua meja')</option>
            @foreach ($tables as $t)
                <option value="{{ $t->number }}">@lang('Meja') {{ $t->number }}</option>
            @endforeach
        </select>
    </div>

    <p class="text-[11px] text-gray-400">
        @lang('Butuh internet utk dibuka customer')
    </p>
</div>
