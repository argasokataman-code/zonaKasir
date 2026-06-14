<x-filament::section>
  <x-slot name="heading">
    <span class="flex items-center gap-2">
      <x-heroicon-o-bolt class="h-5 w-5" />
      {{ __('Quick Actions') }}
    </span>
  </x-slot>

  <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <a href="{{ \App\Filament\Tenant\Pages\Cashier::getUrl() }}"
       class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-primary-500 hover:shadow-md transition-all duration-200 group">
      <div class="p-3 rounded-lg bg-primary-50 dark:bg-primary-900/20 group-hover:bg-primary-100 dark:group-hover:bg-primary-800/30 transition-colors">
        <x-heroicon-o-calculator class="h-6 w-6 text-primary-600 dark:text-primary-400" />
      </div>
      <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
        {{ __('Open Cashier') }}
      </span>
    </a>

    <a href="{{ \App\Filament\Tenant\Resources\SellingResource::getUrl() }}"
       class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-success-500 hover:shadow-md transition-all duration-200 group">
      <div class="p-3 rounded-lg bg-success-50 dark:bg-success-900/20 group-hover:bg-success-100 dark:group-hover:bg-success-800/30 transition-colors">
        <x-heroicon-o-receipt-percent class="h-6 w-6 text-success-600 dark:text-success-400" />
      </div>
      <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-success-600 dark:group-hover:text-success-400 transition-colors">
        {{ __('View Sales') }}
      </span>
    </a>

    <a href="{{ \App\Filament\Tenant\Resources\ProductResource::getUrl() }}"
       class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-info-500 hover:shadow-md transition-all duration-200 group">
      <div class="p-3 rounded-lg bg-info-50 dark:bg-info-900/20 group-hover:bg-info-100 dark:group-hover:bg-info-800/30 transition-colors">
        <x-heroicon-o-cube class="h-6 w-6 text-info-600 dark:text-info-400" />
      </div>
      <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-info-600 dark:group-hover:text-info-400 transition-colors">
        {{ __('Manage Products') }}
      </span>
    </a>

    <a href="{{ \App\Filament\Tenant\Resources\MemberResource::getUrl() }}"
       class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-warning-500 hover:shadow-md transition-all duration-200 group">
      <div class="p-3 rounded-lg bg-warning-50 dark:bg-warning-900/20 group-hover:bg-warning-100 dark:group-hover:bg-warning-800/30 transition-colors">
        <x-heroicon-o-users class="h-6 w-6 text-warning-600 dark:text-warning-400" />
      </div>
      <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-warning-600 dark:group-hover:text-warning-400 transition-colors">
        {{ __('Manage Members') }}
      </span>
    </a>
  </div>
</x-filament::section>
