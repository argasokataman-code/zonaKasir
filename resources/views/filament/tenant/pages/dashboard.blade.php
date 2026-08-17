<x-filament-panels::page class="fi-dashboard-page">
    <div class="space-y-6">
        @if (method_exists($this, 'filtersForm'))
            {{ $this->filtersForm }}
        @endif

        {{-- Quick Actions --}}
        @livewire(\App\Filament\Tenant\Widgets\QuickActions::class)

        {{-- Revenue + Balance --}}
        <div class="grid grid-cols-1 gap-4">
            @livewire(\App\Filament\Tenant\Resources\SellingResource\Widgets\SellingOverview::class)
            @livewire(\App\Filament\Tenant\Widgets\BalanceWidget::class)
        </div>

        {{-- Transaction Stats --}}
        @livewire(\App\Filament\Tenant\Widgets\TransactionStats::class)

        {{-- Charts --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2">
                @livewire(\App\Filament\Tenant\Widgets\SalesChart::class)
            </div>
            <div>
                @livewire(\App\Filament\Tenant\Widgets\PaymentMethodChart::class)
            </div>
        </div>

        {{-- Inventory + Best Selling --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div>
                @livewire(\App\Filament\Tenant\Widgets\InventoryStats::class)
            </div>
            <div>
                @livewire(\App\Filament\Tenant\Widgets\TodaysBestSellingProduct::class)
            </div>
        </div>

        {{-- Low Stock --}}
        @livewire(\App\Filament\Tenant\Widgets\LowStockProducts::class)
    </div>
</x-filament-panels::page>
