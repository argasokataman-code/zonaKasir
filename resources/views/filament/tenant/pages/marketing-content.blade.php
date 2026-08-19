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

        <x-filament::section icon="heroicon-m-chart-bar" title="Jam sepi / jam ramai (7 hari terakhir)">
            @php $max = max(array_column($peakHours, 'total')) ?: 1; @endphp
            <div class="space-y-1.5">
                @foreach ($peakHours as $h)
                    <div class="flex items-center gap-3">
                        <span class="w-10 shrink-0 text-xs text-gray-500">{{ $h['label'] }}</span>
                        <div class="flex-1 bg-gray-100 rounded-full overflow-hidden">
                            <div
                                class="h-3 rounded-full {{ $h['busy'] ? 'bg-green-500' : 'bg-gray-300' }}"
                                style="width: {{ max(4, round(($h['total'] / $max) * 100)) }}%"
                            ></div>
                        </div>
                        <span class="w-8 shrink-0 text-right text-xs font-semibold text-gray-600">{{ $h['total'] }}</span>
                    </div>
                @endforeach
            </div>
            <p class="mt-3 text-xs text-gray-400">
                Hijau = jam ramai (di atas rata-rata), abu-abu = jam sepi. Share ke IG Story buat promosi jam sepi.
            </p>
        </x-filament::section>

        <x-filament::section icon="heroicon-m-heart" title="Kartu terima kasih member">
            <div class="space-y-3">
                @forelse ($memberThanks as $row)
                    <div class="rounded-xl border border-rose-100 bg-rose-50 p-4">
                        <p class="text-sm font-semibold text-gray-800">{{ $row['name'] }}</p>
                        <p class="mt-1 text-sm text-gray-600">"{{ $row['message'] }}"</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Belum ada member berkunjung bulan ini.</p>
                @endforelse
            </div>
            <p class="mt-3 text-xs text-gray-400">
                Frekuensi kunjungan member bulan ini. Kasir bisa screenshot kartu ini buat kirim ke member via WhatsApp.
            </p>
        </x-filament::section>
    </div>
</x-filament-panels::page>
