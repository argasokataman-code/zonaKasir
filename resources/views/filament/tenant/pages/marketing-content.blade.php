<x-filament-panels::page>
    <div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-700 flex items-center gap-2">
        <x-heroicon-s-wifi class="h-5 w-5 shrink-0" />
        <span>{{ __('Fitur ini memerlukan koneksi internet. Posting manual ke media sosial — zonaKasir tidak auto-upload.') }}</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-filament::section icon="heroicon-m-fire" title="Menu terlaris hari ini">
            <div class="space-y-3">
                @forelse ($bestSellers as $i => $row)
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-6 h-6 shrink-0 rounded-full bg-primary-100 text-primary-700 text-xs font-bold flex items-center justify-center">
                                {{ $i + 1 }}
                            </span>
                            <span class="truncate text-sm font-medium">{{ $row['product']['name'] ?? 'Menu' }}</span>
                        </div>
                        <span class="shrink-0 text-sm font-semibold">{{ $row['total_qty'] }} porsi</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Belum ada penjualan hari ini.</p>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section icon="heroicon-m-chat-bubble-left-right" title="Caption siap post">
            <div class="space-y-4">
                <textarea
                    readonly
                    rows="4"
                    class="w-full text-sm border border-gray-300 rounded-xl p-3 bg-gray-50"
                    id="marketing-caption"
                >{{ $caption }}</textarea>

                <div class="flex gap-2">
                    <x-filament::button
                        type="button"
                        icon="heroicon-m-clipboard"
                        onclick="navigator.clipboard.writeText(document.getElementById('marketing-caption').value); this.textContent = '{{ __('Tersalin!') }}';"
                    >
                        {{ __('Salin caption') }}
                    </x-filament::button>
                </div>

                <p class="text-xs text-gray-400">
                    Butuh internet untuk posting ke media sosial. Posting manual — zonaKasir tidak auto-upload.
                </p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
