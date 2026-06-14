<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex justify-end">
            <x-filament::button wire:click="refresh" icon="heroicon-o-arrow-path" color="gray">
                Refresh
            </x-filament::button>
        </div>

        {{-- Flip Balance --}}
        <x-filament::section heading="Flip Balance" icon="heroicon-o-banknotes">
            <div class="p-4">
                @if ($balanceError)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm text-yellow-800">
                        ⚠️ {{ $balanceError }}
                    </div>
                @elseif ($flipBalance)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 rounded-lg border">
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Balance</div>
                            <div class="mt-1 font-semibold text-2xl">
                                Rp {{ number_format(($flipBalance['balance'] ?? 0), 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="p-4 rounded-lg border">
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Pending</div>
                            <div class="mt-1 font-semibold text-lg text-yellow-600">
                                Rp {{ number_format(($flipBalance['pending_balance'] ?? 0), 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="p-4 rounded-lg border">
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Currency</div>
                            <div class="mt-1 font-semibold text-lg">
                                {{ strtoupper($flipBalance['currency'] ?? 'IDR') }}
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-gray-500">Loading balance...</div>
                @endif
            </div>
        </x-filament::section>

        {{-- Flip Disbursement History --}}
        <x-filament::section heading="Flip Disbursement History" icon="heroicon-o-list-bullet">
            <div class="p-4">
                @if (empty($flipDisbursements))
                    <div class="text-center text-gray-500">No Flip disbursement data available</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-gray-500">
                                    <th class="p-2">ID</th>
                                    <th class="p-2">Bank</th>
                                    <th class="p-2">Account</th>
                                    <th class="p-2">Amount</th>
                                    <th class="p-2">Status</th>
                                    <th class="p-2">Remark</th>
                                    <th class="p-2">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($flipDisbursements as $d)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="p-2 font-mono text-xs">{{ $d['id'] ?? '-' }}</td>
                                        <td class="p-2">{{ $d['bank_code'] ?? '-' }}</td>
                                        <td class="p-2 font-mono">{{ $d['account_number'] ?? '-' }}</td>
                                        <td class="p-2">Rp {{ number_format(($d['amount'] ?? 0), 0, ',', '.') }}</td>
                                        <td class="p-2">
                                            @php
                                                $colors = [
                                                    'DONE' => 'success',
                                                    'PENDING' => 'warning',
                                                    'CANCELLED' => 'danger',
                                                    'FAILED' => 'danger',
                                                ];
                                                $color = $colors[$d['status']] ?? 'gray';
                                            @endphp
                                            <x-filament::badge :color="$color">
                                                {{ $d['status'] ?? '-' }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="p-2 max-w-xs truncate">{{ $d['remark'] ?? '-' }}</td>
                                        <td class="p-2 whitespace-nowrap">{{ $d['created_at'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Local Withdrawal History --}}
        <x-filament::section heading="Local Withdrawal History" icon="heroicon-o-clock">
            <div class="p-4">
                @if (empty($localWithdrawals))
                    <div class="text-center text-gray-500">No withdrawals found</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-gray-500">
                                    <th class="p-2">Tenant</th>
                                    <th class="p-2">Amount</th>
                                    <th class="p-2">Bank</th>
                                    <th class="p-2">Status</th>
                                    <th class="p-2">Disburse ID</th>
                                    <th class="p-2">Requested</th>
                                    <th class="p-2">Processed</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($localWithdrawals as $w)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="p-2">{{ $w['tenant_name'] }}</td>
                                        <td class="p-2">Rp {{ number_format($w['amount'], 0, ',', '.') }}</td>
                                        <td class="p-2">{{ $w['bank_name'] }} a/n {{ $w['bank_account_name'] }}</td>
                                        <td class="p-2">
                                            @php
                                                $colors = [
                                                    'completed' => 'success',
                                                    'processing' => 'info',
                                                    'approved' => 'primary',
                                                    'pending' => 'warning',
                                                    'rejected' => 'danger',
                                                    'failed' => 'danger',
                                                ];
                                                $color = $colors[$w['status']] ?? 'gray';
                                            @endphp
                                            <x-filament::badge :color="$color">
                                                {{ $w['status'] }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="p-2 font-mono text-xs">{{ $w['disburse_id'] ?? '-' }}</td>
                                        <td class="p-2 whitespace-nowrap">{{ $w['created_at'] }}</td>
                                        <td class="p-2 whitespace-nowrap">{{ $w['processed_at'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
