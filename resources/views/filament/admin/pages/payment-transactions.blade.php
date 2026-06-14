<x-filament-panels::page>
    <div>
        @if (empty($transactions))
            <x-filament::section>
                <div class="p-6 text-center text-gray-500">
                    No transaction payments found
                </div>
            </x-filament::section>
        @else
            <x-filament::section>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-gray-500">
                                <th class="p-2">Tenant</th>
                                <th class="p-2">Order ID</th>
                                <th class="p-2">Amount</th>
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
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
