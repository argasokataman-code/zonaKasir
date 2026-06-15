<x-filament-panels::page>
    <div>
        {{-- Stats cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <x-filament::section>
                <div class="text-center">
                    <p class="text-xs text-gray-500 uppercase">Total Settled</p>
                    <p class="text-xl font-bold text-success-600">Rp {{ number_format($stats['total_gross'], 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">{{ $stats['count_settled'] }} transactions</p>
                </div>
            </x-filament::section>
            <x-filament::section>
                <div class="text-center">
                    <p class="text-xs text-gray-500 uppercase">Total Fee</p>
                    <p class="text-xl font-bold text-danger-600">Rp {{ number_format($stats['total_fee'], 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">Midtrans + Platform</p>
                </div>
            </x-filament::section>
            <x-filament::section>
                <div class="text-center">
                    <p class="text-xs text-gray-500 uppercase">Net Amount</p>
                    <p class="text-xl font-bold text-gray-900">Rp {{ number_format($stats['total_net'], 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">Yang diterima merchant</p>
                </div>
            </x-filament::section>
            <x-filament::section>
                <div class="text-center">
                    <p class="text-xs text-gray-500 uppercase">Pending</p>
                    <p class="text-xl font-bold text-warning-600">{{ $stats['count_pending'] }}</p>
                    <p class="text-xs text-gray-400">Belum settled</p>
                </div>
            </x-filament::section>
        </div>

        {{-- Transactions table --}}
        <x-filament::section>
            <x-slot name="heading">
                <span>Recent Transactions</span>
            </x-slot>
            @if (empty($transactions))
                <div class="py-6 text-center text-gray-500">
                    No transaction payments found
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-gray-500">
                                <th class="p-2">Tenant</th>
                                <th class="p-2">Order ID</th>
                                <th class="p-2">Gross</th>
                                <th class="p-2">Net</th>
                                <th class="p-2">Status</th>
                                <th class="p-2">Type</th>
                                <th class="p-2">Channel</th>
                                <th class="p-2">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transactions as $tx)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-2">{{ $tx['tenant_name'] }}</td>
                                    <td class="p-2 font-mono text-xs">{{ $tx['order_id'] }}</td>
                                    <td class="p-2">Rp {{ number_format($tx['gross_amount'], 0, ',', '.') }}</td>
                                    <td class="p-2">Rp {{ number_format($tx['net_amount'], 0, ',', '.') }}</td>
                                    <td class="p-2">
                                        @php
                                            $colors = [
                                                'settlement' => 'success',
                                                'capture' => 'success',
                                                'pending' => 'warning',
                                                'deny' => 'danger',
                                                'expire' => 'gray',
                                            ];
                                            $color = $colors[$tx['status']] ?? 'gray';
                                        @endphp
                                        <x-filament::badge :color="$color">
                                            {{ $tx['status'] }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="p-2">{{ $tx['payment_type'] ?? '-' }}</td>
                                    <td class="p-2">{{ $tx['payment_channel'] ?? '-' }}</td>
                                    <td class="p-2 whitespace-nowrap">{{ $tx['created_at'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
