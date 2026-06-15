<x-filament-panels::page>
    <div class="space-y-4">
        @if (empty($withdrawals))
            <x-filament::section>
                <div class="p-6 text-center text-gray-500">
                    {{ __('No pending withdrawals') }}
                </div>
            </x-filament::section>
        @else
            @foreach ($withdrawals as $wd)
                <x-filament::section>
                    <div class="p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="flex items-center gap-2">
                                    <div class="text-lg font-semibold">
                                        Rp {{ number_format($wd['amount'], 0, ',', '.') }}
                                    </div>
                                </div>
                                <div class="text-sm text-gray-600 mt-1">
                                    <strong>{{ $wd['tenant_name'] }}</strong> - 
                                    {{ $wd['bank_name'] }} a/n {{ $wd['bank_account_name'] }}
                                    ({{ $wd['bank_account_number'] }})
                                </div>
                                <div class="text-xs text-gray-400 mt-1">
                                    Requested by {{ $wd['requested_by'] }} on {{ $wd['created_at'] }}
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <x-filament::button 
                                    wire:click="approve('{{ $wd['tenant_id'] }}', {{ $wd['withdrawal_id'] }})"
                                    color="success"
                                    size="sm">
                                    Approve
                                </x-filament::button>
                                <x-filament::button 
                                    wire:click="reject('{{ $wd['tenant_id'] }}', {{ $wd['withdrawal_id'] }})"
                                    color="danger"
                                    size="sm">
                                    Reject
                                </x-filament::button>
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            @endforeach
        @endif
    </div>
</x-filament-panels::page>
