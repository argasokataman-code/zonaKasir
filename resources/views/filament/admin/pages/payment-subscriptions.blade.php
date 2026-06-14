<x-filament-panels::page>
    <div>
        @if (empty($subscriptionPayments))
            <x-filament::section>
                <div class="p-6 text-center text-gray-500">
                    No subscription payments found
                </div>
            </x-filament::section>
        @else
            <x-filament::section>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-gray-500">
                                <th class="p-2">Tenant</th>
                                <th class="p-2">Invoice</th>
                                <th class="p-2">Plan</th>
                                <th class="p-2">Amount</th>
                                <th class="p-2">Status</th>
                                <th class="p-2">Method</th>
                                <th class="p-2">Paid At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($subscriptionPayments as $inv)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-2">{{ $inv['tenant_name'] }}</td>
                                    <td class="p-2 font-mono text-xs">{{ $inv['invoice_number'] }}</td>
                                    <td class="p-2">{{ $inv['plan_name'] }}</td>
                                    <td class="p-2">Rp {{ number_format($inv['amount'], 0, ',', '.') }}</td>
                                    <td class="p-2">
                                        @php
                                            $colors = [
                                                'paid' => 'success',
                                                'pending' => 'warning',
                                                'failed' => 'danger',
                                                'refunded' => 'info',
                                            ];
                                            $color = $colors[$inv['status']] ?? 'gray';
                                        @endphp
                                        <x-filament::badge :color="$color">
                                            {{ $inv['status'] }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="p-2">{{ $inv['payment_method'] ?? '-' }}</td>
                                    <td class="p-2 whitespace-nowrap">{{ $inv['paid_at'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
