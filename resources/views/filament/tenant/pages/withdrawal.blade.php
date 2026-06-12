<x-filament-panels::page>
    @php
        $about = \App\Models\Tenants\About::first();
        $balance = app(\App\Services\Tenants\LedgerService::class)->getCurrentBalance();
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-filament::section>
            <div class="p-4 text-center">
                <div class="text-sm text-gray-500">{{ __('Available Balance') }}</div>
                <div class="text-3xl font-bold text-green-600 mt-2">
                    Rp {{ number_format($balance, 0, ',', '.') }}
                </div>
                <div class="text-xs text-gray-400 mt-1">
                    {{ __('Withdrawal limit 95%: Rp') }} {{ number_format((int)($balance * 0.95), 0, ',', '.') }}
                </div>
            </div>
        </x-filament::section>

        <x-filament::section heading="{{ __('Request Withdrawal') }}">
            @if (!$about || !$about->bank_name || !$about->bank_account_number)
                <div class="p-4 text-sm text-red-600 bg-red-50 rounded-lg">
                    {{ __('Set bank account first in Settings > General > Payment Gateway tab.') }}
                </div>
            @else
                <div class="p-4">
                    <div class="mb-3 text-sm text-gray-600">
                        <strong>{{ __('Withdraw to') }}:</strong>
                        {{ $about->bank_name }} - a/n {{ $about->bank_account_name }}
                        ({{ $about->bank_account_number }})
                    </div>
                    {{ $this->form }}
                </div>
            @endif
        </x-filament::section>
    </div>

    <x-filament::section heading="{{ __('Withdrawal History') }}">
        <div class="p-4">
            {{ $this->table }}
        </div>
    </x-filament::section>
</x-filament-panels::page>
