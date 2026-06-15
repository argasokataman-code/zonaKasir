<x-filament-panels::page class="fi-dashboard-page">
    <div class="space-y-6">
        @if (method_exists($this, 'filtersForm'))
            {{ $this->filtersForm }}
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach ($this->getVisibleWidgets() as $widget)
                @if (class_basename($widget) === 'QuickActions')
                    <div class="md:col-span-2 xl:col-span-4">
                        @livewire($widget)
                    </div>
                @endif
            @endforeach
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($this->getVisibleWidgets() as $widget)
                @if (class_basename($widget) !== 'QuickActions')
                    <div class="widget-card" wire:key="{{ $widget }}-{{ time() }}">
                        <div wire:loading.class="opacity-50">
                            @livewire($widget)
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- Loading States -->
        <div class="hidden" wire:loading.class.remove="hidden" wire:loading.class="fixed inset-0 bg-black bg-opacity-25 z-50 flex items-center justify-center">
            <div class="bg-white rounded-lg p-8 shadow-xl">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto"></div>
                <p class="mt-4 text-gray-600">Loading dashboard data...</p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
