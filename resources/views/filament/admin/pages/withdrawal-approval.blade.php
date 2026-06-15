<x-filament-panels::page>
    <div class="space-y-4">
        @if (empty($withdrawals))
            <x-filament::section>
                <div class="p-6 text-center text-gray-500">
                    {{ __('No withdrawal history') }}
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
                                    @php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'processing' => 'info',
                                            'approved' => 'primary',
                                            'completed' => 'success',
                                            'failed' => 'danger',
                                            'rejected' => 'gray',
                                        ];
                                        $color = $statusColors[$wd['status']] ?? 'gray';
                                    @endphp
                                    <x-filament::badge :color="$color">
                                        {{ ucfirst($wd['status']) }}
                                    </x-filament::badge>
                                </div>
                                <div class="text-sm text-gray-600 mt-1">
                                    <strong>{{ $wd['tenant_name'] }}</strong> - 
                                    {{ $wd['bank_name'] }} a/n {{ $wd['bank_account_name'] }}
                                    ({{ $wd['bank_account_number'] }})
                                </div>
                                <div class="text-xs text-gray-400 mt-1">
                                    {{ __('Requested by') }} {{ $wd['requested_by'] }} {{ __('on') }} {{ $wd['created_at'] }}
                                </div>
                            </div>
                            @if ($wd['status'] === 'pending')
                            <div class="flex space-x-2">
                                <x-filament::button 
                                    wire:click="approve('{{ $wd['tenant_id'] }}', {{ $wd['withdrawal_id'] }})"
                                    color="success"
                                    size="sm">
                                    {{ __('Approve') }}
                                </x-filament::button>
                                <x-filament::button 
                                    wire:click="reject('{{ $wd['tenant_id'] }}', {{ $wd['withdrawal_id'] }})"
                                    color="danger"
                                    size="sm">
                                    {{ __('Reject') }}
                                </x-filament::button>
                            </div>
                            @endif
                        </div>
                    </div>
                </x-filament::section>
            @endforeach
        @endif
    </div>
</x-filament-panels::page>
