<x-filament-panels::page wire:poll.5s="refresh">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($orders as $order)
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <div>
                        <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $order['code'] }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $order['table'] ? __('Table') . ' ' . $order['table'] : __('Takeaway') }}
                        </div>
                    </div>
                    <div class="text-xs text-gray-400">
                        {{ \Illuminate\Support\Carbon::parse($order['created_at'])->diffForHumans() }}
                    </div>
                </div>

                <ul class="divide-y divide-gray-100 px-4 dark:divide-gray-700">
                    @foreach ($order['items'] as $item)
                        <li class="flex items-center justify-between py-2">
                            <div>
                                <div class="font-medium text-gray-800 dark:text-gray-200">{{ $item['product'] }}</div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">x{{ $item['qty'] }}</span>
                                <div class="flex gap-1">
                                    <x-filament::button
                                        size="sm"
                                        color="warning"
                                        wire:click="startCooking({{ $item['id'] }})">
                                        {{ __('Cook') }}
                                    </x-filament::button>
                                    <x-filament::button
                                        size="sm"
                                        color="success"
                                        wire:click="markDone({{ $item['id'] }})">
                                        {{ __('Done') }}
                                    </x-filament::button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-gray-300 p-10 text-center text-gray-500 dark:border-gray-600">
                {{ __('No kitchen orders right now') }}
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
