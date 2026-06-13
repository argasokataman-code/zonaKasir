<x-filament-panels::page>
    @php
        $about = \App\Models\Tenants\About::first();
        $balance = app(\App\Services\Tenants\LedgerService::class)->getCurrentBalance();
        $locale = \App\Models\Tenants\Profile::get()->locale ?? 'en';
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-filament::section>
            <div class="p-4 text-center">
                <div class="text-sm text-gray-500 flex items-center justify-center gap-1">
                    {{ __('Available Balance') }}
                    <span class="group relative">
                        <x-heroicon-o-information-circle class="h-4 w-4 text-gray-400 cursor-help" />
                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 shadow-lg">
                            <p class="font-semibold mb-1">{{ __('Available Balance') }}</p>
                            <p>{{ __('Total settlement (gross) after deducting Midtrans MDR fee.') }}</p>
                            <p class="mt-1">{{ __('Formula: gross_amount - fee_midtrans') }}</p>
                            <p class="mt-1 text-yellow-300">{{ __('Maximum 95% of this balance.') }}</p>
                        </div>
                    </span>
                </div>
                <div class="text-3xl font-bold text-green-600 mt-2">
                    Rp {{ number_format($balance, 0, ',', '.') }}
                </div>
                <div class="text-xs text-gray-400 mt-1">
                    {{ __('Withdrawal limit 95%: Rp') }} {{ number_format((int)($balance * 0.95), 0, ',', '.') }}
                </div>
            </div>
        </x-filament::section>

        <x-filament::section heading="{{ __('Request Withdrawal') }}">
            <x-slot name="heading">
                <div class="flex items-center gap-1">
                    <span>{{ __('Request Withdrawal') }}</span>
                    <span class="group relative">
                        <x-heroicon-o-information-circle class="h-4 w-4 text-gray-400 cursor-help" />
                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 shadow-lg">
                            <p class="font-semibold mb-1">{{ __('Withdrawal Rules') }}</p>
                            <ul class="list-disc pl-4 space-y-1">
                                <li>{{ __('Min: Rp 50,000') }}</li>
                                <li>{{ __('Max: 95% of Available Balance') }}</li>
                                <li>{{ __('< Rp 5M: auto-approve') }}</li>
                                <li>{{ __('Rp 5M - Rp 25M: 1 admin approve') }}</li>
                                <li>{{ __('> Rp 25M: 2 admin approve') }}</li>
                            </ul>
                        </div>
                    </span>
                </div>
            </x-slot>
            @if (!$about || !$about->bank_name || !$about->bank_account_number)
                <div class="p-4 text-sm text-red-600 bg-red-50 rounded-lg">
                    {{ __('Set bank account first in Settings > General > Payment Gateway tab.') }}
                </div>
            @else
                <div class="p-4">
                    <div class="mb-3 text-sm text-gray-600 flex items-center gap-1">
                        <strong>{{ __('Withdraw to') }}:</strong>
                        {{ $about->bank_name }} - a/n {{ $about->bank_account_name }}
                        ({{ $about->bank_account_number }})
                        <span class="group relative">
                            <x-heroicon-o-information-circle class="h-4 w-4 text-gray-400 cursor-help" />
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 shadow-lg">
                                <p>{{ __('Bank account is configured in Settings > General > Payment Gateway tab.') }}</p>
                            </div>
                        </span>
                    </div>
                    {{ $this->form }}
                </div>
            @endif
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-1">
                <span>{{ __('Withdrawal History') }}</span>
                <span class="group relative">
                    <x-heroicon-o-information-circle class="h-4 w-4 text-gray-400 cursor-help" />
                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 shadow-lg">
                        <p class="font-semibold mb-1">{{ __('Status Badges') }}</p>
                        <ul class="list-disc pl-4 space-y-1">
                            <li><span class="text-yellow-300">🟡 Pending</span> — {{ __('awaiting approval') }}</li>
                            <li><span class="text-blue-300">🔵 Processing</span> — {{ __('being processed') }}</li>
                            <li><span class="text-green-300">🟢 Completed</span> — {{ __('transferred') }}</li>
                            <li><span class="text-red-300">🔴 Rejected</span> — {{ __('denied, balance restored') }}</li>
                        </ul>
                    </div>
                </span>
            </div>
        </x-slot>
        <div class="p-4">
            {{ $this->table }}
        </div>
    </x-filament::section>
</x-filament-panels::page>
