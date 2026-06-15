<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Row 1: Core Metrics --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-filament::section>
                <div class="p-4">
                    <div class="text-sm text-gray-500">Total Revenue</div>
                    <div class="text-2xl font-bold text-success-600">
                        Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                    </div>
                    <div class="text-xs text-gray-400 mt-1">
                        {{ $totalPaidInvoices }} paid invoices
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="p-4">
                    <div class="text-sm text-gray-500">Monthly Recurring (30d)</div>
                    <div class="text-2xl font-bold text-primary-600">
                        Rp {{ number_format($monthlyRecurringRevenue, 0, ',', '.') }}
                    </div>
                    <div class="text-xs text-gray-400 mt-1">
                        Forecast: Rp {{ number_format($revenueForecast, 0, ',', '.') }}/yr
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="p-4">
                    <div class="text-sm text-gray-500">Active Subscriptions</div>
                    <div class="text-2xl font-bold text-info-600">
                        {{ $activeSubscriptions }}
                    </div>
                    <div class="text-xs text-gray-400 mt-1">
                        {{ $cancelledSubscriptions }} cancelled
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="p-4">
                    <div class="text-sm text-gray-500">Pending Invoices</div>
                    <div class="text-2xl font-bold text-warning-600">
                        {{ $totalPendingInvoices }}
                    </div>
                    <div class="text-xs text-gray-400 mt-1">
                        Rp {{ number_format($totalPendingAmount, 0, ',', '.') }}
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Row 2: Business Health Metrics --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-filament::section>
                <div class="p-4">
                    <div class="text-sm text-gray-500">Churn Rate</div>
                    <div class="text-2xl font-bold {{ $churnRate > 10 ? 'text-danger-600' : 'text-success-600' }}">
                        {{ $churnRate }}%
                    </div>
                    <div class="text-xs text-gray-400 mt-1">
                        {{ $cancelledSubscriptions }} / {{ $activeSubscriptions + $cancelledSubscriptions }} total
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="p-4">
                    <div class="text-sm text-gray-500">Conversion Rate</div>
                    <div class="text-2xl font-bold text-success-600">
                        {{ $conversionRate }}%
                    </div>
                    <div class="text-xs text-gray-400 mt-1">
                        {{ $totalPaidTenants }} / {{ $totalTenants }} tenants paid
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="p-4">
                    <div class="text-sm text-gray-500">ARPU</div>
                    <div class="text-2xl font-bold text-primary-600">
                        Rp {{ number_format($arpu, 0, ',', '.') }}
                    </div>
                    <div class="text-xs text-gray-400 mt-1">
                        Avg revenue per paying tenant
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="p-4">
                    <div class="text-sm text-gray-500">Growth Rate (MoM)</div>
                    <div class="text-2xl font-bold {{ $growthRateMoM >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                        {{ $growthRateMoM >= 0 ? '+' : '' }}{{ $growthRateMoM }}%
                    </div>
                    <div class="text-xs text-gray-400 mt-1">
                        vs last month
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Row 3: Payment Health --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-filament::section>
                <div class="p-4">
                    <div class="text-sm text-gray-500">Payment Success Rate</div>
                    <div class="text-2xl font-bold {{ $paymentSuccessRate >= 90 ? 'text-success-600' : 'text-warning-600' }}">
                        {{ $paymentSuccessRate }}%
                    </div>
                    <div class="text-xs text-gray-400 mt-1">
                        {{ $totalPaidInvoices }} success / {{ $totalProcessedInvoices }} total processed
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="p-4">
                    <div class="text-sm text-gray-500">Avg Days to Payment</div>
                    <div class="text-2xl font-bold text-info-600">
                        {{ $avgDaysToPayment }} days
                    </div>
                    <div class="text-xs text-gray-400 mt-1">
                        From invoice created → paid
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Row 4: Revenue by Plan --}}
        @if (count($revenueByPlan) > 0)
            <x-filament::section>
                <x-slot name="heading">Revenue by Plan</x-slot>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-gray-500">
                                <th class="p-2">Plan</th>
                                <th class="p-2 text-right">Invoices</th>
                                <th class="p-2 text-right">Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($revenueByPlan as $plan)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-2 font-medium">{{ $plan['plan_name'] }}</td>
                                    <td class="p-2 text-right">{{ $plan['invoice_count'] }}</td>
                                    <td class="p-2 text-right">Rp {{ number_format($plan['total_amount'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        {{-- Row 5: Monthly Trend --}}
        @if (count($monthlyTrend) > 0)
            <x-filament::section>
                <x-slot name="heading">Monthly Trend (Last 6 Months)</x-slot>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-gray-500">
                                <th class="p-2">Month</th>
                                <th class="p-2 text-right">Invoices</th>
                                <th class="p-2 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($monthlyTrend as $trend)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-2 font-medium">{{ $trend['month'] }}</td>
                                    <td class="p-2 text-right">{{ $trend['invoice_count'] }}</td>
                                    <td class="p-2 text-right">Rp {{ number_format($trend['total_amount'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        {{-- Row 6: Expiring Soon + Failed Payments --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Expiring Soon --}}
            <x-filament::section>
                <x-slot name="heading">
                    <span class="{{ count($expiringSoon) > 0 ? 'text-warning-600' : '' }}">
                        ⚠️ Expiring Soon (7 days)
                    </span>
                </x-slot>
                @if (count($expiringSoon) === 0)
                    <div class="p-4 text-center text-gray-500 text-sm">
                        No subscriptions expiring soon
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-gray-500">
                                    <th class="p-2">Tenant</th>
                                    <th class="p-2">Plan</th>
                                    <th class="p-2 text-right">Expires</th>
                                    <th class="p-2 text-right">Days Left</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($expiringSoon as $item)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="p-2">{{ $item['tenant_name'] }}</td>
                                        <td class="p-2">{{ $item['plan_name'] }}</td>
                                        <td class="p-2 text-right">{{ $item['ends_at'] }}</td>
                                        <td class="p-2 text-right">
                                            <x-filament::badge :color="$item['days_left'] <= 2 ? 'danger' : 'warning'">
                                                {{ $item['days_left'] }}d
                                            </x-filament::badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>

            {{-- Failed Payments --}}
            <x-filament::section>
                <x-slot name="heading">
                    <span class="{{ count($failedPayments) > 0 ? 'text-danger-600' : '' }}">
                        ❌ Failed Payments
                    </span>
                </x-slot>
                @if (count($failedPayments) === 0)
                    <div class="p-4 text-center text-gray-500 text-sm">
                        No failed payments
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-gray-500">
                                    <th class="p-2">Tenant</th>
                                    <th class="p-2">Invoice</th>
                                    <th class="p-2 text-right">Amount</th>
                                    <th class="p-2">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($failedPayments as $item)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="p-2">{{ $item['tenant_name'] }}</td>
                                        <td class="p-2 font-mono text-xs">{{ $item['invoice_number'] }}</td>
                                        <td class="p-2 text-right">Rp {{ number_format($item['amount'], 0, ',', '.') }}</td>
                                        <td class="p-2 text-xs">{{ $item['created_at'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>
        </div>

        {{-- Row 7: Top 10 Tenants by Revenue --}}
        @if (count($topTenantsByRevenue) > 0)
            <x-filament::section>
                <x-slot name="heading">🏆 Top 10 Tenants by Revenue</x-slot>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-gray-500">
                                <th class="p-2">#</th>
                                <th class="p-2">Tenant</th>
                                <th class="p-2 text-right">Invoices</th>
                                <th class="p-2 text-right">Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topTenantsByRevenue as $index => $item)
                                <tr class="border-b hover:bg-gray-50 {{ $index < 3 ? 'bg-yellow-50' : '' }}">
                                    <td class="p-2 font-bold">{{ $index + 1 }}</td>
                                    <td class="p-2 font-medium">{{ $item['tenant_name'] }}</td>
                                    <td class="p-2 text-right">{{ $item['invoice_count'] }}</td>
                                    <td class="p-2 text-right font-bold">Rp {{ number_format($item['total_amount'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        {{-- Row 8: Payment List --}}
        @if (empty($subscriptionPayments))
            <x-filament::section>
                <div class="p-6 text-center text-gray-500">
                    No subscription payments found
                </div>
            </x-filament::section>
        @else
            <x-filament::section>
                <x-slot name="heading">Recent Payments</x-slot>
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
