<x-filament-panels::page wire:poll.5s="refresh">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($orders as $order)
            @php
                $pendingCount = $order['items']->where('kitchen_status', null)->count();
                $cookingCount = $order['items']->where('kitchen_status', 'in_progress')->count();
            @endphp
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <div>
                        <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $order['code'] }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $order['table'] ? __('Table') . ' ' . $order['table'] : __('Takeaway') }}
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($pendingCount > 0)
                            <span class="inline-flex items-center rounded-full bg-warning-500/10 px-2 py-1 text-xs font-medium text-warning-700 dark:bg-warning-500/20 dark:text-warning-400">
                                {{ $pendingCount }} {{ __('Pending') }}
                            </span>
                        @endif
                        @if($cookingCount > 0)
                            <span class="inline-flex items-center rounded-full bg-primary-500/10 px-2 py-1 text-xs font-medium text-primary-700 dark:bg-primary-500/20 dark:text-primary-400">
                                {{ $cookingCount }} {{ __('Cooking') }}
                            </span>
                        @endif
                        <div class="text-xs text-gray-400">
                            {{ \Illuminate\Support\Carbon::parse($order['created_at'])->diffForHumans() }}
                        </div>
                    </div>
                </div>

                <ul class="divide-y divide-gray-100 px-4 dark:divide-gray-700">
                    @foreach ($order['items'] as $item)
                        <li class="flex items-center justify-between py-2">
                            <div class="flex items-center gap-2">
                                @if($item['kitchen_status'] === 'in_progress')
                                    <span class="inline-flex items-center rounded-full bg-warning-500/10 px-2 py-0.5 text-xs font-medium text-warning-700 dark:bg-warning-500/20 dark:text-warning-400">
                                        🍳 {{ __('Cooking') }}
                                    </span>
                                @endif
                                <div class="font-medium text-gray-800 dark:text-gray-200">{{ $item['product'] }}</div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">x{{ $item['qty'] }}</span>
                                <div class="flex gap-1">
                                    @if($item['kitchen_status'] === null)
                                        <x-filament::button
                                            size="sm"
                                            color="warning"
                                            wire:click="startCooking({{ $item['id'] }})">
                                            {{ __('Cook') }}
                                        </x-filament::button>
                                    @endif
                                    @if($item['kitchen_status'] === 'in_progress')
                                        <x-filament::button
                                            size="sm"
                                            color="success"
                                            wire:click="markDone({{ $item['id'] }})">
                                            {{ __('Done') }}
                                        </x-filament::button>
                                    @endif
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
